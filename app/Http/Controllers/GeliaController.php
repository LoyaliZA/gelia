<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;

class GeliaController extends Controller
{
    public function generar(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $tipoLista = $request->input('tipo_lista', 'PERSONALIZADA'); 
        $fecha = date('d-m-y');

        // ---------------------------------------------------------
        // MODO 1: LIMPIEZA DE CLIENTES (Independiente)
        // ---------------------------------------------------------
        if ($tipoLista === 'clientes') {
            $validator = Validator::make($request->all(), [
                'clientes' => 'required|file',
            ], [
                'clientes.required' => 'Es necesario subir el archivo CSV de Clientes.'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $listaClientes = [];
            
            // Procesamos el archivo CSV
            $this->procesarArchivoSeguro($request->file('clientes'), function ($ruta) use (&$listaClientes) {
                (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$listaClientes) {
                    
                    $idRaw = $linea[0] ?? '';
                    $nombreRaw = $linea[1] ?? '';

                    // LIMPIEZA DE CEROS Y ESPACIOS
                    // ltrim($var, '0') elimina los ceros a la izquierda (0010 -> 10)
                    $id = ltrim(trim((string)$idRaw), '0'); 
                    $nombre = trim((string)$nombreRaw);

                    // FILTRADO:
                    // Si al quitar ceros queda vacío (ej: "000" o ""), se salta.
                    if ($id === '' || strtolower($id) === 'id' || strtolower($id) === 'clientes') {
                        return;
                    }

                    $listaClientes[] = [
                        'ID'     => $id,
                        'NOMBRE' => $nombre,
                    ];
                });
            });

            return (new FastExcel(collect($listaClientes)))->download("CLIENTES-LIMPIOS-$fecha.xlsx");
        }


        // ---------------------------------------------------------
        // MODO 2: SISTEMA DE EXISTENCIAS (Gelia Original)
        // ---------------------------------------------------------

        // 1. VALIDACIÓN
        $validator = Validator::make($request->all(), [
            'existencias' => 'required|file',
            'precios'     => 'nullable|file|required_without:costos',
            'costos'      => 'nullable|file|required_without:precios',
        ], [
            'precios.required_without' => 'Debes subir al menos el archivo de Precios o el de Costos.',
            'costos.required_without' => 'Debes subir al menos el archivo de Precios o el de Costos.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. CONFIGURACIÓN NOMBRES
        $ordenCadena = $request->input('orden_final'); 

        switch ($tipoLista) {
            case 'resurtido': $nombreArchivo = "LISTA-DE-RESURTIDO-$fecha.xlsx"; break;
            case 'costos': $nombreArchivo = "LISTA-DE-COSTOS-$fecha.xlsx"; break;
            case 'actualizada': $nombreArchivo = "LISTA-ACTUALIZADA-$fecha.xlsx"; break;
            case 'inventario': $nombreArchivo = "LISTA-DE-INVENTARIO-$fecha.xlsx"; break;
            default: $nombreArchivo = "LISTA-PERSONALIZADA-$fecha.xlsx"; break;
        }

        // Columnas
        if (!empty($ordenCadena)) {
            $columnasSeleccionadas = explode(',', $ordenCadena);
        } elseif ($request->has('columnas')) {
            $columnasSeleccionadas = $request->input('columnas');
        } else {
            return response()->json(['error' => 'Debes seleccionar columnas.'], 422);
        }

        // 3. LEER PRECIOS
        $diccionarioPrecios = [];
        if ($request->hasFile('precios')) {
            $this->procesarArchivoSeguro($request->file('precios'), function ($ruta) use (&$diccionarioPrecios) {
                (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$diccionarioPrecios) {
                    if (!isset($linea[1]) || $linea[1] == 'CODIGO_DEL_PRODUCTO' || $linea[1] == '') return;
                    $sku = ltrim(trim((string)$linea[1]), '0');
                    $precio = $linea[7] ?? 0;
                    $diccionarioPrecios[$sku] = is_numeric($precio) ? (float)$precio : 0.0;
                });
            });
        }

        // 4. LEER COSTOS WIZERP
        $diccionarioCostosWizerp = [];
        if ($request->hasFile('costos')) {
            $this->procesarArchivoSeguro($request->file('costos'), function ($ruta) use (&$diccionarioCostosWizerp) {
                (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$diccionarioCostosWizerp) {
                    if (!isset($linea[1]) || $linea[1] == 'SKU' || $linea[1] == '') return;
                    $sku = ltrim(trim((string)$linea[1]), '0');
                    $costo = $linea[5] ?? 0; 
                    $costoLimpio = str_replace(['$', ','], '', (string)$costo);
                    $diccionarioCostosWizerp[$sku] = is_numeric($costoLimpio) ? (float)$costoLimpio : 0.0;
                });
            });
        }

        // 5. PROCESAR EXISTENCIAS
        $listaCompleta = []; 
        $this->procesarArchivoSeguro($request->file('existencias'), function ($ruta) use (&$listaCompleta, $diccionarioPrecios, $diccionarioCostosWizerp, $columnasSeleccionadas) {
            $reader = (new FastExcel)->withoutHeaders()->import($ruta);
            foreach ($reader as $linea) {
                if (!isset($linea[4]) || $linea[4] == 'Código') continue;
                
                $skuCrudo = trim((string)$linea[4]);
                if ($skuCrudo === '') continue; 

                $skuBuscador = ltrim($skuCrudo, '0');

                $almacen = $linea[1] ?? '';
                $folio = $linea[3] ?? '';
                $descripcion = $linea[5] ?? '';
                $marca = $linea[6] ?? '';
                $existenciaRaw = $linea[10] ?? 0;
                $existencia = is_numeric($existenciaRaw) ? (int)$existenciaRaw : 0;

                $pg = $diccionarioPrecios[$skuBuscador] ?? 0.0;
                $costoWizerp = $diccionarioCostosWizerp[$skuBuscador] ?? 0.0;

                $costoCalculado = $pg > 0 ? $pg / 1.3827 : 0.0;
                $plataformas = $pg * 0.77;
                $lista3 = $pg * 0.8572;
                $lista4 = $pg * 0.8229;

                $fila = [];
                foreach ($columnasSeleccionadas as $columna) {
                    switch ($columna) {
                        case 'Folio': $fila['Folio'] = $folio; break;
                        case 'SKU': $fila['SKU'] = $skuCrudo; break;
                        case 'Descripcion': $fila['Descripcion'] = $descripcion; break;
                        case 'Existencia': $fila['Existencia'] = $existencia; break;
                        case 'PG': $fila['PG'] = round($pg, 2); break;
                        case 'Lista3': $fila['Lista3'] = round($lista3, 2); break;
                        case 'Lista4': $fila['Lista4'] = round($lista4, 2); break;
                        case 'Plataformas': $fila['Plataformas'] = round($plataformas, 2); break;
                        case 'CostoWizerp': $fila['Costo (Wizerp)'] = round($costoWizerp, 2); break;
                        case 'CostoCalculado': $fila['Costo (Calculado)'] = round($costoCalculado, 2); break;
                        case 'Almacen': $fila['Almacen'] = $almacen; break;
                        case 'Marca': $fila['Marca'] = $marca; break;
                    }
                }
                $listaCompleta[] = $fila;
            }
        });

        // 6. ORDENAMIENTO
        if (in_array('Descripcion', $columnasSeleccionadas)) {
            usort($listaCompleta, function ($a, $b) {
                return strcasecmp($a['Descripcion'] ?? '', $b['Descripcion'] ?? '');
            });
        }

        return (new FastExcel(collect($listaCompleta)))->download($nombreArchivo);
    }

    private function procesarArchivoSeguro($archivo, callable $callbackLogica)
    {
        if (!$archivo) return;
        $nombreTemp = 'temp_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;
        $archivo->move(sys_get_temp_dir(), $nombreTemp);
        try {
            $callbackLogica($rutaCompleta);
        } finally {
            if (file_exists($rutaCompleta)) unlink($rutaCompleta);
        }
    }
}
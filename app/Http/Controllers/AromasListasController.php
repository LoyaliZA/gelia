<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\CustomList;

class AromasListasController extends Controller
{
    public function index()
    {
        $listasPersonalizadas = CustomList::where('active', true)->get();
        return view('aromas.listados', compact('listasPersonalizadas')); 
    }

    public function guardarLista(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_creador' => 'required|string|max:100',
            'titulo_lista' => 'required|string|max:50',
            'descripcion' => 'nullable|string',
            'color' => 'required|string',
            'archivos_requeridos' => 'required|array',
            'columnas_exportar' => 'required|string',
            'nombre_archivo_salida' => 'required|string|max:50',
            'filtro_relojes' => 'nullable|boolean' // Validación añadida
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $columnasArray = explode(',', $request->columnas_exportar);
        $soloConExistencia = $request->boolean('solo_con_existencia');
        $filtroRelojes = $request->boolean('filtro_relojes'); // Captura del booleano

        $lista = CustomList::create([
            'nombre_creador' => $request->nombre_creador,
            'titulo_lista' => $request->titulo_lista,
            'descripcion' => $request->descripcion,
            'color' => $request->color,
            'archivos_requeridos' => $request->archivos_requeridos,
            'columnas_exportar' => $columnasArray,
            'nombre_archivo_salida' => strtoupper($request->nombre_archivo_salida),
            'solo_con_existencia' => $soloConExistencia,
            'filtro_relojes' => $filtroRelojes, // Guardado en DB
            'active' => true
        ]);

        Log::info("AROMAS - Nueva lista creada: '{$lista->titulo_lista}' por {$lista->nombre_creador}.");

        return response()->json(['message' => 'Lista creada con éxito']);
    }

    public function actualizarLista(Request $request, $id)
    {
        $lista = CustomList::find($id);

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_creador' => 'required|string|max:100',
            'titulo_lista' => 'required|string|max:50',
            'descripcion' => 'nullable|string',
            'color' => 'required|string',
            'archivos_requeridos' => 'required|array',
            'columnas_exportar' => 'required|string',
            'nombre_archivo_salida' => 'required|string|max:50',
            'filtro_relojes' => 'nullable|boolean' // Validación añadida
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $columnasArray = explode(',', $request->columnas_exportar);
        $soloConExistencia = $request->boolean('solo_con_existencia');
        $filtroRelojes = $request->boolean('filtro_relojes'); // Captura del booleano

        $lista->update([
            'nombre_creador' => $request->nombre_creador,
            'titulo_lista' => $request->titulo_lista,
            'descripcion' => $request->descripcion,
            'color' => $request->color,
            'archivos_requeridos' => $request->archivos_requeridos,
            'columnas_exportar' => $columnasArray,
            'nombre_archivo_salida' => strtoupper($request->nombre_archivo_salida),
            'solo_con_existencia' => $soloConExistencia,
            'filtro_relojes' => $filtroRelojes, // Actualización en DB
        ]);

        Log::info("AROMAS - Lista editada: '{$lista->titulo_lista}' por {$lista->nombre_creador}.");

        return response()->json(['message' => 'Lista actualizada con éxito']);
    }

    public function generar(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $divisorCostoCalculado = 1.3827;
        $multiplicadorPlataformas = 0.77;
        $multiplicadorLista3 = 0.8572;
        $multiplicadorLista4 = 0.8229;
        $multiplicadorBoutique = 0.75;

        $tipoLista = $request->input('tipo_lista', 'PERSONALIZADA'); 
        $fecha = date('d-m-y');

        // 1. DETECCIÓN DE CONFIGURACIÓN
        $esListaPersonalizadaBD = is_numeric($tipoLista);
        $configuracionBD = null;

        if ($esListaPersonalizadaBD) {
            $configuracionBD = CustomList::find($tipoLista);
            if (!$configuracionBD) return response()->json(['error' => 'Lista no encontrada'], 404);
            
            $nombreArchivo = $configuracionBD->nombre_archivo_salida . "-$fecha.xlsx";
            $columnasSeleccionadas = $configuracionBD->columnas_exportar;

        } else {
            $ordenCadena = $request->input('orden_final'); 
            
            switch ($tipoLista) {
                case 'resurtido': $nombreArchivo = "LISTA-DE-RESURTIDO-$fecha.xlsx"; break;
                case 'costos': $nombreArchivo = "LISTA-DE-COSTOS-$fecha.xlsx"; break;
                case 'actualizada': $nombreArchivo = "LISTA-ACTUALIZADA-$fecha.xlsx"; break;
                case 'inventario': $nombreArchivo = "LISTA-DE-INVENTARIO-$fecha.xlsx"; break;
                default: $nombreArchivo = "LISTA-PERSONALIZADA-$fecha.xlsx"; break;
            }

            if (!empty($ordenCadena)) {
                $columnasSeleccionadas = explode(',', $ordenCadena);
            } elseif ($request->has('columnas')) {
                $columnasSeleccionadas = $request->input('columnas');
            } else {
                return response()->json(['error' => 'Debes seleccionar columnas.'], 422);
            }
        }

        // 2. VALIDACIÓN DE ARCHIVOS
        $rules = ['existencias' => 'required|file'];
        $messages = [];

        if ($esListaPersonalizadaBD) {
            $reqs = $configuracionBD->archivos_requeridos;
            if (in_array('precios', $reqs)) $rules['precios'] = 'required|file';
            if (in_array('costos', $reqs)) $rules['costos'] = 'required|file';
        } else {
            $rules['precios'] = 'nullable|file|required_without:costos';
            $rules['costos'] = 'nullable|file|required_without:precios';
            $messages['precios.required_without'] = 'Debes subir Precios o Costos.';
            $messages['costos.required_without'] = 'Debes subir Precios o Costos.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
        $inconsistencias = []; 
        $tienePrecios = $request->hasFile('precios'); 

        $this->procesarArchivoSeguro($request->file('existencias'), function ($ruta) use (&$listaCompleta, &$inconsistencias, $diccionarioPrecios, $diccionarioCostosWizerp, $columnasSeleccionadas, $esListaPersonalizadaBD, $configuracionBD, $divisorCostoCalculado, $multiplicadorPlataformas, $multiplicadorLista3, $multiplicadorLista4, $multiplicadorBoutique, $tienePrecios) {
            
            (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$listaCompleta, &$inconsistencias, $diccionarioPrecios, $diccionarioCostosWizerp, $columnasSeleccionadas, $esListaPersonalizadaBD, $configuracionBD, $divisorCostoCalculado, $multiplicadorPlataformas, $multiplicadorLista3, $multiplicadorLista4, $multiplicadorBoutique, $tienePrecios) {
                if (!isset($linea[4]) || $linea[4] == 'Código') return;
                
                $skuCrudo = trim((string)$linea[4]);
                if ($skuCrudo === '') return; 
                $skuBuscador = ltrim($skuCrudo, '0');

                $existenciaRaw = $linea[10] ?? 0;
                $existencia = is_numeric($existenciaRaw) ? (int)$existenciaRaw : 0;

                if ($esListaPersonalizadaBD && $configuracionBD->solo_con_existencia) {
                    if ($existencia <= 0) return; 
                }

                $almacen = $linea[1] ?? '';
                $folio = $linea[3] ?? '';
                $descripcion = $linea[5] ?? '';
                $marca = $linea[6] ?? '';
                
                // Lógica inyectada para descartar productos si el filtro relojes está activo
                if ($esListaPersonalizadaBD && $configuracionBD->filtro_relojes) {
                    $primeraLetra = strtoupper(substr(ltrim($descripcion), 0, 1));
                    if ($primeraLetra !== 'R') {
                        return;
                    }
                }

                $pg = $diccionarioPrecios[$skuBuscador] ?? 0.0;
                $costoWizerp = $diccionarioCostosWizerp[$skuBuscador] ?? 0.0;

                if ($tienePrecios && $existencia > 0 && $pg <= 0) {
                    $inconsistencias[] = [
                        'sku' => $skuCrudo,
                        'descripcion' => $descripcion,
                        'almacen' => $almacen,
                        'existencia' => $existencia
                    ];
                }

                $costoCalculado = $pg > 0 ? $pg / $divisorCostoCalculado : 0.0;
                $plataformas = $pg * $multiplicadorPlataformas;
                $lista3 = $pg * $multiplicadorLista3;
                $lista4 = $pg * $multiplicadorLista4;
                $listaBoutique = $pg * $multiplicadorBoutique; 

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
                        case 'ListaBoutique': $fila['Lista Boutique'] = round($listaBoutique, 2); break; 
                        case 'Plataformas': $fila['Plataformas'] = round($plataformas, 2); break;
                        case 'CostoWizerp': $fila['Costo (Wizerp)'] = round($costoWizerp, 2); break;
                        case 'CostoCalculado': $fila['Costo (Calculado)'] = round($costoCalculado, 2); break;
                        case 'Almacen': $fila['Almacen'] = $almacen; break;
                        case 'Marca': $fila['Marca'] = $marca; break;
                    }
                }
                $listaCompleta[] = $fila;
            });
        });

        // 6. ORDENAMIENTO (Optimizado a nivel C)
        if (in_array('Descripcion', $columnasSeleccionadas) && !empty($listaCompleta)) {
            $descripciones = array_column($listaCompleta, 'Descripcion');
            array_multisort($descripciones, SORT_ASC, SORT_STRING | SORT_FLAG_CASE, $listaCompleta);
        }

        // 7. DESCARGA DIFERIDA CON ESTADO
        if (count($inconsistencias) > 0) {
            $tempFilename = 'excel_temp_' . uniqid() . '.xlsx';
            $tempDir = storage_path('app/temp');
            
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/' . $tempFilename;
            (new FastExcel($listaCompleta))->export($tempPath);

            return response()->json([
                'requiere_confirmacion' => true,
                'inconsistencias' => $inconsistencias,
                'temp_file' => $tempFilename,
                'nombre_descarga' => $nombreArchivo
            ]);
        }

        return (new FastExcel($listaCompleta))->download($nombreArchivo);
    }

    public function descargarTemporal(Request $request)
    {
        $request->validate([
            'temp_file' => ['required', 'string', 'regex:/^excel_temp_[a-zA-Z0-9]+\.xlsx$/'],
            'nombre_descarga' => 'required|string'
        ]);

        $path = storage_path('app/temp/' . $request->temp_file);

        if (!file_exists($path)) {
            return response()->json(['error' => 'El archivo temporal ha expirado o ya fue procesado.'], 404);
        }

        return response()->download($path, $request->nombre_descarga)->deleteFileAfterSend(true);
    }

    public function eliminarLista($id)
    {
        $lista = CustomList::find($id);

        if ($lista) {
            $lista->active = false;
            $lista->save();
            Log::info("AROMAS - Lista eliminada (Ocultada): '{$lista->titulo_lista}'.");
            return response()->json(['message' => 'Lista eliminada correctamente.']);
        }

        return response()->json(['error' => 'Lista no encontrada.'], 404);
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
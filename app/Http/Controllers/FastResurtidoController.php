<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;

class FastResurtidoController extends Controller
{
    public function generar(Request $request)
    {
        // 1. CONFIGURACIÓN
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate([
            'existencias' => 'required|file',
            'precios' => 'required|file',
            'columnas' => 'required|array'
        ]);

        // --- LÓGICA DEL ORDEN DINÁMICO ---
        $ordenCadena = $request->input('orden_final'); 
        $columnasSeleccionadas = empty($ordenCadena) 
            ? ['SKU', 'Descripcion', 'Existencia', 'PG'] 
            : explode(',', $ordenCadena);

        // 2. LECTURA DE ARCHIVOS (MÉTODO SYSTEM TEMP)
        // Usamos la carpeta temporal del sistema operativo para evitar errores de permisos.

        // --- A) DICCIONARIO DE PRECIOS ---
        $diccionarioPrecios = [];
        
        // Función auxiliar definida abajo para leer sin fallas
        $this->procesarArchivoSeguro($request->file('precios'), function ($rutaArchivo) use (&$diccionarioPrecios) {
            
            // Leemos directo del /tmp
            (new FastExcel)->withoutHeaders()->import($rutaArchivo, function ($linea) use (&$diccionarioPrecios) {
                // Validación básica
                if (!isset($linea[1]) || $linea[1] == 'CODIGO_DEL_PRODUCTO' || $linea[1] == '') {
                    return;
                }
                $sku = ltrim(trim((string)$linea[1]), '0');
                $precioCrudo = $linea[7] ?? 0;
                $diccionarioPrecios[$sku] = ($precioCrudo === '' || $precioCrudo === null) ? 0 : (float)$precioCrudo;
            });

        });

        // --- B) EL GENERADOR (STREAMING) ---
        $procesadorDeExistencias = function() use ($request, $diccionarioPrecios, $columnasSeleccionadas) {
            
            // 1. Movemos el archivo a /tmp manualmente
            $archivo = $request->file('existencias');
            $nombreTemp = 'existencias_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
            $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;
            
            // Movemos el archivo físicamente
            $archivo->move(sys_get_temp_dir(), $nombreTemp);

            try {
                // 2. Leemos el archivo desde /tmp
                $reader = (new FastExcel)->withoutHeaders()->import($rutaCompleta);

                foreach ($reader as $linea) {
                    if (!isset($linea[4]) || $linea[4] == 'Código') {
                        continue; 
                    }

                    $skuCrudo = trim((string)$linea[4]);
                    $skuBuscador = ltrim($skuCrudo, '0');

                    // Extracción
                    $almacen = $linea[1] ?? '';
                    $folio = $linea[3] ?? '';
                    $descripcion = $linea[5] ?? '';
                    $marca = $linea[6] ?? '';
                    
                    $existenciaCruda = $linea[10] ?? 0;
                    $existencia = ($existenciaCruda === '' || $existenciaCruda === null) ? 0 : (int)$existenciaCruda;

                    // Cruce
                    $pg = $diccionarioPrecios[$skuBuscador] ?? 0;
                    $costo = $pg > 0 ? $pg / 1.3827 : 0;
                    $plataformas = $pg * 0.77;
                    $lista3 = $pg * 0.8572;
                    $lista4 = $pg * 0.8229;

                    // Construcción
                    $filaLista = [];
                    foreach ($columnasSeleccionadas as $columna) {
                        switch ($columna) {
                            case 'SKU': $filaLista['SKU'] = $skuCrudo; break;
                            case 'Descripcion': $filaLista['Descripcion'] = $descripcion; break;
                            case 'Marca': $filaLista['Marca'] = $marca; break;
                            case 'Existencia': $filaLista['Existencia'] = $existencia; break;
                            case 'Costo': $filaLista['Costo'] = round($costo, 2); break;
                            case 'PG': $filaLista['PG'] = round($pg, 2); break;
                            case 'Plataformas': $filaLista['Plataformas'] = round($plataformas, 2); break;
                            case 'Lista3': $filaLista['Lista3'] = round($lista3, 2); break;
                            case 'Lista4': $filaLista['Lista4'] = round($lista4, 2); break;
                            case 'Almacen': $filaLista['Almacen'] = $almacen; break;
                            case 'Folio': $filaLista['Folio'] = $folio; break;
                        }
                    }
                    yield $filaLista;
                }
            } finally {
                // 3. Limpieza: Borramos el archivo temporal SIEMPRE
                if (file_exists($rutaCompleta)) {
                    unlink($rutaCompleta);
                }
            }
        };

        // 5. DESCARGA INMEDIATA
        $nombreArchivo = 'RESURTIDO_FAST_' . date('d-m-Y_H-i') . '.xlsx';

        return (new FastExcel($procesadorDeExistencias()))->download($nombreArchivo);
    }

    /**
     * Función auxiliar para mover archivo a /tmp, ejecutar lógica y borrarlo.
     */
    private function procesarArchivoSeguro($archivo, callable $callbackLogica)
    {
        // 1. Generar ruta en /tmp (o carpeta temporal del sistema)
        $nombreTemp = 'temp_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;

        // 2. Mover archivo físicamente (bypass Laravel Storage)
        $archivo->move(sys_get_temp_dir(), $nombreTemp);

        try {
            // 3. Ejecutar la lógica de lectura pasando la ruta
            $callbackLogica($rutaCompleta);
        } finally {
            // 4. Borrar archivo
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
    }
}
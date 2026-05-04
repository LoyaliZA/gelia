<?php

namespace App\Services;

use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Log;

class LimpiezaCsvService 
{
    /**
     * Procesa un archivo CSV, retira apóstrofes, maneja caracteres especiales 
     * y sanitiza cadenas para evitar el ajuste de texto en Excel.
     *
     * @param string $rutaArchivo
     * @return array
     */
    public function procesarYLimpiar($rutaArchivo)
    {
        $listaLimpia = [];
        
        try {
            (new FastExcel)->import($rutaArchivo, function ($linea) use (&$listaLimpia) {
                $filaLimpia = [];
                
                foreach ($linea as $columna => $valor) {
                    // 1. Limpieza de Cabeceras
                    $columnaDecodificada = mb_convert_encoding((string) $columna, 'UTF-8', 'ISO-8859-1');
                    // Removemos apóstrofes, espacios y caracteres invisibles (tabulaciones, saltos)
                    $columnaLimpia = trim(preg_replace('/\s+/', ' ', ltrim($columnaDecodificada, "'")));
                    
                    if (str_starts_with($columnaLimpia, 'Unnamed')) {
                        if ($columnaLimpia === 'Unnamed: 1') $columnaLimpia = 'No. Identificación';
                        if ($columnaLimpia === 'Unnamed: 4') $columnaLimpia = 'Descripción';
                    }

                    // 2. Limpieza de Valores 
                    $valorDecodificado = mb_convert_encoding((string) $valor, 'UTF-8', 'ISO-8859-1');
                    
                    // Remueve apóstrofes, y el preg_replace convierte CUALQUIER salto de línea \n, \r o doble espacio en un solo espacio limpio.
                    // Esto evita que Excel dispare el "Ajustar Texto" automáticamente.
                    $valorLimpio = trim(preg_replace('/\s+/', ' ', ltrim($valorDecodificado, "'")));
                    
                    // 3. Casteo Dinámico (Conversión de tipos)
                    if (is_numeric($valorLimpio)) {
                        if (!preg_match('/^0\d+/', $valorLimpio)) {
                            $valorLimpio = strpos($valorLimpio, '.') !== false 
                                ? (float) $valorLimpio 
                                : (int) $valorLimpio;
                        }
                    } else if (empty($valorLimpio) && $valor !== null) {
                         $valorLimpio = '';
                    }
                    
                    $filaLimpia[$columnaLimpia] = $valorLimpio;
                }
                
                $listaLimpia[] = $filaLimpia;
            });
            
            return $listaLimpia;
            
        } catch (\Exception $e) {
            Log::error("Error limpiando CSV: " . $e->getMessage());
            throw $e;
        }
    }
}
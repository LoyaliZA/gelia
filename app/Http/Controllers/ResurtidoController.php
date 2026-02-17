<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResurtidoExport;
use Maatwebsite\Excel\Concerns\ToArray;

class ResurtidoController extends Controller
{
    public function generar(Request $request)
    {
        // 1. CONFIGURACIÓN DEL SERVIDOR (MODO "DIOS")
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // 2. VALIDACIÓN
        $request->validate([
            'existencias' => 'required|file',
            'precios' => 'required|file',
            // Aunque usamos 'orden_final', validamos 'columnas' para asegurar que marcó algo
            'columnas' => 'required|array' 
        ]);

        // --- LÓGICA DEL ORDEN DINÁMICO (Aquí está el cambio clave) ---
        // 1. Recibimos la cadena de texto que generó el JavaScript: "Costo,SKU,Existencia"
        $ordenCadena = $request->input('orden_final'); 

        // 2. Si el usuario no seleccionó nada (o hubo error), usamos un orden por defecto
        if (empty($ordenCadena)) {
            // Si falla el JS, al menos entregamos esto:
            $columnasSeleccionadas = ['SKU', 'Descripcion', 'Existencia', 'PG'];
        } else {
            // 3. Convertimos la cadena en Array real: ['Costo', 'SKU', 'Existencia']
            // Esto asegura que el Excel salga en el orden EXACTO de tus clics.
            $columnasSeleccionadas = explode(',', $ordenCadena);
        }
        // -------------------------------------------------------------

        // Recuperamos el formato (xlsx o csv)
        $formatoElegido = $request->input('formato', 'xlsx');

        // 3. LECTURA DE ARCHIVOS
        $preciosArray = Excel::toArray(new ImportadorArray(), $request->file('precios'))[0];
        $existenciasArray = Excel::toArray(new ImportadorArray(), $request->file('existencias'))[0];

        // 4. DICCIONARIO DE PRECIOS (Con Blindaje de Ceros)
        $diccionarioPrecios = [];
        foreach ($preciosArray as $filaPrecio) {
            if (!isset($filaPrecio[1]) || $filaPrecio[1] == 'CODIGO_DEL_PRODUCTO' || $filaPrecio[1] == '') {
                continue;
            }
            $sku = ltrim(trim((string)$filaPrecio[1]), '0');
            
            // Si viene vacío, forzamos a que sea 0.00
            $precioCrudo = $filaPrecio[7] ?? 0;
            $diccionarioPrecios[$sku] = ($precioCrudo === '' || $precioCrudo === null) ? 0 : (float)$precioCrudo;
        }

        // 5. PROCESAMIENTO
        $listaFinal = [];
        
        foreach ($existenciasArray as $filaExistencia) {
            if (!isset($filaExistencia[4]) || $filaExistencia[4] == 'Código') {
                continue; 
            }

            $skuCrudo = trim((string)$filaExistencia[4]);
            $skuBuscador = ltrim($skuCrudo, '0');

            // Extracción de Datos
            $almacen = $filaExistencia[1] ?? '';
            $folio = $filaExistencia[3] ?? ''; 
            $descripcion = $filaExistencia[5] ?? '';
            $marca = $filaExistencia[6] ?? '';
            
            // Blindaje de Existencia (0 es 0)
            $existenciaCruda = $filaExistencia[10] ?? 0;
            $existencia = ($existenciaCruda === '' || $existenciaCruda === null) ? 0 : (int)$existenciaCruda;

            // Cruce
            $pg = $diccionarioPrecios[$skuBuscador] ?? 0;

            // Fórmulas
            $costo = $pg > 0 ? $pg / 1.3827 : 0;
            $plataformas = $pg * 0.77;
            $lista3 = $pg * 0.8572;
            $lista4 = $pg * 0.8229;

            // CONSTRUCCIÓN DE LA FILA
            // El 'foreach' recorrerá $columnasSeleccionadas en el orden que tú elegiste
            $filaNueva = [];
            foreach ($columnasSeleccionadas as $columna) {
                switch ($columna) {
                    case 'SKU': $filaNueva[] = $skuCrudo; break;
                    case 'Descripcion': $filaNueva[] = $descripcion; break;
                    case 'Marca': $filaNueva[] = $marca; break;
                    case 'Existencia': $filaNueva[] = $existencia; break;
                    case 'Costo': $filaNueva[] = round($costo, 2); break;
                    case 'PG': $filaNueva[] = round($pg, 2); break;
                    case 'Plataformas': $filaNueva[] = round($plataformas, 2); break;
                    case 'Lista3': $filaNueva[] = round($lista3, 2); break;
                    case 'Lista4': $filaNueva[] = round($lista4, 2); break;
                    case 'Almacen': $filaNueva[] = $almacen; break;
                    case 'Folio': $filaNueva[] = $folio; break;
                }
            }
            $listaFinal[] = $filaNueva; 
        }

        // 6. EXPORTACIÓN
        $extension = $formatoElegido === 'csv' ? 'csv' : 'xlsx';
        $tipoLibreria = $formatoElegido === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $nombreArchivo = 'LISTADO_RESURTIDO_' . date('d-m-Y_H-i') . '.' . $extension;

        // Pasamos array_values() para asegurar que los encabezados coincidan
        return Excel::download(
            new ResurtidoExport($listaFinal, array_values($columnasSeleccionadas)), 
            $nombreArchivo, 
            $tipoLibreria
        );
    }
}

// Clase auxiliar
class ImportadorArray implements ToArray {
    public function array(array $array) { return $array; }
}
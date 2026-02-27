<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Vtiful\Kernel\Excel;
use Vtiful\Kernel\Format;

class BellaromaController extends Controller
{
    public function index()
    {
        return view('bellaroma');
    }

    public function generar(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate([
            'existencias' => 'required|file',
            'precios' => 'required|file',
        ]);

        $fecha = date('d-m-y');
        $nombreArchivo = "PLANTILLA-BELLAROMA-{$fecha}.xlsx";
        $rutaTemp = sys_get_temp_dir();

        $diccionarioPrecios = [];
        $this->procesarArchivoSeguro($request->file('precios'), function ($ruta) use (&$diccionarioPrecios) {
            (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$diccionarioPrecios) {
                if (!isset($linea[1]) || $linea[1] == 'CODIGO_DEL_PRODUCTO' || $linea[1] == '') return;
                $sku = ltrim(trim((string)$linea[1]), '0');
                $precio = $linea[7] ?? 0;
                $diccionarioPrecios[$sku] = is_numeric($precio) ? (float)$precio : 0.0;
            });
        });

        $listaProductos = [];
        $multiplicadorLista3 = 0.8572; 

        $this->procesarArchivoSeguro($request->file('existencias'), function ($ruta) use (&$listaProductos, $diccionarioPrecios, $multiplicadorLista3) {
            $reader = (new FastExcel)->withoutHeaders()->import($ruta);
            foreach ($reader as $linea) {
                if (!isset($linea[4]) || $linea[4] == 'Código') continue;
                
                $skuCrudo = trim((string)$linea[4]);
                if ($skuCrudo === '') continue; 
                $skuBuscador = ltrim($skuCrudo, '0');

                $existenciaReal = (int)($linea[10] ?? 0);
                if ($existenciaReal <= 0) continue;

                $folio = $linea[3] ?? '';
                $descripcion = $linea[5] ?? '';
                
                $pg = $diccionarioPrecios[$skuBuscador] ?? 0.0;
                $mayoreo = $pg * $multiplicadorLista3;

                $listaProductos[] = [
                    'folio' => (string)$folio,
                    'sku' => (string)$skuCrudo,
                    'descripcion' => (string)$descripcion,
                    'existenciaMostrar' => $existenciaReal > 10 ? '+10' : (string)$existenciaReal,
                    'mayoreo' => round($mayoreo, 2)
                ];
            }
        });

        usort($listaProductos, function ($a, $b) {
            return strcasecmp($a['descripcion'], $b['descripcion']);
        });

        $config = ['path' => $rutaTemp];
        $excel = new Excel($config);
        $excel->fileName($nombreArchivo, 'Plantilla');

        // =========================================================
        // 5. SISTEMA DE ESTILOS
        // =========================================================
        $formato = new Format($excel->getHandle());
        $estiloDesbloqueado = $formato->unlocked()->toResource();
        
        $formatoNegritaObj = new Format($excel->getHandle());
        $estiloNegrita = $formatoNegritaObj->bold()->toResource();

        $formatoCabeceraBordeObj = new Format($excel->getHandle());
        $estiloCabeceraBorde = $formatoCabeceraBordeObj->bold()->border(Format::BORDER_THIN)->toResource();

        $formatoBordeObj = new Format($excel->getHandle());
        $estiloBorde = $formatoBordeObj->border(Format::BORDER_THIN)->toResource();

        $formatoPedidoObj = new Format($excel->getHandle());
        $estiloPedidoData = $formatoPedidoObj->border(Format::BORDER_THIN)->unlocked()->toResource();

        $excel->setColumn('A:A', 15.0); 
        $excel->setColumn('B:B', 15.0); 
        $excel->setColumn('C:C', 65.0); 
        $excel->setColumn('D:E', 15.0); 
        $excel->setColumn('F:F', 15.0, $estiloDesbloqueado); 
        $excel->setColumn('H:H', 75.0); 

        // =========================================================
        // 6. INYECTAR TEXTOS Y FÓRMULAS
        // =========================================================
        $excel->insertText(0, 0, 'IMPORTE', '', $estiloCabeceraBorde);
        // Función nativa TEXT() de Excel para forzar el formato moneda en la fórmula
        $excel->insertFormula(0, 1, '=TEXT(SUMPRODUCT(E5:E50000,F5:F50000), "$#,##0.00")', $estiloCabeceraBorde);
        $excel->insertText(0, 7, 'INSTRUCCIONES:', '', $estiloNegrita);

        $excel->insertText(1, 0, 'CANTIDAD', '', $estiloCabeceraBorde);
        $excel->insertFormula(1, 1, '=SUM(F5:F50000)', $estiloCabeceraBorde);
        $excel->insertText(1, 7, '1.- PARA LLENAR EL FORMATO DE PEDIDO, UNICAMENTE SE TIENE QUE LLENAR LA COLUMNA F CON LAS CANTIDADES DESEADAS', '', $estiloNegrita);

        $excel->insertText(2, 7, '2.- SE GUARDA EL ARCHIVO', '', $estiloNegrita);

        $excel->insertText(3, 0, 'FOLIO', '', $estiloCabeceraBorde);
        $excel->insertText(3, 1, 'SKU', '', $estiloCabeceraBorde);
        $excel->insertText(3, 2, 'Descripción', '', $estiloCabeceraBorde);
        $excel->insertText(3, 3, 'Existencia', '', $estiloCabeceraBorde);
        $excel->insertText(3, 4, 'MAYOREO', '', $estiloCabeceraBorde);
        $excel->insertText(3, 5, 'PEDIDO', '', $estiloCabeceraBorde);

        $excel->insertText(3, 7, '3.- SE ENVIA A SU EJECUTIVO DE VENTAS', '', $estiloNegrita);
        
        $excel->insertText(4, 7, 'OBSERVACIONES:', '', $estiloNegrita);
        $excel->insertText(5, 7, '1.- TODOS LOS PRODUCTOS QUE EN EXISTENCIA TENGAN UN "+10", SIGNIFICA QUE HAY MAS DE 10 EN INVENTARIO', '', $estiloNegrita);

        // =========================================================
        // 7. ESCRIBIR LOS DATOS DEL INVENTARIO
        // =========================================================
        $filaActual = 4;
        foreach ($listaProductos as $producto) {
            $excel->insertText($filaActual, 0, $producto['folio']);
            $excel->insertText($filaActual, 1, $producto['sku']);
            $excel->insertText($filaActual, 2, $producto['descripcion']);
            
            $excel->insertText($filaActual, 3, $producto['existenciaMostrar'], '', $estiloBorde);
            
            // Inyectamos el formato de Moneda como 4to argumento
            $excel->insertText($filaActual, 4, (float)$producto['mayoreo'], '$#,##0.00');
            
            $excel->insertText($filaActual, 5, '', '', $estiloPedidoData);
            
            $filaActual++;
        }

        // 8. Protección cifrada y Descarga
        $excel->protection('BELLAROMA123'); 
        $rutaFinal = $excel->output();

        return response()->download($rutaFinal, $nombreArchivo)->deleteFileAfterSend(true);
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
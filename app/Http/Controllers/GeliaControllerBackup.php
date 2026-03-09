<?php
//El namespace es la dirección del controlador y donde esta alojado, le dice a laraval exactamende donde se encuentra 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // <-- IMPORTAMOS LOGS PARA LA BITÁCORA
use App\Models\CustomList; // Se importa el modelo para interactuar con la tabla de listas personalizadas

class GeliaController extends Controller
{
    // Muestra la vista principal cargando las listas guardadas
    public function index()
    {
        // Obtenemos solo las listas activas
        $listasPersonalizadas = CustomList::where('active', true)->get();
        return view('gelia', compact('listasPersonalizadas')); // Pasamos las listas a la vista para que se muestren en el select de la vista gelia.blade.php
    }

    // Muestra la vista principal cargando las listas guardadas
    public function testIndex()
    {
        // Obtenemos solo las listas activas
        $listasPersonalizadas = CustomList::where('active', true)->get();
        return view('gelia-test', compact('listasPersonalizadas')); // Pasamos las listas a la vista para que se muestren en el select de la vista gelia.blade.php
    }

    // Guarda una nueva configuración de lista
    public function guardarLista(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_creador' => 'required|string|max:100',
            'titulo_lista' => 'required|string|max:50',
            'descripcion' => 'nullable|string',
            'color' => 'required|string',
            'archivos_requeridos' => 'required|array', // ['existencias', 'precios']
            'columnas_exportar' => 'required|string', // "SKU,PG,Lista3" (viene como string separado por comas)
            'nombre_archivo_salida' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Convertimos la cadena de columnas a array para guardarlo como JSON
        $columnasArray = explode(',', $request->columnas_exportar);
        
        // Atrapamos si el checkbox de "solo existencia" viene marcado
        $soloConExistencia = $request->boolean('solo_con_existencia');

        $lista = CustomList::create([
            'nombre_creador' => $request->nombre_creador,
            'titulo_lista' => $request->titulo_lista,
            'descripcion' => $request->descripcion,
            'color' => $request->color,
            'archivos_requeridos' => $request->archivos_requeridos,
            'columnas_exportar' => $columnasArray,
            'nombre_archivo_salida' => strtoupper($request->nombre_archivo_salida),
            'solo_con_existencia' => $soloConExistencia,
            'active' => true
        ]);

        // LOG: Guardamos el registro de creación tras bambalinas
        Log::info("GELIA - Nueva lista creada: '{$lista->titulo_lista}' por {$lista->nombre_creador}.");

        return response()->json(['message' => 'Lista creada con éxito']);
    }

    // NUEVO MÉTODO: Actualiza una lista existente
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $columnasArray = explode(',', $request->columnas_exportar);
        $soloConExistencia = $request->boolean('solo_con_existencia');

        $lista->update([
            'nombre_creador' => $request->nombre_creador,
            'titulo_lista' => $request->titulo_lista,
            'descripcion' => $request->descripcion,
            'color' => $request->color,
            'archivos_requeridos' => $request->archivos_requeridos,
            'columnas_exportar' => $columnasArray,
            'nombre_archivo_salida' => strtoupper($request->nombre_archivo_salida),
            'solo_con_existencia' => $soloConExistencia,
        ]);

        // LOG: Guardamos el registro de edición tras bambalinas
        Log::info("GELIA - Lista editada: '{$lista->titulo_lista}' por {$lista->nombre_creador}.");

        return response()->json(['message' => 'Lista actualizada con éxito']);
    }

    public function generar(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // =========================================================
        // PANEL DE VARIABLES GLOBALES (Fórmulas)
        // Modifica estos valores si cambian los porcentajes en el futuro
        // =========================================================
        $divisorCostoCalculado = 1.3827;
        $multiplicadorPlataformas = 0.77;
        $multiplicadorLista3 = 0.8572;
        $multiplicadorLista4 = 0.8229;
        $multiplicadorBoutique = 0.75; // <-- NUEVA VARIABLE PARA BOUTIQUE
        // =========================================================

        $tipoLista = $request->input('tipo_lista', 'PERSONALIZADA'); 
        $fecha = date('d-m-y');

        // ---------------------------------------------------------
        // MODO 1: LIMPIEZA DE CLIENTES (Dinámico y Sanitizado)
        // ---------------------------------------------------------
        if ($tipoLista === 'clientes') {
            // 1. Validación de seguridad e inputs
            $validator = Validator::make($request->all(), [
                'clientes' => 'required|file|mimes:csv,txt',
                'columnas_clientes' => 'nullable|string', // Ej: "ID,NOMBRE,RFC,TELEFONO"
                'incluir_sin_id' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // 2. Configuración de parámetros
            $columnasSeleccionadas = $request->input('columnas_clientes') 
                ? explode(',', $request->input('columnas_clientes')) 
                : ['ID', 'NOMBRE']; // Por defecto
                
            $incluirSinId = $request->boolean('incluir_sin_id', true); // Por defecto incluimos todos según tu requerimiento

            $listaClientes = [];

            // 3. Procesamiento seguro
            $this->procesarArchivoSeguro($request->file('clientes'), function ($ruta) use (&$listaClientes, $columnasSeleccionadas, $incluirSinId) {
                (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$listaClientes, $columnasSeleccionadas, $incluirSinId) {
                    
                    $idRaw = $linea[0] ?? '';
                    
                    // Omitir la primera línea inútil del CSV y la línea de encabezados
                    if ($idRaw === 'Clientes' || $idRaw === 'ID' || $idRaw === '') {
                        if (!$incluirSinId || $idRaw === 'Clientes' || $idRaw === 'ID') return;
                    }

                    $id = ltrim(trim((string)$idRaw), '0');

                    // Filtro para excluir los que no tienen ID (si el usuario así lo decide)
                    if (!$incluirSinId && $id === '') {
                        return;
                    }

                    // Función interna para limpieza de codificación y espacios
                    $limpiarTexto = function ($texto) {
                        $texto = trim(preg_replace('/\s+/', ' ', (string)($texto ?? '')));
                        // Detectamos y reparamos codificación ISO a UTF-8 (arregla acentos y Ñ)
                        return mb_check_encoding($texto, 'UTF-8') ? $texto : mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
                    };

                    // Mapeo maestro basado en el índice real de tu CSV
                    $filaCompleta = [
                        'ID' => $id,
                        'NOMBRE' => $limpiarTexto($linea[1] ?? ''),
                        'DIRECCION_FISCAL' => $limpiarTexto($linea[2] ?? ''),
                        'COLONIA_FISCAL' => $limpiarTexto($linea[3] ?? ''),
                        'MUNICIPIO_FISCAL' => $limpiarTexto($linea[4] ?? ''),
                        'CP_FISCAL' => $limpiarTexto($linea[5] ?? ''),
                        'ESTADO_FISCAL' => $limpiarTexto($linea[6] ?? ''),
                        'PAIS_FISCAL' => $limpiarTexto($linea[7] ?? ''),
                        'DIRECCION_CONTACTO' => $limpiarTexto($linea[8] ?? ''),
                        'COLONIA_CONTACTO' => $limpiarTexto($linea[9] ?? ''),
                        'MUNICIPIO_CONTACTO' => $limpiarTexto($linea[10] ?? ''),
                        'ESTADO_CONTACTO' => $limpiarTexto($linea[11] ?? ''),
                        'PAIS_CONTACTO' => $limpiarTexto($linea[12] ?? ''),
                        'CP_CONTACTO' => $limpiarTexto($linea[13] ?? ''),
                        'RFC' => $limpiarTexto($linea[14] ?? ''),
                        'TELEFONO' => $limpiarTexto($linea[15] ?? ''),
                        'EMAIL' => $limpiarTexto($linea[16] ?? ''),
                        'LIMITE_CREDITO' => (float)$limpiarTexto($linea[17] ?? '0'),
                        'CREDITO_DISPONIBLE' => (float)$limpiarTexto($linea[18] ?? '0'),
                        'DIAS_CHEQUE_POSTFECHADO' => (int)$limpiarTexto($linea[19] ?? '0'),
                        'DIAS_VENCIMIENTO' => (int)$limpiarTexto($linea[20] ?? '0'),
                        'PARTE_RELACIONAL' => (int)$limpiarTexto($linea[21] ?? '0'),
                        'REGIMEN_FISCAL' => $limpiarTexto($linea[22] ?? ''),
                        'USO_DE_CFDI' => $limpiarTexto($linea[23] ?? ''),
                        'GRUPO_DESCUENTO' => $limpiarTexto($linea[24] ?? ''),
                        'VARIABLE_CONTABLE' => $limpiarTexto($linea[25] ?? ''),
                        'TAGS' => $limpiarTexto($linea[26] ?? ''),
                        'TIPO' => $limpiarTexto($linea[27] ?? '')
                    ];

                    // Construcción dinámica del array a exportar
                    $filaFinal = [];
                    foreach ($columnasSeleccionadas as $col) {
                        if (array_key_exists($col, $filaCompleta)) {
                            $filaFinal[$col] = $filaCompleta[$col];
                        }
                    }

                    if (!empty($filaFinal)) {
                        $listaClientes[] = $filaFinal;
                    }
                });
            });

            // 4. Retorno del archivo generado
            return (new FastExcel(collect($listaClientes)))->download("CLIENTES-PERSONALIZADO-$fecha.xlsx");
        }

        // ---------------------------------------------------------
        // MODO 1.2: AUDITORÍA DE TAGS (CLIENTES SIN DESCUENTO)
        // Lógica de Mayra: Filtrar clientes sin descuento pero con Tag,
        // ordenarlos por ID y vaciar la columna Tag para "planchar" Wizerp.
        // ---------------------------------------------------------
        if ($tipoLista === 'clientes_auditoria_tags') {
            // 1. Validación de seguridad
            $validator = Validator::make($request->all(), [
                'clientes' => 'required|file|mimes:csv,txt'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $listaAuditoria = [];

            // 2. Procesamiento seguro y en memoria
            $this->procesarArchivoSeguro($request->file('clientes'), function ($ruta) use (&$listaAuditoria) {
                (new FastExcel)->withoutHeaders()->import($ruta, function ($linea) use (&$listaAuditoria) {
                    
                    $idRaw = $linea[0] ?? '';
                    
                    // Omitir la primera línea inútil del CSV, encabezados y filas vacías
                    if ($idRaw === 'Clientes' || $idRaw === 'ID' || $idRaw === '') {
                        return;
                    }

                    $id = ltrim(trim((string)$idRaw), '0');

                    // Descartar si el ID viene completamente vacío
                    if ($id === '') {
                        return;
                    }

                    // Función interna para limpieza de codificación y espacios
                    $limpiarTexto = function ($texto) {
                        $texto = trim(preg_replace('/\s+/', ' ', (string)($texto ?? '')));
                        return mb_check_encoding($texto, 'UTF-8') ? $texto : mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
                    };

                    // Extraemos solo lo necesario según los índices de tu archivo
                    // A(0)=ID, B(1)=NOMBRE, Y(24)=GRUPO_DESCUENTO, AA(26)=TAGS
                    $nombre = $limpiarTexto($linea[1] ?? '');
                    $grupoDescuento = $limpiarTexto($linea[24] ?? '');
                    $tags = $limpiarTexto($linea[26] ?? '');

                    // 3. LÓGICA DE FILTRADO NÚCLEO
                    // Filtrar en grupo de descuentos las vacías AND Filtrar en tags deseleccionando vacías
                    if ($grupoDescuento === '' && $tags !== '') {
                        $listaAuditoria[] = [
                            'ID' => $id,
                            'NOMBRE' => $nombre,
                            'GRUPO_DESCUENTO' => $grupoDescuento,
                            'TAGS' => '' // <-- Vaciamos el tag a propósito para que Wizerp lo elimine al importar
                        ];
                    }
                });
            });

            // 4. ORDENAMIENTO DE LA 'A' A LA 'Z' POR ID (Numérico)
            usort($listaAuditoria, function ($a, $b) {
                return (int)$a['ID'] <=> (int)$b['ID'];
            });

            // 5. Retorno del archivo generado
            return (new FastExcel(collect($listaAuditoria)))->download("AUDITORIA-TAGS-$fecha.xlsx");
        }

        // ---------------------------------------------------------
        // MODO 1.5: GASTOS COMPROBABLES
        // ---------------------------------------------------------
        if ($tipoLista === 'gastos') {
            // 1. Validación estricta de seguridad
            $validator = Validator::make($request->all(), [
                'archivo_gastos' => 'required|file',
                'filtro_tipo' => 'nullable|string|in:TODOS,Remisión,Pedido' // Aseguramos que solo reciba estos 3 valores
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $filtroTipo = $request->input('filtro_tipo', 'TODOS');
            $listaGastos = [];

            // 2. Procesamiento seguro
            $this->procesarArchivoSeguro($request->file('archivo_gastos'), function ($ruta) use (&$listaGastos, $filtroTipo) {
                
                // Importamos conservando los encabezados
                (new FastExcel)->import($ruta, function ($linea) use (&$listaGastos, $filtroTipo) {
                    
                    $folioCrudo = $linea['Folio de Venta'] ?? '';
                    
                    // 3. Lógica de separación
                    $partes = explode(' ', trim((string)$folioCrudo), 2);
                    
                    $tipoVenta = $partes[0] ?? ''; // "Remisión" o "Pedido"
                    $folioVenta = $partes[1] ?? ''; // "42077"

                    // 4. Lógica de filtrado
                    if ($filtroTipo !== 'TODOS' && strcasecmp($tipoVenta, $filtroTipo) !== 0) {
                        return; // Ignoramos esta fila
                    }

                    // 5. Reconstrucción de la fila
                    $listaGastos[] = [
                        'Fecha' => $linea['Fecha'] ?? '',
                        'Cliente' => $linea['Cliente'] ?? '',
                        'Tipo de Venta' => $tipoVenta,      // NUEVA COLUMNA
                        'Folio de Venta' => $folioVenta,    // COLUMNA LIMPIA
                        'Folio gasto' => $linea['Folio gasto'] ?? '',
                        'Descripción' => $linea['Descripción'] ?? '',
                        'Cantidad' => $linea['Cantidad'] ?? '',
                        'Importe' => $linea['Importe'] ?? ''
                    ];
                });
            });

            // 6. Configuración de estilos (Usando directamente la Entidad de OpenSpout)
            $estiloEncabezado = (new \OpenSpout\Common\Entity\Style\Style())
                ->setFontBold();

            return (new FastExcel(collect($listaGastos)))
                ->headerStyle($estiloEncabezado)
                ->download("GASTOS-COMPROBABLES-$fecha.xlsx");
        }

        // ---------------------------------------------------------
        // MODO 1.6: TRANSACCIONES BANCARIAS
        // ---------------------------------------------------------
        if ($tipoLista === 'transacciones') {
            $validator = Validator::make($request->all(), [
                'archivo_transacciones' => 'required|file'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $listaTransacciones = [];

            // 1. Procesamiento y Sanitización de Datos (Elimina los saltos de línea del banco)
            $this->procesarArchivoSeguro($request->file('archivo_transacciones'), function ($ruta) use (&$listaTransacciones) {
                (new FastExcel)->import($ruta, function ($linea) use (&$listaTransacciones) {
                    
                    // Función interna (Closure) para limpiar enters \n, \r y espacios múltiples
                    $limpiarTexto = function ($texto) {
                        return trim(preg_replace('/\s+/', ' ', (string)($texto ?? '')));
                    };

                    // Armamos la fila con cada columna estrictamente sanitizada
                    $listaTransacciones[] = [
                        'Fecha Movimiento'  => $limpiarTexto($linea['Fecha Movimiento']),
                        'Fecha Captura'     => $limpiarTexto($linea['Fecha Captura']),
                        'Cliente/Proveedor' => $limpiarTexto($linea['Cliente/Proveedor']),
                        'Transacción'       => $limpiarTexto($linea['Transacción']),
                        'Depósito'          => $limpiarTexto($linea['Depósito']),
                        'Forma de Pago'     => $limpiarTexto($linea['Forma de Pago']),
                        'Referencia'        => $limpiarTexto($linea['Referencia']),
                        'Concepto'          => $limpiarTexto($linea['Concepto'])
                    ];
                });
            });

            // 2. Configuración de estilos Avanzados (Negrita + Bordes Perimetrales + Evitar Ajuste)
            $colorNegro = \OpenSpout\Common\Entity\Style\Color::BLACK;
            $grosorLinea = \OpenSpout\Common\Entity\Style\Border::WIDTH_THIN;
            $estiloSolido = \OpenSpout\Common\Entity\Style\Border::STYLE_SOLID;

            // Instanciamos el marco celda por celda (Abajo, Arriba, Izquierda, Derecha)
            $bordePerimetral = new \OpenSpout\Common\Entity\Style\Border(
                new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::BOTTOM, $colorNegro, $grosorLinea, $estiloSolido),
                new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::TOP, $colorNegro, $grosorLinea, $estiloSolido),
                new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::LEFT, $colorNegro, $grosorLinea, $estiloSolido),
                new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::RIGHT, $colorNegro, $grosorLinea, $estiloSolido)
            );

            // Inyectamos el borde, la negrita y apagamos explícitamente el "wrap text"
            $estiloEncabezado = (new \OpenSpout\Common\Entity\Style\Style())
                ->setFontBold()
                ->setBorder($bordePerimetral)
                ->setShouldWrapText(false);

            // Configuramos un estilo extra para las filas de datos, asegurando alturas estándar
            $estiloFilas = (new \OpenSpout\Common\Entity\Style\Style())
                ->setShouldWrapText(false);

            return (new FastExcel(collect($listaTransacciones)))
                ->headerStyle($estiloEncabezado)
                ->rowsStyle($estiloFilas)
                ->download("TRANSACCIONES-BANCARIAS-$fecha.xlsx");
        }

        // ---------------------------------------------------------
        // MODO 2: SISTEMA DE EXISTENCIAS (Dinámico + Estándar)
        // ---------------------------------------------------------

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
        $this->procesarArchivoSeguro($request->file('existencias'), function ($ruta) use (&$listaCompleta, $diccionarioPrecios, $diccionarioCostosWizerp, $columnasSeleccionadas, $esListaPersonalizadaBD, $configuracionBD, $divisorCostoCalculado, $multiplicadorPlataformas, $multiplicadorLista3, $multiplicadorLista4, $multiplicadorBoutique) {
            $reader = (new FastExcel)->withoutHeaders()->import($ruta);
            foreach ($reader as $linea) {
                if (!isset($linea[4]) || $linea[4] == 'Código') continue;
                $skuCrudo = trim((string)$linea[4]);
                if ($skuCrudo === '') continue; 
                $skuBuscador = ltrim($skuCrudo, '0');

                $existenciaRaw = $linea[10] ?? 0;
                $existencia = is_numeric($existenciaRaw) ? (int)$existenciaRaw : 0;

                // --- NUEVO: FILTRO SOLO CON EXISTENCIA ---
                if ($esListaPersonalizadaBD && $configuracionBD->solo_con_existencia) {
                    if ($existencia <= 0) {
                        continue; // Si tiene 0 existencias o menos, nos saltamos este producto
                    }
                }
                // -----------------------------------------

                $almacen = $linea[1] ?? '';
                $folio = $linea[3] ?? '';
                $descripcion = $linea[5] ?? '';
                $marca = $linea[6] ?? '';
                
                $pg = $diccionarioPrecios[$skuBuscador] ?? 0.0;
                $costoWizerp = $diccionarioCostosWizerp[$skuBuscador] ?? 0.0;

                // Fórmulas aplicadas usando las variables globales
                $costoCalculado = $pg > 0 ? $pg / $divisorCostoCalculado : 0.0;
                $plataformas = $pg * $multiplicadorPlataformas;
                $lista3 = $pg * $multiplicadorLista3;
                $lista4 = $pg * $multiplicadorLista4;
                $listaBoutique = $pg * $multiplicadorBoutique; // <-- CÁLCULO LISTA BOUTIQUE

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
                        case 'ListaBoutique': $fila['Lista Boutique'] = round($listaBoutique, 2); break; // <-- MAPEO COLUMNA BOUTIQUE
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

    public function eliminarLista($id)
    {
        $lista = CustomList::find($id);

        if ($lista) {
            $lista->active = false;
            $lista->save();

            // LOG: Guardamos el registro de eliminación tras bambalinas
            Log::info("GELIA - Lista eliminada (Ocultada): '{$lista->titulo_lista}'.");

            return response()->json(['message' => 'Lista eliminada correctamente.']);
        }

        return response()->json(['error' => 'Lista no encontrada.'], 404);
    }
}
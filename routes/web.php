<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AromasListasController; // <-- NUEVO CEREBRO PRINCIPAL
use App\Http\Controllers\BellaromaController;
use App\Http\Controllers\AromasClienteController;
use App\Http\Controllers\BellaromaCargaPreciosController; // <-- NUEVO CONTROLADOR PARA CARGA DE PRECIOS DESDE CSV
use App\Http\Controllers\AromasGastoController;
use App\Http\Controllers\AromasTransaccionController;
use App\Http\Controllers\AromasAvisosController; // <-- NUEVO MÓDULO DE AVISOS DE MERCANCÍA
use App\Http\Controllers\AuthController; // <-- CONTROLADOR DE AUTENTICACIÓN
use App\Http\Controllers\AromasAsistenciaController; // <-- NUEVO MÓDULO DE ASISTENCIA TÉCNICA
use App\Http\Controllers\PlatformController; // <-- NUEVO CONTROLADOR PARA GESTIÓN DE PLATAFORMAS

// =========================================================
// RUTA PRINCIPAL (Home - Selección de Departamento)
// =========================================================
Route::get('/', function () {
    return view('home');
})->name('home');

// =========================================================
// MÓDULO AROMAS (Dividido por Funcionalidad)
// =========================================================
Route::prefix('aromas')->group(function () {

    // 1. MÓDULO DE LISTADOS E INVENTARIOS (El nuevo Core)
    // Conservamos los names originales (gelia.*) temporalmente por retrocompatibilidad con las otras vistas
    Route::get('/', [AromasListasController::class, 'index'])->name('gelia.index');
    Route::post('/guardar-lista', [AromasListasController::class, 'guardarLista'])->name('gelia.guardar');
    Route::post('/generar', [AromasListasController::class, 'generar'])->name('gelia.generar');
    Route::delete('/eliminar-lista/{id}', [AromasListasController::class, 'eliminarLista'])->name('gelia.eliminar');
    Route::post('/actualizar-lista/{id}', [AromasListasController::class, 'actualizarLista'])->name('gelia.actualizar');

    // NUEVA RUTA: Descarga Diferida de Excel con Inconsistencias
    Route::get('/descargar-temporal', [AromasListasController::class, 'descargarTemporal'])->name('gelia.descargar-temporal');

    // ---------------------------------------------------------
    // 2. MÓDULO DE CLIENTES INDEPENDIENTE
    // ---------------------------------------------------------
    Route::get('/clientes', [AromasClienteController::class, 'index'])->name('aromas.clientes.index');
    Route::post('/clientes/procesar', [AromasClienteController::class, 'procesar'])->name('aromas.clientes.procesar');

    // ---------------------------------------------------------
    // 3. MÓDULO DE GASTOS COMPROBABLES
    // ---------------------------------------------------------
    Route::get('/gastos', [AromasGastoController::class, 'index'])->name('aromas.gastos.index');
    Route::post('/gastos/procesar', [AromasGastoController::class, 'procesar'])->name('aromas.gastos.procesar');

    // ---------------------------------------------------------
    // 4. MÓDULO DE TRANSACCIONES BANCARIAS
    // ---------------------------------------------------------
    Route::get('/transacciones', [AromasTransaccionController::class, 'index'])->name('aromas.transacciones.index');
    Route::post('/transacciones/procesar', [AromasTransaccionController::class, 'procesar'])->name('aromas.transacciones.procesar');

    // ---------------------------------------------------------
    // 5. MÓDULO DE AVISOS DE MERCANCÍA (Cruce de Inventario)
    // ---------------------------------------------------------
    Route::get('/avisos', [AromasAvisosController::class, 'index'])->name('aromas.avisos.index');
    Route::post('/avisos/procesar', [AromasAvisosController::class, 'procesar'])->name('aromas.avisos.procesar');

    // ---------------------------------------------------------
    // 6. MÓDULO DE ASISTENCIA (Máquina Checadora)
    // ---------------------------------------------------------
    Route::get('/asistencia', [AromasAsistenciaController::class, 'index'])->name('aromas.asistencia.index');
    Route::post('/asistencia/procesar', [AromasAsistenciaController::class, 'procesar'])->name('aromas.asistencia.procesar');
});

// =========================================================
// MÓDULO BELLAROMA | Generador de Plantillas
// =========================================================
Route::prefix('bellaroma')->group(function () {
    Route::get('/', [BellaromaController::class, 'index'])->name('bellaroma.index');
    Route::post('/generar', [BellaromaController::class, 'generar'])->name('bellaroma.generar');
    Route::get('/descargar/{id}', [BellaromaController::class, 'descargar'])->name('bellaroma.descargar');
    Route::delete('/eliminar/{id}', [BellaromaController::class, 'eliminar'])->name('bellaroma.eliminar');
    Route::post('/configuracion/verificar', [BellaromaController::class, 'verificarPin'])->name('bellaroma.config.verificar');
    Route::post('/configuracion/guardar', [BellaromaController::class, 'guardarConfiguracion'])->name('bellaroma.config.guardar');
});

// =========================================================
// RUTAS DE SEGURIDAD (LOGIN / LOGOUT)
// =========================================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================
// MÓDULO WOOCOMMERCE | Sincronización de Precios
// =========================================================
Route::prefix('woocommerce')->middleware('auth')->group(function () {
    // 1. Interfaz Principal
    Route::get('/', [BellaromaCargaPreciosController::class, 'index'])->name('woocommerce.index');

    // 2. Seguridad y Configuración Dinámica
    Route::post('/verificar', [BellaromaCargaPreciosController::class, 'verificarPin'])->name('woocommerce.verificar');
    Route::post('/configuracion/guardar', [BellaromaCargaPreciosController::class, 'guardarConfiguracion'])->name('woocommerce.config.guardar');

    // 3. Sincronización de Base de Datos de Productos (El CSV de Woo)
    Route::post('/productos/sincronizar', [BellaromaCargaPreciosController::class, 'sincronizarProductos'])->name('woocommerce.productos.sincronizar');

    // 4. Procesamiento Diario (Solo subes el Excel de Wizerp)
    Route::post('/procesar', [BellaromaCargaPreciosController::class, 'procesar'])->name('woocommerce.procesar');

    // 5. Gestión del Historial Local
    Route::get('/descargar/{id}', [BellaromaCargaPreciosController::class, 'descargar'])->name('woocommerce.descargar');
    Route::delete('/eliminar/{id}', [BellaromaCargaPreciosController::class, 'eliminar'])->name('woocommerce.eliminar');

    // Ruta para la prueba de concepto de la API
    Route::get('/api/test-productos', [BellaromaCargaPreciosController::class, 'testApiGetProducts'])->name('woocommerce.api.test');
    Route::post('/api/actualizar-prueba', [BellaromaCargaPreciosController::class, 'actualizarPrecioPrueba'])->name('woocommerce.api.update');
    Route::post('/api/descargar-precios', [\App\Http\Controllers\BellaromaCargaPreciosController::class, 'descargarPreciosApi'])->name('woocommerce.api.descargar');
    // ¡NUEVA! Ruta para consultar el progreso (esta es la que causó el 404)
    Route::get('/api/progreso/{id}', [\App\Http\Controllers\BellaromaCargaPreciosController::class, 'consultarProgreso'])->name('woocommerce.api.progreso');
    Route::post('/api/carga-masiva', [BellaromaCargaPreciosController::class, 'iniciarCargaMasiva'])->name('woocommerce.api.carga-masiva');
    Route::put('/api/producto/{id}', [\App\Http\Controllers\BellaromaCargaPreciosController::class, 'actualizarPrecioIndividual'])->name('woocommerce.api.actualizar-individual');
    // Vista del Centro de Auditoría
    Route::get('/auditoria', [\App\Http\Controllers\BellaromaCargaPreciosController::class, 'auditoriaIndex'])->name('woocommerce.auditoria');

    // Descarga del CSV (El que creamos en el paso anterior)
    Route::get('/auditoria/descargar/{id}', [\App\Http\Controllers\BellaromaCargaPreciosController::class, 'descargarAuditoria'])->name('woocommerce.auditoria.descargar');

    // PANEL DE ALERTAS WOOCOMMERCE
    Route::get('/alertas', [BellaromaCargaPreciosController::class, 'alertasInventario'])
        ->name('woocommerce.alertas');

    // ACCIÓN DE EMERGENCIA (AJAX)
    Route::post('/emergencia/ocultar', [BellaromaCargaPreciosController::class, 'emergenciaOcultarProductos'])
        ->name('woocommerce.emergencia');

    Route::post('/sync/{id}/cancelar', [BellaromaCargaPreciosController::class, 'forzarCancelacionSync'])
    ->name('woocommerce.sync.cancelar');

    Route::post('/sync/{id}/reanudar', [BellaromaCargaPreciosController::class, 'reanudarSync'])
    ->name('woocommerce.sync.reanudar');
});



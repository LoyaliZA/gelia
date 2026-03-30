<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AromasListasController; // <-- NUEVO CEREBRO PRINCIPAL
use App\Http\Controllers\BellaromaController;
use App\Http\Controllers\AromasClienteController;
use App\Http\Controllers\BellaromaCargaPreciosController; // <-- NUEVO CONTROLADOR PARA CARGA DE PRECIOS DESDE CSV
use App\Http\Controllers\AromasGastoController;
use App\Http\Controllers\AromasTransaccionController;
use App\Http\Controllers\AromasAvisosController; // <-- NUEVO MÓDULO DE AVISOS DE MERCANCÍA

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
// MÓDULO WOOCOMMERCE | Sincronización de Precios
// =========================================================
Route::prefix('woocommerce')->group(function () {
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
});
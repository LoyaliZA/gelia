<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeliaController;
use App\Http\Controllers\BellaromaController;
use App\Http\Controllers\AromasClienteController;
use App\Http\Controllers\AromasGastoController;
use App\Http\Controllers\AromasTransaccionController; // <-- IMPORTAMOS EL CONTROLADOR DE TRANSACCIONES
//use App\Http\Controllers\SefeController;

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
    // 1. Rutas del Core (Listas, Existencias, Costos - Aún en GeliaController)
    Route::get('/', [GeliaController::class, 'index'])->name('gelia.index');
    Route::post('/guardar-lista', [GeliaController::class, 'guardarLista'])->name('gelia.guardar');
    Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');
    Route::delete('/eliminar-lista/{id}', [GeliaController::class, 'eliminarLista'])->name('gelia.eliminar');
    Route::post('/actualizar-lista/{id}', [GeliaController::class, 'actualizarLista'])->name('gelia.actualizar');
    Route::get('/gelia-test', [GeliaController::class, 'testIndex'])->name('gelia.test');

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
    // 4. NUEVAS RUTAS: MÓDULO DE TRANSACCIONES BANCARIAS
    // ---------------------------------------------------------
    Route::get('/transacciones', [AromasTransaccionController::class, 'index'])->name('aromas.transacciones.index');
    Route::post('/transacciones/procesar', [AromasTransaccionController::class, 'procesar'])->name('aromas.transacciones.procesar');
});

// =========================================================
// MÓDULO BELLAROMA | Generador de Plantillas
// =========================================================
Route::prefix('bellaroma')->group(function () {
    Route::get('/', [BellaromaController::class, 'index'])->name('bellaroma.index');
    Route::post('/generar', [BellaromaController::class, 'generar'])->name('bellaroma.generar');
});

// =========================================================
// S.E.F.E. | Sistema Extractor de Facturas Electrónicas
// =========================================================
//Route::get('/sefe', [SefeController::class, 'index'])->name('sefe.index');
//Route::post('/sefe/proveedor', [SefeController::class, 'guardarProveedor'])->name('sefe.proveedor.guardar');
//Route::post('/sefe/procesar', [SefeController::class, 'procesarFacturas'])->name('sefe.procesar');
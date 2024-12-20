<?php

use App\Http\Controllers\AccountsReceivableController;
use App\Http\Controllers\AopController;
use App\Http\Controllers\AopReceiptController;
use App\Http\Controllers\API\CustomerPaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComparatorController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DksController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasterTokoController;
use App\Http\Controllers\NonAopController;
use App\Http\Controllers\NonReceiptController;
use App\Http\Controllers\ReportDKSController;
use App\Http\Controllers\SalesOrderController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['auth', 'check.online', 'auth.session'])->group(function () {
    // DASHBOARD
    Route::get('/', function () {
        return view('welcome');
    })->name('dashboard');

    // DKS-SCAN
    Route::get('dks-scan/{kd_toko?}', [DksController::class, 'index'])->name('dks.scan');
    Route::get('dks-scan-qr', [DksController::class, 'scan'])->name('dks.scan-qr');
    Route::post('dks-scan/store/{kd_toko}', [DksController::class, 'store'])->name('dks.store');

    // REPORT DKS
    Route::get('report/dks', [ReportDKSController::class, 'index'])->name('report.dks');
    Route::get('report/dks/rekap-punishment', [ReportDKSController::class, 'rekap'])->name('report.dks-rekap-punishment');
    Route::post('report/dks/export', [ReportDKSController::class, 'export'])->name('report-dks.export');

    // MASTER TOKO
    Route::get('master-toko', [MasterTokoController::class, 'index'])->name('master-toko.index');
    Route::get('master-toko/create', [MasterTokoController::class, 'create'])->name('master-toko.create');
    Route::post('master-toko/store', [MasterTokoController::class, 'store'])->name('master-toko.store');
    Route::get('master-toko/edit/{kd_toko}', [MasterTokoController::class, 'edit'])->name('master-toko.edit');
    Route::put('master-toko/update/{kd_toko}', [MasterTokoController::class, 'update'])->name('master-toko.update');
    Route::get('master-toko/destroy/{kd_toko}', [MasterTokoController::class, 'destroy'])->name('master-toko.destroy');

    // PEMBELIAN AOP
    Route::get('/pembelian/aop/upload', [AopController::class, 'indexUpload'])->name('aop.index');
    Route::get('/pembelian/aop/upload/{invoiceAop}', [AopController::class, 'detail'])->name('aop.detail');
    Route::get('/pembelian/aop/final', [AopController::class, 'final'])->name('aop.final');
    Route::get('/pembelian/aop/final/{invoiceAop}', [AopController::class, 'finalDetail'])->name('aop.final.detail');

    // PEMBELIAN NON AOP
    Route::get('/pembelian/non-aop', [NonAopController::class, 'index'])->name('non-aop.index');
    Route::get('/pembelian/non-aop/create', [NonAopController::class, 'create'])->name('non-aop.create');
    Route::get('/pembelian/non-aop/detail/{invoiceNon}', [NonAopController::class, 'detail'])->name('non-aop.detail');

    // GOODS RECEIPT AOP
    Route::get('/gr/aop', [AopReceiptController::class, 'index'])->name('aop-gr.index');
    Route::get('/gr/aop/{invoiceAop}', [AopReceiptController::class, 'detail'])->name('aop-gr.detail');

    // GOODS RECEIPT NON
    Route::get('/gr/non-aop', [NonReceiptController::class, 'index'])->name('non-gr.index');
    Route::get('/gr/non-aop/{invoiceNon}', [NonReceiptController::class, 'detail'])->name('non-gr.detail');

    // DELIVERY ORDER
    Route::get('delivery-order', [DeliveryOrderController::class, 'index'])->name('do.index');
    Route::get('delivery-order/detail/{no_lkh}', [DeliveryOrderController::class, 'detail'])->name('do.detail');

    // INVOICE 
    Route::get('/invoice', [InvoiceController::class, 'index'])->name('inv.index');
    Route::get('/invoice/detail/{noso}', [InvoiceController::class, 'detail'])->name('inv.detail');
    Route::post('/invoice/store', [InvoiceController::class, 'createInvoice'])->name('inv.store');
    Route::get('/invoice/detail-print/{invoice}', [InvoiceController::class, 'detailPrint'])->name('inv.detail-print');
    Route::get('/invoice/batal/{invoice}', [InvoiceController::class, 'batal'])->name('inv.batal');
    Route::get('/invoice/bosnet', [InvoiceController::class, 'invoiceBosnet'])->name('inv.bosnet');
    Route::get('/invoice/history', [InvoiceController::class, 'history'])->name('inv.history');
    Route::get('/invoice/history-detail/{invoice}', [InvoiceController::class, 'historyDetail'])->name('inv.history-detail');
    Route::get('/invoice/print/{invoice}', [InvoiceController::class, 'print'])->name('inv.print');

    // COMPARATOR
    Route::get('/comparator', [ComparatorController::class, 'index'])->name('comparator.index');
    Route::get('/comparator/delete/{part_number}', [ComparatorController::class, 'destroy'])->name('comparator.destroy');
    Route::post('/comparator/edit-qty', [ComparatorController::class, 'edit_qty'])->name('comparator.edit-qty');

    // CUSTOMER PAYMENT
    Route::get('/customer-payment', [CustomerPaymentController::class, 'index'])->name('customer-payment.index');
    Route::get('/customer-payment/detail/{no_piutang}', [CustomerPaymentController::class, 'detail'])->name('customer-payment.detail');

    // STOCK MOVEMENT
    Route::get('/stock-movement', function () {
        return view('stock-movement.index');
    })->name('stock-movement.index');

    // ACCOUNTS RECEIVABLE
    Route::get('/accounts-receivable', [AccountsReceivableController::class, 'index'])->name('ar.index');

    // LOGOUT
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['guest'])->group(function () {
    Route::get('login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

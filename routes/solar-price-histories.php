<?php

use App\Http\Controllers\SolarPriceHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('solar-price-histories/fetch-last-pertamina-solar', [SolarPriceHistoryController::class, 'fetchLastPertaminaSolar'])
    ->name('solar-price-histories.fetch-last');

Route::get('solar-price-histories/invoices/{invoice}/solar-lines', [SolarPriceHistoryController::class, 'solarLinesForInvoice'])
    ->name('solar-price-histories.solar-lines');

Route::get('solar-price-histories/{solar_price_history}/invoice-preview', [SolarPriceHistoryController::class, 'invoicePreview'])
    ->name('solar-price-histories.invoice-preview');

Route::resource('solar-price-histories', SolarPriceHistoryController::class);

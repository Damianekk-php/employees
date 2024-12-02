<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');

Route::get('/generate-pdf-form', [EmployeeController::class, 'showPdfForm'])->name('generate.form');

Route::get('/generate-pdf/{first_name}/{last_name}', [EmployeeController::class, 'generatePdf'])->name('generate.pdf');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/backup', [BackupController::class, 'index'])
    ->name('backup.index');

Route::post('/backup/create', [BackupController::class, 'createBackup'])
    ->name('backup.create');

Route::get('/backup/download/{file}', [BackupController::class, 'downloadBackup'])
    ->name('backup.download');

Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])
    ->name('backup.restore');

Route::delete('/backup/delete/{file}', [BackupController::class, 'deleteBackup'])
    ->name('backup.delete');

Route::get('/', function () {
    return redirect('/backup');
});
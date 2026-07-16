<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
Route::get('/backup/download/{file}', [BackupController::class, 'downloadBackup'])->name('backup.download');
Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');
Route::delete('/backup/delete/{file}', [BackupController::class, 'deleteBackup'])->name('backup.delete');

// Settings
Route::get('/backup/settings', [BackupController::class, 'settingsPage'])->name('backup.settings');
Route::post('/backup/settings', [BackupController::class, 'saveSettings'])->name('backup.settings.save');

// Cloud Config
Route::get('/backup/cloud', [BackupController::class, 'cloudPage'])->name('backup.cloud');
Route::post('/backup/cloud', [BackupController::class, 'saveCloud'])->name('backup.cloud.save');
Route::delete('/backup/cloud/{id}', [BackupController::class, 'deleteCloud'])->name('backup.cloud.delete');

// Validation Tester
Route::get('/backup/validate', [BackupController::class, 'validatePage'])->name('backup.validate');
Route::post('/backup/validate', [BackupController::class, 'runValidation'])->name('backup.validate.run');

Route::get('/', function () {
    return redirect('/backup');
});
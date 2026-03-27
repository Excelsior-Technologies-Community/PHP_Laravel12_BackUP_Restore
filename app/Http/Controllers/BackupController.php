<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BackupController extends Controller
{
    // Show backup page
    public function index()
    {
        $backupPath = storage_path('app/backups');

        // Ensure folder exists
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        // Get all SQL files
        $files = glob($backupPath . '/*.sql');

        // Extract filenames
        $backups = array_map(function ($file) {
            return basename($file);
        }, $files);

        return view('backup.index', compact('backups'));
    }

    // Create backup
    public function createBackup()
    {
        $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        $path = storage_path("app/backups/{$filename}");

        $dbHost = env('DB_HOST');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        // XAMPP mysqldump path
        $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        $command = "\"{$mysqldumpPath}\" -h {$dbHost} -u {$dbUser} ";
        $command .= $dbPass ? "-p{$dbPass} " : "";
        $command .= "{$dbName} > \"{$path}\"";

        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return back()->with('success', "Backup created: {$filename}");
        } else {
            return back()->with('error', 'Backup failed. Check DB credentials, folder permissions, or mysqldump path.');
        }
    }

    // Download backup
    public function downloadBackup($file)
    {
        $filePath = storage_path('app/backups/' . $file);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File not found.');
        }

        return response()->download($filePath);
    }

    // Restore backup
  public function restoreBackup(Request $request)
{
    $request->validate([
        'backup_file' => 'required|string'
    ]);

    $filePath = storage_path('app/backups/' . $request->backup_file);

    if (!file_exists($filePath)) {
        return back()->with('error', 'Backup file not found.');
    }

    $dbHost = env('DB_HOST');
    $dbUser = env('DB_USERNAME');
    $dbPass = env('DB_PASSWORD');
    $dbName = env('DB_DATABASE');

    //  Correct MySQL path
    $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';

    //  FINAL WORKING COMMAND
    $command = "\"{$mysqlPath}\" -h {$dbHost} -u {$dbUser} ";
    $command .= $dbPass ? "-p{$dbPass} " : "";
    $command .= "{$dbName} < \"{$filePath}\"";

    //  VERY IMPORTANT for Windows
    $command = 'cmd /c ' . $command;

    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        return back()->with('success', 'Database restored successfully.');
    } else {
        return back()->with('error', 'Database restore failed. Try again.');
    }
}
}
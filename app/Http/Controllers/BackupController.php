<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackupLog;

class BackupController extends Controller
{
    // Show Page
    public function index(Request $request)
    {
        $backupPath = storage_path('app/backups');

        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $files = glob($backupPath . '/*.sql');

        $search = $request->search;

        $backups = collect($files)->map(function ($file) {

            return [
                'name' => basename($file),
                'size' => round(filesize($file) / 1024, 2),
                'date' => date("d M Y h:i A", filemtime($file)),
                'timestamp' => filemtime($file),
            ];

        })->filter(function ($backup) use ($search) {

            if (!$search) {
                return true;
            }

            return str_contains(
                strtolower($backup['name']),
                strtolower($search)
            );

        })->sortByDesc('timestamp')->values();

        // PAGINATION
        $perPage = 4;

        $currentPage = request()->get('page', 1);

        $pagedData = $backups->slice(
            ($currentPage - 1) * $perPage,
            $perPage
        )->values();

        $backups = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $backups->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $totalBackups = collect($files)->count();

        $totalStorage = round(
            collect($files)->sum(fn($file) => filesize($file)) / 1024 / 1024,
            2
        );

        return view('backup.index', compact(
            'backups',
            'totalBackups',
            'totalStorage'
        ));
    }

    // Create Backup
    public function createBackup()
    {
        $backupPath = storage_path('app/backups');

        // Create folder if not exists
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        // Backup filename
        $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';

        $path = $backupPath . '/' . $filename;

        // Database config
        $dbHost = env('DB_HOST');
        $dbPort = env('DB_PORT');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        // XAMPP mysqldump path
        $mysqldump = "C:/xampp/mysql/bin/mysqldump.exe";

        // Command
        $command = "\"{$mysqldump}\" --host={$dbHost} --port={$dbPort} --user={$dbUser} {$dbName} > \"{$path}\"";

        // Add password if exists
        if (!empty($dbPass)) {
            $command = "\"{$mysqldump}\" --host={$dbHost} --port={$dbPort} --user={$dbUser} --password={$dbPass} {$dbName} > \"{$path}\"";
        }

        // Execute
        system($command, $result);

        // Check success
        if ($result === 0 && file_exists($path) && filesize($path) > 0) {

            BackupLog::create([
                'file_name' => $filename,
                'file_size' => round(filesize($path) / 1024, 2) . ' KB',
                'status' => 'Success'
            ]);

            return back()->with(
                'success',
                'Backup Created Successfully'
            );
        }

        // Delete empty file
        if (file_exists($path)) {
            unlink($path);
        }

        return back()->with(
            'error',
            'Backup Failed'
        );
    }
    // Download Backup
    public function downloadBackup($file)
    {
        $filePath = storage_path('app/backups/' . $file);

        if (!file_exists($filePath)) {
            return back()->with('error', 'File not found');
        }

        return response()->download($filePath);
    }

    // Delete Backup
    public function deleteBackup($file)
    {
        $filePath = storage_path('app/backups/' . $file);

        if (file_exists($filePath)) {

            unlink($filePath);

            BackupLog::where('file_name', $file)->delete();

            return back()->with(
                'success',
                'Backup Deleted Successfully'
            );
        }

        return back()->with(
            'error',
            'File Not Found'
        );
    }

    // Restore Backup
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|string'
        ]);

        $filePath = storage_path(
            'app/backups/' . $request->backup_file
        );

        if (!file_exists($filePath)) {
            return back()->with(
                'error',
                'Backup file not found'
            );
        }

        $dbHost = env('DB_HOST');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';

        $command = "\"{$mysqlPath}\" -h {$dbHost} -u {$dbUser} ";

        $command .= $dbPass ? "-p{$dbPass} " : "";

        $command .= "{$dbName} < \"{$filePath}\"";

        $command = 'cmd /c ' . $command;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {

            return back()->with(
                'success',
                'Database Restored Successfully'
            );
        }

        return back()->with(
            'error',
            'Restore Failed'
        );
    }
}
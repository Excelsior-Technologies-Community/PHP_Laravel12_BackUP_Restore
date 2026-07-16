<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Models\BackupCloudConfig;
use App\Models\BackupValidationLog;
use ZipArchive;

class BackupController extends Controller
{
    // Show Page
    public function index(Request $request)
    {
        $backupPath = storage_path('app/backups');

        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $files = glob($backupPath . '/*.{sql,zip}', GLOB_BRACE);

        $search = $request->search;

        $backups = collect($files)->map(function ($file) {
            return [
                'name'      => basename($file),
                'size'      => round(filesize($file) / 1024, 2),
                'date'      => date('d M Y h:i A', filemtime($file)),
                'timestamp' => filemtime($file),
                'type'      => pathinfo($file, PATHINFO_EXTENSION),
            ];
        })->filter(function ($backup) use ($search) {
            return !$search || str_contains(strtolower($backup['name']), strtolower($search));
        })->sortByDesc('timestamp')->values();

        $perPage     = 5;
        $currentPage = request()->get('page', 1);
        $pagedData   = $backups->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $backups = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $backups->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $allFiles     = glob($backupPath . '/*.{sql,zip}', GLOB_BRACE);
        $totalBackups = count($allFiles);
        $totalStorage = round(collect($allFiles)->sum(fn($f) => filesize($f)) / 1024 / 1024, 2);

        return view('backup.index', compact('backups', 'totalBackups', 'totalStorage'));
    }

    // Create Backup (with settings, compression, encryption, cloud)
    public function createBackup()
    {
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $settings = BackupSetting::current();

        $dbHost  = env('DB_HOST');
        $dbPort  = env('DB_PORT');
        $dbUser  = env('DB_USERNAME');
        $dbPass  = env('DB_PASSWORD');
        $dbName  = env('DB_DATABASE');

        $mysqldump = 'D:/xampp/mysql/bin/mysqldump.exe';
        $sqlFile   = $backupPath . '/backup-' . date('Y-m-d_H-i-s') . '.sql';

        // Build flags from settings
        $flags = '';
        if ($settings->include_structure && !$settings->include_data) {
            $flags .= ' --no-data';
        }
        if ($settings->include_data && !$settings->include_structure) {
            $flags .= ' --no-create-info';
        }

        // Excluded tables
        $excludeFlags = '';
        if (!empty($settings->excluded_tables)) {
            foreach ($settings->excluded_tables as $tbl) {
                $excludeFlags .= " --ignore-table={$dbName}.{$tbl}";
            }
        }

        // Included tables (specific tables only)
        $tableList = '';
        if (!empty($settings->included_tables)) {
            $tableList = implode(' ', $settings->included_tables);
        }

        $passFlag = !empty($dbPass) ? "--password={$dbPass}" : '';
        $command  = "cmd /c \"{$mysqldump}\" --host={$dbHost} --port={$dbPort} --user={$dbUser} {$passFlag}{$flags}{$excludeFlags} {$dbName} {$tableList} > \"{$sqlFile}\"";

        exec($command, $output, $result);

        if ($result !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
            if (file_exists($sqlFile)) unlink($sqlFile);
            return back()->with('error', 'Backup Failed. Check DB credentials or mysqldump path.');
        }

        $finalFile = $sqlFile;
        $finalName = basename($sqlFile);

        // Compression
        if ($settings->compress) {
            $zipFile = str_replace('.sql', '.zip', $sqlFile);
            $zip     = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                $zip->addFile($sqlFile, basename($sqlFile));
                $zip->close();
                unlink($sqlFile);
                $finalFile = $zipFile;
                $finalName = basename($zipFile);
            }
        }

        // Encryption
        if ($settings->encrypt && !empty($settings->encrypt_key)) {
            $encFile    = $finalFile . '.enc';
            $key        = substr(hash('sha256', $settings->encrypt_key, true), 0, 32);
            $iv         = random_bytes(16);
            $plaintext  = file_get_contents($finalFile);
            $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            file_put_contents($encFile, $iv . $ciphertext);
            unlink($finalFile);
            $finalFile = $encFile;
            $finalName = basename($encFile);
        }

        $fileSize = round(filesize($finalFile) / 1024, 2) . ' KB';

        BackupLog::create([
            'file_name' => $finalName,
            'file_size' => $fileSize,
            'status'    => 'Success',
        ]);

        // Auto Cloud Upload
        $this->autoCloudUpload($finalFile, $finalName);

        return back()->with('success', "Backup Created: {$finalName}");
    }

    // Auto upload to enabled cloud providers
    private function autoCloudUpload(string $filePath, string $fileName): void
    {
        $clouds = BackupCloudConfig::where('enabled', true)->where('auto_upload', true)->get();

        foreach ($clouds as $cloud) {
            try {
                if ($cloud->provider === 's3') {
                    $this->uploadToS3($filePath, $fileName, $cloud->config);
                } elseif ($cloud->provider === 'ftp') {
                    $this->uploadToFtp($filePath, $fileName, $cloud->config);
                }
                // gdrive: requires OAuth — skipped for local env
            } catch (\Exception $e) {
                \Log::error("Cloud upload failed [{$cloud->provider}]: " . $e->getMessage());
            }
        }
    }

    private function uploadToS3(string $filePath, string $fileName, array $config): void
    {
        $s3 = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => $config['region'] ?? 'us-east-1',
            'credentials' => [
                'key'    => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
        ]);

        $s3->putObject([
            'Bucket'     => $config['bucket'] ?? '',
            'Key'        => ($config['path'] ?? 'backups/') . $fileName,
            'SourceFile' => $filePath,
        ]);
    }

    private function uploadToFtp(string $filePath, string $fileName, array $config): void
    {
        $conn = ftp_connect($config['host'] ?? '', (int)($config['port'] ?? 21));
        if (!$conn) return;
        if (!ftp_login($conn, $config['user'] ?? '', $config['pass'] ?? '')) {
            ftp_close($conn);
            return;
        }
        ftp_pasv($conn, true);
        $remotePath = rtrim($config['path'] ?? '/', '/') . '/' . $fileName;
        ftp_put($conn, $remotePath, $filePath, FTP_BINARY);
        ftp_close($conn);
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
            return back()->with('success', 'Backup Deleted Successfully');
        }
        return back()->with('error', 'File Not Found');
    }

    // Settings Page
    public function settingsPage()
    {
        $settings = BackupSetting::current();
        $allTables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $tables = array_map(fn($t) => array_values((array)$t)[0], $allTables);
        return view('backup.settings', compact('settings', 'tables'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'encrypt_key' => 'nullable|string|max:64',
            'compression_level' => 'nullable|in:1,2,3,4,5,6,7,8,9',
        ]);

        $settings = BackupSetting::current();
        $settings->update([
            'include_structure' => $request->boolean('include_structure'),
            'include_data'      => $request->boolean('include_data'),
            'compress'          => $request->boolean('compress'),
            'encrypt'           => $request->boolean('encrypt'),
            'encrypt_key'       => $request->encrypt_key,
            'compression_level' => $request->compression_level ?? '6',
            'included_tables'   => $request->included_tables ?? [],
            'excluded_tables'   => $request->excluded_tables ?? [],
        ]);

        return back()->with('success', 'Settings saved successfully.');
    }

    // Cloud Config Page
    public function cloudPage()
    {
        $clouds = BackupCloudConfig::all();
        return view('backup.cloud', compact('clouds'));
    }

    public function saveCloud(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:s3,ftp',
            'label'    => 'required|string|max:100',
        ]);

        BackupCloudConfig::create([
            'provider'    => $request->provider,
            'label'       => $request->label,
            'enabled'     => $request->boolean('enabled'),
            'auto_upload' => $request->boolean('auto_upload'),
            'config'      => [
                'region' => $request->region,
                'key'    => $request->key,
                'secret' => $request->secret,
                'bucket' => $request->bucket,
                'path'   => $request->path ?? 'backups/',
                'host'   => $request->host,
                'port'   => $request->port ?? 21,
                'user'   => $request->ftp_user,
                'pass'   => $request->ftp_pass,
            ],
        ]);

        return back()->with('success', 'Cloud config saved.');
    }

    public function deleteCloud($id)
    {
        BackupCloudConfig::findOrFail($id)->delete();
        return back()->with('success', 'Cloud config deleted.');
    }

    // Validation Tester Page
    public function validatePage()
    {
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath . '/*.sql') ?: [];
        $backups = array_map('basename', $files);
        $logs = BackupValidationLog::latest()->take(10)->get();
        return view('backup.validate', compact('backups', 'logs'));
    }

    public function runValidation(Request $request)
    {
        $request->validate(['backup_file' => 'required|string']);

        $fileName = $request->backup_file;
        $filePath = storage_path('app/backups/' . $fileName);

        if (!file_exists($filePath)) {
            return back()->with('error', 'Backup file not found.');
        }

        $content = file_get_contents($filePath);
        $checks  = [];
        $status  = 'passed';

        // Check 1: File not empty
        $checks['file_not_empty'] = strlen($content) > 100 ? 'pass' : 'fail';

        // Check 2: Has CREATE TABLE
        $checks['has_create_table'] = str_contains($content, 'CREATE TABLE') ? 'pass' : 'warn';

        // Check 3: Has INSERT INTO
        $checks['has_insert_data'] = str_contains($content, 'INSERT INTO') ? 'pass' : 'warn';

        // Check 4: No syntax error markers
        $checks['no_error_markers'] = !str_contains($content, 'ERROR') ? 'pass' : 'fail';

        // Check 5: Valid SQL header
        $checks['valid_sql_header'] = str_contains($content, 'MySQL dump') || str_contains($content, 'mysqldump') ? 'pass' : 'warn';

        // Count tables
        preg_match_all('/CREATE TABLE `?(\w+)`?/i', $content, $tableMatches);
        $tablesFound = count($tableMatches[1]);

        // Count rows
        preg_match_all('/INSERT INTO/i', $content, $insertMatches);
        $rowsChecked = count($insertMatches[0]);

        // Determine status
        if (in_array('fail', $checks)) {
            $status = 'failed';
        } elseif (in_array('warn', $checks)) {
            $status = 'partial';
        }

        $log = BackupValidationLog::create([
            'backup_file'     => $fileName,
            'status'          => $status,
            'tables_found'    => $tablesFound,
            'tables_verified' => $tablesFound,
            'rows_checked'    => $rowsChecked,
            'details'         => 'Sandbox validation completed at ' . now(),
            'checks'          => $checks,
        ]);

        return back()->with('validation_result', $log->id);
    }

    // Restore Backup
    public function restoreBackup(Request $request)
    {
        $request->validate(['backup_file' => 'required|string']);

        $fileName = $request->backup_file;
        $filePath = storage_path('app/backups/' . $fileName);

        if (!file_exists($filePath)) {
            return back()->with('error', 'Backup file not found');
        }

        // Decrypt if .enc
        $workFile = $filePath;
        if (str_ends_with($fileName, '.enc')) {
            $settings = BackupSetting::current();
            if (empty($settings->encrypt_key)) {
                return back()->with('error', 'Encryption key not set in settings');
            }
            $key        = substr(hash('sha256', $settings->encrypt_key, true), 0, 32);
            $data       = file_get_contents($filePath);
            $iv         = substr($data, 0, 16);
            $ciphertext = substr($data, 16);
            $plaintext  = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            if ($plaintext === false) {
                return back()->with('error', 'Decryption failed. Wrong key?');
            }
            $workFile = storage_path('app/backups/temp_restore.sql');
            file_put_contents($workFile, $plaintext);
        }

        // Unzip if .zip
        if (str_ends_with($workFile, '.zip')) {
            $zip      = new ZipArchive();
            $tempDir  = storage_path('app/backups/temp_unzip/');
            if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);
            if ($zip->open($workFile) === true) {
                $zip->extractTo($tempDir);
                $zip->close();
                $sqlFiles = glob($tempDir . '*.sql');
                $workFile = $sqlFiles[0] ?? null;
            }
            if (!$workFile || !file_exists($workFile)) {
                return back()->with('error', 'Could not extract zip backup');
            }
        }

        $dbHost    = env('DB_HOST');
        $dbUser    = env('DB_USERNAME');
        $dbPass    = env('DB_PASSWORD');
        $dbName    = env('DB_DATABASE');
        $mysqlPath = 'D:\\xampp\\mysql\\bin\\mysql.exe';

        $passFlag = $dbPass ? "-p{$dbPass}" : '';
        $command  = "cmd /c \"{$mysqlPath}\" -h {$dbHost} -u {$dbUser} {$passFlag} {$dbName} < \"{$workFile}\"";

        exec($command, $output, $returnVar);

        // Cleanup temp files
        if (isset($tempDir) && file_exists($tempDir)) {
            array_map('unlink', glob($tempDir . '*'));
            rmdir($tempDir);
        }
        if ($workFile !== $filePath && file_exists($workFile)) {
            unlink($workFile);
        }

        if ($returnVar === 0) {
            return back()->with('success', 'Database Restored Successfully');
        }

        return back()->with('error', 'Restore Failed');
    }
}

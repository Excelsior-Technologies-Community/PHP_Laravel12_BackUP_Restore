# PHP_Laravel12_BackUP_Restore

## Introduction

PHP_Laravel12_BackUP_Restore is a modern Laravel 12-based web application designed to simplify database backup and restoration processes through an intuitive user interface.

This project provides a practical implementation of database management techniques by integrating system-level tools such as mysqldump and MySQL CLI within a Laravel environment.

It is particularly useful for developers and administrators who need a quick and reliable way to create, download, and restore database backups without manually executing command-line operations.

The application is fully compatible with Windows environments (XAMPP) and demonstrates real-world backend development concepts, making it ideal for learning and project submission purposes.

---

## Project Overview

This project is designed to automate and simplify database backup and restoration processes using Laravel 12.

The application provides a user-friendly interface where users can create backups of their database, view available backup files, download them, and restore the database when needed.

### Workflow:

1. The user interacts with the web interface (Blade UI)
2. Requests are sent through defined routes
3. The controller processes the request
4. System-level commands (mysqldump / mysql) are executed
5. Backup files are stored in the local storage directory
6. The result (success or failure) is displayed back to the user

### Core Functionalities:

- **Backup Creation**  
  Generates `.sql` files using mysqldump and stores them locally

- **Backup Listing**  
  Displays all available backup files from the storage directory

- **Download Backup**  
  Allows users to download any backup file for external use

- **Database Restore**  
  Restores the database using the selected backup file via MySQL CLI

- **File Management**  
  Handles backup storage inside `storage/app/backups`

This project reflects a real-world use case where developers need a reliable and quick solution to manage database backups without manually executing command-line operations.

---

## Features

- Create database backup (.sql file)
- Store backups locally (storage/app/backups)
- Display all backups in UI
- Download backup files
- Restore database from selected backup
- Modern UI using Tailwind CSS
- Windows-compatible command execution

---

## Tech Stack

- Backend: Laravel 12  
- Frontend: Blade + Tailwind CSS  
- Database: MySQL (XAMPP)  
- Tools Used: mysqldump, mysql CLI  

---

## Installation Steps

## Step 1: Create Project

```bash
composer create-project laravel/laravel PHP_Laravel12_BackUP_Restore "12.*"
cd PHP_Laravel12_BackUP_Restore
```

---

## Step 2: Setup Database

Update .env file:

```.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backup_restore_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## Step 3: Run Migration

Run:

```bash
php artisan migrate
```

---

## Step 4: Create Storage Folder

```
mkdir storage\app\backups
```

---

## Step 5: Controllers

Run:

```bash
php artisan make:controller BackupController
```
File: `app/Http/Controllers/BackupController`

```php
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
```

---

## Step 6: Blade Views

### index.blade.php 

File: `resources/views/backup/index.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Laravel Backup & Restore</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center p-6">

    <div class="w-full max-w-4xl backdrop-blur-lg bg-white/10 border border-white/20 rounded-2xl shadow-2xl p-8 text-white">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold">🚀 Database Backup & Restore</h1>
            <p class="text-sm text-gray-200 mt-1">Securely manage your database backups</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-green-500/20 border border-green-400 text-green-200 p-3 mb-4 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/20 border border-red-400 text-red-200 p-3 mb-4 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Create Backup -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">📦 Create Backup</h2>
            <form action="{{ route('backup.create') }}" method="POST">
                @csrf
                <button class="w-full bg-blue-600 hover:bg-blue-700 transition-all duration-300 px-4 py-3 rounded-lg font-medium shadow-lg">
                    ➕ Create New Backup
                </button>
            </form>
        </div>

        <!-- Existing Backups -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">📂 Existing Backups</h2>

            @if(count($backups) > 0)
                <div class="space-y-3 max-h-48 overflow-y-auto pr-2">
                    @foreach($backups as $backup)
                        <div class="flex justify-between items-center bg-white/10 border border-white/20 p-3 rounded-lg">
                            <span class="text-sm">{{ $backup }}</span>

                            <a href="{{ route('backup.download', $backup) }}"
                               class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm transition">
                                ⬇ Download
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-300 text-sm">No backups found.</p>
            @endif
        </div>

        <!-- Restore Backup -->
        <div>
            <h2 class="text-xl font-semibold mb-3">♻ Restore Backup</h2>

            <form action="{{ route('backup.restore') }}" method="POST" class="space-y-4">
                @csrf

                <select name="backup_file"
                    class="w-full bg-white/10 border border-white/20 text-white p-3 rounded-lg focus:outline-none">
                    <option value="" class="text-black">Select backup file</option>
                    @foreach($backups as $backup)
                        <option value="{{ $backup }}" class="text-black">{{ $backup }}</option>
                    @endforeach
                </select>

                <button class="w-full bg-red-500 hover:bg-red-600 transition-all duration-300 px-4 py-3 rounded-lg font-medium shadow-lg">
                    ♻ Restore Selected Backup
                </button>
            </form>
        </div>

    </div>

</body>

</html>
```

---

## Step 7: Routes

File: `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
Route::get('/backup/download/{file}', [BackupController::class, 'downloadBackup'])->name('backup.download');
Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');

Route::get('/', function () {
    return view('welcome');
});
```

---

## Step 8: Run Server

Run:

```bash
php artisan serve
```

Open in browser:

```bash
http://127.0.0.1:8000/backup
```
---

## Step 9: Testing Steps

### 1) Insert Data

```
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Demo', 'Demo@test.com', '123', NOW(), NOW());
```

### 2) Create Backup

Click Create Backup

### 3) Delete Data

DELETE FROM users;

### 4) Restore Backup

Select backup file

Click Restore

### 5) Verify

```
SELECT * FROM users;
```

Data will be restored successfully

---

## Output

<img src="screenshots/Screenshot 2026-03-27 172255.png" width="1000">

<img src="screenshots/Screenshot 2026-03-27 172332.png" width="1000">

<img src="screenshots/Screenshot 2026-03-27 172408.png" width="1000">

<img src="screenshots/Screenshot 2026-03-27 172424.png" width="1000">

---

## Project Structure

```
PHP_Laravel12_BackUP_Restore/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── BackupController.php   # Main logic (backup & restore)
│   │   │
│   │   └── Middleware/
│   │
│   ├── Models/
│   │   └── User.php
│   │
│   └── Providers/
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/   # users, jobs, cache tables
│   └── seeders/
│
├── public/
│   └── index.php
│
├── resources/
│   ├── views/
│   │   ├── backup/
│   │   │   └── index.blade.php   # UI (backup + restore page)
│   │   │
│   │   └── welcome.blade.php
│   │
│   ├── css/
│   └── js/
│
├── routes/
│   └── web.php   # All routes (backup, restore, download)
│
├── storage/
│   ├── app/
│   │   └── backups/   # IMPORTANT (stores .sql files)
│   │
│   ├── framework/
│   └── logs/
│
├── tests/
│
├── vendor/
│
├── .env   # Database configuration
├── artisan
├── composer.json
└── README.md   # (your project documentation)
```

---

Your PHP_Laravel12_BackUP_Restore Project is now ready!



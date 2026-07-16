<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupValidationLog extends Model
{
    protected $fillable = [
        'backup_file',
        'status',
        'tables_found',
        'tables_verified',
        'rows_checked',
        'details',
        'checks',
    ];

    protected $casts = [
        'checks' => 'array',
    ];
}

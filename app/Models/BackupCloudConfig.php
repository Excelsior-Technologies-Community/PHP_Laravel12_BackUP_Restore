<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupCloudConfig extends Model
{
    protected $fillable = [
        'provider',
        'label',
        'enabled',
        'auto_upload',
        'config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_upload' => 'boolean',
        'config' => 'array',
    ];
}

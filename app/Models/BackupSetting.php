<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'included_tables',
        'excluded_tables',
        'include_structure',
        'include_data',
        'compress',
        'encrypt',
        'encrypt_key',
        'compression_level',
    ];

    protected $casts = [
        'included_tables' => 'array',
        'excluded_tables' => 'array',
        'include_structure' => 'boolean',
        'include_data' => 'boolean',
        'compress' => 'boolean',
        'encrypt' => 'boolean',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'include_structure' => true,
            'include_data' => true,
            'compress' => false,
            'encrypt' => false,
            'compression_level' => '6',
        ]);
    }
}

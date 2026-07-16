<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_cloud_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // s3, gdrive, ftp
            $table->string('label');
            $table->boolean('enabled')->default(false);
            $table->boolean('auto_upload')->default(false);
            $table->json('config')->nullable(); // bucket, region, key, secret, path etc
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_cloud_configs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->json('included_tables')->nullable();
            $table->json('excluded_tables')->nullable();
            $table->boolean('include_structure')->default(true);
            $table->boolean('include_data')->default(true);
            $table->boolean('compress')->default(false);
            $table->boolean('encrypt')->default(false);
            $table->string('encrypt_key', 64)->nullable();
            $table->string('compression_level')->default('6');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};

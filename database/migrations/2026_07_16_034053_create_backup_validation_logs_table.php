<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_validation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_file');
            $table->string('status'); // passed, failed, partial
            $table->integer('tables_found')->default(0);
            $table->integer('tables_verified')->default(0);
            $table->integer('rows_checked')->default(0);
            $table->text('details')->nullable();
            $table->json('checks')->nullable(); // individual check results
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_validation_logs');
    }
};

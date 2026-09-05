<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDSPhien')) {
            return;
        }

        if (! Schema::connection($this->connection)->hasColumn('DatDSPhien', 'STT')) {
            return;
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table) {
            $table->dropColumn('STT');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDSPhien')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'STT')) {
            return;
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table) {
            $table->unsignedInteger('STT')->nullable();
        });
    }
};

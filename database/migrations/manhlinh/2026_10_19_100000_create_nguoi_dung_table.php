<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('NguoiDung')) {
            return;
        }

        $schema->create('NguoiDung', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('Ten', 150);
            $table->string('SoDienThoai', 20);
            $table->string('MatKhau', 255);
            $table->string('VaiTro', 50);
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('SoDienThoai', 'UX_NguoiDung_SoDienThoai');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('NguoiDung');
    }
};

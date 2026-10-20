<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NguoiDung: một người nhiều vai trò (NguoiDungVaiTro), bỏ cột VaiTro đơn.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('NguoiDungVaiTro')) {
            $schema->create('NguoiDungVaiTro', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('NguoiDungId');
                $table->string('MaVaiTro', 50);

                $table->unique(['NguoiDungId', 'MaVaiTro'], 'UX_NguoiDungVaiTro_NguoiDung_VaiTro');
                $table->foreign('NguoiDungId', 'FK_NguoiDungVaiTro_NguoiDung')
                    ->references('Id')
                    ->on('NguoiDung')
                    ->cascadeOnDelete();
            });
        }

        if ($schema->hasColumn('NguoiDung', 'VaiTro')) {
            DB::connection($this->connection)->statement(
                "INSERT INTO [NguoiDungVaiTro] ([NguoiDungId], [MaVaiTro])
                 SELECT [Id], [VaiTro] FROM [NguoiDung]
                 WHERE [VaiTro] IS NOT NULL AND [VaiTro] <> ''"
            );
            $schema->table('NguoiDung', function (Blueprint $table) {
                $table->dropColumn('VaiTro');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('NguoiDung', 'VaiTro')) {
            $schema->table('NguoiDung', function (Blueprint $table) {
                $table->string('VaiTro', 50)->nullable();
            });
        }

        if ($schema->hasTable('NguoiDungVaiTro')) {
            DB::connection($this->connection)->statement(
                'UPDATE n SET [VaiTro] = (SELECT TOP 1 v.[MaVaiTro] FROM [NguoiDungVaiTro] v WHERE v.[NguoiDungId] = n.[Id] ORDER BY v.[Id])
                 FROM [NguoiDung] n'
            );
            $schema->drop('NguoiDungVaiTro');
        }
    }
};

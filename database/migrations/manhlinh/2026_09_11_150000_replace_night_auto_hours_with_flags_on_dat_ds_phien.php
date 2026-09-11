<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDSPhien')) {
            return;
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('DatDSPhien', 'LaBanDem')) {
                $table->boolean('LaBanDem')->default(false)->after('QuangDuongThucHanhKm');
            }
            if (! Schema::connection($this->connection)->hasColumn('DatDSPhien', 'LaTuDong')) {
                $table->boolean('LaTuDong')->default(false)->after('LaBanDem');
            }
        });

        if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'ThoiGianLaiBanDemGio')) {
            DB::connection($this->connection)->statement('
                UPDATE DatDSPhien
                SET LaBanDem = CASE WHEN COALESCE(ThoiGianLaiBanDemGio, 0) > 0 THEN 1 ELSE 0 END,
                    LaTuDong = CASE WHEN COALESCE(ThoiGianLaiXeSoTuDong, 0) > 0 THEN 1 ELSE 0 END
            ');
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'ThoiGianLaiBanDemGio')) {
                $table->dropColumn('ThoiGianLaiBanDemGio');
            }
            if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'ThoiGianLaiXeSoTuDong')) {
                $table->dropColumn('ThoiGianLaiXeSoTuDong');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDSPhien')) {
            return;
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('DatDSPhien', 'ThoiGianLaiBanDemGio')) {
                $table->decimal('ThoiGianLaiBanDemGio', 10, 4)->nullable()->after('QuangDuongThucHanhKm');
            }
            if (! Schema::connection($this->connection)->hasColumn('DatDSPhien', 'ThoiGianLaiXeSoTuDong')) {
                $table->decimal('ThoiGianLaiXeSoTuDong', 10, 4)->nullable()->after('ThoiGianLaiBanDemGio');
            }
        });

        if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'LaBanDem')) {
            DB::connection($this->connection)->statement('
                UPDATE DatDSPhien
                SET ThoiGianLaiBanDemGio = CASE WHEN LaBanDem = 1 THEN COALESCE(ThoiGianThucHanhGio, 0) ELSE 0 END,
                    ThoiGianLaiXeSoTuDong = CASE WHEN LaTuDong = 1 THEN COALESCE(ThoiGianThucHanhGio, 0) ELSE 0 END
            ');
        }

        Schema::connection($this->connection)->table('DatDSPhien', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'LaBanDem')) {
                $table->dropColumn('LaBanDem');
            }
            if (Schema::connection($this->connection)->hasColumn('DatDSPhien', 'LaTuDong')) {
                $table->dropColumn('LaTuDong');
            }
        });
    }
};

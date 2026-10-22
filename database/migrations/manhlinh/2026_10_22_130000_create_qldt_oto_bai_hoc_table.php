<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bài học theo hạng + ca (tên + khung giờ BĐ/KT); sáng và chiều có danh sách riêng. Bài dạy từng ngày trỏ tới bài học (BaiHocId),
 * nên sửa khung giờ của bài học là mọi ngày đang gán bài đó đổi theo.
 * Dữ liệu cũ: mỗi Nội dung khác nhau trong một hạng + ca thành một bài học, giờ lấy theo dòng xuất hiện đầu tiên.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        if (! $schema->hasTable('QldtOtoBaiHoc')) {
            $schema->create('QldtOtoBaiHoc', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('Hang', 10);
                $table->string('Ca', 10);
                $table->string('Ten', 500);
                $table->string('GioBatDau', 50)->nullable();
                $table->string('GioKetThuc', 50)->nullable();
                $table->integer('ThuTu')->default(0);
                $table->dateTime('NgayTao')->nullable();
                $table->dateTime('NgayCapNhat')->nullable();
                $table->unique(['Hang', 'Ca', 'Ten'], 'UX_QldtOtoBaiHoc_Hang_Ca_Ten');
            });
        }

        if (! $schema->hasColumn('QldtOtoBaiDay', 'BaiHocId')) {
            $schema->table('QldtOtoBaiDay', function (Blueprint $table) {
                $table->unsignedInteger('BaiHocId')->nullable();
            });
        }

        if ($schema->hasColumn('QldtOtoBaiDay', 'NoiDung')) {
            $now = Carbon::now();
            $baiHocId = [];
            $thuTu = [];
            $rows = $conn->table('QldtOtoBaiDay')->orderBy('Hang')->orderBy('Ca')->orderBy('ThuTu')->orderBy('Id')->get();
            foreach ($rows as $r) {
                $ten = trim((string) preg_replace('/\s+/u', ' ', (string) $r->NoiDung));
                $tab = $r->Hang.'|'.$r->Ca;
                $key = $tab.'|'.mb_strtolower($ten);
                if (! isset($baiHocId[$key])) {
                    $thuTu[$tab] = ($thuTu[$tab] ?? 0) + 1;
                    $baiHocId[$key] = $conn->table('QldtOtoBaiHoc')->insertGetId([
                        'Hang' => $r->Hang,
                        'Ca' => $r->Ca,
                        'Ten' => $ten,
                        'GioBatDau' => $r->GioBatDau,
                        'GioKetThuc' => $r->GioKetThuc,
                        'ThuTu' => $thuTu[$tab],
                        'NgayTao' => $now,
                        'NgayCapNhat' => $now,
                    ]);
                }
                $conn->table('QldtOtoBaiDay')->where('Id', $r->Id)->update(['BaiHocId' => $baiHocId[$key]]);
            }

            $schema->table('QldtOtoBaiDay', function (Blueprint $table) {
                $table->dropColumn(['NoiDung', 'GioBatDau', 'GioKetThuc']);
            });
        }

        $conn->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [BaiHocId] INT NOT NULL');
        $conn->statement(
            "IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_QldtOtoBaiDay_BaiHoc')
                ALTER TABLE [QldtOtoBaiDay] ADD CONSTRAINT [FK_QldtOtoBaiDay_BaiHoc] FOREIGN KEY ([BaiHocId]) REFERENCES [QldtOtoBaiHoc]([Id])"
        );
        $conn->statement(
            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_QldtOtoBaiDay_BaiHocId' AND object_id = OBJECT_ID(N'dbo.QldtOtoBaiDay'))
                CREATE INDEX [IX_QldtOtoBaiDay_BaiHocId] ON [QldtOtoBaiDay]([BaiHocId])"
        );
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        if (! $schema->hasColumn('QldtOtoBaiDay', 'NoiDung')) {
            $schema->table('QldtOtoBaiDay', function (Blueprint $table) {
                $table->string('NoiDung', 500)->nullable();
                $table->string('GioBatDau', 50)->nullable();
                $table->string('GioKetThuc', 50)->nullable();
            });
        }
        $conn->statement(
            'UPDATE d SET d.[NoiDung] = h.[Ten], d.[GioBatDau] = h.[GioBatDau], d.[GioKetThuc] = h.[GioKetThuc]
             FROM [QldtOtoBaiDay] d JOIN [QldtOtoBaiHoc] h ON h.[Id] = d.[BaiHocId]'
        );
        $conn->statement("UPDATE [QldtOtoBaiDay] SET [NoiDung] = N'' WHERE [NoiDung] IS NULL");
        $conn->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [NoiDung] NVARCHAR(500) NOT NULL');

        $conn->statement(
            "IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_QldtOtoBaiDay_BaiHoc')
                ALTER TABLE [QldtOtoBaiDay] DROP CONSTRAINT [FK_QldtOtoBaiDay_BaiHoc]"
        );
        $conn->statement(
            "IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_QldtOtoBaiDay_BaiHocId' AND object_id = OBJECT_ID(N'dbo.QldtOtoBaiDay'))
                DROP INDEX [IX_QldtOtoBaiDay_BaiHocId] ON [QldtOtoBaiDay]"
        );
        if ($schema->hasColumn('QldtOtoBaiDay', 'BaiHocId')) {
            $schema->table('QldtOtoBaiDay', function (Blueprint $table) {
                $table->dropColumn('BaiHocId');
            });
        }
        $schema->dropIfExists('QldtOtoBaiHoc');
    }
};

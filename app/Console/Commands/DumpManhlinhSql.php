<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DumpManhlinhSql extends Command
{
    protected $signature = 'manhlinh:dump-sql
                            {--output= : Đường dẫn file .sql (mặc định: database/dumps/MANHLINH.sql)}
                            {--fresh-db : DROP + CREATE DATABASE (xóa sạch DB trước khi import)}';

    protected $description = 'Xuất DB MANHLINH ra file SQL (CREATE TABLE + INSERT), tương thích SQL Server 2012+';

    /** @var list<string> */
    private const TABLES = [
        'GiaoVien',
        'XeTapLai',
        'KhoaDaoTao',
        'PhanCongDaoTao',
        'TienDoDaoTao',
    ];

    /** @var list<string> */
    private const IDENTITY_TABLES = [
        'GiaoVien',
        'XeTapLai',
        'KhoaDaoTao',
        'PhanCongDaoTao',
        'TienDoDaoTao',
    ];

    public function handle(): int
    {
        $output = $this->option('output') ?? database_path('dumps/MANHLINH.sql');

        $outputDir = dirname($output);
        if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            $this->error("Không tạo được thư mục: {$outputDir}");

            return self::FAILURE;
        }

        $schemaPath = database_path('sql/manhlinh-schema.sql');
        if (! is_file($schemaPath)) {
            $this->error("Thiếu file schema: {$schemaPath}");

            return self::FAILURE;
        }

        $lines = [];
        $lines[] = '-- MANHLINH SQL dump — schema + INSERT (DB MANHLINH phải đã tồn tại)';
        $lines[] = '-- Tương thích SQL Server 2012+ (chạy bằng SSMS hoặc sqlcmd)';
        $lines[] = '-- Generated: '.now()->format('Y-m-d H:i:s');
        $lines[] = '';

        if ($this->option('fresh-db')) {
            $lines[] = 'IF DB_ID(N\'MANHLINH\') IS NOT NULL';
            $lines[] = 'BEGIN';
            $lines[] = '    ALTER DATABASE [MANHLINH] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;';
            $lines[] = '    DROP DATABASE [MANHLINH];';
            $lines[] = 'END';
            $lines[] = 'GO';
            $lines[] = 'CREATE DATABASE [MANHLINH];';
            $lines[] = 'GO';
        } else {
            $lines[] = 'IF DB_ID(N\'MANHLINH\') IS NULL';
            $lines[] = '    CREATE DATABASE [MANHLINH];';
            $lines[] = 'GO';
        }

        $lines[] = 'USE [MANHLINH];';
        $lines[] = 'GO';
        $lines[] = '';
        $lines[] = trim((string) file_get_contents($schemaPath));
        $lines[] = '';

        $connection = DB::connection('sqlsrv_manhlinh');

        foreach (self::TABLES as $table) {
            $rows = $connection->table($table)->orderBy('Id')->get();
            $lines[] = '-- Data: '.$table.' ('.number_format($rows->count()).' rows)';

            if ($rows->isEmpty()) {
                $lines[] = 'GO';
                $lines[] = '';

                continue;
            }

            if (in_array($table, self::IDENTITY_TABLES, true)) {
                $lines[] = 'SET IDENTITY_INSERT ['.$table.'] ON;';
            }

            foreach ($rows as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(
                    fn (string $col): string => $this->sqlLiteral($row->{$col} ?? null),
                    $columns
                );
                $lines[] = 'INSERT INTO ['.$table.'] (['.implode('], [', $columns).']) VALUES ('.implode(', ', $values).');';
            }

            if (in_array($table, self::IDENTITY_TABLES, true)) {
                $lines[] = 'SET IDENTITY_INSERT ['.$table.'] OFF;';
            }

            $lines[] = 'GO';
            $lines[] = '';
        }

        file_put_contents($output, implode(PHP_EOL, $lines));

        $size = filesize($output);
        $this->info('Đã xuất: '.$output.' ('.number_format($size / 1024, 1).' KB)');

        return self::SUCCESS;
    }

    private function sqlLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return "N'".$value->format('Y-m-d H:i:s')."'";
        }

        $string = (string) $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $string) === 1) {
            return "N'{$string}'";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $string) === 1) {
            return "N'{$string}'";
        }

        $escaped = str_replace("'", "''", $string);

        return "N'{$escaped}'";
    }
}

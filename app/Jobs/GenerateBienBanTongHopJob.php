<?php

namespace App\Jobs;

use App\Support\SatHach\BienBanDocxGenerator;
use App\Support\SatHach\BienBanImportStore;
use App\Support\SatHach\XmlSatHachParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateBienBanTongHopJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(public string $jobId)
    {
    }

    public function handle(XmlSatHachParser $parser, BienBanDocxGenerator $generator): void
    {
        $store = new BienBanImportStore();
        $meta = $store->get($this->jobId);
        if ($meta === null) {
            return;
        }

        try {
            $store->update($this->jobId, [
                'status' => 'processing',
                'error_message' => null,
            ]);

            @ini_set('memory_limit', '512M');
            @set_time_limit(0);

            $xmlPath = $store->xmlPath($this->jobId);
            $data = $parser->parse($xmlPath);
            $outputPath = $store->outputPath($this->jobId);
            $generator->generate($data['thi_sinh'], $outputPath);

            $store->update($this->jobId, [
                'status' => 'done',
                'thi_sinh_count' => count($data['thi_sinh']),
                'output_name' => basename($outputPath),
                'error_message' => null,
            ]);
            $store->deleteXml($this->jobId);
        } catch (Throwable $e) {
            $this->failed($e);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        (new BienBanImportStore())->update($this->jobId, [
            'status' => 'failed',
            'error_message' => $e?->getMessage() ?? 'Lỗi không xác định',
        ]);
    }
}

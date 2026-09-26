<?php

namespace App\Support;

use App\Support\SatHach\Utf8;
use Symfony\Component\Process\Process;

class ShellProcess
{
    /**
     * @return array{code: int, output: string}
     */
    public static function run(array $command, ?string $cwd = null, int $timeoutSeconds = 600): array
    {
        $process = new Process($command, $cwd, null, null, $timeoutSeconds);
        $process->run();
        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return [
            'code' => $process->getExitCode() ?? 1,
            'output' => Utf8::sanitize($output),
        ];
    }
}

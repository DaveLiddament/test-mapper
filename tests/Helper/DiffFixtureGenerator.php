<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Tests\Helper;

use Symfony\Component\Process\Process;

final class DiffFixtureGenerator
{
    public static function generate(string $beforePath, string $afterPath, string $filePath): string
    {
        $process = new Process([
            'git', 'diff', '--no-index', '--unified=0',
            '--no-color', '--no-ext-diff',
            $beforePath, $afterPath,
        ]);
        $process->run(); // exit code 1 = files differ (normal)

        $diff = $process->getOutput();

        // Replace actual filesystem paths with desired file path.
        // git strips the leading "/" from absolute paths in its output.
        if ('/dev/null' !== $beforePath) {
            $diff = str_replace(ltrim($beforePath, '/'), $filePath, $diff);
        }
        if ('/dev/null' !== $afterPath) {
            $diff = str_replace(ltrim($afterPath, '/'), $filePath, $diff);
        }

        return $diff;
    }
}

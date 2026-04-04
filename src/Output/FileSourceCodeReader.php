<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

final class FileSourceCodeReader implements SourceCodeReader
{
    public function readLines(string $filePath, int $startLine, int $endLine): string
    {
        $lines = @file($filePath);

        if (false === $lines) {
            return '';
        }

        $extracted = \array_slice($lines, $startLine - 1, $endLine - $startLine + 1);

        return rtrim(implode('', $extracted));
    }

    public function readFile(string $filePath): string
    {
        $contents = @file_get_contents($filePath);

        if (false === $contents) {
            return '';
        }

        return rtrim($contents);
    }
}

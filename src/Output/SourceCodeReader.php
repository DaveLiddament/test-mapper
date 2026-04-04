<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Output;

interface SourceCodeReader
{
    /**
     * Read lines startLine..endLine (inclusive, 1-based) from a file.
     */
    public function readLines(string $filePath, int $startLine, int $endLine): string;

    /**
     * Read the entire contents of a file.
     */
    public function readFile(string $filePath): string;
}

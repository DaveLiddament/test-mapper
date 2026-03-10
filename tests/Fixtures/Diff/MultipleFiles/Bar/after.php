<?php

declare(strict_types=1);

namespace App;

final class Bar
{
    private int $count;

    public function __construct()
    {
        $this->count = 1;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}

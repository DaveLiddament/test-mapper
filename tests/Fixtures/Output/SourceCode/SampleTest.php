<?php

declare(strict_types=1);

namespace App\Tests;

class SampleTest
{
    public function itDoesSomething(): void
    {
        $a = 1;
        $b = 2;
    }

    public static function dataProvider(): array
    {
        return [
            [1, 2],
            [3, 4],
        ];
    }
}

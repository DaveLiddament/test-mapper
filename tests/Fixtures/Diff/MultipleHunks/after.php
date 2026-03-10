<?php

declare(strict_types=1);

namespace App;

final class Foo
{
    use SomeTrait;

    private string $name;
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

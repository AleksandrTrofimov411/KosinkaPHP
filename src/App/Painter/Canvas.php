<?php

declare(strict_types=1);

namespace App\Painter;

class Canvas
{
    private array $canvas = [];

    public function getCanvas(): array
    {
        return $this->canvas;
    }

    public function setCanvas(array $canvas): void
    {
        $this->canvas = $canvas;
    }

    public function toString(): string
    {
        $picture = '';
        foreach ($this->canvas as $line) {
            $picture .= implode('', $line);
            $picture .= "\n";
        }

        return $picture;
    }
}
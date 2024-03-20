<?php

declare(strict_types=1);

namespace App\Painter;

class RightBoard extends BoardSystem
{
    protected array $board = [];

    public function __construct(int $width, int $height)
    {
        $this->createBoard($width, $height);
    }
}
<?php

declare(strict_types = 1) ;

namespace App\Painter;

class BoardSystem
{

    protected array $board = [];

    public function createBoard(int $width, int $height): void
    {
        for ($i = 0; $i < $height; $i++) {
            for ($j = 0; $j < $width; $j++) {
                if ($i === 0 || $i === $height - 1 || $j === 0 || $j === $width - 1) {
                    $this->board[$i][$j] = '*' ;
                } else {
                    $this->board[$i][$j] = ' ' ;
                }
            }
        }
    }

    public function toString(): string
    {
        $picture = '';
        foreach ($this->board as $line) {
            $picture .= implode('', $line);
            $picture .= "\n";
        }
        return $picture;
    }
}
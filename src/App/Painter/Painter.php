<?php

declare(strict_types=1);

namespace App\Painter;

class Painter
{
    private MainBoard $mainBoard;

    private RightBoard $rightBoard;

    private Canvas $canvas;

    public function __construct(int $width, int $height)
    {
        $this->canvas = new Canvas();
        $this->rightBoard = new RightBoard(54, $height);
        $this->mainBoard = new MainBoard($width, $height);
        $this->addBoardsOnCanvas();
    }

    public function addBoardsOnCanvas(): void
    {
        $canvas = [];
        $canvas = $this->addPicture($canvas, $this->mainBoard->toString(), 0, 0);
        $canvas = $this->addPicture($canvas, $this->rightBoard->toString(), 107, 0);
        $this->canvas->setCanvas($canvas);
    }

    public function addPicture(array $mainScreen, string $picture, int $x, int $y): array
    {
        $picture = str_split($picture);
        $initX = $x;
        for ($i = 0; $i < count($picture); $i++) {
            if ($picture[$i] === "\n") {
                $y++;
                $x = $initX;
                continue;
            }
            $mainScreen[$y][$x] = $picture[$i];
            $x++;
        }

        return $mainScreen;
    }

    public function getCanvas(): Canvas
    {
        return $this->canvas;
    }
}
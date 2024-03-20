<?php

declare(strict_types=1);
namespace App\Pictures;

class CardImageSystem
{
    protected string $image;

    public function getPicture(): string
    {
        return $this->image;
    }
}
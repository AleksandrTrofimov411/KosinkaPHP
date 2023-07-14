<?php

namespace App\Pictures;

class CardImageSystem
{
    protected string $image ;

    public function getPicture(): string
    {
        return $this->image ;
    }
}
<?php

declare(strict_types=1);

namespace App;

class Keyboard
{
    public function input(): string
    {
        return readline();
    }
}
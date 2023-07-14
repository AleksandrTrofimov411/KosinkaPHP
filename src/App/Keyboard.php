<?php

namespace App;

class Keyboard
{

    public function input(): string
    {
        return readline();
    }

}
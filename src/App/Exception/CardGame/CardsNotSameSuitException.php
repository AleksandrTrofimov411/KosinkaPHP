<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class CardsNotSameSuitException extends \Exception
{
    protected $message = "In this section you need to place\n cards of the same suit on top of each other";
}
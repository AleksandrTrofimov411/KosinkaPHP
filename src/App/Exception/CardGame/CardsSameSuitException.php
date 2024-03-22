<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class CardsSameSuitException extends \Exception
{
    protected $message = "In this section you cannot place cards \nof the same suit on top of each other.";
}
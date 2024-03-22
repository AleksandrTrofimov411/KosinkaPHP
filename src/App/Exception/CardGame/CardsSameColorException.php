<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class CardsSameColorException extends \Exception
{
    protected $message = "In this section you cannot place cards \nof the same color on top of each other.";
}
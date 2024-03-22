<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class NotValidMoveException extends \Exception
{
    protected $message = "You cannot make this move!";
}
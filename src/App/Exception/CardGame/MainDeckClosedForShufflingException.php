<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class MainDeckClosedForShufflingException extends \Exception
{
    protected $message = 'The main deck of cards cannot be washed yet.';
}
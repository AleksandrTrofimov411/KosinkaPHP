<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class PlayingCardMissingException extends \Exception
{
    protected $message = 'Playing card missing';
}
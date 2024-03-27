<?php

namespace App\Exception\CardGame;

class CardDeckNotExistException extends \Exception
{
    protected $message = 'This card deck does not exist';
}
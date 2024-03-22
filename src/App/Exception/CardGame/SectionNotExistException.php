<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class SectionNotExistException extends \Exception
{
    protected $message = 'This section does not exist';
}
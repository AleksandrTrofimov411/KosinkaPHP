<?php

declare(strict_types=1);

namespace App\Exception\CardGame;

class AttemptOpenMissingCardException extends \Exception
{
    protected $message = "First flip the main deck\nwith the R button!";
}
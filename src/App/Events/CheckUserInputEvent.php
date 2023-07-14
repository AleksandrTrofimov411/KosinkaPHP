<?php

namespace App\Events;

class CheckUserInputEvent extends Event
{
    private string $input;
    public function __construct(string $acceptInput)
    {
        $this->input = $acceptInput;
    }

    public function getInput(): string
    {
        return $this->input;
    }

}
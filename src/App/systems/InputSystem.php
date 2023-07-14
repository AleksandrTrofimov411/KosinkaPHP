<?php

declare(strict_types = 1) ;

namespace App\systems;

use App\Events\CheckUserInputEvent;
use App\Events\InputEvent;
use App\Keyboard;

class InputSystem extends AbstractSystem
{

    private Keyboard $keyboard;

    public function __construct(Keyboard $keyboard)
    {
        $this->keyboard = $keyboard;
    }

    public function getSubscriptions(): array
    {
        return [
            InputEvent::class => fn() => $this->acceptPlayerInput()
        ] ;
    }

    public function acceptPlayerInput(): void
    {
        $acceptInput = $this->keyboard->input() ;
        $this->eventPusher->push(new CheckUserInputEvent($acceptInput));

    }

}
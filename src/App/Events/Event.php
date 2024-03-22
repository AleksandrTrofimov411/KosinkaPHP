<?php

declare(strict_types=1);

namespace App\Events;

class Event
{

    protected array $gameData = [];

    protected string $message = "";

    public function getGameData(): array
    {
        return $this->gameData;
    }

    public function setGameData(array $gameData): void
    {
        $this->gameData = $gameData;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
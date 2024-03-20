<?php

declare(strict_types=1);

namespace App\Factory\DeckFactory;

interface CardDecksFactoryInterface
{
    public function build(): array;
}
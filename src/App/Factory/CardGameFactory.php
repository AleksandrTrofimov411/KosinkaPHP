<?php

declare(strict_types=1);

namespace App\Factory;

use App\Factory\DeckFactory\CardDecksFactory;
use App\systems\CardGame;

class CardGameFactory implements FactoryInterface
{
    public function build(): object
    {
        return new CardGame(new CardDecksFactory());
    }
}
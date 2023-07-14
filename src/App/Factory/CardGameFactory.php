<?php

namespace App\Factory;

use App\systems\CardGame;

class CardGameFactory implements FactoryInterface
{
    public function build(): object
    {
        return new CardGame(new DeckFactory());
    }
}
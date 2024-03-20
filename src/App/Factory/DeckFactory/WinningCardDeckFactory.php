<?php

declare(strict_types=1);

namespace App\Factory\DeckFactory;

use App\GameObject\CardDeck;

class WinningCardDeckFactory
{
    public function build(): CardDeck
    {
        $winningCardDeck = new CardDeck('winningCardDeck');
        for ($i = 1; $i <= 4; $i++) {
            $winningCardDeck->addCards([NULL]);
        }

        return $winningCardDeck;
    }
}
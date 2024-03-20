<?php

declare(strict_types=1);

namespace App\Factory\DeckFactory;

use App\GameObject\Card;
use App\GameObject\CardDeck;

class MainCardDeckFactory
{
    const
        MAX_NUMBER_OF_CARDS = 14,
        NUMBER_OF_CARD_SUITS = 4;

    const
        WORMS = 1,
        BOOBY = 2,
        BAPTIZE = 3,
        PEAKS = 4;

    private array $typeCard = [
        self::WORMS => 'Worms',
        self::BOOBY => 'Diamonds',
        self::BAPTIZE => 'Clubs',
        self::PEAKS => 'Spades'
    ];

    private array $colorCard = [
        self::WORMS => 'red',
        self::BOOBY => 'red',
        self::BAPTIZE => 'black',
        self::PEAKS => 'black'
    ];

    public function build(): CardDeck
    {
        $mainCardDeck = new CardDeck('mainCardDeck');
        $this->createMainCardDeck($mainCardDeck);

        return $mainCardDeck;
    }

    private function createMainCardDeck(CardDeck $cardDeck): void
    {
        for ($i = 1; $i <= self::NUMBER_OF_CARD_SUITS; $i++) {
            $this->createDeckOfCardsSameSuit($this->typeCard[$i], $cardDeck, $this->colorCard[$i]);
        }
        $cardDeck->shuffleDeck();
    }

    private function createDeckOfCardsSameSuit(string $typeSuit, CardDeck $cardDeck, string $colorCard): void
    {
        for ($i = 2; $i <= self::MAX_NUMBER_OF_CARDS; $i++) {
            $cardDeck->addCards(new Card($typeSuit, $i, $colorCard));
        }
    }
}
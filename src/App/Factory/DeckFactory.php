<?php

namespace App\Factory;

use App\GameObject\Card;
use App\GameObject\CardDeck;

class DeckFactory
{

    const
        NUMBER_OF_CARD_COLUMNS = 7,
        MAX_NUMBER_OF_CARDS = 14,
        TOTAL_NUMBER_OF_CARDS = 52,
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
    ] ;

    public function buildMainCardDeck(): object
    {
        $cardDeck = new CardDeck('mainCardDeck');
        $this->createMainCardDeck($cardDeck);
        return $cardDeck;
    }

    public function buildColumnCardDeck(CardDeck $mainCardDeck): object
    {
        $columnsCardDeck = new CardDeck('columnsCardDeck');
        $this->createColumnsOfCards($columnsCardDeck, $mainCardDeck);
        return $columnsCardDeck;
    }

    public function buildWinningDeckOfCards(): object
    {
        $winningCardDeck = new CardDeck('winningCardDeck') ;
        for ($i = 1; $i <= 4; $i++) {
            $winningCardDeck->addCards([NULL]);
        }
        return $winningCardDeck ;
    }

    public function createColumnsOfCards(CardDeck $columnsCardDeck, CardDeck $mainCardDeck): void
    {
        for ($i = 0; $i < self::NUMBER_OF_CARD_COLUMNS; $i++) {
            $columnCard = [];
            for ($j = 0; $j <= $i; $j++) {
                $card = $mainCardDeck->giveCard();
                if ($j === $i) {
                    $card->setCardPosition('open');
                    $columnCard[] = $card;
                    continue;
                }
                $columnCard[] = $card;
            }
            $columnsCardDeck->addCards($columnCard);
        }
    }


    public function createMainCardDeck(CardDeck $cardDeck): void
    {
        for ($i = 1; $i <= self::NUMBER_OF_CARD_SUITS; $i++) {
            $this->createDeckOfCardsSameSuit($this->typeCard[$i], $cardDeck, $this->colorCard[$i]);
        }
        $cardDeck->shuffleDeck() ;
    }

    public function createDeckOfCardsSameSuit(string $typeSuit, CardDeck $cardDeck, string $colorCard): void
    {
        for ($i = 2; $i <= self::MAX_NUMBER_OF_CARDS; $i++) {
            $cardDeck->addCards(new Card($typeSuit, $i, $colorCard)) ;
        }
    }

}
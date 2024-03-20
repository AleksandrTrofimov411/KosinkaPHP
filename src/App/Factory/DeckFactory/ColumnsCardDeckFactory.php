<?php

declare(strict_types=1);

namespace App\Factory\DeckFactory;

use App\GameObject\CardDeck;

class ColumnsCardDeckFactory
{
    const NUMBER_OF_CARD_COLUMNS = 7;

    public function build(CardDeck $mainCardDeck): CardDeck
    {
        if (!$mainCardDeck instanceof CardDeck) {
            throw new \Exception("В метод build необходимо передать массив ['mainCardDeck' => mainCardDeckObject]");
        }
        $columnsCardDeck = new CardDeck('columnsCardDeck');
        $this->fillColumnsWithCardsFromMainDeck($columnsCardDeck, $mainCardDeck);

        return $columnsCardDeck;
    }

    private function fillColumnsWithCardsFromMainDeck(CardDeck $columnsCardDeck, CardDeck $mainCardDeck): void
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
}
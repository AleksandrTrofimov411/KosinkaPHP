<?php

declare(strict_types=1);

namespace App\Factory\DeckFactory;

class CardDecksFactory implements CardDecksFactoryInterface
{
    private $mainCardDeckFactory;
    private $columnsCardDeckFactory;
    private $winningCardDeckFactory;

    public function __construct()
    {
        $this->mainCardDeckFactory = new MainCardDeckFactory();
        $this->columnsCardDeckFactory = new ColumnsCardDeckFactory();
        $this->winningCardDeckFactory = new WinningCardDeckFactory();
    }

    /**
     * @throws \Exception
     */
    public function build(): array
    {
        $mainCardDeck = $this->mainCardDeckFactory->build();

        return [
            'mainCardDeck' => $mainCardDeck,
            'columnsCardDeck' => $this->columnsCardDeckFactory->build($mainCardDeck),
            'winningCardDeck' => $this->winningCardDeckFactory->build()
        ];
    }
}
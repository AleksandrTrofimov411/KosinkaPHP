<?php
namespace App\GameObject;

class CardDeck
{

    private string $typeDeck;

    private array $deck = [];

    public function __construct(string $typeDeck)
    {
        $this->typeDeck = $typeDeck;
    }

    public function getTypeDeck(): string
    {
        return $this->typeDeck;
    }

    public function addCards(Card|array $card): void
    {
        $this->deck[] = $card;
    }

    public function giveCard(): Card
    {
        return array_pop($this->deck);
    }

    public function shuffleDeck(): void
    {
        shuffle($this->deck);
    }

    public function getCards(): array
    {
        return $this->deck ;
    }
}
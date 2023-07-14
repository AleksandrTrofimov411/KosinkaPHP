<?php

namespace App\GameObject ;

class Card
{

    private string $typeCard ;

    private int $numCard ;

    private string $color ;

    private string $cardPosition ;

    public function __construct(string $typeCard, int $numCard, string $color, string $cardPosition = 'closed')
    {
        $this->color = $color ;
        $this->numCard = $numCard ;
        $this->typeCard = $typeCard ;
        $this->cardPosition = $cardPosition ;
    }

    public function getColor(): string
    {
        return $this->color ;
    }

    public function setCardPosition(string $cardPosition): void
    {
        $this->cardPosition = $cardPosition ;
    }

    public function getCardPosition(): string
    {
        return $this->cardPosition ;
    }

    public function getTypeCard(): string
    {
        return $this->typeCard ;
    }

    public function getNumCard(): int
    {
        return $this->numCard;
    }
}
<?php

namespace App\Factory;

use App\GameObject\Card;

class CardPictureFactory
{
    private string $namespaceCardPictures = '\\App\\Pictures\\' ;
    public function buildPicture(Card $card): object
    {
        $className = $card->getTypeCard() . $card->getNumCard() ;
        $string = $this->namespaceCardPictures . $className ;
        return new $string ;
    }
}
<?php

declare(strict_types=1);

namespace App\Factory\CardFactory;

use App\GameObject\Card;

class CardPictureFactory implements CardFactoryInterface
{
    private string $namespaceCardPictures = '\\App\\Pictures\\';

    public function build(Card $card): object
    {
        $className = $card->getTypeCard() . $card->getNumCard();
        $string = $this->namespaceCardPictures . $className;

        return new $string;
    }
}
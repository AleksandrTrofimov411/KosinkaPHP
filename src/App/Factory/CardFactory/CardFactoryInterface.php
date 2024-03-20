<?php

declare(strict_types=1);

namespace App\Factory\CardFactory;

use App\GameObject\Card;

interface CardFactoryInterface
{
    public function build(Card $card): object;
}
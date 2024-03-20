<?php

declare(strict_types=1);

namespace App\Factory;

use App\Factory\CardFactory\CardPictureFactory;
use App\systems\GraphicSystem;

class GraphicSystemFactory implements FactoryInterface
{
    public function build(): object
    {
        return new GraphicSystem(new CardPictureFactory());
    }
}
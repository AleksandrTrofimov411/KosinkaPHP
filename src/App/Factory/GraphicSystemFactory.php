<?php

namespace App\Factory;

use App\systems\GraphicSystem;

class GraphicSystemFactory implements FactoryInterface
{
    public function build(): object
    {
        return new GraphicSystem(new CardPictureFactory());
    }
}
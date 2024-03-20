<?php

declare(strict_types=1);

namespace App\Factory;

interface FactoryInterface
{
    public function build(): object;
}
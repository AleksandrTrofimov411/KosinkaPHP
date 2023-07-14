<?php

declare(strict_types=1);

namespace App;

interface EventPusher
{
    public function push(...$events): void;
}

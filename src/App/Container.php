<?php

declare(strict_types=1);

namespace App;

use App\Factory\FactoryInterface;

class  Container
{
    /**
     * @var array<string, string>
     */
    private array $config;

    public function __construct(string $pathToContainerConfig)
    {
        $this->config = require($pathToContainerConfig);
    }

    public function get(string $className): object
    {
        if (!$factory = $this->config[$className] ?? null) {
            throw new \Exception($className . ' factory not found');
        }
        if (!$factory instanceof FactoryInterface) {
            throw new \Exception('factory must implement FactoryInterface');
        }
        return $factory->build();
    }
}

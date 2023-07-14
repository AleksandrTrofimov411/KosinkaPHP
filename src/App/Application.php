<?php

declare(strict_types=1);

namespace App;

use App\Events\StartGame;
use App\Systems\AbstractSystem;
use App\systems\CardGame;
use App\Systems\GraphicSystem;
use App\Systems\InputSystem;

class Application
{
    /**
     * @var AbstractSystem[]
     */
    private array $systems = [
        CardGame::class,
        GraphicSystem::class,
        InputSystem::class,
    ];

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        $container = new Container(__DIR__ . '/container-config.php');
        $eventLoop = new EventLoop();
        foreach ($this->systems as $systemClass) {
            /** @var AbstractSystem $system */
            $system = $container->get($systemClass);
            $eventLoop->register($system);
        }
        $eventLoop->push(new StartGame()) ;
        $eventLoop->run();
    }
}

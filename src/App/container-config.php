<?php

return [
    \App\systems\CardGame::class => new \App\Factory\CardGameFactory(),
    \App\Systems\GraphicSystem::class => new \App\Factory\GraphicSystemFactory(),
    \App\Systems\InputSystem::class => new \App\Factory\InputSystemFactory()
];

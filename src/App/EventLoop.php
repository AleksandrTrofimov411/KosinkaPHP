<?php

declare(strict_types=1);

namespace App;

use App\Events\Event;
use App\Systems\AbstractSystem;

class EventLoop implements EventPusher
{
    /**
     * @var Event[]
     */
    private array $events;

    /**
     * @var array<string, callable>
     */
    private array $subscribers;

    public function push(...$events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    public function register(AbstractSystem $system): void
    {
        $system->setEventPusher($this);
        $subscriptions = $system->getSubscriptions();
        foreach ($subscriptions as $eventName => $handler) {
            $this->subscribers[$eventName] = $handler;
        }
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        while ($this->events) {
            $event = array_shift($this->events);
            if (!$handler = $this->subscribers[$event::class] ?? null) {
                throw new \Exception('unhandled event ');
            }
            $handler($event);
        }
    }
}

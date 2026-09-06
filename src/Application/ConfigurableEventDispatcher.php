<?php

declare(strict_types=1);

namespace Bookshelf\Application;

/**
 * Trente lignes. C'est pour ca qu'on n'utilise pas celui du framework : le sien ne sait
 * pas faire `dispatchAll()`, ses evenements sont souvent mutables, et un abonne peut y
 * arreter la propagation. Un abonne qui empeche les autres de recevoir un fait metier,
 * ca n'a pas de sens : le fait a eu lieu.
 */
final class ConfigurableEventDispatcher implements EventDispatcher
{
    /** @var array<class-string, list<callable>> */
    private array $subscribers = [];

    /** @param class-string $eventType */
    public function addSubscriber(string $eventType, callable $subscriber): void
    {
        $this->subscribers[$eventType][] = $subscriber;
    }

    public function dispatchAll(array $evenements): void
    {
        foreach ($evenements as $evenement) {
            foreach ($this->subscribers[$evenement::class] ?? [] as $subscriber) {
                $subscriber($evenement);
            }
        }
    }
}

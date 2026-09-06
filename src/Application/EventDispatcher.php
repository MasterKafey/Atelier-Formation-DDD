<?php

declare(strict_types=1);

namespace Bookshelf\Application;

/** Port sortant : « pour publier des evenements metier ». */
interface EventDispatcher
{
    /** @param object[] $evenements */
    public function dispatchAll(array $evenements): void;
}

<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Smoke;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Suite de fumee : prouve seulement que l'autoloader et PHPUnit sont bien installes.
 * Sur `main` c'est le seul test du depot, et il doit passer.
 */
final class AutoloadTest extends TestCase
{
    #[Test]
    public function l_autoloader_psr4_est_configure(): void
    {
        self::assertTrue(
            class_exists(\Symfony\Component\Uid\Uuid::class),
            'Lancez `composer install` avant `composer test`.'
        );
    }
}

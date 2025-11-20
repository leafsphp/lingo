<?php

declare(strict_types=1);

namespace Leaf\Lingo\Handler;

use Leaf\Lingo\Handler;

class Header implements Handler
{
    protected static array $config = [];

    /**
     * @inheritDoc
     */
    public static function create(array $config): static
    {
        static::$config = $config;
        return new static();
    }

    /**
     * @inheritDoc
     */
    public static function setCurrentLocale(string $locale): void
    {
        // No action needed for header strategy
    }

    /**
     * @inheritDoc
     */
    public static function getCurrentLocale(): ?string
    {
        return request()->headers('Accept-Language') ?? static::$config['locales.default'] ?? null;
    }
}

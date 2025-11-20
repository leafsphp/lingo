<?php

declare(strict_types=1);

namespace Leaf\Lingo\Handler;

use Leaf\Lingo\Handler;

class Session implements Handler
{
    protected static array $config = [];

    /**
     * @inheritDoc
     */
    public static function create(array $config): static
    {
        static::$config = $config;

        session()->set(
            static::$config['locales.cacheKey'],
            static::$config['locales.default']
        );

        return new static();
    }

    /**
     * @inheritDoc
     */
    public static function setCurrentLocale(string $locale): void
    {
        session()->set(static::$config['locales.cacheKey'], $locale);
    }

    /**
     * @inheritDoc
     */
    public static function getCurrentLocale(): ?string
    {
        return session()->get(static::$config['locales.cacheKey']);
    }
}

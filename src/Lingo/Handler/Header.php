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
        $requestedLocale = request()->headers('Accept-Language');
        if (empty($requestedLocale)) {
            return static::$config['locales.default'] ?? null;
        }
        $requestedLocales = array_reduce(
            explode(
                ',',
                $requestedLocale
            ),
            function ($carry, $item) {
                $locale = explode(';', trim($item));
                if (isset($locale[1]) && strpos($locale[1], 'q=') === 0) {
                    $carry[$locale[0]] = floatval(substr($locale[1], 2));
                } else {
                    $carry[$locale[0]] = 1.0;
                }
                return $carry;
            }
        );
        arsort($requestedLocales, SORT_NUMERIC);
        $requestedLocale = array_key_first($requestedLocales);
        if ($requestedLocale === '*') {
            return static::$config['locales.default'] ?? null;
        }
        return $requestedLocale;
    }
}

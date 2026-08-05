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
        $header = request()->headers('Accept-Language');

        if ($header) {
            $available = static::$config['locales.available'] ?? [];

            foreach (static::parseAcceptLanguage($header) as $locale) {
                if (in_array($locale, $available)) {
                    return $locale;
                }

                // en_GB can still fall back to en or the first en_* file available
                $language = explode('_', $locale)[0];

                if (in_array($language, $available)) {
                    return $language;
                }

                foreach ($available as $availableLocale) {
                    if (str_starts_with($availableLocale, "{$language}_")) {
                        return $availableLocale;
                    }
                }
            }
        }

        return static::$config['locales.default'] ?? null;
    }

    /**
     * Parse an Accept-Language header into locales ordered by preference
     *
     * "en-GB,en;q=0.9,fr;q=0.8" => ['en_GB', 'en', 'fr']
     *
     * @param string $header The raw Accept-Language header
     * @return array<int, string>
     */
    protected static function parseAcceptLanguage(string $header): array
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $segments = explode(';', trim($part));
            $locale = trim($segments[0]);

            if ($locale === '' || $locale === '*') {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($segments, 1) as $param) {
                if (preg_match('/^\s*q\s*=\s*([0-9.]+)/', $param, $matches)) {
                    $quality = (float) $matches[1];
                }
            }

            $locales[str_replace('-', '_', $locale)] = $quality;
        }

        arsort($locales);

        return array_keys($locales);
    }
}

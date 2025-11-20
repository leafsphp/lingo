<?php

declare(strict_types=1);

namespace Leaf\Lingo;

interface Handler
{
    /**
     * Set up the handler
     * @return static
     */
    public static function create(array $config): static;

    /**
     * Set the current locale
     * @param string $locale The locale to set
     */
    public static function setCurrentLocale(string $locale): void;

    /**
     * Get the current locale
     * @return string|null
     */
    public static function getCurrentLocale(): ?string;
}

<?php

declare(strict_types=1);


namespace Leaf\Lingo;

interface Handler
{
    /**
     * Load config into handler
     * @return static
     */
    public function loadConfig(array $config): static;

    /**
     * Set up the handler
     * @return static
     */
    public function create(): static;

    /**
     * Set the current locale
     * @param string $locale The locale to set
     */
    public function setCurrentLocale(string $locale): void;

    /**
     * Get the current locale
     * @return string|null
     */
    public function getCurrentLocale(): ?string;
}

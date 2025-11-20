<?php

declare(strict_types=1);


namespace Leaf\Lingo\Plugins;


use Leaf\Http\Session;

class SessionLocaleStrategyPlugin implements LocaleStrategyPluginInterface
{
    private const SESSION_KEY_CURRENT_LOCALE = '__lingo.locale__';

    /**
     * @inheritDoc
     */
    public function setCurrentLocale(string $locale): void
    {
        $session = new Session();

        $session::set(self::SESSION_KEY_CURRENT_LOCALE, $locale);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentLocale(): ?string
    {
        $session = new Session();

        return $session::get(self::SESSION_KEY_CURRENT_LOCALE);
    }
}

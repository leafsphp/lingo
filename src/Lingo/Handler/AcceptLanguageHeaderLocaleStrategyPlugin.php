<?php

declare(strict_types=1);


namespace Leaf\Lingo\Plugins;


use Leaf\Http\Request;

class AcceptLanguageHeaderLocaleStrategyPlugin implements LocaleStrategyPluginInterface
{
    /**
     * @inheritDoc
     */
    public function setCurrentLocale(string $locale): void
    {
        // Not Needed in this case
    }

    /**
     * @inheritDoc
     */
    public function getCurrentLocale(): ?string
    {
        return Request::headers('Accept-Language');
    }
}

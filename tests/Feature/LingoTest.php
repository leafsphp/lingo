<?php

test('create() discovers yml locale files', function () {
    $lingo = freshLingo();

    expect($lingo->getAvailableLocales())->toBe(['en_US', 'pt_BR', 'pt_PT']);
});

test('create() with an unknown strategy throws LocaleStrategyNotFoundException', function () {
    (new \Leaf\Lingo())->create([
        'locales.path' => localesPath(),
        'locales.strategy' => 'nonsense',
    ]);
})->throws(Leaf\Lingo\Exceptions\LocaleStrategyNotFoundException::class);

test('create() with custom strategy but no handler throws', function () {
    (new \Leaf\Lingo())->create([
        'locales.path' => localesPath(),
        'locales.strategy' => 'custom',
    ]);
})->throws(Leaf\Lingo\Exceptions\LocaleStrategyNotFoundException::class);

test('create() with custom strategy that does not implement Handler throws', function () {
    (new \Leaf\Lingo())->create([
        'locales.path' => localesPath(),
        'locales.strategy' => 'custom',
        'locales.customStrategy' => \stdClass::class,
    ]);
})->throws(Leaf\Lingo\Exceptions\LocaleStrategyNotFoundException::class);

test('config() gets and sets values', function () {
    $lingo = freshLingo();

    expect($lingo->config('locales.default'))->toBe('en_US');
    expect($lingo->config('unknown.key'))->toBeNull();

    $lingo->config('some.key', 'value');
    expect($lingo->config('some.key'))->toBe('value');
});

test('config() can set falsy values', function () {
    $lingo = freshLingo();

    $lingo->config('some.flag', false);
    expect($lingo->config('some.flag'))->toBeFalse();

    $lingo->config('some.zero', 0);
    expect($lingo->config('some.zero'))->toBe(0);
});

test('getDefaultLocale returns the configured default', function () {
    expect(freshLingo()->getDefaultLocale())->toBe('en_US');
    expect(freshLingo(['locales.default' => 'pt_PT'])->getDefaultLocale())->toBe('pt_PT');
});

test('getCurrentLocale reflects the handler locale', function () {
    $lingo = freshLingo();

    expect($lingo->getCurrentLocale())->toBe('en_US');

    $lingo->setCurrentLocale('pt_BR');
    expect($lingo->getCurrentLocale())->toBe('pt_BR');
});

test('overrideCurrentLocale takes precedence over the handler', function () {
    $lingo = freshLingo();

    $lingo->overrideCurrentLocale('pt_PT');
    expect($lingo->getCurrentLocale())->toBe('pt_PT');
    expect($lingo->translate('greeting'))->toBe('Olá');

    $lingo->overrideCurrentLocale(null);
    expect($lingo->getCurrentLocale())->toBe('en_US');
});

test('getCurrentLanguage returns the language part of the locale', function () {
    $lingo = freshLingo();

    expect($lingo->getCurrentLanguage())->toBe('en');

    $lingo->setCurrentLocale('pt_BR');
    expect($lingo->getCurrentLanguage())->toBe('pt');
});

test('getAvailableLocalesWithNames maps codes to display names', function () {
    expect(freshLingo()->getAvailableLocalesWithNames())->toBe([
        'en_US' => 'English (United States)',
        'pt_BR' => 'Português (Brasil)',
        'pt_PT' => 'Português (Portugal)',
    ]);
});

test('getLocaleName resolves known codes and returns null otherwise', function () {
    $lingo = freshLingo();

    expect($lingo->getLocaleName('fr'))->toBe('Français');
    expect($lingo->getLocaleName('xx_XX'))->toBeNull();
});

test('is() checks the current locale', function () {
    $lingo = freshLingo();

    expect($lingo->is('en_US'))->toBeTrue();
    expect($lingo->is('pt_PT'))->toBeFalse();
});

test('variants() picks the value for the current locale', function () {
    $lingo = freshLingo();

    expect($lingo->variants(['en_US' => 'Color', 'pt_PT' => 'Cor']))->toBe('Color');

    $lingo->setCurrentLocale('pt_PT');
    expect($lingo->variants(['en_US' => 'Color', 'pt_PT' => 'Cor']))->toBe('Cor');
    expect($lingo->variants(['en_US' => 'Color']))->toBeNull();
});

test('url() returns the path unchanged for non-router strategies', function () {
    expect(freshLingo()->url('/about'))->toBe('/about');
});

test('lingo() and __() helpers translate through the singleton', function () {
    \Leaf\Config::singleton('lingo', function () {
        return new \Leaf\Lingo();
    });

    TestLocaleHandler::$locale = null;

    lingo()->create([
        'locales.path' => localesPath(),
        'locales.default' => 'en_US',
        'locales.strategy' => 'custom',
        'locales.customStrategy' => TestLocaleHandler::class,
    ]);

    expect(lingo())->toBeInstanceOf(\Leaf\Lingo::class);
    expect(lingo('greeting'))->toBe('Hello');
    expect(lingo('greet_user', ['name' => 'Leaf']))->toBe('Hello, Leaf!');
    expect(__('greeting'))->toBe('Hello');
    expect(__('greet_user', ['name' => 'Leaf']))->toBe('Hello, Leaf!');
});

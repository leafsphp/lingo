<?php

function headerLocalesPath(): string
{
    return __DIR__ . '/../data/locales-header';
}

function routerLingo(array $config = []): \Leaf\Lingo
{
    $lingo = new \Leaf\Lingo();
    $lingo->create(array_merge([
        'locales.path' => headerLocalesPath(), // en, en_GB, fr
        'locales.default' => 'en',
        'locales.strategy' => 'router',
    ], $config));

    return $lingo;
}

function headerLingo(array $config = []): \Leaf\Lingo
{
    $lingo = new \Leaf\Lingo();
    $lingo->create(array_merge([
        'locales.path' => headerLocalesPath(), // en, en_GB, fr
        'locales.default' => 'en',
        'locales.strategy' => 'header',
    ], $config));

    return $lingo;
}

afterEach(function () {
    unset($_SERVER['REQUEST_URI'], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
});

// Router strategy

test('router strategy picks the locale from the first url segment', function () {
    $_SERVER['REQUEST_URI'] = '/fr/page';

    expect(routerLingo()->getCurrentLocale())->toBe('fr');
});

test('router strategy falls back to the default when the first segment is not a locale', function () {
    $_SERVER['REQUEST_URI'] = '/about/team';

    expect(routerLingo()->getCurrentLocale())->toBe('en');
});

test('router strategy translates based on the url locale', function () {
    $_SERVER['REQUEST_URI'] = '/fr/page';

    expect(routerLingo()->translate('greeting'))->toBe('Bonjour');
});

test('switch() swaps the locale prefix in the current url', function () {
    $_SERVER['REQUEST_URI'] = '/fr/page';

    expect(routerLingo()->switch('en'))->toBe('/en/page');
});

test('switch() prepends the locale when the url has no locale prefix', function () {
    $_SERVER['REQUEST_URI'] = '/page';

    expect(routerLingo()->switch('en'))->toBe('/en/page');
});

test('url() prefixes the path with the current locale under router strategy', function () {
    $_SERVER['REQUEST_URI'] = '/fr/page';

    expect(routerLingo()->url('/about'))->toBe('/fr/about');
});

// Header strategy

test('header strategy matches an exact locale', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';

    expect(headerLingo()->getCurrentLocale())->toBe('fr');
});

test('header strategy converts hyphens to underscores', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB';

    expect(headerLingo()->getCurrentLocale())->toBe('en_GB');
});

test('header strategy respects q-value ordering', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr;q=0.8,en-GB;q=0.9';

    expect(headerLingo()->getCurrentLocale())->toBe('en_GB');
});

test('header strategy falls back from region to plain language', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CA';

    expect(headerLingo()->getCurrentLocale())->toBe('fr');
});

test('header strategy falls back from language to first regional variant', function () {
    // only en_US exists in the main fixture set
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';

    $lingo = new \Leaf\Lingo();
    $lingo->create([
        'locales.path' => localesPath(), // en_US, pt_BR, pt_PT
        'locales.default' => 'pt_PT',
        'locales.strategy' => 'header',
    ]);

    expect($lingo->getCurrentLocale())->toBe('en_US');
});

test('header strategy ignores wildcard entries', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '*;q=1,fr;q=0.5';

    expect(headerLingo()->getCurrentLocale())->toBe('fr');
});

test('header strategy uses the default when nothing matches', function () {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,es;q=0.9';

    expect(headerLingo()->getCurrentLocale())->toBe('en');
});

test('header strategy uses the default when no header is sent', function () {
    expect(headerLingo()->getCurrentLocale())->toBe('en');
});

// Session strategy

test('session strategy stores and reads the locale from session', function () {
    $lingo = new \Leaf\Lingo();
    $lingo->create([
        'locales.path' => localesPath(),
        'locales.default' => 'en_US',
        'locales.strategy' => 'session',
    ]);

    expect($lingo->getCurrentLocale())->toBe('en_US');
    expect(session()->get('__lingo.locale__'))->toBe('en_US');
});

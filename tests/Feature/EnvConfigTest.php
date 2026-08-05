<?php

/*
|--------------------------------------------------------------------------
| Environment-driven config
|--------------------------------------------------------------------------
|
| The docs tell people to pick a strategy and a default locale from .env,
| so both keys need to actually reach the config.
|
*/

beforeEach(function () {
    unset($_ENV['APP_LOCALE'], $_ENV['LOCALES_STRATEGY']);

    // the session stand-in is a singleton, and Session::create() only seeds
    // the default when the key is absent — so clear it between tests
    session()->set('__lingo.locale__', null);
});

afterEach(function () {
    unset($_ENV['APP_LOCALE'], $_ENV['LOCALES_STRATEGY']);
});

function lingoWithEnv(array $config = []): \Leaf\Lingo
{
    $lingo = new \Leaf\Lingo();
    $lingo->create(array_merge(['locales.path' => localesPath()], $config));

    return $lingo;
}

it('defaults to the router strategy when no env is set', function () {
    $lingo = lingoWithEnv();

    expect($lingo->config('locales.strategy'))->toBe('router');
    expect($lingo->config('locales.default'))->toBe('en_US');
});

it('reads the strategy from LOCALES_STRATEGY', function () {
    $_ENV['LOCALES_STRATEGY'] = 'session';

    expect(lingoWithEnv()->config('locales.strategy'))->toBe('session');
});

it('reads the default locale from APP_LOCALE', function () {
    $_ENV['APP_LOCALE'] = 'fr';

    expect(lingoWithEnv()->config('locales.default'))->toBe('fr');
});

it('actually builds the handler named by LOCALES_STRATEGY', function () {
    $_ENV['LOCALES_STRATEGY'] = 'session';

    // session mode seeds the default locale on create(), so a locale in
    // the session proves the Session handler was the one constructed
    lingoWithEnv(['locales.default' => 'de']);

    expect(session()->get('__lingo.locale__'))->toBe('de');
});

it('lets config passed to create() win over env', function () {
    $_ENV['APP_LOCALE'] = 'fr';
    $_ENV['LOCALES_STRATEGY'] = 'session';

    $lingo = lingoWithEnv([
        'locales.default' => 'es',
        'locales.strategy' => 'custom',
        'locales.customStrategy' => TestLocaleHandler::class,
    ]);

    expect($lingo->config('locales.default'))->toBe('es');
    expect($lingo->config('locales.strategy'))->toBe('custom');
});

it('ignores an unset APP_LOCALE rather than nulling the default', function () {
    $_ENV['LOCALES_STRATEGY'] = 'router';

    expect(lingoWithEnv()->config('locales.default'))->toBe('en_US');
});

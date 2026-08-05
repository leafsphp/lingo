<?php

test('translates a flat key for the current locale', function () {
    $lingo = freshLingo();

    expect($lingo->translate('greeting'))->toBe('Hello');
});

test('flattens nested maps into dot notation keys', function () {
    $lingo = freshLingo();

    expect($lingo->translate('welcome.title'))->toBe('Welcome to Leaf');
    expect($lingo->translate('welcome.subtitle'))->toBe('Build simple apps');
});

test('replaces {{ name }} style parameters', function () {
    $lingo = freshLingo();

    expect($lingo->translate('greet_user', ['name' => 'Michael']))->toBe('Hello, Michael!');
});

test('replaces $name style parameters', function () {
    $lingo = freshLingo();

    expect($lingo->translate('greet_dollar', ['name' => 'Michael']))->toBe('Hello, Michael!');
});

test('replaces multiple parameters in one string', function () {
    $lingo = freshLingo();

    expect($lingo->translate('items_count', ['count' => '3', 'name' => 'Michael']))
        ->toBe('You have 3 items, Michael');
});

test('returns the key itself when translation is missing', function () {
    $lingo = freshLingo();

    expect($lingo->translate('does.not.exist'))->toBe('does.not.exist');
});

test('translates using a switched locale', function () {
    $lingo = freshLingo();
    $lingo->setCurrentLocale('pt_PT');

    expect($lingo->translate('greeting'))->toBe('Olá');
    expect($lingo->translate('welcome.title'))->toBe('Bem-vindo ao Leaf');
});

test('get() reads a key from a specific locale', function () {
    $lingo = freshLingo();

    expect($lingo->get('pt_BR', 'greeting'))->toBe('Oi');
    expect($lingo->get('en_US', 'greeting'))->toBe('Hello');
});

test('get() throws LocaleNotFoundException for an unknown locale', function () {
    $lingo = freshLingo();
    $lingo->get('xx_XX', 'greeting');
})->throws(Leaf\Lingo\Exceptions\LocaleNotFoundException::class);

test('getLocaleData throws LocaleNotFoundException for an unknown locale', function () {
    freshLingo()->getLocaleData('xx_XX');
})->throws(Leaf\Lingo\Exceptions\LocaleNotFoundException::class);

test('getLocaleData returns the flattened translation map', function () {
    $data = freshLingo()->getLocaleData('en_US');

    expect($data)->toHaveKey('greeting', 'Hello');
    expect($data)->toHaveKey('welcome.title', 'Welcome to Leaf');
    expect($data)->not->toHaveKey('welcome');
});

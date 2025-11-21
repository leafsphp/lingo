<?php

declare(strict_types=1);

namespace Leaf\Lingo\Handler;

use Leaf\Lingo\Handler;

class Router implements Handler
{
    protected static array $config = [];

    /**
     * Set up the handler
     * @return static
     */
    public static function create(array $config): static
    {
        static::$config = $config;

        app()->hook('router.before.route', function ($context) {
            $updatedRoutes = [];

            foreach ($context['routes'] as $method => $routeGroup) {
                foreach ($routeGroup as $route) {
                    if (static::shouldPrefixWithLocale($route)) {
                        $updatedRoutes[$method] = static::createLocalePrefixedRoutes($route);
                    }
                }
            }

            return ['routes' => $updatedRoutes];
        });

        return new static();
    }

    /**
     * @inheritDoc
     */
    public static function setCurrentLocale(string $locale): void
    {
        $currentUrl = request()->getPath();
        $segments = explode('/', ltrim($currentUrl, '/'));

        if (\count($segments) > 0) {
            $segments[0] = $locale;
            $newUrl = '/' . implode('/', $segments);
            response()->redirect($newUrl);

            exit;
        }
    }

    /**
     * @inheritDoc
     */
    public static function getCurrentLocale(): ?string
    {
        $currentUrl = request()->getPath();
        $segments = explode('/', ltrim($currentUrl, '/'));

        return ($segments[0] ?? null) ?: static::$config['locales.default'];
    }

    /**
     * Determine if a route should be prefixed with locale
     *
     * @param array $route
     * @return bool
     */
    protected static function shouldPrefixWithLocale(array $route): bool
    {
        if (isset($route['lingo.no_locale_prefix']) && $route['lingo.no_locale_prefix'] === true) {
            return false;
        }

        return true;
    }

    protected static function createLocalePrefixedRoutes(array $route): array
    {
        $prefixedRoutes = [];
        $defaultLocale = static::$config['locales.default'] ?? null;
        $availableLocales = static::$config['locales.available'] ?? [];

        foreach ($availableLocales as $locale) {
            if (isset($route['lingo.routes'][$locale])) {
                $newRoute = $route;
                $newRoute['pattern'] = '/' . $locale . ($route['lingo.routes'][$locale] === '/' ? '' : $route['lingo.routes'][$locale]);
                $prefixedRoutes[] = $newRoute;
                continue;
            }

            $newRoute = $route;
            $newRoute['pattern'] = '/' . $locale . ($route['pattern'] === '/' ? '' : $route['pattern']);
            $prefixedRoutes[] = $newRoute;
        }

        $prefixedRoutes[] = array_merge($route, [
            'handler' => function () use ($defaultLocale) {
                return response()->redirect('/' . $defaultLocale . request()->getPath());
            }
        ]);

        return $prefixedRoutes;
    }
}

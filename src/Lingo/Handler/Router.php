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
                        $updatedRoutes[$method] = array_merge(
                            $updatedRoutes[$method] ?? [],
                            static::createLocalePrefixedRoutes($route)
                        );
                    } else {
                        $updatedRoutes[$method][] = $route;
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
        $segments = explode('/', ltrim(request()->getPath(), '/'));

        if (static::isKnownLocale($segments[0] ?? '')) {
            // /fr/page → /en/page, never /en/fr/page
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        response()->redirect('/' . implode('/', $segments));

        exit;
    }

    /**
     * @inheritDoc
     */
    public static function getCurrentLocale(): ?string
    {
        $segments = explode('/', ltrim(request()->getPath(), '/'));
        $firstSegment = $segments[0] ?? '';

        return static::isKnownLocale($firstSegment)
            ? $firstSegment
            : static::$config['locales.default'];
    }

    /**
     * Check that a URL segment is actually one of the app's locales
     *
     * @param string $segment The URL segment to check
     * @return bool
     */
    protected static function isKnownLocale(string $segment): bool
    {
        return $segment !== '' && in_array($segment, static::$config['locales.available'] ?? []);
    }

    /**
     * Determine if a route should be prefixed with locale
     *
     * @param array $route
     * @return bool
     */
    protected static function shouldPrefixWithLocale(array $route): bool
    {
        if (isset($route['lingo.routes']) && $route['lingo.routes'] === false) {
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
            if (!empty($route['lingo.routes'][$locale])) {
                $newRoute = $route;
                $newRoute['pattern'] = "/$locale" . ($route['lingo.routes'][$locale] === '/' ? '' : $route['lingo.routes'][$locale]);
                $prefixedRoutes[] = $newRoute;

                foreach ($route['lingo.routes'] as $key => $value) {
                    if ($key === $locale) {
                        continue;
                    }

                    $otherLingoInRoute = $route;
                    $otherLingoInRoute['pattern'] = "/$locale" . ($value === '/' ? '' : $value);
                    $otherLingoInRoute['handler'] = function () use ($locale, $route) {
                        $data = '';
                        $params = request()->urlData();
                        $value = $route['lingo.routes'][$locale];

                        if (\count($params) > 0) {
                            $data = '?' . http_build_query($params);
                        }

                        return response()->redirect("/$locale" . ($value === '/' ? '' : $value) . $data);
                    };

                    $prefixedRoutes[] = $otherLingoInRoute;
                }

                continue;
            }

            $newRoute = $route;
            $newRoute['pattern'] = "/$locale" . ($route['pattern'] === '/' ? '' : $route['pattern']);
            $prefixedRoutes[] = $newRoute;
        }

        $prefixedRoutes[] = array_merge(
            $route,
            [
                'sitemap' => false,
            ],
            [
                'handler' => function () use ($defaultLocale) {
                    $data = '';
                    $params = request()->urlData();

                    if (\count($params) > 0) {
                        $data = '?' . http_build_query($params);
                    }

                    return response()->redirect("/$defaultLocale" . request()->getPath() . $data);
                },
            ]
        );

        return $prefixedRoutes;
    }
}

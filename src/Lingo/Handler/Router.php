<?php

declare(strict_types=1);

namespace Leaf\Lingo\Handler;

use Leaf\Lingo\Handler;

class Router implements Handler
{
    protected array $config = [];

    /**
     * @inheritDoc
     */
    public function loadConfig(array $config): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function create(): static
    {
        app()->hook('router.before.route', function ($context) {
            foreach ($context['routes'] as $method => &$routeGroup) {
                foreach ($routeGroup as &$route) {
                    if ($this->shouldPrefixWithLocale($route)) {
                        $route['pattern'] = '/{locale}' . $route['pattern'];
                    }
                }
            }

            return $context;
        });

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCurrentLocale(string $locale): void
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
    public function getCurrentLocale(): ?string
    {
        $currentUrl = request()->getPath();
        $segments = explode('/', ltrim($currentUrl, '/'));

        return ($segments[0] ?? null) ?: $this->config['locales.default'];
    }

    /**
     * Determine if a route should be prefixed with locale
     *
     * @param array $route
     * @return bool
     */
    protected function shouldPrefixWithLocale(array $route): bool
    {
        if (!$route['lingo.no_locale_prefix'] ?? false) {
            return false;
        }

        return true;
    }
}

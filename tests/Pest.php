<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

// The default PHPUnit TestCase is sufficient; Tests\ is not autoloaded.

/*
|--------------------------------------------------------------------------
| Environment stubs
|--------------------------------------------------------------------------
|
| leafs/http and leafs/leaf are not installed in this test environment, so
| we provide minimal request()/app() stubs driven by superglobals. These
| are only defined if the real helpers are absent.
|
*/

if (!class_exists('Leaf\Config')) {
    // Leaf\Config ships with anchor v5 (dev-only right now); polyfill for tests.
    class TestsLeafConfigPolyfill
    {
        protected static array $store = [];
        protected static array $singletons = [];

        public static function getStatic(string $key)
        {
            return static::$store[$key] ?? null;
        }

        public static function singleton(string $key, callable $resolver): void
        {
            static::$store[$key] = true;
            static::$singletons[$key] = $resolver;
            unset(static::$store["__resolved.$key"]);
        }

        public static function get(string $key)
        {
            if (!isset(static::$store["__resolved.$key"]) && isset(static::$singletons[$key])) {
                static::$store["__resolved.$key"] = (static::$singletons[$key])();
            }

            return static::$store["__resolved.$key"] ?? null;
        }
    }

    class_alias(TestsLeafConfigPolyfill::class, 'Leaf\Config');
}

if (!function_exists('request')) {
    function request()
    {
        return new class () {
            public function getPath(): string
            {
                $uri = $_SERVER['REQUEST_URI'] ?? '/';

                return parse_url($uri, PHP_URL_PATH) ?? '/';
            }

            public function headers(?string $name = null)
            {
                if ($name === null) {
                    return [];
                }

                $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

                return $_SERVER[$key] ?? null;
            }
        };
    }
}

if (!function_exists('session')) {
    // leafs/session's session() helper is skipped when Leaf\Config is absent
    // at autoload time, so we provide an in-memory stand-in.
    function session()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new class () {
                protected array $data = [];

                public function get(string $key)
                {
                    return $this->data[$key] ?? null;
                }

                public function set(string $key, $value): void
                {
                    $this->data[$key] = $value;
                }
            };
        }

        return $instance;
    }
}

if (!function_exists('_env')) {
    // leaf core's _env() caches the environment on first call, which would
    // make per-test env changes invisible. The stand-in reads live instead.
    function _env($key, $default = null)
    {
        $env = array_merge(getenv() ?: [], $_ENV ?? []);

        return array_key_exists($key, $env) ? $env[$key] : $default;
    }
}

if (!function_exists('app')) {
    function app()
    {
        return new class () {
            public function hook(string $name, callable $handler): void
            {
                // no-op for tests
            }
        };
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A predictable custom locale strategy for tests.
 */
class TestLocaleHandler implements \Leaf\Lingo\Handler
{
    public static ?string $locale = null;
    public static array $config = [];

    public static function create(array $config): static
    {
        static::$config = $config;
        static::$locale = static::$locale ?? ($config['locales.default'] ?? null);

        return new static();
    }

    public static function setCurrentLocale(string $locale): void
    {
        static::$locale = $locale;
    }

    public static function getCurrentLocale(): ?string
    {
        return static::$locale;
    }
}

function localesPath(): string
{
    return __DIR__ . '/data/locales';
}

/**
 * Fresh Lingo instance using the custom test strategy.
 */
function freshLingo(array $config = []): \Leaf\Lingo
{
    TestLocaleHandler::$locale = null;

    $lingo = new \Leaf\Lingo();
    $lingo->create(array_merge([
        'locales.path' => localesPath(),
        'locales.default' => 'en_US',
        'locales.strategy' => 'custom',
        'locales.customStrategy' => TestLocaleHandler::class,
    ], $config));

    return $lingo;
}

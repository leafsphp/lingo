<?php

declare(strict_types=1);


namespace Leaf;

use Symfony\Component\Yaml\Yaml;

class Lingo
{
    protected array $locales = [
        // English
        'en' => 'English',
        'en_US' => 'English (United States)',
        'en_GB' => 'English (United Kingdom)',
        'en_CA' => 'English (Canada)',
        'en_AU' => 'English (Australia)',
        'en_NZ' => 'English (New Zealand)',
        'en_IE' => 'English (Ireland)',
        'en_IN' => 'English (India)',

        // German
        'de' => 'Deutsch',
        'de_DE' => 'Deutsch (Deutschland)',
        'de_AT' => 'Deutsch (Österreich)',
        'de_CH' => 'Deutsch (Schweiz)',

        // French
        'fr' => 'Français',
        'fr_FR' => 'Français (France)',
        'fr_CA' => 'Français (Canada)',
        'fr_BE' => 'Français (Belgique)',
        'fr_CH' => 'Français (Suisse)',

        // Spanish
        'es' => 'Español',
        'es_ES' => 'Español (España)',
        'es_MX' => 'Español (México)',
        'es_AR' => 'Español (Argentina)',
        'es_CO' => 'Español (Colombia)',
        'es_CL' => 'Español (Chile)',
        'es_PE' => 'Español (Perú)',

        // Portuguese
        'pt' => 'Português',
        'pt_PT' => 'Português (Portugal)',
        'pt_BR' => 'Português (Brasil)',

        // Italian
        'it' => 'Italiano',
        'it_IT' => 'Italiano (Italia)',
        'it_CH' => 'Italiano (Svizzera)',

        // Dutch
        'nl' => 'Nederlands',
        'nl_NL' => 'Nederlands (Nederland)',
        'nl_BE' => 'Nederlands (België)',

        // Chinese — custom rules
        'cn' => '简体中文',
        'zh' => '中文',
        'zh_CN' => '简体中文',
        'zh_SG' => '简体中文（新加坡）',
        'zh_TW' => '繁體中文',
        'zh_HK' => '香港中文',

        // Japanese
        'ja' => '日本語',
        'ja_JP' => '日本語（日本）',

        // Korean
        'ko' => '한국어',
        'ko_KR' => '한국어 (대한민국)',

        // Russian
        'ru' => 'Русский',
        'ru_RU' => 'Русский (Россия)',
        'ru_UA' => 'Русский (Украина)',

        // Arabic (multiple popular regions)
        'ar' => 'العربية',
        'ar_SA' => 'العربية (السعودية)',
        'ar_AE' => 'العربية (الإمارات)',
        'ar_EG' => 'العربية (مصر)',

        // Turkish
        'tr' => 'Türkçe',
        'tr_TR' => 'Türkçe (Türkiye)',

        // Polish
        'pl' => 'Polski',
        'pl_PL' => 'Polski (Polska)',

        // Swedish
        'sv' => 'Svenska',
        'sv_SE' => 'Svenska (Sverige)',

        // Danish
        'da' => 'Dansk',
        'da_DK' => 'Dansk (Danmark)',

        // Norwegian
        'no' => 'Norsk',
        'nb_NO' => 'Norsk Bokmål',
        'nn_NO' => 'Norsk Nynorsk',

        // Finnish
        'fi' => 'Suomi',
        'fi_FI' => 'Suomi (Suomi)',

        // Czech
        'cs' => 'Čeština',
        'cs_CZ' => 'Čeština (Česko)',

        // Slovak
        'sk' => 'Slovenčina',
        'sk_SK' => 'Slovenčina (Slovensko)',

        // Hungarian
        'hu' => 'Magyar',
        'hu_HU' => 'Magyar (Magyarország)',

        // Romanian
        'ro' => 'Română',
        'ro_RO' => 'Română (România)',

        // Greek
        'el' => 'Ελληνικά',
        'el_GR' => 'Ελληνικά (Ελλάδα)',

        // Thai
        'th' => 'ไทย',
        'th_TH' => 'ไทย (ประเทศไทย)',

        // Vietnamese
        'vi' => 'Tiếng Việt',
        'vi_VN' => 'Tiếng Việt (Việt Nam)',

        // Hindi (common)
        'hi' => 'हिन्दी',
        'hi_IN' => 'हिन्दी (भारत)',

        // Indonesian
        'id' => 'Bahasa Indonesia',
        'id_ID' => 'Bahasa Indonesia (Indonesia)',

        // Malay
        'ms' => 'Bahasa Melayu',
        'ms_MY' => 'Bahasa Melayu (Malaysia)',

        // Ukrainian
        'uk' => 'Українська',
        'uk_UA' => 'Українська (Україна)',

        // Hebrew
        'he' => 'עברית',
        'he_IL' => 'עברית (ישראל)',
    ];
    protected array $config = [
        'locales.default' => 'en_US',
        'locales.available' => [],
        'locales.path' => 'locales',
        'locales.strategy' => 'router', // router, header, session, custom
        'locales.customStrategy' => null,
        'locales.cacheKey' => '__lingo.locale__',
    ];

    protected array $cache = [];
    protected array $fileIndex = [];
    protected array $translations = [];
    protected $overrideLocale = null;
    protected Lingo\Handler $handler;
    protected array $drivers = [
        'router' => Lingo\Handler\Router::class,
        'header' => Lingo\Handler\Header::class,
        'session' => Lingo\Handler\Session::class,
    ];

    /**
     * Initialize lingo with config
     *
     * @param array $config
     *
     * @return void
     */
    public function create(array $config = []): void
    {
        $this->config = array_merge($this->config, $this->configFromEnv(), $config);

        $this->getTranslationFiles();

        $strategy = $this->config['locales.strategy'];

        if ($strategy === 'custom') {
            $customStrategy = $this->config['locales.customStrategy'];

            if (!$customStrategy || !is_subclass_of($customStrategy, Lingo\Handler::class)) {
                throw new Lingo\Exceptions\LocaleStrategyNotFoundException($strategy);
            }

            $this->handler = $customStrategy::create($this->config);

            return;
        }

        if (!isset($this->drivers[$strategy])) {
            throw new Lingo\Exceptions\LocaleStrategyNotFoundException($strategy);
        }

        $this->handler = $this->drivers[$strategy]::create($this->config);
    }

    /**
     * Config values that can come from the environment
     *
     * These sit between the defaults and whatever is passed to create(),
     * so a value set in code always wins over a .env entry. Lingo can be
     * used without leaf core, so `_env` may not be there at all.
     *
     * @return array<string, mixed>
     */
    protected function configFromEnv(): array
    {
        if (!function_exists('_env')) {
            return [];
        }

        return array_filter([
            'locales.default' => _env('APP_LOCALE'),
            'locales.strategy' => _env('LOCALES_STRATEGY'),
        ], fn ($value) => $value !== null);
    }

    /**
     * Get/Set a config for locales
     *
     * @param string $key
     * @param mixed|null $value
     *
     * @return mixed|null
     */
    public function config(string $key, $value = null)
    {
        if (func_num_args() === 1) {
            return $this->config[$key] ?? null;
        }

        $this->config[$key] = $value;
    }

    /**
     * Static method to get translation
     *
     * @param string $locale - locale code
     * @param string $key - translation key defined in the locale file
     *
     * @return string
     */
    public function get(string $locale, string $key): string
    {
        if (isset($this->cache[$locale][$key])) {
            return $this->cache[$locale][$key];
        }

        if (!isset($this->fileIndex[$locale])) {
            $this->fileIndex[$locale] = $this->getLocaleData($locale);
        }

        if (isset($this->fileIndex[$locale][$key])) {
            $this->cache[$locale][$key] = (string) $this->fileIndex[$locale][$key];
            return $this->cache[$locale][$key];
        }

        return $key;
    }

    protected function parseTranslationParameters(string $translation, array $params): string
    {
        foreach ($params as $paramKey => $paramValue) {
            $translation = str_replace(["{{ $paramKey }}", "$$paramKey"], $paramValue, $translation);
        }

        return $translation;
    }

    /**
     * Get the data in a translation file
     *
     * @param string $locale The locale to get
     * @return array
     */
    public function getLocaleData(string $locale): array
    {
        if (!isset($this->translations[$locale])) {
            throw new Lingo\Exceptions\LocaleNotFoundException($locale);
        }

        $data = Yaml::parseFile($this->translations[$locale]);

        return $this->flattenTranslations(is_array($data) ? $data : []);
    }

    /**
     * Flatten nested translation maps into dot notation keys
     *
     * ['welcome' => ['title' => 'Hi']] => ['welcome.title' => 'Hi']
     *
     * @param array $translations The parsed translation data
     * @param string $prefix Key prefix carried through recursion
     * @return array<string, string>
     */
    protected function flattenTranslations(array $translations, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "$prefix.$key";

            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenTranslations($value, $fullKey));
            } else {
                $flattened[$fullKey] = (string) $value;
            }
        }

        return $flattened;
    }

    /**
     * Get the translation files in user workspace
     */
    public function getTranslationFiles()
    {
        $fileDirectory = $this->config('locales.path');

        if (!storage()->exists($fileDirectory)) {
            return;
        }

        $files = glob("$fileDirectory/*.yml");

        $this->translations = [];
        $this->config['locales.available'] = [];

        foreach ($files as $file) {
            $localeName = basename($file, '.yml');

            $this->translations[$localeName] = $file;
            $this->config['locales.available'][] = $localeName;
        }
    }

    /**
     * @param string $key - translation key defined in the locale file
     * @param array $params - parameter in the translated string defined in the locale file
     *
     * @return string
     */
    public function translate(string $key, array $params = []): string
    {
        return $this->parseTranslationParameters($this->get(
            $this->getCurrentLocale(),
            $key,
        ), $params);
    }

    /**
     * Sets the current locale to be used
     *
     * @param string $locale Must match the file name Ex: file: en_US.yml, localeName: en_US
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setCurrentLocale(string $locale): void
    {
        $this->handler->setCurrentLocale($locale);
    }

    /**
     * Override current locale without handler effects
     *
     * @param string|null $locale The locale to set
     *
     * @return void
     */
    public function overrideCurrentLocale(?string $locale): void
    {
        $this->overrideLocale = $locale;
    }

    /**
     * Returns the current locale being used
     * @return string|null
     */
    public function getCurrentLocale(): ?string
    {
        return $this->overrideLocale ?? $this->handler->getCurrentLocale();
    }

    /**
     * Returns the current language being used
     * @return string|null
     */
    public function getCurrentLanguage(): ?string
    {
        $locale = $this->getCurrentLocale();

        return $locale ? explode('_', $locale)[0] : null;
    }

    /**
     * Returns all available locales based on the created locale files
     *
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return $this->config('locales.available');
    }

    /**
     * Returns all available locales with their names if possible
     *
     * @return array<string, string>
     */
    public function getAvailableLocalesWithNames(): array
    {
        $locales = [];

        foreach ($this->getAvailableLocales() as $locale) {
            $locales[$locale] = $this->locales[$locale] ?? $locale;
        }

        return $locales;
    }

    /**
     * Return the name of a locale from code
     *
     * @return string|null
     */
    public function getLocaleName($code)
    {
        return $this->locales[$code] ?? null;
    }

    /**
     * Returns configured default locale
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->config('locales.default');
    }

    /**
     * Return a route based on the current locale
     *
     * @param array $routes Associative array of locale => route
     *
     * @return string|null
     */
    public function matchRoute(array $routes)
    {
        $route = $routes[$this->getCurrentLocale()] ?? null;

        return $route ? '/' . $this->getCurrentLocale() . $route : null;
    }

    /**
     * Switch route to a different locale
     *
     * @param string $locale The locale to switch to
     *
     * @return string
     */
    public function switch(string $locale): string
    {
        $currentUrl = request()->getPath();
        $segments = explode('/', ltrim($currentUrl, '/'));

        if (($segments[0] ?? '') !== '' && in_array($segments[0], $this->getAvailableLocales())) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        return '/' . implode('/', $segments);
    }

    /**
     * Get a route based on the current locale
     *
     * @param string $path The path to use
     *
     * @return string|null
     */
    public function url(string $path): ?string
    {
        $locale = $this->getCurrentLocale();

        if ($this->config('locales.strategy') !== 'router') {
            return $path;
        }

        return $locale ? str_replace('//', '/', "/$locale/$path") : null;
    }

    /**
     * Check if a locale is the current locale
     *
     * @param string $locale The locale to check
     *
     * @return bool
     */
    public function is(string $locale): bool
    {
        return $locale === $this->getCurrentLocale();
    }

    /**
     * Output variant based on current locale
     *
     * @param array $texts Associative array of locale => text
     *
     * @return string|null
     */
    public function variants(array $variants): ?string
    {
        $locale = $this->getCurrentLocale();

        return $locale ? ($variants[$locale] ?? null) : null;
    }
}

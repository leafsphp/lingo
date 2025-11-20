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
        $this->config = array_merge($this->config, $config);

        $this->getTranslationFiles();

        $this->handler = $this->drivers[$this->config['locales.strategy']]::create($this->config);
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
        if (!$value) {
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
            $this->fileIndex[$locale] = $this->indexFile($locale);
        }

        if (isset($this->fileIndex[$locale][$key])) {
            $this->cache[$locale][$key] = $this->fileIndex[$locale][$key];
            return $this->cache[$locale][$key];
        }

        return $key;
    }

    protected function parseTranslationParameters(string $translation, array $params): string
    {
        foreach ($params as $paramKey => $paramValue) {
            $translation = str_replace("{{$paramKey}}", $paramValue, $translation);
        }

        return $translation;
    }

    protected function indexFile(string $locale): array
    {
        $filePath = $this->translations[$locale];
        $data = Yaml::parseFile($filePath);

        return $data;
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

        foreach ($files as $file) {
            $localeName = str_replace('.yml', '', path($file)->basename());

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
            $this->handler->getCurrentLocale(),
            $key,
        ), $params);
    }

    /**
     * Sets the current locale to be used
     *
     * @param string $locale - must match the file name Ex: file: en_US.locale.json, localeName: en_US
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
     * Returns the current locale being used
     * @return string|null
     */
    public function getCurrentLocale(): ?string
    {
        return $this->handler->getCurrentLocale();
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
     * Returns configured default locale
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->config('locales.default');
    }

    /**
     * Add routes for language switching
     *
     * @param string|null $path
     * @param string|null $requestLocaleParamName
     * @param bool $redirectToReferer
     *
     * @return void
     *
     * @throws \Exception
     */
    public function route(
        ?string $path = '/lingo/switch',
        ?string $requestLocaleParamName = 'locale',
        bool $redirectToReferer = true
    ): void {
        //
    }
}

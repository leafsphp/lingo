<?php

if (!function_exists('lingo')) {
    /**
     * Return the Lingo instance or translate a string
     * 
     * @param string|null $key The translation key
     * @param array $params The parameters for the translation
     * 
     * @return \Leaf\Lingo|string
     */
    function lingo(?string $key = null, array $params = [])
    {
        if (!(\Leaf\Config::getStatic('lingo'))) {
            \Leaf\Config::singleton('lingo', function () {
                return new \Leaf\Lingo();
            });
        }

        $instance = \Leaf\Config::get('lingo');

        if ($key) {
            return $instance->translate($key, $params);
        }

        return $instance;
    }
}

if (!function_exists('__')) {
    /**
     * Translate a key using the Lingo instance
     *
     * @param string $key The translation key
     * @param array $params The parameters for the translation
     *
     * @return string
     */

    function __(string $key, array $params = []): string
    {
        return lingo($key, $params);
    }
}

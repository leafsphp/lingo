<?php

declare(strict_types=1);


namespace Leaf\Lingo\Exceptions;

class LocaleNotFoundException extends \Exception
{
    public function __construct(string $currentLocale, $message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "Translation file not found for locale \"$currentLocale\" make sure you have a translation file named \"$currentLocale.yml\" in your translation files folder";
    }
}

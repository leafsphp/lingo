<?php

declare(strict_types=1);


namespace Leaf\Lingo\Exceptions;

class LocaleParamNotFoundException extends \Exception
{
    public function __construct(string $key, string $translationString, $message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "Param \"$key\" not found in translation string \"$translationString\"";
    }
}

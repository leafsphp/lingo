<?php

declare(strict_types=1);


namespace Leaf\Lingo\Exceptions;

class LocaleStrategyNotFoundException extends \Exception
{
    public function __construct(string $strategy, $message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = "No locale strategy found for \"$strategy\". Use \"router\", \"header\", \"session\", or set the strategy to \"custom\" with a \"locales.customStrategy\" class that implements Leaf\\Lingo\\Handler";
    }
}

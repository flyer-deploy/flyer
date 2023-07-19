<?php

namespace Flyer\Exception;

class FlyerException extends \Exception
{
    public function __construct(string $message = "", ?\Exception $orig = null)
    {
        parent::__construct($message, 0, $orig);
    }
}
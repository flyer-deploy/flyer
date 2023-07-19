<?php

namespace Flyer\Config\Exception;

use Flyer\Exception\FlyerException;
use Illuminate\Support\MessageBag;

class ConfigValidationException extends FlyerException
{
    private MessageBag $errors;

    public function __construct(string $message, MessageBag $errors, \Exception $orig = null)
    {
        parent::__construct($message, $orig);

        $this->errors = $errors;
    }

    public function get_errors()
    {
        return $this->errors;
    }
}
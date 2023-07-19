<?php

namespace Flyer\Config;

/**
 * To make Laravel Validation work outside Laravel.
 * 
 * https://medium.com/@jeffochoa/using-the-illuminate-validation-validator-class-outside-laravel-6b2b0c07d3a4
 * 
 **/

use Illuminate\Validation\Validator as V;

use Illuminate\Validation;
use Illuminate\Translation;
use Illuminate\Filesystem\Filesystem;

class ValidatorFactory
{
    private Validation\Factory $factory;
    private V $validator;

    public function __construct()
    {
        $this->factory = new Validation\Factory(
            $this->loadTranslator()
        );
    }

    protected function loadTranslator()
    {
        $filesystem = new Filesystem();
        $loader = new Translation\FileLoader(
            $filesystem, dirname(dirname(__FILE__)) . '/lang'
        );
        $loader->addNamespace(
            'lang',
            dirname(dirname(__FILE__)) . '/lang'
        );
        $loader->load('en', 'validation', 'lang');
        return new Translation\Translator($loader, 'en');
    }

    /**
     * For type-hinting.
     * 
     * @return V
     */
    public function make(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        return $this->factory->make($data, $rules, $messages, $attributes);
    }
}
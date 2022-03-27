<?php

if (!function_exists('document')) {
    function document(): \Apsonex\LaravelDocument\Support\DocumentFactory
    {
        return app('document');
    }
}
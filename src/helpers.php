<?php

if (!function_exists('document')) {
    function document(): \Apsonex\LaravelDocument\DocumentManager
    {
        return app('document');
    }
}
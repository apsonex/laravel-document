<?php

if (!function_exists('document')) {
    function document(): \Apsonex\Document\DocumentManager
    {
        return app('document');
    }
}
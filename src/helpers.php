<?php

if (!function_exists('document')) {
    function document(): \Apsonex\Document\Document
    {
        return app('document');
    }
}
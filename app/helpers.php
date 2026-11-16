<?php

if (!function_exists('hasPermission')) {
    /*
    |--------------------------------------------------------------------------
    | Permission Helper
    |--------------------------------------------------------------------------
    */
    function hasPermission(string $permission): bool {
        $user = auth()->user();

        return $user ? $user->canPermission($permission) : false;
    }
}

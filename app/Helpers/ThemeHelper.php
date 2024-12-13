<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getActiveTheme')) {
    function getActiveTheme()
    {
        $activeTheme = DB::table('settings_theme')->first();
        return $activeTheme ? $activeTheme->active_theme_id : 1;
    }
}

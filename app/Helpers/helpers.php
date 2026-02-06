<?php

if (!function_exists('settings')) {
    /**
     * Get application settings singleton
     */
    function settings()
    {
        return \App\Models\settings::first();
    }
}

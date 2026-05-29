<?php

/**
 * Redirect ke folder public secara otomatis.
 * File ini memastikan URL tanpa /public tetap bisa diakses.
 */

// Redirect ke /public/
header('Location: public/');
exit;

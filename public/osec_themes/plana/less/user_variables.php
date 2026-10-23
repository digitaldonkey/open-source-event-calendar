<?php

if (! defined('ABSPATH')) {
    exit;
}

// Plana takes Vortex's variables. Its text follows the site (the font size presets and the
// site's font), so the base font size and font are fixed relative values here and hidden
// from Theme Options, where they would have no effect.
$osec_variables = require OSEC_DEFAULT_THEME_ROOT . '/' . OSEC_ROOT_THEME_NAME . '/less/user_variables.php';

$osec_variables['baseFontSize']['value']    = '1em';
$osec_variables['baseFontSize']['hidden']   = true;
$osec_variables['baseFontFamily']['value']  = 'inherit';
$osec_variables['baseFontFamily']['hidden'] = true;

return $osec_variables;

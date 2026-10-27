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

// Plana's own colours, sizes and spacing, used only in rules plana renders (plana-variables.less,
// plana-colours.less). The vortex colour options have no visible effect of their own in plana, so
// they are hidden; LESS still gets their defaults.
// - 'css_var': the custom property the setting writes; only these settings accept var() values
//   (CssVarValue).
// - 'current_color': a text-only setting that may be currentColor; all other colours are also used
//   as backgrounds and refuse anything that ends up as currentColor.
// - 'migrate_from': vortex options a changed value is taken over from, once, while the setting
//   itself is not stored yet (LessController::migrate_css_var_settings()).
// Button pairs (toolbar, controls) start with Twenty Twenty-One's button colours
// (--global--color-background on --global--color-primary); themes without them use the next level.
foreach ($osec_variables as $osec_name => $osec_variable) {
    if ('color' === $osec_variable['type']) {
        $osec_variables[$osec_name]['hidden'] = true;
    }
}
$osec_variables = [
    'osecText' => [
        'value'       => 'currentColor',
        'description' => __('Text (default: the theme\'s text colour)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-text',
        'current_color' => true,
    ],
    'osecLink' => [
        'value'       => 'var(--wp--preset--color--contrast, ' . \Osec\Theme\CssVarValue::DEFAULT_LINK_COLOR . ')',
        'description' => __('Links', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-link',
        'migrate_from' => ['linkColor'],
    ],
    'osecLinkHover' => [
        'value'       => 'color-mix(in srgb, var(--osec-link), black 25%)',
        'description' => __('Links (hover)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-link-hover',
        'migrate_from' => ['linkColorHover'],
    ],
    'osecEventDefault' => [
        'value'       => '#3d6b99',
        'description' => __('Event colour (events without category colour)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-event-default',
        'migrate_from' => ['eventDefaultColor'],
    ],
    'osecEventTitle' => [
        'value'       => 'var(--osec-event-default, ' . \Osec\Theme\CssVarValue::DEFAULT_LINK_COLOR . ')',
        'description' => __('Event titles', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-event-title',
        'migrate_from' => ['eventDefaultColor', 'linkColor'],
    ],
    'osecEventTitleHover' => [
        'value'       => 'color-mix(in srgb, var(--osec-event-default), black 25%)',
        'description' => __('Event titles (hover)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-event-title-hover',
        'migrate_from' => ['eventDefaultColorHover', 'linkColorHover'],
    ],
    'osecBackground' => [
        'value'       => '#ffffff',
        'description' => __('Background (tables, agenda events)', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-background',
        'migrate_from' => ['bodyBackground', 'eventBackground', 'tableBackground'],
    ],
    'osecBackgroundSubtle' => [
        'value'       => '#f5f5f5',
        'description' => __('Subtle background (table headers, empty days, hour labels)', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-background-subtle',
        'migrate_from' => ['tableLabelBackground', 'agendaDateBackground', 'dropdownLinkBackgroundHover'],
    ],
    'osecDayBackground' => [
        'value'       => '#eaf4ff',
        'description' => __('Day number background (month)', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-day-background',
        'migrate_from' => ['monthViewDateBackground'],
    ],
    'osecLine' => [
        'value'       => '#dddddd',
        'description' => __('Lines (grid, borders)', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-line',
        'migrate_from' => ['eventBorder'],
    ],
    'osecLineHover' => [
        'value'       => '#808080',
        'description' => __('Strong lines (agenda events and date titles)', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-line-thick',
    ],
    'osecTextOnColor' => [
        'value'       => '#ffffff',
        'description' => __(
            'Text on coloured backgrounds (event bars, date badge, all-day badge)',
            'open-source-event-calendar'
        ),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-text-on-color',
        'migrate_from' => ['eventStubAllDayText', 'agendaDateTitleBackground'],
    ],
    'osecToday' => [
        'value'       => '#ffffdd',
        'description' => __('Today highlight', 'open-source-event-calendar'),
        'tab'         => 'backgrounds',
        'type'        => 'color',
        'css_var'     => 'osec-today',
        'migrate_from' => ['todayColor', 'agendaTodayBackground'],
    ],
    'osecAccent' => [
        'value'       => '#6689ac',
        'description' => __('Day date badge', 'open-source-event-calendar'),
        'tab'         => 'agenda',
        'type'        => 'color',
        'css_var'     => 'osec-date-badge',
        'migrate_from' => ['agendaDateTitleBorder'],
    ],
    'osecReadMoreText' => [
        'value'       => 'var(--osec-link, ' . \Osec\Theme\CssVarValue::DEFAULT_LINK_COLOR . ')',
        'description' => __('"Read more" button text', 'open-source-event-calendar'),
        'tab'         => 'agenda',
        'type'        => 'color',
        'css_var'     => 'osec-read-more-text',
    ],
    'osecReadMoreBackground' => [
        'value'       => 'var(--osec-controls-background, buttonFace)',
        'description' => __('"Read more" button background', 'open-source-event-calendar'),
        'tab'         => 'agenda',
        'type'        => 'color',
        'css_var'     => 'osec-read-more-background',
    ],
    'osecControlsText' => [
        'value'       => 'var(--global--color-background, var(--wp--preset--color--contrast, buttontext))',
        'description' => __('Controls text (buttons on the event page)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-controls-text',
    ],
    'osecControlsBackground' => [
        'value'       => 'var(--global--color-primary, buttonFace)',
        'description' => __('Controls background', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-controls-background',
    ],
    'osecToolbarText' => [
        'value'       => 'var(--global--color-background, var(--osec-link, #3d6b99))',
        'description' => __(
            'Toolbar text (toolbar, filter and view menus, subscribe, back to calendar)',
            'open-source-event-calendar'
        ),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-toolbar-text',
    ],
    'osecToolbarBackground' => [
        'value'       => 'var(--global--color-primary, var(--osec-controls-background, buttonFace))',
        'description' => __('Toolbar background', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-toolbar-background',
    ],
    'osecEditLink' => [
        'value'       => 'var(--wp-admin-theme-color, buttonFace)',
        'description' => __('Edit link (logged in)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-edit-link',
    ],
    'osecLabelText' => [
        'value'       => 'var(--osec-text, currentColor)',
        'description' => __('Label text (event page)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-label-text',
        'current_color' => true,
    ],
    'osecTextMuted' => [
        'value'       => '#9e9e9e',
        'description' => __('Muted text (weekdays, hours, labels, day numbers)', 'open-source-event-calendar'),
        'tab'         => 'colors',
        'type'        => 'color',
        'css_var'     => 'osec-text-muted',
        'migrate_from' => ['tableLabelText', 'labelColor'],
    ],
    'osecFontSizeSmall' => [
        'value'       => 'var(--wp--preset--font-size--small, .875rem)',
        'description' => __('Small text', 'open-source-event-calendar'),
        'tab'         => 'sizes',
        'type'        => 'size',
        'css_var'     => 'osec-font-size-small',
    ],
    'osecFontSizeNormal' => [
        'value'       => 'var(--wp--preset--font-size--normal, 1rem)',
        'description' => __('Normal text', 'open-source-event-calendar'),
        'tab'         => 'sizes',
        'type'        => 'size',
        'css_var'     => 'osec-font-size-normal',
    ],
    'osecSpacing' => [
        'value'       => 'var(--wp--preset--spacing--50, 2em)',
        'description' => __('Spacing above and below Agenda (large screens)', 'open-source-event-calendar'),
        'tab'         => 'agenda',
        'type'        => 'size',
        'css_var'     => 'osec-spacing',
    ],
] + $osec_variables;

return $osec_variables;

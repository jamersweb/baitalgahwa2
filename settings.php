<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage(
    'themesettingbaitulghawa',
    get_string('configtitle', 'theme_baitulghawa')
);

$addsettings = function(admin_settingpage $settingspage) use ($ADMIN): void {
    if (!$ADMIN->fulltree) {
        return;
    }

    $settingspage->add(new admin_setting_configcolourpicker(
        'theme_baitulghawa/brandcolor',
        get_string('brandcolor', 'theme_baitulghawa'),
        get_string('brandcolor_desc', 'theme_baitulghawa'),
        '#8b5a2b'
    ));

    $settingspage->add(new admin_setting_configselect(
        'theme_baitulghawa/preset',
        get_string('preset', 'theme_baitulghawa'),
        get_string('preset_desc', 'theme_baitulghawa'),
        'default.scss',
        ['default.scss' => get_string('presetdefault', 'theme_baitulghawa')]
    ));

    $settingspage->add(new admin_setting_configtextarea(
        'theme_baitulghawa/rawscsspre',
        get_string('rawscsspre', 'theme_baitulghawa'),
        get_string('rawscsspre_desc', 'theme_baitulghawa'),
        '',
        PARAM_RAW
    ));

    $settingspage->add(new admin_setting_configtextarea(
        'theme_baitulghawa/rawscss',
        get_string('rawscss', 'theme_baitulghawa'),
        get_string('rawscss_desc', 'theme_baitulghawa'),
        '',
        PARAM_RAW
    ));
};

$addsettings($settings);

$settingsalias = new admin_settingpage(
    'themesettingbaitulgahwa',
    get_string('configtitle', 'theme_baitulghawa')
);
$addsettings($settingsalias);
$ADMIN->add('themes', $settingsalias);

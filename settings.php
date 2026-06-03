<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $setting = new admin_setting_configcolourpicker(
        'theme_baitulghawa/brandcolor',
        get_string('brandcolor', 'theme_baitulghawa'),
        get_string('brandcolor_desc', 'theme_baitulghawa'),
        '#8b5a2b'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configselect(
        'theme_baitulghawa/preset',
        get_string('preset', 'theme_baitulghawa'),
        get_string('preset_desc', 'theme_baitulghawa'),
        'default.scss',
        ['default.scss' => get_string('presetdefault', 'theme_baitulghawa')]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'theme_baitulghawa/rawscsspre',
        get_string('rawscsspre', 'theme_baitulghawa'),
        get_string('rawscsspre_desc', 'theme_baitulghawa'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'theme_baitulghawa/rawscss',
        get_string('rawscss', 'theme_baitulghawa'),
        get_string('rawscss_desc', 'theme_baitulghawa'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}

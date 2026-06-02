<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'baitulghawa';
$THEME->parents = ['boost'];
$THEME->sheets = ['dashboard'];
$THEME->editor_sheets = [];
$THEME->parents_exclude_sheets = [];
$THEME->enable_dock = false;

$THEME->scss = function($theme) {
    return theme_baitulghawa_get_main_scss_content($theme);
};

$THEME->prescsscallback = 'theme_baitulghawa_get_pre_scss';
$THEME->extrascsscallback = 'theme_baitulghawa_get_extra_scss';

$THEME->layouts = [
    'base' => [
        'file' => 'drawers.php',
        'regions' => [],
    ],
    'standard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'course' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'coursecategory' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'incourse' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'frontpage' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'admin' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'mydashboard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true, 'langmenu' => true],
    ],
    'mypublic' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'login' => [
        'file' => 'drawers.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'popup' => [
        'file' => 'columns1.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nonavbar' => true, 'activityheader' => false],
    ],
    'embedded' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],
    'frametop' => [
        'file' => 'columns1.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nocoursefooter' => true, 'activityheader' => false],
    ],
    'maintenance' => [
        'file' => 'maintenance.php',
        'regions' => [],
    ],
    'print' => [
        'file' => 'columns1.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nonavbar' => false, 'activityheader' => false],
    ],
    'redirect' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],
    'report' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'secure' => [
        'file' => 'secure.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
];

$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

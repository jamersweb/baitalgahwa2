<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => \theme_baitulghawa\hook_callbacks::class . '::before_standard_head_html_generation',
    ],
];

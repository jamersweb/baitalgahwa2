<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the compiled SCSS for the Baitulghawa theme.
 *
 * @param theme_config $theme The theme configuration.
 * @return string
 */
function theme_baitulghawa_get_main_scss_content($theme): string {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : 'default.scss';
    $preset = $CFG->dirroot . '/theme/baitulghawa/scss/preset/' . $filename;

    if (is_readable($preset)) {
        $scss .= file_get_contents($preset);
    } else {
        $parentconfig = theme_config::load('boost');
        $scss .= theme_boost_get_main_scss_content($parentconfig);
    }

    $post = $CFG->dirroot . '/theme/baitulghawa/scss/post.scss';
    if (is_readable($post)) {
        $scss .= "\n" . file_get_contents($post);
    }

    return $scss;
}

/**
 * Adds SCSS variables before Bootstrap and Moodle styles are compiled.
 *
 * @param theme_config $theme The theme configuration.
 * @return string
 */
function theme_baitulghawa_get_pre_scss($theme): string {
    global $CFG;

    $scss = '';
    $pre = $CFG->dirroot . '/theme/baitulghawa/scss/pre.scss';
    if (is_readable($pre)) {
        $scss .= file_get_contents($pre);
    }

    if (!empty($theme->settings->brandcolor)) {
        $brandcolor = $theme->settings->brandcolor;
        $scss .= "\n\$primary: {$brandcolor};";
        $scss .= "\n\$brand-primary: {$brandcolor};";
    }

    if (!empty($theme->settings->rawscsspre)) {
        $scss .= "\n" . $theme->settings->rawscsspre;
    }

    return $scss;
}

/**
 * Adds admin-provided SCSS after theme styles.
 *
 * @param theme_config $theme The theme configuration.
 * @return string
 */
function theme_baitulghawa_get_extra_scss($theme): string {
    return !empty($theme->settings->rawscss) ? $theme->settings->rawscss : '';
}

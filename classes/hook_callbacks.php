<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_baitulghawa;

defined('MOODLE_INTERNAL') || die();

/**
 * Output hook callbacks for the Baitulghawa theme.
 */
final class hook_callbacks {
    /**
     * Adds dashboard CSS directly to the standard page head.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook The output hook.
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $CFG, $PAGE;

        if (empty($PAGE) || $PAGE->pagetype !== 'my-index') {
            return;
        }

        $dashboardcss = $CFG->dirroot . '/theme/baitulghawa/style/dashboard.css';
        if (!is_readable($dashboardcss)) {
            return;
        }

        $hook->add_html(\html_writer::tag('style', file_get_contents($dashboardcss), [
            'id' => 'theme-baitulghawa-dashboard-css',
        ]));
    }
}

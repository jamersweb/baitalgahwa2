<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/boost/classes/output/core_renderer.php');

/**
 * Core renderer override for the Baitulghawa theme.
 */
class theme_baitulghawa_core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Adds dashboard CSS to the page head after Moodle's standard head output.
     *
     * @return string
     */
    public function standard_head_html(): string {
        return parent::standard_head_html() . $this->dashboard_css();
    }

    /**
     * Returns the dashboard CSS block for the user's dashboard page only.
     *
     * @return string
     */
    private function dashboard_css(): string {
        global $CFG, $PAGE;

        if (empty($PAGE) || $PAGE->pagetype !== 'my-index') {
            return '';
        }

        $dashboardcss = $CFG->dirroot . '/theme/baitulghawa/style/dashboard.css';
        if (!is_readable($dashboardcss)) {
            return '';
        }

        return html_writer::tag('style', file_get_contents($dashboardcss), [
            'id' => 'theme-baitulghawa-dashboard-css',
        ]);
    }
}

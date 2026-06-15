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

/**
 * Injects dashboard CSS directly into the page head.
 *
 * This is intentionally used as a fallback because some Moodle installs can
 * serve the Boost preset without appending this child theme's post SCSS.
 *
 * @return string
 */
function theme_baitulghawa_before_standard_html_head(): string {
    global $CFG, $PAGE;

    if (empty($PAGE)) {
        return '';
    }

    $styles = '';

    if ($PAGE->pagetype === 'my-index') {
        $dashboardcss = $CFG->dirroot . '/theme/baitulghawa/style/dashboard.css';
        if (is_readable($dashboardcss)) {
            $styles .= file_get_contents($dashboardcss);
        }
    }

    if (theme_baitulghawa_is_landing_request()) {
        $styles .= "\n" . theme_baitulghawa_landing_chrome_reset_css();
        $landingcss = $CFG->dirroot . '/theme/baitulghawa/scss/post.scss';
        if (is_readable($landingcss)) {
            $styles .= "\n" . file_get_contents($landingcss);
        }
    }

    if ($styles === '') {
        return '';
    }

    return html_writer::tag('style', $styles, [
        'id' => 'theme-baitulghawa-inline-css',
    ]);
}

/**
 * Hard-hides Moodle chrome on the public landing pages.
 *
 * @return string
 */
function theme_baitulghawa_landing_chrome_reset_css(): string {
    return '
        body { padding-top: 0 !important; }
        .navbar,
        .navbar.fixed-top,
        .navbar-bootswatch,
        .navbar-light,
        #page-header,
        .breadcrumb,
        .secondary-navigation,
        .drawer,
        .drawercontent,
        .drawer-toggles,
        #page-footer,
        .footer-popover {
            display: none !important;
        }
        #page,
        #page-wrapper,
        #page.drawers,
        #page.drawers div[role="main"],
        #page.drawers .main-inner {
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
        }
    ';
}

/**
 * Replaces the public front page with the Bait Al Gahwa landing experience.
 *
 * @return string
 */
function theme_baitulghawa_before_standard_top_of_body_html(): string {
    global $PAGE;

    if (!theme_baitulghawa_is_landing_request()) {
        return '';
    }

    $page = theme_baitulghawa_landing_page();
    $urls = theme_baitulghawa_landing_urls($PAGE->pagetype === 'login-index');

    $brand = theme_baitulghawa_landing_brand();
    $nav = theme_baitulghawa_landing_nav($urls, $page, $brand);
    $footer = theme_baitulghawa_landing_footer($urls, $brand);

    $content = '';
    if ($page === 'programmes') {
        $content = theme_baitulghawa_programmes_page($urls);
    } else if ($page === 'course') {
        $content = theme_baitulghawa_course_page($urls);
    } else if ($page === 'contact') {
        $content = theme_baitulghawa_contact_page($urls);
    } else {
        $content = theme_baitulghawa_home_page($urls);
    }

    return html_writer::tag('div', $nav . $content . $footer, [
        'class' => 'bag-landing-shell',
    ]);
}

/**
 * Detects when the Figma-style public landing should replace Moodle content.
 *
 * @return bool
 */
function theme_baitulghawa_is_landing_request(): bool {
    global $PAGE;

    if (empty($PAGE)) {
        return false;
    }

    if ($PAGE->pagetype === 'site-index') {
        return true;
    }

    return $PAGE->pagetype === 'login-index' && !optional_param('baglogin', 0, PARAM_BOOL);
}

/**
 * Returns validated landing page key.
 *
 * @return string
 */
function theme_baitulghawa_landing_page(): string {
    $page = optional_param('bagpage', 'home', PARAM_ALPHA);
    $allowedpages = ['home', 'programmes', 'course', 'contact'];

    return in_array($page, $allowedpages, true) ? $page : 'home';
}

/**
 * Landing URLs. Login-page base is used when Moodle force-login redirects home.
 *
 * @param bool $useloginbase
 * @return array
 */
function theme_baitulghawa_landing_urls(bool $useloginbase): array {
    $basepath = $useloginbase ? '/login/index.php' : '/';

    return [
        'home' => new moodle_url($basepath),
        'programmes' => new moodle_url($basepath, ['bagpage' => 'programmes']),
        'course' => new moodle_url($basepath, ['bagpage' => 'course']),
        'contact' => new moodle_url($basepath, ['bagpage' => 'contact']),
        'login' => new moodle_url('/login/index.php', ['baglogin' => 1]),
        'signup' => new moodle_url('/login/signup.php'),
        'courses' => new moodle_url('/course/index.php'),
    ];
}

/**
 * Public brand text.
 *
 * @return string
 */
function theme_baitulghawa_landing_brand(): string {
    global $SITE;

    return format_string($SITE->shortname ?: 'Bait Al Gahwa');
}

/**
 * Builds landing navigation.
 *
 * @param array $urls
 * @param string $page
 * @param string $brand
 * @return string
 */
function theme_baitulghawa_landing_nav(array $urls, string $page, string $brand): string {
    $items = [
        'home' => 'Home',
        'about' => 'About Us',
        'programmes' => 'Training Program',
        'contact' => 'Contact Us',
    ];

    $links = '';
    foreach ($items as $key => $label) {
        $target = $key === 'about' ? $urls['home'] : $urls[$key];
        $active = ($page === $key) || ($key === 'about' && $page === 'home');
        $links .= html_writer::link($target, $label, [
            'class' => 'bag-nav-link' . ($active ? ' is-active' : ''),
        ]);
    }

    return html_writer::tag('header',
        html_writer::tag('a', html_writer::tag('span', 'Bait Al Gahwa') . html_writer::tag('small', $brand), [
            'class' => 'bag-brand',
            'href' => (string)$urls['home'],
        ]) .
        html_writer::tag('nav', $links, ['class' => 'bag-nav-links', 'aria-label' => 'Main navigation']) .
        html_writer::tag('div',
            html_writer::link($urls['login'], 'Login', ['class' => 'bag-login']) .
            html_writer::link($urls['signup'], 'Register', ['class' => 'bag-signup']),
            ['class' => 'bag-auth-actions']
        ),
        ['class' => 'bag-site-header']
    );
}

/**
 * Home page markup.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_home_page(array $urls): string {
    $stats = [
        ['50+', 'Professional courses'],
        ['20+', 'Expert instructors'],
        ['5k+', 'Learners trained'],
    ];

    $stathtml = '';
    foreach ($stats as $stat) {
        $stathtml .= html_writer::tag('li',
            html_writer::tag('strong', $stat[0]) . html_writer::tag('span', $stat[1])
        );
    }

    $programmes = theme_baitulghawa_programme_cards($urls, 3);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Bait Al Gahwa', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'Empower Your Future With Excellence') .
                html_writer::tag('p', 'Learn coffee craft, hospitality skills and professional service standards through focused training built for real careers.') .
                html_writer::link($urls['programmes'], 'Explore Courses', ['class' => 'bag-btn bag-btn-primary']),
                ['class' => 'bag-hero-copy']
            ),
            ['class' => 'bag-hero']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('div', '', ['class' => 'bag-collage-main']) .
                html_writer::tag('div', '', ['class' => 'bag-collage-small bag-collage-beans']) .
                html_writer::tag('div', '', ['class' => 'bag-collage-small bag-collage-cup']),
                ['class' => 'bag-collage']
            ) .
            html_writer::tag('div',
                html_writer::tag('p', 'Why Choose Us', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Expertise Across All Disciplines') .
                html_writer::tag('p', 'From barista foundations to front-of-house service, every programme combines hands-on learning with practical assessment.') .
                html_writer::tag('ul', $stathtml, ['class' => 'bag-stats']),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Featured Learning', ['class' => 'bag-eyebrow bag-center']) .
                html_writer::tag('h2', 'Our Training Programmes', ['class' => 'bag-center']) .
                html_writer::tag('div', $programmes, ['class' => 'bag-programme-grid']) .
                html_writer::tag('div', html_writer::link($urls['programmes'], 'View Courses', ['class' => 'bag-btn bag-btn-outline']), ['class' => 'bag-center']),
                ['class' => 'bag-section-inner']
            ),
            ['class' => 'bag-section bag-programmes-band']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Professional growth', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Excellence in Professional Development') .
                html_writer::tag('ul',
                    html_writer::tag('li', 'Interactive training with industry-led instruction') .
                    html_writer::tag('li', 'Practical workplace scenarios and assessments') .
                    html_writer::tag('li', 'Career-focused learning paths for hospitality teams') .
                    html_writer::tag('li', 'Certification support after programme completion'),
                    ['class' => 'bag-check-list']
                ),
                ['class' => 'bag-section-copy']
            ) .
            html_writer::tag('div', '', ['class' => 'bag-student-photo']),
            ['class' => 'bag-section bag-two-column bag-development']
        ) .
        theme_baitulghawa_cta($urls),
        ['class' => 'bag-landing-main']
    );
}

/**
 * Programmes page markup.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_programmes_page(array $urls): string {
    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('p', 'Featured Learning', ['class' => 'bag-eyebrow bag-center']) .
            html_writer::tag('h1', 'Our Training Programmes', ['class' => 'bag-center']) .
            html_writer::tag('div', theme_baitulghawa_programme_cards($urls, 6), ['class' => 'bag-programme-grid bag-programme-grid-wide']) .
            html_writer::tag('div', html_writer::link($urls['course'], '1', ['class' => 'bag-page-dot is-active']) . html_writer::link($urls['course'], '2', ['class' => 'bag-page-dot']), ['class' => 'bag-pagination']),
            ['class' => 'bag-page-section']
        ),
        ['class' => 'bag-landing-main']
    );
}

/**
 * Course detail page markup.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_course_page(array $urls): string {
    $lessons = [
        'Espresso extraction',
        'Milk steaming',
        'Latte art basics',
        'Coffee menu workflow',
    ];

    $lessonhtml = '';
    foreach ($lessons as $lesson) {
        $lessonhtml .= html_writer::tag('li', $lesson);
    }

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div', '', ['class' => 'bag-course-photo']) .
            html_writer::tag('div',
                html_writer::tag('p', 'Professional certificate', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'Certified Gahwa Specialist') .
                html_writer::tag('p', 'A focused programme for learners who want practical coffee preparation skills, service confidence and recognised training outcomes.') .
                html_writer::tag('div',
                    html_writer::tag('span', '6 Weeks') .
                    html_writer::tag('span', 'Beginner') .
                    html_writer::tag('span', 'Certificate'),
                    ['class' => 'bag-course-meta']
                ) .
                html_writer::link($urls['login'], 'Enroll Now', ['class' => 'bag-btn bag-btn-primary']),
                ['class' => 'bag-course-summary']
            ),
            ['class' => 'bag-course-hero']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('h2', 'What You Will Learn') .
                html_writer::tag('ul', $lessonhtml, ['class' => 'bag-check-list']),
                ['class' => 'bag-detail-panel']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Course Information') .
                html_writer::tag('p', 'Hands-on sessions, guided assessment and mentor feedback designed around professional hospitality settings.'),
                ['class' => 'bag-detail-panel']
            ),
            ['class' => 'bag-course-details']
        ),
        ['class' => 'bag-landing-main']
    );
}

/**
 * Contact page markup.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_contact_page(array $urls): string {
    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('h1', 'Contact Us', ['class' => 'bag-center']) .
            html_writer::tag('div',
                html_writer::tag('aside',
                    html_writer::tag('h2', 'Contact Information') .
                    html_writer::tag('p', 'Email: info@baitalgahwa.com') .
                    html_writer::tag('p', 'Phone: +966 55 000 0000') .
                    html_writer::tag('p', 'Address: Riyadh, Saudi Arabia') .
                    html_writer::tag('h2', 'Business Time') .
                    html_writer::tag('p', 'Sunday to Thursday, 9:00 AM - 6:00 PM'),
                    ['class' => 'bag-contact-info']
                ) .
                html_writer::tag('form',
                    html_writer::tag('div',
                        html_writer::tag('input', '', ['type' => 'text', 'name' => 'firstname', 'placeholder' => 'First name']) .
                        html_writer::tag('input', '', ['type' => 'text', 'name' => 'lastname', 'placeholder' => 'Last name']),
                        ['class' => 'bag-form-row']
                    ) .
                    html_writer::tag('input', '', ['type' => 'email', 'name' => 'email', 'placeholder' => 'Email address']) .
                    html_writer::tag('input', '', ['type' => 'tel', 'name' => 'phone', 'placeholder' => 'Phone number']) .
                    html_writer::tag('select',
                        html_writer::tag('option', 'Course inquiry') .
                        html_writer::tag('option', 'Corporate training') .
                        html_writer::tag('option', 'General question'),
                        ['name' => 'subject']
                    ) .
                    html_writer::tag('textarea', '', ['name' => 'message', 'placeholder' => 'Write your message', 'rows' => 5]) .
                    html_writer::tag('button', 'Send Message', ['class' => 'bag-btn bag-btn-primary', 'type' => 'submit']),
                    ['class' => 'bag-contact-form', 'action' => (string)$urls['contact'], 'method' => 'get']
                ),
                ['class' => 'bag-contact-grid']
            ),
            ['class' => 'bag-page-section']
        ) .
        html_writer::tag('section',
            html_writer::tag('div', '', ['class' => 'bag-location-photo']) .
            html_writer::tag('div',
                html_writer::tag('p', 'Our Location', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'House of Artisans - Al Hosn Site') .
                html_writer::tag('p', 'A welcoming training space designed for practical workshops, hospitality learning and learner support.'),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column bag-location']
        ),
        ['class' => 'bag-landing-main']
    );
}

/**
 * Shared programme cards.
 *
 * @param array $urls
 * @param int $count
 * @return string
 */
function theme_baitulghawa_programme_cards(array $urls, int $count): string {
    $cards = [
        ['Coffee Barista Specialist', 'bag-card-img-1', '6 Weeks'],
        ['Barista Art Basics', 'bag-card-img-2', '4 Weeks'],
        ['Certified Customer Hospitality', 'bag-card-img-3', '5 Weeks'],
        ['Certified Coffee Specialist', 'bag-card-img-4', '6 Weeks'],
        ['General Artisan', 'bag-card-img-5', '3 Weeks'],
        ['Certified Service Associate', 'bag-card-img-6', '5 Weeks'],
    ];

    $html = '';
    foreach (array_slice($cards, 0, $count) as $card) {
        $html .= html_writer::tag('article',
            html_writer::tag('a', '', ['class' => 'bag-card-image ' . $card[1], 'href' => (string)$urls['course'], 'aria-label' => $card[0]]) .
            html_writer::tag('div',
                html_writer::tag('h3', html_writer::link($urls['course'], $card[0])) .
                html_writer::tag('p', 'Build practical skills with guided instruction and workplace-focused practice.') .
                html_writer::tag('div',
                    html_writer::tag('span', $card[2]) .
                    html_writer::tag('span', 'Certificate'),
                    ['class' => 'bag-card-meta']
                ) .
                html_writer::link($urls['course'], 'View Details', ['class' => 'bag-card-link']),
                ['class' => 'bag-card-body']
            ),
            ['class' => 'bag-programme-card']
        );
    }

    return $html;
}

/**
 * CTA band.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_cta(array $urls): string {
    return html_writer::tag('section',
        html_writer::tag('h2', 'Ready To Begin Your Journey?') .
        html_writer::tag('p', 'Start learning with Bait Al Gahwa today.') .
        html_writer::link($urls['login'], 'Enroll Now', ['class' => 'bag-btn bag-btn-light']),
        ['class' => 'bag-cta']
    );
}

/**
 * Landing footer.
 *
 * @param array $urls
 * @param string $brand
 * @return string
 */
function theme_baitulghawa_landing_footer(array $urls, string $brand): string {
    return html_writer::tag('footer',
        html_writer::tag('div',
            html_writer::tag('div',
                html_writer::tag('strong', 'Bait Al Gahwa') .
                html_writer::tag('p', $brand . ' provides coffee, hospitality and artisan training for ambitious learners and teams.') .
                html_writer::tag('div',
                    html_writer::tag('span', '') . html_writer::tag('span', '') . html_writer::tag('span', ''),
                    ['class' => 'bag-socials']
                ),
                ['class' => 'bag-footer-brand']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Quick Links') .
                html_writer::link($urls['home'], 'Home') .
                html_writer::link($urls['programmes'], 'Courses') .
                html_writer::link($urls['contact'], 'Contact Us'),
                ['class' => 'bag-footer-links']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Training') .
                html_writer::link($urls['course'], 'Gahwa Specialist') .
                html_writer::link($urls['courses'], 'Moodle Courses') .
                html_writer::link($urls['login'], 'Student Login'),
                ['class' => 'bag-footer-links']
            ),
            ['class' => 'bag-footer-grid']
        ) .
        html_writer::tag('p', 'Copyright ' . date('Y') . ' Bait Al Gahwa. All rights reserved.', ['class' => 'bag-copyright']),
        ['class' => 'bag-footer']
    );
}

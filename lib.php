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

    if (theme_baitulghawa_is_landing_request() || theme_baitulghawa_is_auth_design_request()) {
        $styles .= "\n" . theme_baitulghawa_landing_chrome_reset_css();
        $landingcss = $CFG->dirroot . '/theme/baitulghawa/scss/post.scss';
        if (is_readable($landingcss)) {
            $styles .= "\n" . file_get_contents($landingcss);
        }
        $styles .= "\n" . theme_baitulghawa_landing_asset_override_css();
    }

    if ($styles === '') {
        return '';
    }

    return html_writer::tag('style', $styles, [
        'id' => 'theme-baitulghawa-inline-css',
    ]);
}

/**
 * Overrides key image assets after the full stylesheet has loaded.
 *
 * @return string
 */
function theme_baitulghawa_landing_asset_override_css(): string {
    $hero = theme_baitulghawa_asset_url('hero-workshop.png');
    $flower = theme_baitulghawa_asset_url('footer-flower.png');

    return '
        .bag-hero {
            background: linear-gradient(rgba(10, 10, 10, .58), rgba(10, 10, 10, .58)), url("' . s($hero) . '") center / cover !important;
        }
        .bag-hero::before {
            background-image: url("' . s($flower) . '") !important;
        }
    ';
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

    if (!theme_baitulghawa_is_landing_request() && !theme_baitulghawa_is_auth_design_request()) {
        return '';
    }

    $page = theme_baitulghawa_landing_page();
    $urls = theme_baitulghawa_landing_urls($PAGE->pagetype === 'login-index' || $PAGE->pagetype === 'login-signup');

    $brand = theme_baitulghawa_landing_brand();
    $nav = theme_baitulghawa_landing_nav($urls, $page, $brand);
    $footer = theme_baitulghawa_landing_footer($urls, $brand);

    $content = '';
    if (theme_baitulghawa_is_auth_design_request()) {
        $content = $PAGE->pagetype === 'login-signup'
            ? theme_baitulghawa_register_page($urls)
            : theme_baitulghawa_login_page($urls);
    } else if ($page === 'about') {
        $content = theme_baitulghawa_about_page($urls);
    } else if ($page === 'programmes') {
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
 * Detects custom auth pages.
 *
 * @return bool
 */
function theme_baitulghawa_is_auth_design_request(): bool {
    global $PAGE;

    if (empty($PAGE)) {
        return false;
    }

    if ($PAGE->pagetype === 'login-signup') {
        return true;
    }

    return $PAGE->pagetype === 'login-index' && optional_param('baglogin', 0, PARAM_BOOL);
}

/**
 * Returns validated landing page key.
 *
 * @return string
 */
function theme_baitulghawa_landing_page(): string {
    $page = optional_param('bagpage', 'home', PARAM_ALPHA);
    $allowedpages = ['home', 'about', 'programmes', 'course', 'contact'];

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
        'about' => new moodle_url($basepath, ['bagpage' => 'about']),
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
    $logo = theme_baitulghawa_asset_url('logo-dark.png');
    $items = [
        'home' => 'Home',
        'about' => 'About Us',
        'programmes' => 'Training Program',
        'contact' => 'Contact Us',
    ];

    $links = '';
    foreach ($items as $key => $label) {
        $target = $urls[$key];
        $active = $page === $key;
        $links .= html_writer::link($target, $label, [
            'class' => 'bag-nav-link' . ($active ? ' is-active' : ''),
        ]);
    }

    return html_writer::tag('header',
        html_writer::tag('a', html_writer::empty_tag('img', [
            'src' => $logo,
            'alt' => $brand,
        ]), [
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

    $programmes = theme_baitulghawa_programme_cards(3);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Bait Al Gahwa', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'Empower Your Future With Excellence') .
                html_writer::tag('p', 'Join Bait Al Gahwa\'s premium training programs and unlock your potential through world-class education inspired by Emirati heritage.') .
                html_writer::tag('div',
                    html_writer::link($urls['programmes'], 'Explore Course', ['class' => 'bag-btn bag-btn-gold']) .
                    html_writer::link($urls['signup'], 'Register', ['class' => 'bag-btn bag-btn-ghost']),
                    ['class' => 'bag-hero-actions']
                ),
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
 * About page markup without the training programme cards.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_about_page(array $urls): string {
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

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('div', '', ['class' => 'bag-collage-main']) .
                html_writer::tag('div', '', ['class' => 'bag-collage-small bag-collage-beans']) .
                html_writer::tag('div', '', ['class' => 'bag-collage-small bag-collage-cup']),
                ['class' => 'bag-collage']
            ) .
            html_writer::tag('div',
                html_writer::tag('p', 'About Us', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'Expertise Across All Disciplines') .
                html_writer::tag('p', 'Bait Al Gahwa empowers professionals through quality training and development programmes inspired by Emirati heritage.') .
                html_writer::tag('ul', $stathtml, ['class' => 'bag-stats']),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column bag-about-page']
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
                html_writer::tag('div', theme_baitulghawa_programme_cards(0), ['class' => 'bag-programme-grid bag-programme-grid-wide']),
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
    $outcomes = [
        'Practical mastery of traditional Gahwa preparation',
        'Full readiness to lead formal, authentic Gahwa experiences',
        'Qualification to pursue roles like Gahwa Specialist, Host, or Certified Instructor',
    ];

    $outcomehtml = '';
    foreach ($outcomes as $outcome) {
        $outcomehtml .= html_writer::tag('li', $outcome);
    }

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('aside',
                    html_writer::tag('div',
                        html_writer::tag('span', 'Available', ['class' => 'bag-course-status']),
                        ['class' => 'bag-course-card-image']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('strong', 'Fees are free') .
                        html_writer::link($urls['login'], 'Enroll Now', ['class' => 'bag-course-enroll']) .
                        html_writer::tag('span', 'Limited seats available', ['class' => 'bag-course-seats']),
                        ['class' => 'bag-course-card-body']
                    ),
                    ['class' => 'bag-course-card']
                ) .
                html_writer::tag('div',
                    html_writer::tag('h1', 'Certified Gahwa Specialist') .
                    html_writer::tag('p', 'A hands-on advanced course that dives into traditional Gahwa making skills, including roasting and brewing. Tailored for those looking to master Gahwa craftsmanship.') .
                    html_writer::tag('div',
                        html_writer::tag('span', '15/03/2026') .
                        html_writer::tag('span', 'end on 17/03/2026'),
                        ['class' => 'bag-course-dates']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('div', html_writer::tag('span', 'Duration') . html_writer::tag('strong', '8 Days')) .
                        html_writer::tag('div', html_writer::tag('span', 'Languages') . html_writer::tag('strong', 'Arabic')) .
                        html_writer::tag('div', html_writer::tag('span', 'Location') . html_writer::tag('strong', 'Abu Dhabi')),
                        ['class' => 'bag-course-facts']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('span', '', ['class' => 'bag-course-avatar']) .
                        html_writer::tag('strong', 'Gahwa Specialist,<br>Dari Al Gahwa Host') .
                        html_writer::link($urls['contact'], 'More and enroll', ['class' => 'bag-course-more']),
                        ['class' => 'bag-course-teacher']
                    ),
                    ['class' => 'bag-course-summary']
                ),
                ['class' => 'bag-course-hero-inner']
            ),
            ['class' => 'bag-course-hero']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('h2', 'Program Outcome') .
                html_writer::tag('ul', $outcomehtml, ['class' => 'bag-course-outcome-list']),
                ['class' => 'bag-course-outcomes']
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
    $contactitems = [
        ['mail', 'Email', 'BaitAlGahwa@DCTAbuDhabi.ae'],
        ['phone', 'Phone', '+971 2 444 0444'],
        ['instagram', 'Instagram', 'abudhabiculture'],
        ['location', 'Location', 'Abu Dhabi<br>United Arab Emirates'],
    ];

    $contacthtml = '';
    foreach ($contactitems as $item) {
        $contacthtml .= html_writer::tag('div',
            html_writer::tag('span', '', ['class' => 'bag-contact-icon bag-contact-icon-' . $item[0]]) .
            html_writer::tag('div',
                html_writer::tag('strong', $item[1]) .
                html_writer::tag('span', $item[2])
            ),
            ['class' => 'bag-contact-item']
        );
    }

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('aside',
                    html_writer::tag('h2', 'Contact Information') .
                    html_writer::tag('div', $contacthtml, ['class' => 'bag-contact-list']) .
                    html_writer::tag('div',
                        html_writer::tag('h2', 'Response Time') .
                        html_writer::tag('p', 'We typically respond within 24-48 hours during weekdays. For urgent rescue situations, please call or WhatsApp directly.') .
                        html_writer::tag('p', 'Usually active during UAE business hours'),
                        ['class' => 'bag-response-time']
                    ),
                    ['class' => 'bag-contact-info']
                ) .
                html_writer::tag('form',
                    html_writer::tag('h2', 'Send Us a Message') .
                    html_writer::tag('div',
                        theme_baitulghawa_contact_field('text', 'firstname', 'First Name*', 'First Name*', 'user') .
                        theme_baitulghawa_contact_field('text', 'lastname', 'Last Name', 'Last Name*', 'user'),
                        ['class' => 'bag-form-row']
                    ) .
                    theme_baitulghawa_contact_field('email', 'email', 'Your Email', 'Your Email', 'paper-plane') .
                    theme_baitulghawa_contact_field('tel', 'phone', 'Phone Number *', 'Phone Number', 'phone') .
                    html_writer::tag('label',
                        html_writer::tag('span', 'Inquiry Type') .
                        html_writer::tag('select',
                            html_writer::tag('option', 'Select Inquiry Type') .
                            html_writer::tag('option', 'Course inquiry') .
                            html_writer::tag('option', 'Corporate training') .
                            html_writer::tag('option', 'General question'),
                            ['name' => 'subject']
                        ),
                        ['class' => 'bag-contact-field']
                    ) .
                    html_writer::tag('label',
                        html_writer::tag('span', 'Message') .
                        html_writer::tag('span',
                            html_writer::tag('textarea', '', ['name' => 'message', 'placeholder' => 'Write Message...', 'rows' => 5]) .
                            html_writer::tag('i', '', ['class' => 'bag-contact-form-icon bag-contact-form-icon-message']),
                            ['class' => 'bag-contact-input']
                        ),
                        ['class' => 'bag-contact-field']
                    ) .
                    html_writer::tag('p', 'Note: This form is for demonstration purposes. For actual inquiries, please contact us via email at BaitAlGahwa@DCTAbuDhabi.ae', ['class' => 'bag-contact-note']) .
                    html_writer::tag('button', 'Send Message', ['class' => 'bag-btn bag-btn-primary', 'type' => 'submit']),
                    ['class' => 'bag-contact-form', 'action' => (string)$urls['contact'], 'method' => 'get']
                ),
                ['class' => 'bag-contact-grid']
            ),
            ['class' => 'bag-page-section bag-contact-page']
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
 * Contact form field markup.
 *
 * @param string $type
 * @param string $name
 * @param string $label
 * @param string $placeholder
 * @param string $icon
 * @return string
 */
function theme_baitulghawa_contact_field(string $type, string $name, string $label, string $placeholder, string $icon): string {
    return html_writer::tag('label',
        html_writer::tag('span', $label) .
        html_writer::tag('span',
            html_writer::tag('input', '', ['type' => $type, 'name' => $name, 'placeholder' => $placeholder]) .
            html_writer::tag('i', '', ['class' => 'bag-contact-form-icon bag-contact-form-icon-' . $icon]),
            ['class' => 'bag-contact-input']
        ),
        ['class' => 'bag-contact-field']
    );
}

/**
 * Custom login page.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_login_page(array $urls): string {
    $token = '';
    if (class_exists('\core\session\manager')) {
        $token = \core\session\manager::get_login_token();
    }

    $hidden = html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'logintoken',
        'value' => $token,
    ]);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('h1', 'Login to Your Account') .
                html_writer::tag('form',
                    $hidden .
                    theme_baitulghawa_auth_field('text', 'username', 'Your Email', 'Your Email', 'paper-plane') .
                    theme_baitulghawa_auth_field('password', 'password', 'Password*', 'Password*', 'lock', true) .
                    html_writer::tag('div',
                        html_writer::tag('label',
                            html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'rememberusername', 'value' => '1']) .
                            html_writer::tag('span', 'Remember me')
                        ) .
                        html_writer::link(new moodle_url('/login/forgot_password.php'), 'Forgot password?'),
                        ['class' => 'bag-auth-row']
                    ) .
                    html_writer::tag('button', 'Login', ['class' => 'bag-auth-submit', 'type' => 'submit']) .
                    html_writer::tag('p', 'Don\'t have an account? ' . html_writer::link($urls['signup'], 'Signup'), ['class' => 'bag-auth-switch']) .
                    theme_baitulghawa_password_toggle_script(),
                    ['class' => 'bag-auth-form', 'action' => (string)$urls['login'], 'method' => 'post']
                ),
                ['class' => 'bag-auth-card bag-login-card']
            ),
            ['class' => 'bag-auth-section']
        ),
        ['class' => 'bag-landing-main bag-auth-main']
    );
}

/**
 * Custom registration page.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_register_page(array $urls): string {
    $action = new moodle_url('/login/signup.php');
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstname = trim(optional_param('firstname', '', PARAM_TEXT));
        $lastname = trim(optional_param('lastname', '', PARAM_TEXT));
        $email = trim(optional_param('email', '', PARAM_RAW_TRIMMED));
        $password = optional_param('password', '', PARAM_RAW);
        $password2 = optional_param('password2', '', PARAM_RAW);
        $errors = [];

        if ($firstname === '' || $lastname === '') {
            $errors[] = 'First name and last name are required.';
        }
        if (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address, for example name@example.com.';
        }
        if ($password !== $password2) {
            $errors[] = 'Password and confirm password must match.';
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,15}$/', $password)) {
            $errors[] = 'Password must be 8-15 characters and include lowercase, uppercase, number, and special character.';
        }

        if (empty($errors)) {
            $errors[] = 'Your details look valid. If this still appears, please check Moodle email self-registration and SMTP settings.';
        }

        $message = html_writer::tag('div', implode(' ', $errors), ['class' => 'bag-auth-alert', 'role' => 'alert']);
    }

    $hidden = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => '_qf__login_signup_form', 'value' => '1']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'username', 'value' => '']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'email2', 'value' => '']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'city', 'value' => 'Riyadh']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'country', 'value' => 'SA']);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('h1', 'Register') .
                html_writer::tag('form',
                    $hidden .
                    $message .
                    html_writer::tag('div',
                        theme_baitulghawa_auth_field('text', 'firstname', 'First Name*', 'First Name*', 'user') .
                        theme_baitulghawa_auth_field('text', 'middlename', 'Middle Name', 'Middle Name', 'user') .
                        theme_baitulghawa_auth_field('text', 'lastname', 'Last Name*', 'Last Name*', 'user'),
                        ['class' => 'bag-auth-three']
                    ) .
                    theme_baitulghawa_auth_field('email', 'email', 'Your Email', 'Your Email', 'paper-plane') .
                    theme_baitulghawa_auth_field('password', 'password', 'Password*', 'Password*', 'lock', true) .
                    html_writer::tag('p', 'Password must be 8-15 characters and include 1 lowercase letter, 1 uppercase letter, 1 number, and 1 special character.', ['class' => 'bag-password-note']) .
                    theme_baitulghawa_auth_field('password', 'password2', 'Confirm Password*', 'Confirm Password*', 'lock', true) .
                    html_writer::tag('button', 'Register', ['class' => 'bag-auth-submit', 'type' => 'submit']) .
                    html_writer::tag('p', 'Already have an account? ' . html_writer::link($urls['login'], 'Login'), ['class' => 'bag-auth-switch']) .
                    html_writer::link($urls['home'], 'Back', ['class' => 'bag-auth-back']),
                    ['class' => 'bag-auth-form bag-register-form', 'action' => (string)$action, 'method' => 'post']
                ) .
                html_writer::script("
                    document.querySelectorAll('.bag-register-form').forEach(function(form) {
                        form.addEventListener('submit', function(event) {
                            var email = form.querySelector('[name=\"email\"]');
                            var username = form.querySelector('[name=\"username\"]');
                            var email2 = form.querySelector('[name=\"email2\"]');
                            var password = form.querySelector('[name=\"password\"]');
                            var password2 = form.querySelector('[name=\"password2\"]');
                            var alert = form.querySelector('.bag-auth-alert');
                            if (!alert) {
                                alert = document.createElement('div');
                                alert.className = 'bag-auth-alert';
                                alert.setAttribute('role', 'alert');
                                form.insertBefore(alert, form.firstChild.nextSibling);
                            }
                            var emailValue = email ? email.value.trim() : '';
                            var validEmail = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(emailValue);
                            if (!validEmail) {
                                event.preventDefault();
                                alert.textContent = 'Please enter a valid email address, for example name@example.com.';
                                return;
                            }
                            if (password && password2 && password.value !== password2.value) {
                                event.preventDefault();
                                alert.textContent = 'Password and confirm password must match.';
                                return;
                            }
                            var strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,15}$/.test(password ? password.value : '');
                            if (!strongPassword) {
                                event.preventDefault();
                                alert.textContent = 'Password must be 8-15 characters and include lowercase, uppercase, number, and special character.';
                                return;
                            }
                            if (email && username) {
                                var safeusername = emailValue.split('@')[0].toLowerCase().replace(/[^a-z0-9._-]/g, '');
                                username.value = safeusername || ('user' + Date.now());
                            }
                            if (email && email2) {
                                email2.value = emailValue;
                            }
                        });
                    });
                ") .
                theme_baitulghawa_password_toggle_script(),
                ['class' => 'bag-auth-card bag-register-card']
            ),
            ['class' => 'bag-auth-section']
        ),
        ['class' => 'bag-landing-main bag-auth-main']
    );
}

/**
 * Password visibility toggle for custom auth pages.
 *
 * @return string
 */
function theme_baitulghawa_password_toggle_script(): string {
    return html_writer::tag('script', "
        (function() {
            if (window.bagPasswordToggleReady) {
                return;
            }
            window.bagPasswordToggleReady = true;
            document.addEventListener('click', function(event) {
                var button = event.target.closest('.bag-password-toggle');
                if (!button) {
                    return;
                }
                event.preventDefault();
                var field = button.closest('.bag-auth-input');
                var input = field ? field.querySelector('input[type=\"password\"], input[type=\"text\"]') : null;
                if (!input) {
                    return;
                }
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.setAttribute('aria-pressed', showing ? 'false' : 'true');
                button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        })();
    ");
}

/**
 * Shared auth input.
 *
 * @param string $type
 * @param string $name
 * @param string $label
 * @param string $placeholder
 * @param string $icon
 * @param bool $toggle
 * @return string
 */
function theme_baitulghawa_auth_field(string $type, string $name, string $label, string $placeholder, string $icon, bool $toggle = false): string {
    $attributes = [
        'type' => $type,
        'name' => $name,
        'placeholder' => $placeholder,
    ];
    if ($name !== 'middlename') {
        $attributes['required'] = 'required';
    }

    $iconhtml = $toggle
        ? html_writer::tag('button', html_writer::tag('i', '', ['class' => 'bag-auth-icon bag-auth-icon-' . $icon]), [
            'class' => 'bag-password-toggle',
            'type' => 'button',
            'aria-label' => 'Show password',
            'aria-pressed' => 'false',
        ])
        : html_writer::tag('i', '', ['class' => 'bag-auth-icon bag-auth-icon-' . $icon]);

    return html_writer::tag('label',
        html_writer::tag('span', $label, ['class' => 'bag-auth-label']) .
        html_writer::tag('span',
            html_writer::empty_tag('input', $attributes) .
            $iconhtml,
            ['class' => 'bag-auth-input']
        ),
        ['class' => 'bag-auth-field']
    );
}

/**
 * Shared programme cards.
 *
 * @param array $urls
 * @param int $count
 * @return string
 */
function theme_baitulghawa_programme_cards(int $count): string {
    $courses = theme_baitulghawa_get_public_courses($count);

    if (empty($courses)) {
        return html_writer::tag('p', 'Training programmes will appear here as soon as courses are published.', [
            'class' => 'bag-empty-programmes',
        ]);
    }

    $html = '';
    foreach ($courses as $course) {
        $html .= html_writer::tag('article',
            html_writer::tag('a', '', [
                'class' => 'bag-card-image',
                'href' => (string)$course['url'],
                'aria-label' => $course['name'],
                'style' => 'background-image: url("' . s($course['image']) . '");',
            ]) .
            html_writer::tag('div',
                html_writer::tag('h3', html_writer::link($course['url'], $course['name'])) .
                html_writer::tag('p', $course['summary']) .
                html_writer::tag('div',
                    html_writer::tag('span', $course['category']) .
                    html_writer::tag('span', 'Course'),
                    ['class' => 'bag-card-meta']
                ) .
                html_writer::link($course['url'], 'View Details', ['class' => 'bag-card-link']),
                ['class' => 'bag-card-body']
            ),
            ['class' => 'bag-programme-card']
        );
    }

    return $html;
}

/**
 * Gets visible Moodle courses for the public training programme cards.
 *
 * @param int $limit Zero means all visible courses.
 * @return array
 */
function theme_baitulghawa_get_public_courses(int $limit = 0): array {
    global $DB, $SITE;

    $params = ['siteid' => $SITE->id, 'visible' => 1];
    $records = $DB->get_records_select(
        'course',
        'id <> :siteid AND visible = :visible',
        $params,
        'sortorder ASC, fullname ASC',
        'id, fullname, shortname, summary, summaryformat, category',
        0,
        $limit > 0 ? $limit : 0
    );

    $courses = [];
    foreach ($records as $record) {
        $courses[] = [
            'name' => format_string($record->fullname),
            'summary' => theme_baitulghawa_course_summary($record),
            'category' => theme_baitulghawa_course_category_name((int)$record->category),
            'url' => new moodle_url('/login/index.php', ['bagpage' => 'course', 'courseid' => $record->id]),
            'image' => theme_baitulghawa_course_image_url($record),
        ];
    }

    return $courses;
}

/**
 * Builds a short card summary from the course summary.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_summary(stdClass $course): string {
    $summary = trim(strip_tags(format_text($course->summary, $course->summaryformat, ['noclean' => true, 'para' => false])));
    if ($summary === '') {
        return 'Build practical skills with guided instruction and workplace-focused practice.';
    }

    return shorten_text($summary, 115);
}

/**
 * Gets the category name for a course.
 *
 * @param int $categoryid
 * @return string
 */
function theme_baitulghawa_course_category_name(int $categoryid): string {
    global $DB;

    if ($categoryid <= 0) {
        return 'Training';
    }

    $category = $DB->get_record('course_categories', ['id' => $categoryid], 'name', IGNORE_MISSING);
    return $category ? format_string($category->name) : 'Training';
}

/**
 * Uses the course image when available, otherwise falls back to supplied artwork.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_image_url(stdClass $course): string {
    global $CFG;

    $context = context_course::instance($course->id, IGNORE_MISSING);
    if ($context) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'sortorder ASC, id ASC', false);
        foreach ($files as $file) {
            $mimetype = (string)$file->get_mimetype();
            if ($file->is_valid_image() || strpos($mimetype, 'image/') === 0) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }
    }

    return theme_baitulghawa_asset_url('training-screen.png');
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
    $logo = theme_baitulghawa_asset_url('logo.png');
    $flower = theme_baitulghawa_asset_url('footer-flower.png');

    return html_writer::tag('footer',
        html_writer::tag('div',
            html_writer::tag('div',
                html_writer::empty_tag('img', [
                    'class' => 'bag-footer-logo',
                    'src' => $logo,
                    'alt' => $brand,
                ]) .
                html_writer::tag('p', 'Empowering professionals through quality training and development programs inspired by Emirati heritage.') .
                html_writer::tag('div',
                    html_writer::tag('span', '') . html_writer::tag('span', '') . html_writer::tag('span', ''),
                    ['class' => 'bag-socials']
                ),
                ['class' => 'bag-footer-brand']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Quick Links') .
                html_writer::link($urls['home'], 'Home') .
                html_writer::link($urls['home'], 'About Us') .
                html_writer::link($urls['programmes'], 'Training Program') .
                html_writer::link($urls['contact'], 'Contact Us'),
                ['class' => 'bag-footer-links']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Programs') .
                html_writer::tag('span', 'Leadership Training') .
                html_writer::tag('span', 'Digital Transformation') .
                html_writer::tag('span', 'Professional Development') .
                html_writer::tag('span', 'Customer Service'),
                ['class' => 'bag-footer-links']
            ),
            ['class' => 'bag-footer-grid']
        ) .
        html_writer::tag('p', '&#169; 2026 Bait Al Gahwa Training Platform. All rights reserved. | Developed by Acusync Technology', ['class' => 'bag-copyright']),
        ['class' => 'bag-footer', 'style' => '--bag-footer-flower: url("' . s($flower) . '");']
    );
}

/**
 * Returns a theme pix asset URL.
 *
 * @param string $filename
 * @return string
 */
function theme_baitulghawa_asset_url(string $filename): string {
    global $CFG;

    $path = $CFG->dirroot . '/theme/baitulghawa/pix/' . $filename;
    $version = is_readable($path) ? filemtime($path) : time();

    return rtrim($CFG->wwwroot, '/') . '/theme/baitulghawa/pix/' . rawurlencode($filename) . '?v=' . $version;
}

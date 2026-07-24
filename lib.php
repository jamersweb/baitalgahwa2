<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the compiled SCSS for the Bait Al Gahwa Academy theme.
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
    $html = theme_baitulghawa_moodle_language_guard();

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

    if ($styles !== '') {
        $html .= html_writer::tag('style', $styles, [
            'id' => 'theme-baitulghawa-inline-css',
        ]);
    }

    return $html;
}

/**
 * Keeps Moodle application pages in English when public pages are viewed in Arabic.
 *
 * Moodle's built-in lang URL parameter is global for the session. The public
 * theme uses baglang instead, but this guard recovers existing Arabic sessions
 * when users move back into Moodle dashboard, courses, reports or administration.
 *
 * @return string
 */
function theme_baitulghawa_moodle_language_guard(): string {
    global $PAGE;

    if (empty($PAGE) || theme_baitulghawa_is_landing_request() || theme_baitulghawa_is_auth_design_request()) {
        return '';
    }

    if (strpos(current_language(), 'ar') !== 0) {
        return '';
    }

    if (optional_param('lang', '', PARAM_ALPHA) === 'en') {
        return '';
    }

    $englishurl = new moodle_url($PAGE->url);
    $englishurl->remove_params(['baglang']);
    $englishurl->param('lang', 'en');

    return html_writer::tag('script', "
        (function() {
            window.location.replace(" . json_encode((string)$englishurl) . ");
        })();
    ", ['id' => 'theme-baitulghawa-moodle-language-guard']);
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
        return theme_baitulghawa_academy_label_script();
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
    } else if ($page === 'certificate') {
        $content = theme_baitulghawa_certificate_page($urls);
    } else if ($page === 'contact') {
        $content = theme_baitulghawa_contact_page($urls);
    } else {
        $content = theme_baitulghawa_home_page($urls);
    }

    return theme_baitulghawa_academy_label_script() . html_writer::tag('div', $nav . $content . $footer, [
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
    $allowedpages = ['home', 'about', 'programmes', 'course', 'certificate', 'contact'];

    return in_array($page, $allowedpages, true) ? $page : 'home';
}

/**
 * Returns the public landing language without changing Moodle's global session language.
 *
 * @return string
 */
function theme_baitulghawa_landing_language(): string {
    $language = optional_param('baglang', 'en', PARAM_ALPHA);

    return $language === 'ar' ? 'ar' : 'en';
}

/**
 * Landing URLs. Login-page base is used when Moodle force-login redirects home.
 *
 * @param bool $useloginbase
 * @return array
 */
function theme_baitulghawa_landing_urls(bool $useloginbase): array {
    $basepath = $useloginbase ? '/login/index.php' : '/';
    $languageparams = theme_baitulghawa_landing_language() === 'ar' ? ['baglang' => 'ar'] : [];

    return [
        'home' => new moodle_url($basepath, $languageparams),
        'about' => new moodle_url($basepath, $languageparams + ['bagpage' => 'about']),
        'programmes' => new moodle_url($basepath, $languageparams + ['bagpage' => 'programmes']),
        'course' => new moodle_url($basepath, $languageparams + ['bagpage' => 'course']),
        'certificate' => new moodle_url($basepath, $languageparams + ['bagpage' => 'certificate']),
        'contact' => new moodle_url($basepath, $languageparams + ['bagpage' => 'contact']),
        'login' => new moodle_url('/login/index.php', $languageparams + ['baglogin' => 1]),
        'signup' => new moodle_url('/login/signup.php', $languageparams),
        'courses' => new moodle_url('/course/index.php'),
    ];
}

/**
 * Public brand text.
 *
 * @return string
 */
function theme_baitulghawa_landing_brand(): string {
    return 'Bait Al Gahwa Academy';
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
        'home' => 'Academy Home',
        'about' => 'About the Academy',
        'programmes' => 'Programme Catalogue',
        'certificate' => 'Certificate Preview',
        'contact' => 'Support',
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
            theme_baitulghawa_language_switcher() .
            html_writer::link($urls['login'], 'Sign in', ['class' => 'bag-login']) .
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
        ['Preserve', 'Safeguard Emirati Gahwa as living heritage'],
        ['Practice', 'Build capability through guided learning'],
        ['Standardise', 'Apply approved tools, methods and etiquette'],
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
                html_writer::tag('h1', 'Learn the heritage. Practice the standards. Carry it forward.') .
                html_writer::tag('p', 'Welcome to Bait Al Gahwa Academy, the learning platform dedicated to the heritage, preparation and serving etiquette of Emirati Gahwa.') .
                html_writer::tag('div',
                    html_writer::link($urls['programmes'], 'Explore Programmes', ['class' => 'bag-btn bag-btn-gold']) .
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
                html_writer::tag('p', 'Academy purpose', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Preserving the knowledge, skills and etiquette of Emirati Gahwa.') .
                html_writer::tag('p', 'Bait Al Gahwa Academy combines cultural knowledge, guided practice and assessment to prepare learners to deliver the Bait Al Gahwa experience with authenticity, care and respect.') .
                html_writer::tag('ul', $stathtml, ['class' => 'bag-stats']),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Programme catalogue', ['class' => 'bag-eyebrow bag-center']) .
                html_writer::tag('h2', 'Learning pathways for Emirati Gahwa practice', ['class' => 'bag-center']) .
                html_writer::tag('div', $programmes, ['class' => 'bag-programme-grid']) .
                html_writer::tag('div', html_writer::link($urls['programmes'], 'View Programme Catalogue', ['class' => 'bag-btn bag-btn-outline']), ['class' => 'bag-center']),
                ['class' => 'bag-section-inner']
            ),
            ['class' => 'bag-section bag-programmes-band']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Standards-led learning', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Practical learning rooted in respect, generosity and care') .
                html_writer::tag('ul',
                    html_writer::tag('li', 'Heritage and values introduced before technical preparation') .
                    html_writer::tag('li', 'Approved tools, ingredients, measurements and sequence') .
                    html_writer::tag('li', 'Serving etiquette and majlis practice explained clearly') .
                    html_writer::tag('li', 'Knowledge checks and practical assessment where applicable'),
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
        ['Respect', 'For guests, the majlis, equipment and standards'],
        ['Generosity', 'Hospitality through attentive preparation and service'],
        ['Care', 'Faithful practice with precision and cultural confidence'],
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
                html_writer::tag('p', 'About Bait Al Gahwa Academy', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'The standards-led learning platform for Emirati Gahwa') .
                html_writer::tag('p', 'Bait Al Gahwa is the custodian and approved reference for the Emirati Gahwa experience and its standards. The Academy builds knowledge and capability through standards-led learning, guided practice and professional development, helping preserve Emirati Gahwa as living heritage that is practiced and passed on to future generations.') .
                html_writer::tag('ul', $stathtml, ['class' => 'bag-stats']),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column bag-about-page']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Learning principles', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Preserve, practice, standardise and share') .
                html_writer::tag('ul',
                    html_writer::tag('li', 'Cultural meaning and values come before beverage language') .
                    html_writer::tag('li', 'Technical content traces to approved Emirati Gahwa standards') .
                    html_writer::tag('li', 'Achievement is recognised only through approved learning and assessment routes') .
                    html_writer::tag('li', 'Learning supports practitioners, trainers, operators, schools and the wider community'),
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
    $activecategory = optional_param('bagcategory', '', PARAM_ALPHANUMEXT);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('p', 'Programme Catalogue', ['class' => 'bag-eyebrow bag-center']) .
            html_writer::tag('h1', 'Explore learning pathways for Emirati Gahwa', ['class' => 'bag-center']) .
            html_writer::tag('p', 'Explore learning pathways designed to build cultural knowledge, practical skill and confidence in the preparation and serving of Emirati Gahwa.', ['class' => 'bag-page-intro bag-center']) .
                theme_baitulghawa_programme_category_filters($urls['programmes'], $activecategory) .
                html_writer::tag('div', theme_baitulghawa_programme_cards(0, $activecategory), ['class' => 'bag-programme-grid bag-programme-grid-wide']),
            ['class' => 'bag-page-section']
        ),
        ['class' => 'bag-landing-main']
    );
}

/**
 * Certificate preview page for management demos.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_certificate_page(array $urls): string {
    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Certificate Preview', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h1', 'Certificate preview') .
                html_writer::tag('p', 'A polished sample of the learner recognition moment for completed Academy programmes, ready to present to management.') .
                html_writer::link($urls['programmes'], 'View Programme Catalogue', ['class' => 'bag-btn bag-btn-gold']),
                ['class' => 'bag-certificate-copy']
            ) .
            html_writer::tag('div',
                html_writer::tag('div',
                    html_writer::tag('span', '', ['class' => 'bag-certificate-seal', 'aria-hidden' => 'true']) .
                    html_writer::tag('div',
                        html_writer::tag('span', 'Bait Al Gahwa Academy') .
                        html_writer::tag('strong', 'Certificate of Completion'),
                        ['class' => 'bag-certificate-heading']
                    ) .
                    html_writer::tag('p', 'This certifies that') .
                    html_writer::tag('h2', 'Learner Name') .
                    html_writer::tag('p', 'has successfully completed the') .
                    html_writer::tag('h3', 'Emirati Gahwa Practitioner Pathway') .
                    html_writer::tag('div',
                        html_writer::tag('span', 'Issued 24 July 2026') .
                        html_writer::tag('span', 'Credential ID BAG-MOCK-2026'),
                        ['class' => 'bag-certificate-meta']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('div',
                            html_writer::tag('span', 'Academy Director') .
                            html_writer::tag('strong', 'Authorised Signature')
                        ) .
                        html_writer::tag('div',
                            html_writer::tag('span', 'Department of Culture and Tourism - Abu Dhabi') .
                            html_writer::tag('strong', 'Issuing Authority')
                        ),
                        ['class' => 'bag-certificate-signatures']
                    ),
                    ['class' => 'bag-certificate-paper']
                ),
                ['class' => 'bag-certificate-preview', 'aria-label' => 'Mock certificate preview']
            ),
            ['class' => 'bag-page-section bag-certificate-section']
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
    $courseid = optional_param('courseid', 0, PARAM_INT);
    $course = theme_baitulghawa_get_public_course($courseid);

    if (!$course) {
        return html_writer::tag('main',
            html_writer::tag('section',
                html_writer::tag('p', 'Programme Catalogue', ['class' => 'bag-eyebrow bag-center']) .
                html_writer::tag('h1', 'This programme is no longer available', ['class' => 'bag-center']) .
                html_writer::tag('p', 'It may have been unpublished or removed by the Academy team. Please choose from the current published programmes.', ['class' => 'bag-page-intro bag-center']) .
                html_writer::tag('div', html_writer::link($urls['programmes'], 'View Programme Catalogue', ['class' => 'bag-btn bag-btn-gold']), ['class' => 'bag-center']),
                ['class' => 'bag-page-section']
            ),
            ['class' => 'bag-landing-main']
        );
    }

    $name = format_string($course->fullname);
    $summary = theme_baitulghawa_course_summary($course);
    $category = theme_baitulghawa_course_category_name((int)$course->category);
    $level = theme_baitulghawa_course_level_label($course);
    $duplicatenote = theme_baitulghawa_course_duplicate_note($course);
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $image = theme_baitulghawa_course_image_url($course);
    $fallbackimage = theme_baitulghawa_course_fallback_image_url((int)$course->id);
    $dates = theme_baitulghawa_course_date_label($course);

    $outcomes = [
        'Review the course content published by the Academy team',
        'Complete the activities, checks and assessments assigned in Moodle',
        'Track your progress through the official learning platform',
        'Continue with the latest course version maintained by administrators',
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
                        html_writer::tag('span', 'Published programme', ['class' => 'bag-course-status']),
                        [
                            'class' => 'bag-course-card-image',
                            'style' => theme_baitulghawa_background_image_style($image, $fallbackimage),
                        ]
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('strong', $category) .
                        html_writer::link($courseurl, 'Open Course', ['class' => 'bag-course-enroll']) .
                        html_writer::tag('span', 'This course is loaded from the current Moodle course catalogue.', ['class' => 'bag-course-seats']),
                        ['class' => 'bag-course-card-body']
                    ),
                    ['class' => 'bag-course-card']
                ) .
                html_writer::tag('div',
                    html_writer::tag('h1', $name) .
                    html_writer::tag('p', $summary) .
                    html_writer::tag('div',
                        html_writer::tag('span', $category) .
                        html_writer::tag('span', $dates),
                        ['class' => 'bag-course-dates']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('div', html_writer::tag('span', 'Course') . html_writer::tag('strong', format_string($course->shortname ?: $course->fullname))) .
                        html_writer::tag('div', html_writer::tag('span', 'Category') . html_writer::tag('strong', $category)) .
                        html_writer::tag('div', html_writer::tag('span', 'Level') . html_writer::tag('strong', $level)),
                        ['class' => 'bag-course-facts']
                    ) .
                    $duplicatenote .
                    html_writer::tag('div',
                        html_writer::tag('span', '', ['class' => 'bag-course-avatar']) .
                        html_writer::tag('strong', 'Academy Course<br>Official Moodle content') .
                        html_writer::link($courseurl, 'Continue', ['class' => 'bag-course-more']),
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
                html_writer::tag('h2', 'By the end of this programme, you will be able to:') .
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
        ['mail', 'Academy Support', 'BaitAlGahwa@DCTAbuDhabi.ae'],
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
                    html_writer::tag('h2', 'Academy Support') .
                    html_writer::tag('div', $contacthtml, ['class' => 'bag-contact-list']) .
                    html_writer::tag('div',
                        html_writer::tag('h2', 'How we can help') .
                        html_writer::tag('p', 'Tell us what you need help with. Include the programme name and a screenshot where possible so the Academy Support team can assist you efficiently.') .
                        html_writer::tag('p', 'Support is reviewed during UAE business hours.'),
                        ['class' => 'bag-response-time']
                    ),
                    ['class' => 'bag-contact-info']
                ) .
                html_writer::tag('form',
                    html_writer::tag('h2', 'Contact Academy Support') .
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
                            html_writer::tag('option', 'Programme enquiry') .
                            html_writer::tag('option', 'Practical session support') .
                            html_writer::tag('option', 'Certificate or completion record') .
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
                    html_writer::tag('p', 'For urgent account or access support, contact Academy Support by email and include your programme name.', ['class' => 'bag-contact-note']) .
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
                html_writer::tag('p', 'Learning location', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'House of Artisans - Al Hosn Site') .
                html_writer::tag('p', 'A setting for guided practice, cultural learning and respectful engagement with Emirati Gahwa standards.'),
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
                html_writer::tag('h1', 'Sign in to continue your learning') .
                html_writer::tag('p', 'Learn the heritage. Practice the standards. Carry it forward.', ['class' => 'bag-auth-intro']) .
                html_writer::tag('form',
                    $hidden .
                    theme_baitulghawa_auth_field('text', 'username', 'Email or username', 'Email or username', 'paper-plane') .
                    theme_baitulghawa_auth_field('password', 'password', 'Password*', 'Password*', 'lock', true) .
                    html_writer::tag('div',
                        html_writer::tag('label',
                            html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'rememberusername', 'value' => '1']) .
                            html_writer::tag('span', 'Remember me')
                        ) .
                        html_writer::link(new moodle_url('/login/forgot_password.php'), 'Forgot password?'),
                        ['class' => 'bag-auth-row']
                    ) .
                    html_writer::tag('button', 'Sign in', ['class' => 'bag-auth-submit', 'type' => 'submit']) .
                    html_writer::tag('p', 'Having trouble signing in? Contact Academy Support.', ['class' => 'bag-auth-switch']) .
                    html_writer::tag('p', 'Need an account? ' . html_writer::link($urls['signup'], 'Register'), ['class' => 'bag-auth-switch']) .
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
    $countries = get_string_manager()->get_list_of_countries();
    $countryselect = html_writer::tag('label',
        html_writer::tag('span', 'Country*', ['class' => 'bag-auth-label']) .
        html_writer::tag('span',
            html_writer::select($countries, 'country', 'SA', null, ['class' => 'bag-auth-select']),
            ['class' => 'bag-auth-input']
        ),
        ['class' => 'bag-auth-field']
    );

    $hidden = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => '_qf__login_signup_form', 'value' => '1']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitbutton', 'value' => 'Create my new account']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'auth', 'value' => 'email']);

    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('h1', 'Register for Bait Al Gahwa Academy') .
                html_writer::tag('p', 'Create your learner profile to access assigned programmes and Academy messages.', ['class' => 'bag-auth-intro']) .
                html_writer::tag('form',
                    $hidden .
                    theme_baitulghawa_auth_field('text', 'username', 'Username*', 'Username*', 'user') .
                    html_writer::tag('div',
                        theme_baitulghawa_auth_field('text', 'firstname', 'First Name*', 'First Name*', 'user') .
                        theme_baitulghawa_auth_field('text', 'middlename', 'Middle Name', 'Middle Name', 'user') .
                        theme_baitulghawa_auth_field('text', 'lastname', 'Last Name*', 'Last Name*', 'user'),
                        ['class' => 'bag-auth-three']
                    ) .
                    theme_baitulghawa_auth_field('email', 'email', 'Email address*', 'Email address*', 'paper-plane') .
                    theme_baitulghawa_auth_field('email', 'email2', 'Email again*', 'Email again*', 'paper-plane') .
                    html_writer::tag('div',
                        theme_baitulghawa_auth_field('text', 'city', 'City/town*', 'City/town*', 'user') .
                        $countryselect,
                        ['class' => 'bag-auth-two']
                    ) .
                    theme_baitulghawa_auth_field('password', 'password', 'Password*', 'Password*', 'lock', true) .
                    html_writer::tag('p', 'Password must be 8-15 characters and include 1 lowercase letter, 1 uppercase letter, 1 number, and 1 special character.', ['class' => 'bag-password-note']) .
                    theme_baitulghawa_auth_field('password', 'password2', 'Confirm Password*', 'Confirm Password*', 'lock', true) .
                    html_writer::tag('button', 'Register', ['class' => 'bag-auth-submit', 'type' => 'submit']) .
                    html_writer::tag('p', 'Already have an account? ' . html_writer::link($urls['login'], 'Sign in'), ['class' => 'bag-auth-switch']) .
                    html_writer::link($urls['home'], 'Back', ['class' => 'bag-auth-back']) .
                    theme_baitulghawa_password_toggle_script(),
                    ['class' => 'bag-auth-form bag-register-form', 'action' => (string)$action, 'method' => 'post']
                ),
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
function theme_baitulghawa_programme_cards(int $count, string $categoryslug = ''): string {
    $courses = theme_baitulghawa_get_public_courses(0);

    if ($categoryslug !== '') {
        $courses = array_values(array_filter($courses, static function(array $course) use ($categoryslug): bool {
            return $course['categoryslug'] === $categoryslug;
        }));
    }

    if ($count > 0) {
        $courses = array_slice($courses, 0, $count);
    }

    if (empty($courses)) {
        return html_writer::tag('p', 'No learning programmes are currently published. Browse again later or contact Academy Support if you believe a programme is missing.', [
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
                'style' => theme_baitulghawa_background_image_style($course['image'], $course['fallbackimage']),
            ]) .
            html_writer::tag('div',
                html_writer::tag('h3', html_writer::link($course['url'], $course['name'])) .
                html_writer::tag('p', $course['summary']) .
                html_writer::tag('div',
                    html_writer::tag('span', $course['category']) .
                    html_writer::tag('span', $course['level']),
                    ['class' => 'bag-card-meta']
                ) .
                (!empty($course['duplicate_note'])
                    ? html_writer::tag('p', $course['duplicate_note'], ['class' => 'bag-duplicate-note'])
                    : '') .
                html_writer::link($course['url'], 'View Programme', ['class' => 'bag-card-link']),
                ['class' => 'bag-card-body']
            ),
            ['class' => 'bag-programme-card']
        );
    }

    return $html;
}

/**
 * Visible category filters for the public programme catalogue.
 *
 * @param moodle_url $baseurl
 * @param string $activecategory
 * @return string
 */
function theme_baitulghawa_programme_category_filters(moodle_url $baseurl, string $activecategory): string {
    $courses = theme_baitulghawa_get_public_courses(0);
    if (empty($courses)) {
        return '';
    }

    $categories = [];
    foreach ($courses as $course) {
        $categories[$course['categoryslug']] = $course['category'];
    }

    asort($categories, SORT_NATURAL | SORT_FLAG_CASE);

    $filters = html_writer::link($baseurl, 'All levels', [
        'class' => 'bag-category-filter' . ($activecategory === '' ? ' is-active' : ''),
    ]);

    foreach ($categories as $slug => $label) {
        $url = new moodle_url($baseurl, ['bagcategory' => $slug]);
        $filters .= html_writer::link($url, $label, [
            'class' => 'bag-category-filter' . ($activecategory === $slug ? ' is-active' : ''),
        ]);
    }

    return html_writer::tag('div', $filters, [
        'class' => 'bag-category-filters',
        'aria-label' => 'Programme category filters',
    ]);
}

/**
 * Gets visible Moodle courses for the public training programme cards.
 *
 * Courses are ordered by the latest updates first so newly added/published
 * items appear in the custom landing sections without manual sorting.
 *
 * @param int $limit Zero means all visible courses.
 * @return array
 */
function theme_baitulghawa_get_public_courses(int $limit = 0): array {
    global $DB;

    $params = [];
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.category
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
             WHERE " . theme_baitulghawa_public_course_conditions($params);
    $records = $DB->get_records_sql($sql . ' ORDER BY c.timemodified DESC, c.id DESC, c.sortorder ASC, c.fullname ASC', $params);

    $courses = [];
    foreach ($records as $record) {
        $key = theme_baitulghawa_course_duplicate_key(format_string($record->fullname));
        if (isset($courses[$key])) {
            $courses[$key] = theme_baitulghawa_merge_public_course_cards($courses[$key], $record);
            continue;
        }

        $category = theme_baitulghawa_course_category_name((int)$record->category);
        $courses[$key] = [
            'id' => (int)$record->id,
            'name' => format_string($record->fullname),
            'summary' => theme_baitulghawa_course_summary($record),
            'category' => $category,
            'categoryslug' => theme_baitulghawa_slug($category),
            'level' => theme_baitulghawa_course_level_label($record),
            'url' => new moodle_url('/course/view.php', ['id' => $record->id]),
            'image' => theme_baitulghawa_course_image_url($record),
            'fallbackimage' => theme_baitulghawa_course_fallback_image_url((int)$record->id),
            'duplicateids' => [(int)$record->id],
            'duplicate_note' => '',
        ];
    }

    $courses = array_values($courses);

    return $limit > 0 ? array_slice($courses, 0, $limit) : $courses;
}

/**
 * Merges duplicate public course cards while keeping the richest visible summary.
 *
 * Logical duplicates are grouped only for catalogue display. Moodle course
 * records remain untouched so administrators can review the split safely.
 *
 * @param array $existing
 * @param stdClass $record
 * @return array
 */
function theme_baitulghawa_merge_public_course_cards(array $existing, stdClass $record): array {
    $summary = theme_baitulghawa_course_summary($record);
    if (core_text::strlen($summary) > core_text::strlen($existing['summary'])) {
        $existing['summary'] = $summary;
    }

    $existing['duplicateids'][] = (int)$record->id;
    $existing['duplicate_note'] = 'Duplicate Moodle course entries with the same normalized title are shown here as one programme. Useful content is preserved in the source courses for administrator review.';

    return $existing;
}

/**
 * Gets one published Moodle course for the public course page.
 *
 * @param int $courseid
 * @return stdClass|null
 */
function theme_baitulghawa_get_public_course(int $courseid): ?stdClass {
    global $DB;

    if ($courseid <= 0) {
        return null;
    }

    $params = ['courseid' => $courseid];
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.category, c.startdate, c.enddate
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
             WHERE c.id = :courseid
               AND " . theme_baitulghawa_public_course_conditions($params);

    $course = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
    return $course ?: null;
}

/**
 * Shared SQL conditions for courses that should appear in the public catalogue.
 *
 * @param array $params
 * @return string
 */
function theme_baitulghawa_public_course_conditions(array &$params): string {
    global $DB, $SITE;

    $params += [
        'siteid' => $SITE->id,
        'visible' => 1,
        'categoryvisible' => 1,
    ];

    $conditions = [
        'c.id <> :siteid',
        'c.visible = :visible',
        'cc.visible = :categoryvisible',
    ];

    $columns = $DB->get_columns('course');
    if (isset($columns['deletioninprogress'])) {
        $params['deletioninprogress'] = 0;
        $conditions[] = 'c.deletioninprogress = :deletioninprogress';
    }

    return implode("\n               AND ", $conditions);
}

/**
 * Formats course start/end dates for the public course page.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_date_label(stdClass $course): string {
    if (!empty($course->startdate) && !empty($course->enddate)) {
        return userdate($course->startdate, get_string('strftimedate', 'langconfig')) . ' - ' .
            userdate($course->enddate, get_string('strftimedate', 'langconfig'));
    }

    if (!empty($course->startdate)) {
        return 'Starts ' . userdate($course->startdate, get_string('strftimedate', 'langconfig'));
    }

    return 'Available now';
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
        return 'Build cultural knowledge, practical skill and confidence in the preparation and serving of Emirati Gahwa.';
    }

    return shorten_text($summary, 115);
}

/**
 * Normalizes course names for duplicate catalogue grouping.
 *
 * @param string $name
 * @return string
 */
function theme_baitulghawa_course_duplicate_key(string $name): string {
    $key = core_text::strtolower($name);
    $key = preg_replace('/\b(copy|duplicate|version|draft|final|old|new|v\d+)\b/u', ' ', $key);
    $key = preg_replace('/[\(\[\{].*?[\)\]\}]/u', ' ', $key);
    $key = preg_replace('/[^a-z0-9]+/u', ' ', $key);
    $key = trim(preg_replace('/\s+/u', ' ', $key));

    return $key !== '' ? $key : core_text::strtolower($name);
}

/**
 * Builds a stable URL/category slug.
 *
 * @param string $text
 * @return string
 */
function theme_baitulghawa_slug(string $text): string {
    $slug = core_text::strtolower($text);
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'learning-pathway';
}

/**
 * Derives a learner-facing level from course category/name metadata.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_level_label(stdClass $course): string {
    $category = theme_baitulghawa_course_category_name((int)$course->category);
    $haystack = core_text::strtolower($category . ' ' . format_string($course->fullname) . ' ' . format_string($course->shortname ?? ''));

    if (strpos($haystack, 'foundation') !== false || strpos($haystack, 'intro') !== false || strpos($haystack, 'beginner') !== false) {
        return 'Foundation';
    }

    if (strpos($haystack, 'trainer') !== false || strpos($haystack, 'instructor') !== false) {
        return 'Trainer';
    }

    if (strpos($haystack, 'school') !== false || strpos($haystack, 'community') !== false) {
        return 'Schools & Community';
    }

    if (strpos($haystack, 'advanced') !== false || strpos($haystack, 'practitioner') !== false || strpos($haystack, 'professional') !== false) {
        return 'Practitioner';
    }

    return $category !== '' ? $category : 'Learning Pathway';
}

/**
 * Explains duplicate course handling on the public course detail page.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_duplicate_note(stdClass $course): string {
    global $DB;

    $params = [];
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.category
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
             WHERE " . theme_baitulghawa_public_course_conditions($params);
    $records = $DB->get_records_sql($sql, $params);
    $currentkey = theme_baitulghawa_course_duplicate_key(format_string($course->fullname));
    $matches = [];

    foreach ($records as $record) {
        if ((int)$record->id !== (int)$course->id && theme_baitulghawa_course_duplicate_key(format_string($record->fullname)) === $currentkey) {
            $matches[] = format_string($record->fullname);
        }
    }

    if (empty($matches)) {
        return '';
    }

    return html_writer::tag(
        'p',
        'Duplicate review: matching Moodle course records are treated as the same public programme because their normalized names match. Separate source entries remain available to administrators so useful content is not removed without review.',
        ['class' => 'bag-course-duplicate-note']
    );
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
        return 'Learning Pathway';
    }

    $category = $DB->get_record('course_categories', ['id' => $categoryid], 'name', IGNORE_MISSING);
    return $category ? format_string($category->name) : 'Learning Pathway';
}

/**
 * Uses the course image when available, otherwise falls back to supplied artwork.
 *
 * @param stdClass $course
 * @return string
 */
function theme_baitulghawa_course_image_url(stdClass $course): string {
    $context = context_course::instance($course->id, IGNORE_MISSING);
    if ($context) {
        foreach (['overviewfiles', 'summary'] as $filearea) {
            $imageurl = theme_baitulghawa_course_file_area_image_url($context, $filearea);
            if ($imageurl !== '') {
                return $imageurl;
            }
        }
    }

    return theme_baitulghawa_course_fallback_image_url((int)$course->id);
}

/**
 * Builds a layered CSS background declaration with a guaranteed fallback image.
 *
 * @param string $image
 * @param string $fallbackimage
 * @return string
 */
function theme_baitulghawa_background_image_style(string $image, string $fallbackimage): string {
    return 'background-image: url("' . s($image) . '"), url("' . s($fallbackimage) . '");';
}

/**
 * Gets the first image from a Moodle course file area.
 *
 * @param context_course $context
 * @param string $filearea
 * @return string
 */
function theme_baitulghawa_course_file_area_image_url(context_course $context, string $filearea): string {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'course', $filearea, false, 'sortorder ASC, id ASC', false);

    foreach ($files as $file) {
        $imageurl = theme_baitulghawa_stored_image_url($file);
        if ($imageurl !== '') {
            return $imageurl;
        }
    }

    return '';
}

/**
 * Converts a stored image file to a public-safe URL for landing cards.
 *
 * @param stored_file $file
 * @return string
 */
function theme_baitulghawa_stored_image_url(stored_file $file): string {
    $mimetype = (string)$file->get_mimetype();
    if (!$file->is_valid_image() && strpos($mimetype, 'image/') !== 0) {
        return '';
    }

    if (theme_baitulghawa_is_landing_request() || theme_baitulghawa_is_auth_design_request()) {
        $datauri = theme_baitulghawa_course_image_data_uri($file);
        if ($datauri !== '') {
            return $datauri;
        }
    }

    return moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    )->out(false);
}

/**
 * Provides stable theme artwork when an admin image is not attached to a course.
 *
 * @param int $courseid
 * @return string
 */
function theme_baitulghawa_course_fallback_image_url(int $courseid): string {
    $fallbacks = [
        'training-screen.png',
        'course-training-screen.png',
        'course-training-screen-alt.png',
        'coffee-beans-bag.png',
        'instructor.png',
        'coffee-roast.png',
    ];

    $index = $courseid > 0 ? $courseid % count($fallbacks) : 0;
    return theme_baitulghawa_asset_url($fallbacks[$index]);
}

/**
 * Converts a course overview image into an embeddable public card image.
 *
 * Course overview pluginfile URLs can be blocked for unauthenticated visitors
 * on login/public pages. Embedding the approved overview image keeps the public
 * catalogue aligned with the course image selected by administrators.
 *
 * @param stored_file $file
 * @return string
 */
function theme_baitulghawa_course_image_data_uri(stored_file $file): string {
    $mimetype = (string)$file->get_mimetype();
    if (strpos($mimetype, 'image/') !== 0) {
        return '';
    }

    if ($file->get_filesize() > 25 * 1024 * 1024) {
        return '';
    }

    try {
        $content = $file->get_content();
    } catch (Throwable $exception) {
        return '';
    }

    if ($content === false || $content === '') {
        return '';
    }

    return 'data:' . $mimetype . ';base64,' . base64_encode($content);
}

/**
 * CTA band.
 *
 * @param array $urls
 * @return string
 */
function theme_baitulghawa_cta(array $urls): string {
    return html_writer::tag('section',
        html_writer::tag('h2', 'Continue your learning with Bait Al Gahwa Academy') .
        html_writer::tag('p', 'Review the standards, prepare for your next practical session and carry the practice forward with care.') .
        html_writer::link($urls['login'], 'Sign in', ['class' => 'bag-btn bag-btn-light']),
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
                html_writer::tag('p', 'Bait Al Gahwa Academy supports standards-led learning for the living heritage, preparation and serving etiquette of Emirati Gahwa.') .
                html_writer::tag('div',
                    html_writer::tag('span', '') . html_writer::tag('span', '') . html_writer::tag('span', ''),
                    ['class' => 'bag-socials']
                ),
                ['class' => 'bag-footer-brand']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Quick Links') .
                html_writer::link($urls['home'], 'Academy Home') .
                html_writer::link($urls['about'], 'About the Academy') .
                html_writer::link($urls['programmes'], 'Programme Catalogue') .
                html_writer::link($urls['contact'], 'Support'),
                ['class' => 'bag-footer-links']
            ) .
            html_writer::tag('div',
                html_writer::tag('h2', 'Pathways') .
                html_writer::tag('span', 'Foundations') .
                html_writer::tag('span', 'Practitioner') .
                html_writer::tag('span', 'Trainer') .
                html_writer::tag('span', 'Schools & Community'),
                ['class' => 'bag-footer-links']
            ),
            ['class' => 'bag-footer-grid']
        ) .
        html_writer::tag('p', '&#169; 2026 Bait Al Gahwa Academy | Department of Culture and Tourism - Abu Dhabi | Privacy | Accessibility | Terms | Support', ['class' => 'bag-copyright']),
        ['class' => 'bag-footer', 'style' => '--bag-footer-flower: url("' . s($flower) . '");']
    );
}

/**
 * Visible language links for the Academy public pages.
 *
 * @return string
 */
function theme_baitulghawa_language_switcher(): string {
    global $PAGE;

    $currenturl = !empty($PAGE->url) ? $PAGE->url : new moodle_url('/');
    $englishurl = new moodle_url($currenturl);
    $arabicurl = new moodle_url($currenturl);
    $englishurl->remove_params(['lang', 'baglang']);
    $arabicurl->remove_params(['lang', 'baglang']);
    $englishurl->param('baglang', 'en');
    $arabicurl->param('baglang', 'ar');

    return html_writer::tag('span',
        html_writer::link($englishurl, 'English') .
        html_writer::tag('span', '|', ['aria-hidden' => 'true']) .
        html_writer::link($arabicurl, 'العربية'),
        ['class' => 'bag-language-switcher', 'aria-label' => 'Language selector']
    );
}

/**
 * Re-labels learner-facing Moodle strings to match the Academy terminology.
 *
 * Site administrators should still configure permanent language customisations
 * in Moodle. This fallback keeps the themed learner experience aligned where
 * core strings are still using default Moodle wording.
 *
 * @return string
 */
function theme_baitulghawa_academy_label_script(): string {
    global $PAGE;

    if (empty($PAGE) || $PAGE->pagelayout === 'admin') {
        return '';
    }

    $isarabic = theme_baitulghawa_is_landing_request() || theme_baitulghawa_is_auth_design_request()
        ? theme_baitulghawa_landing_language() === 'ar'
        : strpos(current_language(), 'ar') === 0;
    $replacements = [
        'Site home' => 'Academy Home',
        'Home' => 'Academy Home',
        'Dashboard' => 'My Learning',
        'My courses' => 'My Programmes',
        'All courses' => 'Programme Catalogue',
        'Courses' => 'Programmes',
        'Course overview' => 'Learning Overview',
        'Recently accessed courses' => 'Continue Learning',
        'Upcoming events' => 'Upcoming Learning Activities',
        'Participants' => 'Learners',
        'Teachers' => 'Academy Trainers',
        'Teacher' => 'Academy Trainer',
        'Students' => 'Learners',
        'Student' => 'Learner',
        'Grades' => 'Results',
        'Grade' => 'Result',
        'Quiz' => 'Knowledge Check',
        'Assignment' => 'Learning Activity',
        'Badges' => 'Achievements',
        'Calendar' => 'Learning Calendar',
        'Competencies' => 'Standards & Competencies',
        'Course completion' => 'Programme Progress',
        'Announcements' => 'Academy Announcements',
        'Messages' => 'Academy Messages',
        'Log in' => 'Sign in',
        'Login' => 'Sign in',
        'Log out' => 'Sign out',
        'Logout' => 'Sign out',
    ];

    $arabictranslations = [
        'Academy Home' => 'الرئيسية',
        'About the Academy' => 'عن الأكاديمية',
        'Programme Catalogue' => 'دليل البرامج',
        'Support' => 'الدعم',
        'Sign in' => 'تسجيل الدخول',
        'Register' => 'التسجيل',
        'English' => 'English',
        'العربية' => 'العربية',
        'Bait Al Gahwa' => 'بيت القهوة',
        'Learn the heritage. Practice the standards. Carry it forward.' => 'تعلّم الإرث. أتقن المعايير. وانقله للأجيال.',
        'Welcome to Bait Al Gahwa Academy, the learning platform dedicated to the heritage, preparation and serving etiquette of Emirati Gahwa.' => 'مرحباً بكم في أكاديمية بيت القهوة، المنصة التعليمية المتخصصة في تراث القهوة الإماراتية وإعدادها وسنع تقديمها.',
        'Explore Programmes' => 'استكشف البرامج',
        'Academy purpose' => 'هدف الأكاديمية',
        'Preserving the knowledge, skills and etiquette of Emirati Gahwa.' => 'حفظ معارف ومهارات وسنع القهوة الإماراتية.',
        'Bait Al Gahwa Academy combines cultural knowledge, guided practice and assessment to prepare learners to deliver the Bait Al Gahwa experience with authenticity, care and respect.' => 'تجمع أكاديمية بيت القهوة بين المعرفة الثقافية والتدريب التطبيقي والتقييم، لتأهيل المتعلمين على تقديم تجربة بيت القهوة بأصالة وعناية واحترام.',
        'Preserve' => 'الحفظ',
        'Safeguard Emirati Gahwa as living heritage' => 'صون القهوة الإماراتية بوصفها إرثاً حياً',
        'Practice' => 'الممارسة',
        'Build capability through guided learning' => 'بناء القدرات من خلال التعلم الموجّه',
        'Standardise' => 'توحيد المعايير',
        'Apply approved tools, methods and etiquette' => 'تطبيق الأدوات والأساليب والسنع المعتمدة',
        'Programme catalogue' => 'دليل البرامج',
        'Learning pathways for Emirati Gahwa practice' => 'مسارات تعلم لممارسة القهوة الإماراتية',
        'Explore learning pathways for Emirati Gahwa' => 'استكشف مسارات تعلم القهوة الإماراتية',
        'Explore learning pathways designed to build cultural knowledge, practical skill and confidence in the preparation and serving of Emirati Gahwa.' => 'استكشف المسارات التعليمية المصممة لتنمية المعرفة الثقافية والمهارات التطبيقية والثقة في إعداد القهوة الإماراتية وتقديمها.',
        'View Programme' => 'عرض البرنامج',
        'View Programme Catalogue' => 'عرض دليل البرامج',
        'Programme' => 'برنامج',
        'Standards-led learning' => 'تعلم قائم على المعايير',
        'Practical learning rooted in respect, generosity and care' => 'تعلم تطبيقي يرتكز على الاحترام والكرم والعناية',
        'Heritage and values introduced before technical preparation' => 'تقديم الإرث والقيم قبل الجوانب التقنية للإعداد',
        'Approved tools, ingredients, measurements and sequence' => 'الأدوات والمكونات والمقاييس وتسلسل الإعداد المعتمدة',
        'Serving etiquette and majlis practice explained clearly' => 'شرح سنع التقديم وممارسة المجلس بوضوح',
        'Knowledge checks and practical assessment where applicable' => 'التحقق من المعرفة والتقييم العملي عند الحاجة',
        'Continue your learning with Bait Al Gahwa Academy' => 'تابع تعلمك مع أكاديمية بيت القهوة',
        'Review the standards, prepare for your next practical session and carry the practice forward with care.' => 'راجع المعايير، واستعد لجلستك التطبيقية القادمة، وواصل نقل الممارسة بعناية.',
        'Quick Links' => 'روابط سريعة',
        'Pathways' => 'المسارات',
        'Foundations' => 'الأساسيات',
        'Practitioner' => 'الممارس',
        'Trainer' => 'المدرب',
        'Schools & Community' => 'المدارس والمجتمع',
        'Bait Al Gahwa Academy supports standards-led learning for the living heritage, preparation and serving etiquette of Emirati Gahwa.' => 'تدعم أكاديمية بيت القهوة التعلم القائم على المعايير للإرث الحي وإعداد القهوة الإماراتية وسنع تقديمها.',
        'Bait Al Gahwa Academy | Department of Culture and Tourism - Abu Dhabi | Privacy | Accessibility | Terms | Support' => 'أكاديمية بيت القهوة | دائرة الثقافة والسياحة - أبوظبي | الخصوصية | سهولة الوصول | الشروط | الدعم',
        'The standards-led learning platform for Emirati Gahwa' => 'منصة تعلم قائمة على المعايير للقهوة الإماراتية',
        'About Bait Al Gahwa Academy' => 'عن أكاديمية بيت القهوة',
        'Bait Al Gahwa is the custodian and approved reference for the Emirati Gahwa experience and its standards. The Academy builds knowledge and capability through standards-led learning, guided practice and professional development, helping preserve Emirati Gahwa as living heritage that is practiced and passed on to future generations.' => 'بيت القهوة هو الحاضن والمرجعية المعتمدة لتجربة القهوة الإماراتية ومعاييرها. وتعمل الأكاديمية على بناء المعارف والقدرات من خلال تعلم قائم على المعايير وتدريب تطبيقي وتطوير مهني، بما يسهم في حفظ القهوة الإماراتية بوصفها إرثاً حياً يمارس وينقل للأجيال.',
        'Learning principles' => 'مبادئ التعلم',
        'Preserve, practice, standardise and share' => 'الحفظ والممارسة وتوحيد المعايير والمشاركة',
        'Cultural meaning and values come before beverage language' => 'المعنى الثقافي والقيم تأتي قبل لغة المشروبات',
        'Technical content traces to approved Emirati Gahwa standards' => 'يرتبط المحتوى التقني بمعايير القهوة الإماراتية المعتمدة',
        'Achievement is recognised only through approved learning and assessment routes' => 'يتم الاعتراف بالإنجاز فقط من خلال مسارات تعلم وتقييم معتمدة',
        'Learning supports practitioners, trainers, operators, schools and the wider community' => 'يدعم التعلم الممارسين والمدربين والمشغلين والمدارس والمجتمع الأوسع',
        'Emirati Gahwa Practitioner Pathway' => 'مسار ممارس القهوة الإماراتية',
        'Learning pathway' => 'مسار تعلم',
        'Start Programme' => 'ابدأ البرنامج',
        'Assessment and recognition follow the approved Academy route.' => 'يتبع التقييم والاعتراف مسار الأكاديمية المعتمد.',
        'A standards-led programme introducing the heritage, preparation, equipment and serving etiquette of Emirati Gahwa through guided learning and practical application.' => 'برنامج قائم على المعايير يقدم تراث القهوة الإماراتية وإعدادها وأدواتها وسنع تقديمها من خلال تعلم موجه وتطبيق عملي.',
        'Blended learning' => 'تعلم مدمج',
        'Practical session required where scheduled' => 'جلسة تطبيقية مطلوبة عند جدولتها',
        'Pathway' => 'المسار',
        'Languages' => 'اللغات',
        'English and Arabic' => 'العربية والإنجليزية',
        'Recognition' => 'الاعتراف',
        'Completion record' => 'سجل إتمام',
        'Academy Trainer' => 'مدرب الأكاديمية',
        'Guided practice' => 'تدريب موجه',
        'Ask Academy Support' => 'اسأل دعم الأكاديمية',
        'By the end of this programme, you will be able to:' => 'بنهاية هذا البرنامج، ستكون قادراً على:',
        'Explain the cultural meaning, values and etiquette of Emirati Gahwa' => 'شرح المعنى الثقافي والقيم وسنع القهوة الإماراتية',
        'Identify approved traditional tools, ingredients and preparation stages' => 'تحديد الأدوات التقليدية والمكونات ومراحل الإعداد المعتمدة',
        'Apply the approved method with care, consistency and respect' => 'تطبيق الطريقة المعتمدة بعناية واتساق واحترام',
        'Prepare for knowledge checks and practical assessment where applicable' => 'الاستعداد للتحقق من المعرفة والتقييم العملي عند الحاجة',
        'Academy Support' => 'دعم الأكاديمية',
        'How we can help' => 'كيف يمكننا مساعدتك',
        'Tell us what you need help with. Include the programme name and a screenshot where possible so the Academy Support team can assist you efficiently.' => 'أخبرنا بنوع المساعدة التي تحتاج إليها. اذكر اسم البرنامج وأرفق لقطة شاشة إن أمكن، ليتمكن فريق دعم الأكاديمية من مساعدتك بكفاءة.',
        'Support is reviewed during UAE business hours.' => 'تتم مراجعة طلبات الدعم خلال ساعات العمل في دولة الإمارات.',
        'Contact Academy Support' => 'تواصل مع دعم الأكاديمية',
        'First Name*' => 'الاسم الأول*',
        'Last Name' => 'اسم العائلة',
        'Your Email' => 'بريدك الإلكتروني',
        'Phone Number *' => 'رقم الهاتف*',
        'Inquiry Type' => 'نوع الاستفسار',
        'Select Inquiry Type' => 'اختر نوع الاستفسار',
        'Programme enquiry' => 'استفسار عن برنامج',
        'Practical session support' => 'دعم جلسة تطبيقية',
        'Certificate or completion record' => 'شهادة أو سجل إتمام',
        'General question' => 'سؤال عام',
        'Message' => 'الرسالة',
        'Write Message...' => 'اكتب الرسالة...',
        'For urgent account or access support, contact Academy Support by email and include your programme name.' => 'لدعم الحساب أو الوصول العاجل، تواصل مع دعم الأكاديمية عبر البريد الإلكتروني واذكر اسم البرنامج.',
        'Send Message' => 'إرسال الرسالة',
        'Learning location' => 'موقع التعلم',
        'House of Artisans - Al Hosn Site' => 'بيت الحرفيين - موقع الحصن',
        'A setting for guided practice, cultural learning and respectful engagement with Emirati Gahwa standards.' => 'بيئة للتدريب الموجه والتعلم الثقافي والتفاعل باحترام مع معايير القهوة الإماراتية.',
        'Sign in to continue your learning' => 'سجّل الدخول لمتابعة تعلمك',
        'Email or username' => 'البريد الإلكتروني أو اسم المستخدم',
        'Password*' => 'كلمة المرور*',
        'Remember me' => 'تذكرني',
        'Forgot password?' => 'هل نسيت كلمة المرور؟',
        'Having trouble signing in? Contact Academy Support.' => 'هل تواجه صعوبة في تسجيل الدخول؟ تواصل مع دعم الأكاديمية.',
        'Need an account?' => 'تحتاج إلى حساب؟',
        'Register for Bait Al Gahwa Academy' => 'التسجيل في أكاديمية بيت القهوة',
        'Create your learner profile to access assigned programmes and Academy messages.' => 'أنشئ ملفك التعليمي للوصول إلى البرامج المسندة ورسائل الأكاديمية.',
        'Username*' => 'اسم المستخدم*',
        'Middle Name' => 'الاسم الأوسط',
        'Email address*' => 'عنوان البريد الإلكتروني*',
        'Email again*' => 'تأكيد البريد الإلكتروني*',
        'City/town*' => 'المدينة*',
        'Country*' => 'الدولة*',
        'Password must be 8-15 characters and include 1 lowercase letter, 1 uppercase letter, 1 number, and 1 special character.' => 'يجب أن تتكون كلمة المرور من 8 إلى 15 خانة وتحتوي على حرف صغير وحرف كبير ورقم ورمز خاص.',
        'Confirm Password*' => 'تأكيد كلمة المرور*',
        'Already have an account?' => 'لديك حساب بالفعل؟',
        'Back' => 'رجوع',
    ];

    $json = json_encode($replacements, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $arabicjson = json_encode($arabictranslations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $arabicflag = $isarabic ? 'true' : 'false';

    return html_writer::tag('script', "
        (function() {
            var replacements = {$json};
            var arabicTranslations = {$arabicjson};
            var isArabic = {$arabicflag};
            var selectors = [
                'a', 'button', 'span', 'p', 'li', 'strong', 'h1', 'h2', 'h3', 'h4', 'option',
                '.nav-link', '.dropdown-item', '.breadcrumb-item',
                '.card-title', '.page-header-headings h1'
            ];

            function normaliseText(text) {
                return String(text || '').replace(/\\s+/g, ' ').trim();
            }

            function translatePlaceholders(root) {
                if (!isArabic) {
                    return;
                }

                root.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(function(node) {
                    var translated = arabicTranslations[normaliseText(node.getAttribute('placeholder'))];
                    if (translated) {
                        node.setAttribute('placeholder', translated);
                    }
                });
            }

            function applyAcademyLabels(root) {
                if (!root || document.body.classList.contains('pagelayout-admin')) {
                    return;
                }

                selectors.forEach(function(selector) {
                    root.querySelectorAll(selector).forEach(function(node) {
                        if (node.children.length > 0) {
                            return;
                        }

                        var text = normaliseText(node.textContent);
                        if (Object.prototype.hasOwnProperty.call(replacements, text)) {
                            text = replacements[text];
                            node.textContent = text;
                        }

                        if (isArabic && Object.prototype.hasOwnProperty.call(arabicTranslations, text)) {
                            node.textContent = arabicTranslations[text];
                        }
                    });
                });

                translatePlaceholders(root);

                if (document.title) {
                    document.title = document.title
                        .replace(/\\bBAG Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bBayt Al Gahwa\\b/g, 'Bait Al Gahwa')
                        .replace(/\\bCoffee Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bArabic Coffee Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bBait Al Gahwa\\b(?! Academy)/g, 'Bait Al Gahwa Academy');
                    if (isArabic) {
                        document.title = document.title.replace(/Bait Al Gahwa Academy/g, 'أكاديمية بيت القهوة');
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                applyAcademyLabels(document);
                if ('MutationObserver' in window) {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) {
                                    applyAcademyLabels(node);
                                }
                            });
                        });
                    });
                    observer.observe(document.body, {childList: true, subtree: true});
                }
            });
        })();
    ", ['id' => 'theme-baitulghawa-academy-labels']);
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

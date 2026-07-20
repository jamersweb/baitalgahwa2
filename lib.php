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
        ['Practise', 'Build capability through guided learning'],
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
                html_writer::tag('h1', 'Learn the heritage. Practise the standards. Carry it forward.') .
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
                html_writer::tag('p', 'Bait Al Gahwa is the custodian and approved reference for the Emirati Gahwa experience and its standards. The Academy builds knowledge and capability through standards-led learning, guided practice and professional development, helping preserve Emirati Gahwa as living heritage that is practised and passed on to future generations.') .
                html_writer::tag('ul', $stathtml, ['class' => 'bag-stats']),
                ['class' => 'bag-section-copy']
            ),
            ['class' => 'bag-section bag-two-column bag-about-page']
        ) .
        html_writer::tag('section',
            html_writer::tag('div',
                html_writer::tag('p', 'Learning principles', ['class' => 'bag-eyebrow']) .
                html_writer::tag('h2', 'Preserve, practise, standardise and share') .
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
    return html_writer::tag('main',
        html_writer::tag('section',
            html_writer::tag('p', 'Programme Catalogue', ['class' => 'bag-eyebrow bag-center']) .
            html_writer::tag('h1', 'Explore learning pathways for Emirati Gahwa', ['class' => 'bag-center']) .
            html_writer::tag('p', 'Explore learning pathways designed to build cultural knowledge, practical skill and confidence in the preparation and serving of Emirati Gahwa.', ['class' => 'bag-page-intro bag-center']) .
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
        'Explain the cultural meaning, values and etiquette of Emirati Gahwa',
        'Identify approved traditional tools, ingredients and preparation stages',
        'Apply the approved method with care, consistency and respect',
        'Prepare for knowledge checks and practical assessment where applicable',
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
                        html_writer::tag('span', 'Learning pathway', ['class' => 'bag-course-status']),
                        ['class' => 'bag-course-card-image']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('strong', 'Standards-led learning') .
                        html_writer::link($urls['login'], 'Start Programme', ['class' => 'bag-course-enroll']) .
                        html_writer::tag('span', 'Assessment and recognition follow the approved Academy route.', ['class' => 'bag-course-seats']),
                        ['class' => 'bag-course-card-body']
                    ),
                    ['class' => 'bag-course-card']
                ) .
                html_writer::tag('div',
                    html_writer::tag('h1', 'Emirati Gahwa Practitioner Pathway') .
                    html_writer::tag('p', 'A standards-led programme introducing the heritage, preparation, equipment and serving etiquette of Emirati Gahwa through guided learning and practical application.') .
                    html_writer::tag('div',
                        html_writer::tag('span', 'Blended learning') .
                        html_writer::tag('span', 'Practical session required where scheduled'),
                        ['class' => 'bag-course-dates']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('div', html_writer::tag('span', 'Pathway') . html_writer::tag('strong', 'Foundations')) .
                        html_writer::tag('div', html_writer::tag('span', 'Languages') . html_writer::tag('strong', 'English and Arabic')) .
                        html_writer::tag('div', html_writer::tag('span', 'Recognition') . html_writer::tag('strong', 'Completion record')),
                        ['class' => 'bag-course-facts']
                    ) .
                    html_writer::tag('div',
                        html_writer::tag('span', '', ['class' => 'bag-course-avatar']) .
                        html_writer::tag('strong', 'Academy Trainer<br>Guided practice') .
                        html_writer::link($urls['contact'], 'Ask Academy Support', ['class' => 'bag-course-more']),
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
                html_writer::tag('p', 'Learn the heritage. Practise the standards. Carry it forward.', ['class' => 'bag-auth-intro']) .
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
function theme_baitulghawa_programme_cards(int $count): string {
    $courses = theme_baitulghawa_get_public_courses($count);

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
                'style' => 'background-image: url("' . s($course['image']) . '");',
            ]) .
            html_writer::tag('div',
                html_writer::tag('h3', html_writer::link($course['url'], $course['name'])) .
                html_writer::tag('p', $course['summary']) .
                html_writer::tag('div',
                    html_writer::tag('span', $course['category']) .
                    html_writer::tag('span', 'Programme'),
                    ['class' => 'bag-card-meta']
                ) .
                html_writer::link($course['url'], 'View Programme', ['class' => 'bag-card-link']),
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
 * Courses are ordered by the latest updates first so newly added/published
 * items appear in the custom landing sections without manual sorting.
 *
 * @param int $limit Zero means all visible courses.
 * @return array
 */
function theme_baitulghawa_get_public_courses(int $limit = 0): array {
    global $DB, $SITE;

    $params = ['siteid' => $SITE->id, 'visible' => 1, 'categoryvisible' => 1];
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.category
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
             WHERE c.id <> :siteid
               AND c.visible = :visible
               AND cc.visible = :categoryvisible";
    $records = $DB->get_records_sql(
        $sql . ' ORDER BY c.timemodified DESC, c.id DESC, c.sortorder ASC, c.fullname ASC',
        $params,
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
        return 'Build cultural knowledge, practical skill and confidence in the preparation and serving of Emirati Gahwa.';
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
    $englishurl->param('lang', 'en');
    $arabicurl->param('lang', 'ar');

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

    $json = json_encode($replacements, JSON_UNESCAPED_SLASHES);

    return html_writer::tag('script', "
        (function() {
            var replacements = {$json};
            var selectors = [
                'a', 'button', 'span', 'h1', 'h2', 'h3', 'h4',
                '.nav-link', '.dropdown-item', '.breadcrumb-item',
                '.card-title', '.page-header-headings h1'
            ];

            function applyAcademyLabels(root) {
                if (!root || document.body.classList.contains('pagelayout-admin')) {
                    return;
                }

                selectors.forEach(function(selector) {
                    root.querySelectorAll(selector).forEach(function(node) {
                        if (node.children.length > 0) {
                            return;
                        }

                        var text = node.textContent.trim();
                        if (Object.prototype.hasOwnProperty.call(replacements, text)) {
                            node.textContent = replacements[text];
                        }
                    });
                });

                if (document.title) {
                    document.title = document.title
                        .replace(/\\bBAG Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bBayt Al Gahwa\\b/g, 'Bait Al Gahwa')
                        .replace(/\\bCoffee Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bArabic Coffee Academy\\b/g, 'Bait Al Gahwa Academy')
                        .replace(/\\bBait Al Gahwa\\b(?! Academy)/g, 'Bait Al Gahwa Academy');
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

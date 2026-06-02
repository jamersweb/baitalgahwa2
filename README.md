# Baitulghawa Moodle Theme

Baitulghawa is a custom Moodle theme built as a child theme of Boost.

## Install with Git

Clone this repository directly inside the Moodle theme directory:

```bash
cd /path/to/moodle/theme
git clone https://github.com/jamersweb/baitalgahwa2.git baitulghawa
```

Then open Moodle as an administrator and complete the plugin upgrade screen:

```text
https://your-site.example/admin/index.php?cache=0
```

Select the theme from:

```text
Site administration > Appearance > Themes > Theme selector
```

## Update

Future updates do not need copy/paste. Pull directly from the live theme folder:

```bash
cd /path/to/moodle/theme/baitulghawa
git pull origin main
```

Then purge caches from Moodle:

```text
Site administration > Development > Purge caches
```

## Customisation

Most visual work lives in `scss/pre.scss` and `scss/post.scss`.

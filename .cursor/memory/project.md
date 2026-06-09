# Project facts

## Site

- **URL:** https://proauc.ru
- **Repo:** https://github.com/Voynere/proauc-seo (private)
- **Purpose:** SEO and development workspace for the WordPress site proauc.ru (auto import from Japan/Korea/China, spec техника, HDM catalog)

## Stack

- WordPress + custom theme **proautospec** (Picostrap 5 / Bootstrap 5.3)
- SEO: **Rank Math** + **Rank Math Pro**, custom filters in `wp-content/themes/proautospec/rank-math.php`
- Forms: Contact Form 7, amoCRM integration, Yandex captcha, honeypot
- Content: ACF / ACF Pro, Admin Columns
- Performance: Autoptimize
- Other: Cyr2Lat, Breadcrumb NavXT, WP Telegram, HandL UTM Grabber, db-robotstxt

## Catalog pages (WordPress page IDs)

SEO titles/descriptions for catalog routes are loaded from DB tables via `rank-math.php`:

| Page ID | Purpose |
|---------|---------|
| 45, 46, 48, 51 | Country catalog (korea/china/japan) — mark/model URLs |
| 40, 41, 43 | HDM (spec техника) groups/types |

Tables: `wp_api_vendors`, `wp_api_models`, `wp_api_hdm_groups`, `wp_api_hdm_types`.

## API

- `/api/` on production serves car catalog JSON (korea/china/japan endpoints)
- Theme JS: `wp-content/themes/proautospec/js/api/*.js`
- **Not in Git** — sync from server separately (~296 MB)

## Assets

- `/assets/` on production (~2.5 GB static) — **not in Git**, sync separately

## Git exclusions

See root `.gitignore`: wp-config.php, .htaccess, uploads, cache, assets/, api/

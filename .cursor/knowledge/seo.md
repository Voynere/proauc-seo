# SEO architecture

## Plugins

- **seo-by-rank-math** + **seo-by-rank-math-pro** — primary SEO plugin
- **db-robotstxt** — robots.txt management (also `robots.txt` in site root)
- Theme override: `wp-content/themes/proautospec/rank-math.php`

## Dynamic SEO (catalog)

For catalog pages, Rank Math title/description/H1 come from MySQL, not post meta:

- Country routes (`country`, `mark`, `model` query vars) → `wp_api_vendors`, `wp_api_models`
- HDM routes (`hdm-group`, `hdm-type`) → `wp_api_hdm_groups`, `wp_api_hdm_types`

Key filters in `rank-math.php`:

- `rank_math/frontend/title`
- `rank_math/frontend/description`
- Custom H1/content filters for catalog templates

## Sitemaps

Root files: `sitemap_japan.xml`, `sitemap_korea.xml`, `sitemap_china.xml`  
`robots.txt` references `https://proauc.ru/sitemap.xml`

## robots.txt highlights

- Disallow `/wp-`, search, feeds, xmlrpc, UTM params
- Yandex: `Clean-Param` for filters (year, mileage, category, etc.)
- Allow static assets and `admin-ajax.php`

## Theme SEO files

- `wp-content/themes/proautospec/seo.csv` — SEO data reference
- `wp-content/themes/proautospec/rank-math-old.php` — legacy, check before editing

## UTM / analytics

- Plugin: **handl-utm-grabber**
- Cloudflare headers used for real IP in theme (`HTTP_CF_CONNECTING_IP`)

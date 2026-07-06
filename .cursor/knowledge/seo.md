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

- `wp-content/themes/proautospec/rank-math.php` — main SEO filters
- `wp-content/themes/proautospec/inc/blog-seo.php` — blog CTA, FAQ, JSON-LD helpers
- `wp-content/themes/proautospec/inc/blog-articles.php` — seed content (waves 1–4)
- `wp-content/themes/proautospec/seo.csv` — SEO data reference
- `wp-content/themes/proautospec/rank-math-old.php` — legacy, check before editing

## Blog (P3)

- URL base: `/blog/` (`category_base=blog`)
- Seeds: `proauc_blog_seed_v1` … `v4`, migrations `proauc_blog_dates_v1`, thumbnails `proauc_blog_thumbnail`
- Clusters: `yaponiya`, `koreya`, `kitaj`, `spectehnika`, `mototsikly`, `obzory`, `kejsy`
- Schema: `CollectionPage` + `ItemList` on archive; `BlogPosting` + `BreadcrumbList` + `FAQPage` on single
- Static `/blog/` title/description in `proauc_get_static_landing_seo()`
- `category.php` — cluster archives with post cards (not catalog `archive.php`)
- Admin: **Яндекс Метрика** (пункт меню слева, ACF) — OAuth-токен API, ID счётчика, кнопка «Проверить API»

## UTM / analytics

- Plugin: **handl-utm-grabber**
- Cloudflare headers used for real IP in theme (`HTTP_CF_CONNECTING_IP`)

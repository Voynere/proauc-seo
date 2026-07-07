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
- `wp-content/themes/proautospec/inc/blog-articles.php` — seed content (waves 1–5)
- `wp-content/themes/proautospec/seo.csv` — SEO data reference
- `wp-content/themes/proautospec/rank-math-old.php` — legacy, check before editing

## Blog (P3)

- URL base: `/blog/` (`category_base=blog`)
- Seeds: `proauc_blog_seed_v1` … `v5`, schedule migrations `proauc_blog_wave4_schedule_v1`, `proauc_blog_wave5_schedule_v1`
- Clusters: `yaponiya`, `koreya`, `kitaj`, `spectehnika`, `mototsikly`, `obzory`, `kejsy`
- Schema: `CollectionPage` + `ItemList` on archive; `BlogPosting` + `BreadcrumbList` + `FAQPage` on single
- Static `/blog/` title/description in `proauc_get_static_landing_seo()`
- `category.php` — cluster archives with post cards (not catalog `archive.php`)
- Admin: **Яндекс Метрика** — top-level WP menu (ACF options page, pattern like ferma-dv «Яндекс Директ»)
  - Slug: `yandex-metrika-settings`
  - Fields: `metrika_oauth_token`, `metrika_counter_id` (default `98962652`)
  - Helpers: `proauc_get_metrika_oauth_token()`, `proauc_metrika_health_check()`
  - Fallback wp-config: `PROAUC_METRIKA_OAUTH_TOKEN`, `PROAUC_METRIKA_COUNTER_ID`
  - File: `inc/seo-settings.php`

## Sitemap lots

- `generate_sitemap_lots()` in `functions.php` — default **340** lots per country (korea/china/japan)
- Regenerate: `https://proauc.ru/?sitemap-lots-create=1` → `sitemap_lots.xml` (~517 URL as of 06.07.2026)
- Full sitemap: `https://proauc.ru/?sitemap-create=1`

## Position monitoring

- **Service:** [seo.smyalichi.ru](https://seo.smyalichi.ru) — SEO-кабинет Smyalichi (свой сервис, не Topvisor)
- **CSV in `seov/`:** `semantic-core-high.csv` (215), `semantic-core-dv.csv` (112 ДВ), `semantic-core.csv` (508)
- **Baseline:** 30.06.2026 — compare weekly TOP-3/10/30
- **Regions:** Владивосток, Хабаровск, Благовещенск, Южно-Сахалинск, Якутск

## UTM / analytics

- Plugin: **handl-utm-grabber**
- Cloudflare headers used for real IP in theme (`HTTP_CF_CONNECTING_IP`)

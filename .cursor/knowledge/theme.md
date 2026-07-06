# Theme proautospec

## Location

`wp-content/themes/proautospec/`

Based on **Picostrap 5** (Bootstrap 5.3.3). Version in `style.css`.

## Bootstrap

```
inc/
  theme-settings.php    — defaults
  setup.php             — theme supports
  clean-head.php        — remove WP bloat
  enqueues.php          — CSS/JS
  content-filtering.php — LC plugin compat
  bootstrap-navwalker.php
  editor.php
  pagination.php
  template-tags.php
  widgets.php
```

## Key templates

| File | Role |
|------|------|
| `front-page.php` | Homepage |
| `page-40.php` … `page-51.php` | Catalog / landing pages |
| `page-45.php` | Japan catalog (`/avto-iz-yaponii/catalog/…`) |
| `page-48.php` | Korea catalog — **other dev** (slug `-SERIES`) |
| `page-hdm-lot.php`, `page-moto-lot.php` | Lot detail |
| `page-spectehnika.php`, `page-motorcycles.php` | Sections |
| `rank-math.php` | SEO filters (required) |
| `home.php`, `single.php`, `category.php` | Blog list, post, cluster archive |
| `inc/blog-seo.php` | Blog bootstrap, CTA, FAQ, JSON-LD |
| `inc/blog-articles.php` | P3 seed articles (waves 1–5) |
| `inc/seo-settings.php` | Admin «Яндекс Метрика» (ACF), API token |

## Frontend JS API clients

`js/api/` — catalog filters and data:

- `cars-catalog-japan.js`, `cars-catalog-korea.js`, `cars-catalog-china.js`
- `cars-catalog.js`, `cars-catalog-filter.js`
- `hdm-catalog.js`, `hdm-catalog-filter.js`

Data source: `/api/*.php` on production (not in Git).

## URL structure

Rewrite/query vars (see `functions.php`):

- Catalog: `/avto-iz-{yaponii|korei|kitaya}/catalog/{mark}/` and `…/{mark}/{model}/`
- Blog: `/blog/` (archive), clusters `/blog/category/{slug}/`
- **Posts:** root URLs `/slug/` (not `/blog/slug/`) — e.g. `/obzor-byd-seal-iz-kitaya/`
- HDM: `hdm-group`, `hdm-type` slugs
- 404 validation calls live API when mark has zero listings (`proauc_catalog_has_listings`)

## Catalog files (other dev — prod is source of truth)

- `functions.php` — `parseData`, transients `manufacturers_*`, `proauc_catalog_api_count`
- `page-48.php` — Korea model slug (`-SERIES`)
- `js/api/cars-catalog.js` — `model_name` without `replaceAll('-', ' ')`

Do not deploy our copies over prod unless intentionally syncing from prod first.

## Styles

- SASS under `sass/` → compiled CSS
- Picosass compiler in `inc/picosass/`

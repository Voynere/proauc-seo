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
| `page-hdm-lot.php`, `page-moto-lot.php` | Lot detail |
| `page-spectehnika.php`, `page-motorcycles.php` | Sections |
| `rank-math.php` | SEO filters (required) |

## Frontend JS API clients

`js/api/` — catalog filters and data:

- `cars-catalog-japan.js`, `cars-catalog-korea.js`, `cars-catalog-china.js`
- `cars-catalog.js`, `cars-catalog-filter.js`
- `hdm-catalog.js`, `hdm-catalog-filter.js`

Data source: `/api/*.php` on production (not in Git).

## URL structure

Rewrite/query vars (see `functions.php`):

- `{country}/{mark}/` and `{country}/{mark}/{model}/` — country = korea | china | japan
- HDM: `hdm-group`, `hdm-type` slugs
- 404 validation calls live API when mark has zero models

## Styles

- SASS under `sass/` → compiled CSS
- Picosass compiler in `inc/picosass/`

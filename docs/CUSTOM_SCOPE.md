# Custom code scope

## Included in the repository

- `wp-content/themes/proautospec/` — тема сайта (SEO, блог, шаблоны, стили)
- `.cursor/` — память агента и документация проекта (локальная RAG-база)
- `scripts/` — деплой и служебные скрипты

## Deploy exclusions (catalog zone)

Файлы каталога принадлежат другому разработчику. GitHub Actions **не перезаписывает** их на проде:

- `wp-content/themes/proautospec/functions.php`
- `wp-content/themes/proautospec/page-48.php`
- `wp-content/themes/proautospec/js/api/cars-catalog.js`

Прод — source of truth для каталога. Перед локальной работой подтягивать эти файлы с сервера.

## Explicitly excluded

- WordPress core: `wp-admin/`, `wp-includes/`, root `wp-*.php`
- сторонние плагины (Rank Math, CF7, ACF и др.)
- дефолтные темы (`twentytwenty*`), бэкап `proautospec___`
- `assets/`, `api/` (~2.8 ГБ статики на сервере)
- uploads, caches, language packs, backups, logs
- environment-specific: `wp-config.php`, `.htaccess`
- generated / verification: `sitemap_*.xml`, `yandex_*.html`, `google*.html`
- `seov/` — локальные отчёты и планы

## Notes

Если появятся кастомные плагины, добавить их в `.gitignore` (whitelist) и расширить deploy workflow.

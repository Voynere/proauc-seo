# Bugfix


## 2026-06-23 04:06 UTC

sitemap-create=1: lots/hdm генерируются первыми; proauc_http_get_body fallback; sitemap_lots не пустой после полной генерации

## 2026-06-24 02:46 UTC

sitemap_index.xml: лишний </sitemap> после japan в rank_math/sitemap/index (rank-math.php) — удалён 24.06

## 2026-07-03 07:22 UTC

На проде у каталога баг — proauc_catalog_api_count использует неопределённую $model (закомментировали присвоение). Не чиним — зона другого разработчика.

## 2026-07-10 01:33 UTC

10.07 bugfix: nginx proxy_cache отдавал gzip-тело без Content-Encoding (пустая страница). Fix: proxy_set_header Accept-Encoding "" на / и /avtodoma/, убран Vary из ignore_headers, purge cache.

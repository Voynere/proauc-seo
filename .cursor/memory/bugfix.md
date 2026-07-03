# Bugfix


## 2026-06-23 04:06 UTC

sitemap-create=1: lots/hdm генерируются первыми; proauc_http_get_body fallback; sitemap_lots не пустой после полной генерации

## 2026-06-24 02:46 UTC

sitemap_index.xml: лишний </sitemap> после japan в rank_math/sitemap/index (rank-math.php) — удалён 24.06

## 2026-07-03 07:22 UTC

На проде у каталога баг — proauc_catalog_api_count использует неопределённую $model (закомментировали присвоение). Не чиним — зона другого разработчика.

# Decision


## 2026-07-03 04:10 UTC

Не трогать правки другого программиста на проде (functions.php slug/кэш каталога, page-48.php -SERIES, cars-catalog.js). Их зона — каталог/API. Наша зона — SEO и мелкие техфичи темы.

## 2026-07-03 04:13 UTC

Прод — source of truth для каталога. При расхождении: scp functions.php, page-48.php, cars-catalog.js с прода; наши SEO-правки подстраиваем поверх, чужой код каталога не меняем.

## 2026-07-06 08:08 UTC

06.07 вечер: зафиксировано. HEAD 7a42eee. Блог 21 статья, Metrika ACF admin, sitemap 517. Док: README, knowledge/*.md, seov отчёт. Каталог чужой — не трогать.

## 2026-07-07 06:04 UTC

Мониторинг позиций proauc.ru — seo.smyalichi.ru (SEO-кабинет Smyalichi), не Topvisor. CSV: seov/semantic-core-high.csv 215, semantic-core-dv.csv 112. Базовый срез 30.06.2026.

## 2026-07-07 06:06 UTC

Позиции: seo.smyalichi.ru без API — еженедельно вручную в seov/positions-weekly.md, оттуда блок в SEO-отчет-прогресс.md. 1-й съём ~14.07.

## 2026-07-07 08:47 UTC

Motorhome import: внешний importer (adapters → normalizer → WP-CLI writer), dedup meta _source + _source_id, локальное зеркалирование фото. Не трогать /api/ аукционного каталога и functions.php/page-48/cars-catalog.js.

## 2026-07-07 08:47 UTC

Источники motorhome: Fujicars (JP, EN, Phase 2), Bobaedream (KR, camp filter, Phase 3), Encar (KR, camping car, Phase 4 — сначала проверить Korea API на проде).

## 2026-07-09 03:41 UTC

Репозиторий proauc-seo переведён на custom-code модель (как atk-ved/ferma-dv): в git только wp-content/themes/proautospec/, scripts/, .cursor/. WP core и плагины исключены. Deploy через GitHub Actions rsync, каталожные файлы (functions.php, page-48.php, cars-catalog.js) не перезаписываются.

## 2026-07-09 06:38 UTC

Motorhome import Phase 0/1: tools/motorhome-import/ с CLI python -m motorhome_import run. Fujicars inventory на /search/list_en?body=9 (не /english/). Dedup _source/_source_id. Fujicars adapter работает (list+detail+gallery). Bobaedream/Encar — stubs. ACF keys и image sideload — блокеры.

## 2026-07-09 06:43 UTC

Motorhome import Phase 1 complete: acf_mapping.yaml from prod (properties/photos field keys, drive-type front/rear/4wd). pricing.py uses get-price.php sum×USDRUB. media.py sideload via wp-cli/ssh/REST. Bobaedream list parser works UTF-8 div.list-inner ~70/page ?page=N.

## 2026-07-09 07:04 UTC

Motorhome import Phase 1-3: Bobaedream detail parser (gallery/specs/description, no Playwright). Encar list via api.encar.com Ryvuss API; camping filter not found server-side — client filter on 캠핑/model_groups. Dry-run: Fujicars price_rub ~1.16M, Bobaedream ~1.52M KRW listing.

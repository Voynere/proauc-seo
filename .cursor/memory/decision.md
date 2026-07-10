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

## 2026-07-09 07:47 UTC

Fujicars body=9: li.carName = подтип (バンコン=минивэн, исключать; キャブコン/バスコン/軽キャンパー=автодом). Фильтр is_motorhome() в fujicars.py.

## 2026-07-09 07:51 UTC

Motorhome import: JP titles/grades translated via motorhome_import/translate.py (dictionary + katakana). CLI: retranslate, translate-demo. Prod fix: wp post update post_title + properties_grade.

## 2026-07-09 07:55 UTC

Motorhome translate: デリカ→Delica, ケイワークス→Keiworks, クルーズ→Cruise; Latin fixes Derika/Delika→Delica, D5→D:5 dedupe.

## 2026-07-09 22:22 UTC

Motorhome polish 2026-07-10: /avtodoma/ (301 с /avto-v-nalichii/), меню «Автодома», цена «По запросу» cat1/_source, ACF photos fix через update_field (wp-cli двойная сериализация), backfill 23 постов, retranslate 21 title, sample post 1926 — 20 фото.

## 2026-07-09 23:08 UTC

Motorhome JP→RU translate.py: добавлены KANJI_MAP (暖房, 軽, 2段, 家), camping builders (Toyo Factory, Vantec, Hijet), latin fixes для частичных retranslate. retranslate --no-dry-run на prod: 5 заголовков fujicars исправлено, 0 JP осталось в 24 published avto.
## 2026-07-10 08:22 UTC

План 11.07 / чеклист 14.07: Яндекс.Вебмастер переобход post-sitemap; мониторинг волны 5 (18–31.07); первый съём позиций в seov/positions-weekly.md (seo.smyalichi.ru). In-body JPG для не-obzor статей — только по явному запросу клиента.

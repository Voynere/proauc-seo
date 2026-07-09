# Seo


## 2026-06-22 01:39 UTC

SEO plan location: /home/voynere/Projects/proauc-seo/seo-audit-client.pdf — 18-page client SEO audit for proauc.ru (P1–P3 priorities, 90-day roadmap, custom SEO module recommendation)

## 2026-06-22 06:48 UTC

Started SEO plan implementation, client report at SEO-отчет-прогресс.md; safe fixes in rank-math.php: china/spectehnika catalog descriptions, post-40 api_meta description, api_meta h1 guard

## 2026-06-22 18:01 UTC

Client progress report moved to seov/ (gitignored, not for prod): seov/SEO-отчет-прогресс.md and seov/SEO-отчет-прогресс.pdf (WeasyPrint via seov/md_to_pdf.py + seov/.venv-pdf). Deploy is scp of changed files only; seov/ must never be uploaded to document root.

## 2026-06-22 08:02 UTC

SEO progress report: seov/SEO-отчет-прогресс.md и PDF seov/SEO-отчет-прогресс.pdf; seov/ в .gitignore, не деплоить на proauc.ru

## 2026-06-22 08:41 UTC

Client report seov/, canonical and schema implemented

## 2026-06-22 09:02 UTC

Sitemap korea/china/japan: generate_sitemap фильтрует марки/модели через proauc_catalog_has_listings (API count>0), общая логика с check_maker_model_available. Обновление: ?sitemap-create. robots.txt в репо совпадает с продом. Schema: Vehicle на car-lot, LocalBusiness+ContactPage на kontaktyi, BlogPosting enrich через rank_math/json_ld.

## 2026-06-23 01:23 UTC

23.06: title/description priority fix — DB seo_title/seo_description no longer overwritten by auto template; fallback only when DB empty. Static descriptions via proauc_request_path(). Spectehnika catalog page_id=41.

## 2026-06-23 02:25 UTC

P2 auction list page /kak-chitat-aukczionnyj-list/: improved title/description + FAQPage schema in rank-math.php. Static landing SEO centralized in proauc_get_static_landing_seo().

## 2026-06-23 03:42 UTC

23.06 P2 batch: sitemap_lots.xml (40 URLs), sitemap_hdm.xml (280 URLs), BreadcrumbList JSON-LD, HDM meta fallback, catalog root titles, spec.csv path fix, sedelnye/drygoe meta

## 2026-06-24 01:40 UTC

23.06 night: sitemap init hook, lots pn pagination 307 URL, noindex catalog filters, sitemap-create text output

## 2026-06-24 01:55 UTC

24.06 P1: noindex lot shells car-lot/moto-lot/hdm-lot, spasibo, exclude from page-sitemap; report format keep all days + вывод дня

## 2026-06-24 03:55 UTC

P3 блог: волны 1-2 на prod — 8 статей, /blog/, FAQ+CTA+FAQPage, post-sitemap 9 URL; seed v1/v2 через proauc_blog_seed_v1/v2; category_base=blog

## 2026-06-24 05:41 UTC

SEO-отчёт 24.06.2026: добавлен блок UX блога — даты статей с 01.06, крошки, отступ /blog/, fix белого текста, JSON-LD BlogPosting/FAQPage/BreadcrumbList; P3 ~45%

## 2026-06-24 05:41 UTC

Блог /blog/: post_date в seed (2026-06-01 +3 дня), миграция proauc_blog_dates_v1, крошки proauc_render_blog_breadcrumbs в home.php/single.php, отступ b-blog-hero в app.css

## 2026-06-24 05:47 UTC

Блог proauc.ru: JSON-LD CollectionPage+ItemList(BlogPosting)+BreadcrumbList на /blog/; single post — BlogPosting (headline, dates из WP post_date, author, publisher, mainEntityOfPage, inLanguage) + BreadcrumbList + FAQPage без дубля; microdata breadcrumbs в home.php/single.php; rank_math/json_ld enrich; миграция proauc_blog_dates_v1 из seeds; вывод через proauc_print_json_ld в blog-seo.php

## 2026-06-24 05:53 UTC

Блог single: русские метки (Категории, автор Редакция Proauc, дата d.m.Y), блок proauc-blog-expert после CTA; category.php для кластеров yaponiya/koreya/kitaj/spectehnika — карточки post вместо archive.php avto

## 2026-06-24 07:01 UTC

P3 24.06 финал: волна 3 (4 статьи, 12 всего), обложки proauc_blog_thumbnail, Читайте также, перелинковка аукционный лист, meta рубрик блога, post-sitemap 13 URL; P3 ~58%

## 2026-06-25 01:03 UTC

Семантическое ядро: seov/semantic-core.csv + .md для мониторинга позиций

## 2026-06-30 23:29 UTC

semantic-core phase3: 468 keys (+85), high-only CSV 174 rows, minus-words.txt; layers: model long-tail, service/process, comparisons, spec PC200/ZX/SK, regional delivery, USS/stat.jp

## 2026-07-01 00:59 UTC

SEO приоритет: продвижение в городах ДВ, seov/semantic-core-dv.csv

## 2026-07-01 01:14 UTC

Клиентский отчёт seov/SEO-отчет-семантика.md — семантическое ядро proauc.ru (508 ключей, high 215, ДВ 112)

## 2026-07-01 08:00 UTC

Июньский клиентский отчёт seov/SEO-отчет-прогресс.md дополнен блоками 25-30.06: семантика 508 ключей, мониторинг позиций Topvisor, базовый срез 30.06, итоги июня

## 2026-07-03 07:22 UTC

03.07.2026: проверка прода — правки каталога 01-02.07 (functions.php, page-48.php, cars-catalog.js) другим разработчиком. Подтянуты в репо с прода as-is. Зоны: каталог — он, SEO — мы. Волна 4 блога в работе (Palisade и др.), деплой SEO ещё не выкатывали.

## 2026-07-06 03:39 UTC

06.07.2026: волна 4 блога — 4 статьи (BYD Seal, кейс Kia Sorento, Komatsu PC200, доставка ДВ). seed proauc_blog_seed_v4. URL постов без /blog/ префикса. Деплой только blog-seo.php + blog-articles.php.

## 2026-07-06 03:59 UTC

06.07.2026 полный SEO-блок: волна 5 (5 статей), гео-сниппеты, sitemap_lots 517 URL, Дзен черновики seov/dzen/, отчёт обновлён. functions.php — только generate_sitemap_lots лимит 340.

## 2026-07-06 04:20 UTC

Админка WP: Настройки → Proauc SEO — OAuth-токен Яндекс.Метрики (proauc_metrika_oauth_token), ID счётчика (proauc_metrika_counter_id, default 98962652). Файл inc/seo-settings.php.

## 2026-07-06 08:06 UTC

Итог сессии 06.07: блог 21 статья (волны 1–5), sitemap_lots 517 URL, гео-сниппеты, отчёт+PDF обновлены, Дзен seov/dzen/. Админка «Яндекс Метрика» (ACF, как ferma). Следующее: токен в админке, API-отчёты, Вебмастер, Topvisor, публикация Дзена.

## 2026-07-07 00:39 UTC

07.07: prod каталог без изменений MD5. post-sitemap fix Rank Math cache+hook. Metrika SEO dashboard inc/metrika-reports.php. BYD в sitemap 14 URL.

## 2026-07-07 05:30 UTC

07.07 день: IndexNow включён, 14 URL HTTP 202, inc/seo-indexing.php, related by cluster, robots sitemap_index.xml

## 2026-07-09 06:53 UTC

09.07 SEO: перелинковка посадочных→блог (proauc_get_landing_blog_links, b-blog-links на avto-iz-*/spectehnika/motorcycles), motorcycles title+intro, волна 6 блога (3 статьи: sravnenie stran, statistika aukcionov, Habarovsk) seed v6

## 2026-07-09 07:02 UTC

09.07 SEO deploy prod: landing→blog links, motorcycles meta, wave6 seed (3 future aug). Отчёт seov/SEO-отчет-прогресс.md+PDF обновлён 09.07

## 2026-07-09 07:18 UTC

Blog covers: inc/blog-covers.php генерирует 24 уникальных SVG 1200x630 images/blog/{slug}.svg, scripts/generate-blog-covers.php, single.php hero, proauc_blog_covers_v1 migration. Prod 09.07.

## 2026-07-09 08:04 UTC

Blog covers photorealistic: 24 JPG images/blog/{slug}.jpg AI-generated, JPEG ~5.5MB total, blog-covers.php prefers jpg over svg, proauc_blog_covers_v2 migration, prod 09.07 night

## 2026-07-09 08:16 UTC

Blog in-body images: images/blog/content/, proauc_blog_content_figure() in blog-seo.php, proauc_sync_blog_post_content(slug) in blog-seo.php. Первый кейс — obzor-byd-seal-iz-kitaya (3 JPG: interior, charging, exterior). CSS .proauc-blog-figure в app.css. Миграция proauc_blog_content_byd_seal_v1. Prod 09.07.

## 2026-07-10 08:17 UTC

Чеклист ~14.07.2026 (волны 4–5 блога):

**Статус волн (10.07):** 1–3 опубликованы; волна 4 — 2/4 (BYD Seal 06.07, Kia Sorento 09.07); Komatsu 12.07, доставка ДВ 15.07 — future; волна 5 (5 статей) 18–31.07 — future. post-sitemap ~14 URL → ~21 к 31.07.

**14.07 — действия:**
1. Проверить публикации: `/obzor-komatsu-pc200-iz-yaponii/` (12.07), `/dostavka-avto-v-regiony-dalnego-vostoka/` (15.07)
2. Открыть `https://proauc.ru/post-sitemap.xml` — ожидать 15–16 URL
3. Яндекс.Вебмастер → Индексирование → переобход `post-sitemap.xml` (+ опционально sitemap_index.xml)
4. Переобход ключевых URL вручную (Komatsu, доставка ДВ)
5. Снимок позиций → seov/positions-weekly.md (seo.smyalichi.ru)
6. Проверить WP-Cron — future-посты публикуются автоматически; при сбое `wp cron event run --due-now`

**Волна 5 (18–31.07):** Alphard, X-Trail, Vezel, Carnival, BYD vs Zeekr — мониторинг + переобход post-sitemap после 31.07.

**Деплой до 14.07 (если не на проде):** blog-covers.php, blog-seo.php, blog-articles.php, images/blog/*.jpg, css/app.css, single.php, b-blog-links.php.

## 2026-07-09 22:18 UTC

Чеклист ~14.07: переобход post-sitemap в Яндекс.Вебмастере; волна 4 (2/4 опубликовано, Komatsu 12.07, доставка ДВ 15.07); волна 5 (5 статей 18–31.07) по расписанию через WP future; post-sitemap ~14→21 URL; снимок позиций seov/positions-weekly.md

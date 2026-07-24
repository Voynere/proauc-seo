# SEO-отчёт для заказчика

**Дата:** 21 июля 2026  
**Сайт:** proauc.ru  
**Репозиторий:** [Voynere/proauc-seo](https://github.com/Voynere/proauc-seo) · ветка `main`

> Техпакет 1–5, волна 7 блога, правка `robots.txt` (Googlebot) и **волна 8 блога** — на production (21.07). Источник robots в репозитории: `scripts/robots.txt` → корень сайта.

---

## Обложки блога: wave 7 SVG meta + X-Trail T33 — 21.07.2026

**Проблема:** на `/blog/` у 4 постов волны 7 в карточках показывались логотипы Λ (`bg-alpha-*.svg` / `bg-recently-bought-card.svg`), хотя JPG уже лежали в `images/blog/{slug}.jpg` на проде. Причина: seed/meta `proauc_blog_thumbnail` записан до появления JPG; миграция `covers_v2` уже была отмечена и не перепривязала meta.

**Что сделали:**
1. Runtime: `proauc_blog_card_image_url` / `proauc_get_post_schema_image` предпочитают `images/blog/{slug}.jpg` и игнорируют cluster SVG-заглушки.
2. Миграция `proauc_blog_covers_v3` → `proauc_refresh_blog_cover_meta()`.
3. Nissan X-Trail: новая обложка + exterior/awd (модель **2023–2025 / T33**, вместо T30 на cover и старых in-body). Interior уже был современный.

**Посты с исправленной meta:** `kak-proverit-avto-iz-korei-pered-pokupkoj`, `avto-iz-yaponii-v-blagoveshchensk`, `rastamozka-avto-iz-korei`, `obzor-toyota-prado-iz-yaponii`.

---

## Новые статьи (волна 8 блога) — 21.07.2026

Добавлены **4 статьи** (seed `proauc_blog_seed_v8`). **Задеплоены на прод 21.07**, досрочно опубликованы, IndexNow + `?sitemap-create=1`.

| # | Заголовок | URL / slug | Основной ключ | Кластер | Обложка | In-body | Индексация |
|---|-----------|------------|---------------|---------|---------|---------|------------|
| 1 | Авто из Японии в Южно-Сахалинск: доставка и сроки | https://proauc.ru/avto-iz-yaponii-v-yuzhno-sahalinske/ | авто из Японии Южно-Сахалинск | yaponiya | `images/blog/avto-iz-yaponii-v-yuzhno-sahalinske.jpg` | — | live · IndexNow · HTTP 200 |
| 2 | Сколько стоит привезти авто из Кореи в 2026 году | https://proauc.ru/skolko-stoit-privezti-avto-iz-korei/ | сколько стоит привезти авто из Кореи | koreya | `images/blog/skolko-stoit-privezti-avto-iz-korei.jpg` | — | live · IndexNow · HTTP 200 |
| 3 | Обзор Hyundai Santa Fe из Кореи | https://proauc.ru/obzor-hyundai-santa-fe-iz-korei/ | Hyundai Santa Fe из Кореи | koreya / obzory | `images/blog/obzor-hyundai-santa-fe-iz-korei.jpg` | 3 JPG в `images/blog/content/` | live · IndexNow · HTTP 200 |
| 4 | Оформление ЭПТС для авто из Японии | https://proauc.ru/oformlenie-epts-avto-iz-yaponii/ | оформление ЭПТС авто из Японии | yaponiya | `images/blog/oformlenie-epts-avto-iz-yaponii.jpg` | — | live · IndexNow · HTTP 200 |

**Что входит в пакет:** тексты + Rank Math title/description, FAQ (FAQPage), внутренние ссылки, JPG-обложки 1536×1024, перелинковка с лендингов Японии/Кореи (`proauc_get_landing_blog_links`).

**Сделано на проде 21.07:**
1. `scp` `blog-seo.php`, `blog-articles.php`, JPG обложек/in-body → document root.
2. Seed через тему (`proauc_blog_seed_v8` + schedule); досрочная `publish`.
3. IndexNow: 5 URL (4 статьи + `/blog/`).
4. `?sitemap-create=1` + ping Яндекс.Вебмастера для post-sitemap.
5. `post-sitemap.xml` live: HTTP 200, **27** `<loc>` (4 статьи волны 8 внутри).

---

## robots.txt: сужение правил Googlebot — 21.07.2026

**Проблема:** у `User-agent: GoogleBot` стояло `Disallow: *?*` — Google не обходил **любые** URL с query-строкой (включая потенциально полезные), хотя фильтры каталога и так закрываются через `noindex`/`canonical` в теме.

**Что сделали:** убрали blanket `*?*`. Вместо него — точечные `Disallow` только для:
- параметров фильтров каталога (`year-start/end`, `mileage-*`, `engine-*`, `price-*`, `rate`, `auction_date`, `category`, `mark-id`, `model-id`, `pn`, `page`, поиск `s`);
- трекинга (`utm*`, `openstat`, `gclid`, `yclid`, `ymclid`, `from`, `tpclid`);
- служебных `sitemap-*-create`.

Правила совпадают со списком в `proauc_catalog_has_noncanonical_query()` (`rank-math.php`). Блоки `*` и `Yandex` без изменений (у Яндекса по-прежнему `Clean-Param`).

**На проде:** `https://proauc.ru/robots.txt` обновлён через `scp` 21.07 — live, без `Disallow: *?*`.

---

## Новые статьи (волна 7 блога) — 21.07.2026

Добавлены **4 статьи** (seed `proauc_blog_seed_v7`). **Задеплоены на прод 21.07**, досрочно опубликованы (как Komatsu/доставка ДВ), IndexNow + `?sitemap-create=1` отработаны.

| # | Заголовок | URL / slug | Основной ключ | Кластер | Обложка | In-body | Индексация |
|---|-----------|------------|---------------|---------|---------|---------|------------|
| 1 | Растаможка авто из Кореи: документы и платежи | https://proauc.ru/rastamozka-avto-iz-korei/ | растаможка авто из Кореи | koreya | `images/blog/rastamozka-avto-iz-korei.jpg` | — | live · IndexNow HTTP 200 · HTTP 200 |
| 2 | Авто из Японии в Благовещенск: доставка и сроки | https://proauc.ru/avto-iz-yaponii-v-blagoveshchensk/ | авто из Японии Благовещенск | yaponiya | `images/blog/avto-iz-yaponii-v-blagoveshchensk.jpg` | — | live · IndexNow HTTP 200 · HTTP 200 |
| 3 | Обзор Toyota Land Cruiser Prado с аукциона Японии | https://proauc.ru/obzor-toyota-prado-iz-yaponii/ | Toyota Prado с аукциона Японии | yaponiya / obzory | `images/blog/obzor-toyota-prado-iz-yaponii.jpg` | 3 JPG в `images/blog/content/` | live · IndexNow HTTP 200 · HTTP 200 |
| 4 | Как проверить авто из Кореи перед покупкой | https://proauc.ru/kak-proverit-avto-iz-korei-pered-pokupkoj/ | проверка авто из Кореи | koreya | `images/blog/kak-proverit-avto-iz-korei-pered-pokupkoj.jpg` | — | live · IndexNow HTTP 200 · HTTP 200 |

**Что входит в пакет:** тексты + Rank Math title/description, FAQ (FAQPage), внутренние ссылки на посадочные/каталоги/смежные статьи, JPG-обложки 1536×1024, перелинковка с лендингов Кореи/Японии (`proauc_get_landing_blog_links`).

**Сделано на проде 21.07:**
1. `scp` `blog-seo.php`, `blog-articles.php`, JPG обложек/in-body → document root.
2. Seed через WP-CLI (`proauc_blog_seed_v7` + schedule); посты ID 2654–2657.
3. Досрочная публикация `publish` (таймзона WP `+03:00`).
4. IndexNow: 5 URL (4 статьи + `/blog/`) — HTTP 200 (повторный ping 21.07 вечером — снова HTTP 200).
5. `?sitemap-create=1` — HTTP 200.
6. `post-sitemap.xml` live: HTTP 200, **23** `<loc>` (все 4 статьи волны 7 + `/blog/` внутри).

**Яндекс.Вебмастер / переобход post-sitemap (21.07):**
- **Публичный ping sitemap** выполнен: `webmaster.yandex.ru/ping?sitemap=…` для `post-sitemap.xml` и `sitemap_index.xml` — оба **HTTP 200** (`rs_weight=1`).
- **API/UI «Переобход»** — **недоступен из этой среды:** нет OAuth-токена Яндекс.Вебмастера (константы `PROAUC_WEBMASTER_OAUTH_TOKEN` нет; токен Метрики на проде пустой; в теме только IndexNow + Метрика, CLI/скрипта recrawl нет). Чеклист в памяти проекта и ранее подразумевал ручной шаг в UI: Индексирование → переобход `post-sitemap.xml`.
- **Опционально вручную:** если нужен именно queue recrawl в кабинете Вебмастера — зайти под аккаунтом владельца сайта и поставить переобход `https://proauc.ru/post-sitemap.xml` (+ при желании 4 URL статей).

---

## Краткий итог (техпакет 21.07)

Выполнены **5 согласованных SEO-задач** плюс правки шапки сайта, плюс **волна 7 блога**, плюс **сужение Googlebot в robots.txt** (см. выше). Цель — улучшить выдачу/шаринг, каталоги для роботов, alt/навигацию, безопасность SQL в title и crawl budget Google.

| № | Что сделано | Статус |
|---|-------------|--------|
| 1 | Картинка по умолчанию для шаринга (Open Graph) | Готово |
| 2 | Заголовок и описание главной согласованы с H1 | Готово |
| 3 | Безопасные SQL-запросы в SEO-заголовках | Готово |
| 4 | Alt у изображений + пункт «Блог» в меню (+ правки шапки) | Готово |
| 5 | Серверная отрисовка каталога Китая (и сохранение SSR у Японии/Кореи) | Готово* |
| 6 | robots.txt: Googlebot без blanket `Disallow: *?*` | Готово · live |

\* С оговорками: фильтры и страницы пагинации по-прежнему подгружаются через JavaScript — это нормально для каталога; полный SSR всех страниц списка не делался.

**Прогресс по списку:** 6 из 6 (техпакет + robots).

---

## Что сделано

### 1. Картинка для шаринга в соцсетях и мессенджерах

**Зачем:** когда страницу без своей обложки кидают в Telegram, VK, WhatsApp и т.п., часто не было нормальной картинки — превью выглядело «пустым» или случайным.

**Что сделали:** добавили стандартное изображение 1200×630 и подключили его как запасной вариант для Open Graph / Twitter. У статей блога с собственной обложкой своя картинка по-прежнему важнее.

**Для вас:** после деплоя ссылки на страницы без обложки должны давать аккуратное превью с фирменной картинкой.

---

### 2. Title и description главной страницы

**Зачем:** в поисковой выдаче заголовок и сниппет должны совпадать с тем, что человек видит на странице (H1), и отражать суть услуги.

**Что сделали:**
- **Заголовок:** «Автомобили и спецтехника под заказ — Владивосток и Дальний Восток»
- **Описание:** авто и спецтехника из Японии, Кореи и Китая, доставка во Владивосток и по Дальнему Востоку
- Согласовано с H1 главной: «Автомобили и спецтехника под заказ»

**Для вас:** главная лучше «читается» поисковиком и выглядит понятнее в выдаче.

---

### 3. Безопасность SQL в SEO-заголовках

**Зачем:** заголовки страниц марок/моделей строились с запросами к базе. Их привели к безопасному виду (`prepare`), чтобы снизить риск ошибок и атак через параметры URL.

**Что сделали:** обновили логику формирования SEO-title для вендоров и моделей (включая группы спецтехники). Поведение для пользователя то же — заголовки как раньше, код надёжнее.

**Для вас:** это техническая гигиена; на внешний вид сайта почти не влияет, на устойчивость — да.

---

### 4. Подписи к картинкам (alt), меню «Блог» и шапка

**Зачем:** alt помогает доступности и SEO; в меню не хватало явного входа в блог; в шапке были мелкие технические дубли.

**Что сделали:**
- Исправили и дополнили alt на лендингах и в карточках каталога (название авто вместо пустых/ошибочных подписей)
- В основном меню добавлен пункт **Блог** → `/blog/`
- В шапке: корректный charset в начале `<head>`, убран лишний `robots` и дублирующий Open Graph prefix (остаётся вариант от Rank Math)

**Для вас:** навигация к блогу заметнее; картинки описаны осмысленно.

---

### 5. Каталог «из Китая» отдаёт контент сразу в HTML

**Зачем:** если список лотов появляется только после JavaScript, поисковик и пользователь с медленным каналом могут «не увидеть» карточки при первом открытии.

**Что сделали:**
- Каталог Китая: заголовок, счётчик, первые карточки со ссылками, SEO-текст и список производителей уже в HTML при загрузке
- Япония и Корея: уже были карточки в HTML — теперь они не затираются лишним AJAX при первом открытии без фильтров

**Для вас:** каталоги лучше индексируются и быстрее показывают смысл страницы. При фильтрах и переходе на 2+ страницу по-прежнему работает подгрузка — это ожидаемо.

---

## Что ещё желательно / риски

| Тема | Простым языком |
|------|----------------|
| Фильтры каталога | Результаты фильтров и страницы «дальше первой» — через AJAX; полный SSR всей пагинации не входил в этот объём. Фильтры по-прежнему noindex + Disallow точечными правилами. |
| Другие блоки на JS | Часть превью/статистики по-прежнему без серверного HTML — вне текущего минимального scope. |
| Обложки статей | У постов блога свои картинки; формат может отличаться от 1200×630 — для статей это нормально. |
| Блок команды | Правки alt в шаблоне команды есть, но сам блок на сайте пока отключён (в комментарии) — в «живой» вёрстке не виден, пока его не включат. |
| Яндекс переобход | Публичный ping sitemap сделан; UI «Переобход» в Вебмастере — вручную под аккаунтом владельца (нет OAuth). |

---

## Рекомендации дальше

1. **Волна 9 блога** — следующие ключи (Якутск / модели JP-KR-CN / процесс), обложки, IndexNow, sitemap.
2. **Снимок позиций** — `seo.smyalichi.ru` → `seov/positions-weekly.md` (сравнение с базой 30.06).
3. **По желанию:** в админке Rank Math назначить дефолтную OG-картинку из медиабиблиотеки (сейчас файловый fallback в теме).
4. **Опционально:** расширить SSR/превью других JS-блоков; полный SSR пагинации каталога — низкий приоритет.

---

## Приложение для разработчиков

Кратко — что где лежит.

| Область | Файлы |
|---------|--------|
| OG default | `images/og-default.jpg`, `rank-math.php` |
| Homepage meta | `rank-math.php` → `proauc_get_static_landing_seo()` ключ `'/'` |
| SQL prepare в title | `rank-math.php` (`rank_math/frontend/title`) |
| Header / Blog nav | `header.php` |
| Alt (лендинги) | `front-page.php`, `page-kompaniya.php`, `page-avto-iz-yaponii.php`, `page-spectehnika.php`, `b-team.php` |
| Alt (каталог SSR/JS) | `page-45/48/41/51`, `page-gruzoviki.php`, `loops/avto.php`, `cars-catalog-*.js`, `hdm-catalog.js` |
| Catalog SSR China | `page-51.php`; сохранение SSR: `js/api/cars-catalog.js` |
| robots Googlebot | `scripts/robots.txt` → document root `/robots.txt` |

**Технические оговорки:**
- Сырые SQL в шаблонах списков вендоров (`page-45.php`, `page-48.php` и др.) — вне scope title-hooks.
- `$update_seo` может перебить static meta главной (порядок в том же хуке).
- Скрытые JS-шаблоны `#car-item` могут иметь пустой alt до клонирования — JS перезаписывает.
- Репозиторий: `https://github.com/Voynere/proauc-seo.git`, ветка `main`.

---

## SEO-анализ и исправления — 24.07.2026

### Аудит: состояние на 24.07

| Параметр | Значение |
|----------|----------|
| Семантическое ядро | 831 ключ, 48 групп |
| Блог-посты | 27 → **32** (опубликованы wave5–6) |
| Каталог Япония | 1 295 лотов |
| Каталог Корея | 294 лота |
| Каталог Китай | 235 лотов |
| Лоты (аукцион) | 784 лота |
| HDM (автодома) | 281 лот |
| Статические страницы | 19 |
| Sitemap-файлов | 7 |

### Что сделано (24.07)

| № | Задача | Статус |
|---|--------|--------|
| 1 | Публикация 5 future-постов (wave5–6) | ✅ SQL UPDATE → publish |
| 2 | Schema.org JSON-LD: главная (Organization + LocalBusiness + WebSite + FAQPage) | ✅ rank-math.php |
| 3 | Schema.org JSON-LD: 6 лендингов (Organization + WebSite + WebPage) | ✅ rank-math.php |
| 4 | Исправление битых URL в семантике (geely → geely-auto, ЭПТС slug) | ✅ seov/keywords-by-groups.csv |
| 5 | IndexNow: 6 URL (5 статей + /blog/) | ✅ HTTP 200 |

### Опубликованные статьи (24.07)

| ID | Slug | Был статус | Дата |
|----|------|-----------|------|
| 866 | byd-seal-i-zeekr-001-sravnenie | future (31.07) | 24.07 |
| 865 | obzor-kia-carnival-iz-korei | future (27.07) | 24.07 |
| 872 | sravnenie-avto-iz-yaponii-korei-kitaya | future (04.08) | 24.07 |
| 873 | kak-polzovatsya-statistikoj-aukcionov-yaponii | future (08.08) | 24.07 |
| 874 | avto-iz-yaponii-v-habarovsk | future (12.08) | 24.07 |

### Schema.org — что добавлено

**Главная (`/`):**
- `Organization` (name, url, logo, sameAs, contactPoint)
- `LocalBusiness` (address, openingHours, telephone, email)
- `WebSite` (SearchAction)
- `FAQPage` (7 вопросов: наличие, цены, как привезти, доставка по РФ, сроки, НДС, лизинг)

**Лендинги (`/avto-iz-yaponii/`, `/avto-iz-korei/`, `/avto-iz-kitaya/`, `/avtodoma/`, `/spectehnika/`, `/motorcycles/`):**
- `Organization` + `WebSite` + `WebPage`

### Nginx proxy_cache

Обнаружен `proxy_cache proauc_html` (TTL 5 min, max 256m). Файл: `/etc/nginx/fastpanel2-available/proauc_ru_usr/proauc.ru.conf`. Кеш-зона: `/var/cache/nginx/proauc/`. Очистка: `rm -rf /var/cache/nginx/proauc/* && nginx -s reload`.

### Точки роста (рекомендации)

1. **Волна 9 блога**: автодома (модели Starex/Staria/Hiace), гео-лендинги (Уссурийск, П-Камчатский, Находка, Комсомольск, Якутск, Чита, Магадан, Артём)
2. **PageSpeed/CWV**: jQuery → vanilla, CSS merge, Swiper lazy-load
3. **Внутренняя перелинковка**: каталог → блог, кросс-ссылки между статьями
4. **Sitemap**: Rank Math кеширует sitemap — при публикации через SQL нужно вручную очищать transient + nginx cache

---

## Wave 9: гео-лендинги + перелинковка каталог→блог — 24.07.2026

### Гео-статьи (8 городов ДВ)

| # | Город | Slug | ID | Дата |
|---|-------|------|----|------|
| 1 | Уссурийск | avto-iz-yaponii-v-ussurijske | 4223 | 24.07 |
| 2 | Находка | avto-iz-yaponii-v-nahodke | 4224 | 24.07 |
| 3 | П-Камчатский | avto-iz-yaponii-v-petropavlovsk-kamchatskij | 4225 | 24.07 |
| 4 | Комсомольск-на-Амуре | avto-iz-yaponii-v-komsomolske-na-amure | 4226 | 24.07 |
| 5 | Чита | avto-iz-yaponii-v-chite | 4227 | 24.07 |
| 6 | Якутск | avto-iz-yaponii-v-yakutske | 4228 | 24.07 |
| 7 | Магадан | avto-iz-yaponii-v-magadane | 4229 | 24.07 |
| 8 | Артём | avto-iz-yaponii-v-arteme | 4230 | 24.07 |

**Итого постов в блоге:** 27 → **40** (wave1–9).

### Перелинковка каталог → блог

| Файл каталога | Кластер | Статус |
|---------------|---------|--------|
| page-45.php (Япония) | yaponiya | ✅ |
| page-48.php (Корея) | koreya | ✅ |
| page-51.php (Китай) | kitaj | ✅ |
| page-41.php (Спецтехника) | spectehnika | ✅ |
| page-gruzoviki.php | spectehnika | ✅ |
| page-motorcycles.php | mototsikly | ✅ |

Механизм: `proauc_render_catalog_blog_sidebar()` → 5 ссылок из `proauc_get_landing_blog_links()` + «Все статьи → /blog/».

### IndexNow
9 URL отправлены (8 статей + /blog/) — HTTP 200.

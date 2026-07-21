# SEO-отчёт: внедрение рекомендаций

**Дата:** 2026-07-21  
**Репозиторий:** `/home/voynere/Projects/proauc`  
**Тема:** `wp-content/themes/proautospec`

## Сводка

| # | Пункт | Статус |
|---|--------|--------|
| 1 | Default OG-image 1200×630 | ✅ done |
| 2 | Homepage title/description ↔ H1 | ✅ done |
| 3 | SQL `$wpdb->prepare()` в SEO title hooks | ✅ done |
| 4 | Alt text + Blog в primary nav (+ header fixes) | ✅ done |
| 5 | Catalog SSR | ✅ done (частично по рискам ниже) |

**Общий прогресс:** 5/5 завершено.

---

## 1. Default OG-image 1200×630

**Статус:** done

### Что изменилось
- Ассет `wp-content/themes/proautospec/images/og-default.jpg` — 1200×630 JPEG (center-crop из `images/blog/sravnenie-avto-iz-yaponii-korei-kitaya.jpg`).
- В `wp-content/themes/proautospec/rank-math.php`:
  - `proauc_get_default_og_image_url()`
  - hooks `rank_math/opengraph/facebook|twitter/add_images` и фильтры `.../image`
  - fallback в `rank_math/settings` → `titles.open_graph_image`
- Статьи блога: если у поста уже есть featured/cover — дефолт не перебивает.

### Остаточные риски
- На проде нужно задеплоить `og-default.jpg` вместе с `rank-math.php`.
- В Rank Math Admin можно позже заменить на медиабиблиотечный attachment ID (опционально).
- Соотношение у статейных JPG-обложек 1536×1024 (не 1200×630) — для статей ок через featured image.

---

## 2. Homepage title/description ↔ H1

**Статус:** done

### Что изменилось
- `proauc_get_static_landing_seo()` в `rank-math.php` — ключ `'/'`:
  - **title:** «Автомобили и спецтехника под заказ — Владивосток и Дальний Восток»
  - **description:** про авто/спецтехнику из Японии/Кореи/Китая, доставку во Владивосток и по ДВ
- Fallback через `is_front_page()` в фильтрах `rank_math/frontend/title` и `.../description`.
- Согласовано с H1 главной: «Автомобили и спецтехника под заказ».

### Остаточные риски
- `$update_seo` может перебить static meta (идёт после static в том же хуке).

---

## 3. SQL `$wpdb->prepare()` в SEO title hooks

**Статус:** done

### Что изменилось
- `wp-content/themes/proautospec/rank-math.php` — фильтр `rank_math/frontend/title`:
  - vendors/models: `$wpdb->prepare()` с `%s` / `%d`
  - HDM groups/types (post 40, 41, 43): `$wpdb->prepare()` по `slug`
- Поведение сохранено: mark → vendor row; mark+model → model row.

### Остаточные риски
- В шаблонах каталога (`page-45.php`, `page-48.php` и др.) остаются сырые SQL для списков вендоров/моделей — вне scope title-hooks.

---

## 4. Alt text + Blog в primary nav (+ header)

**Статус:** done

### Что изменилось
- `header.php`: charset в начале `<head>`; убран дубль `robots`; убран ручной `prefix` (остаётся один от Rank Math); пункт меню **Блог** → `/blog/`.
- Alt:
  - `page-avto-iz-yaponii.php` — логотипы «из Японии» (было «из Кореи»)
  - `page-spectehnika.php` — intro alt «из Японии»
  - `front-page.php`, `page-kompaniya.php` — director alt
  - `b-team.php` — имена команды (блок пока в комментарии)
  - SSR/JS карточки: `page-45/48/41/51/gruzoviki/spectehnika`, `avto.php`, `cars-catalog-*.js`, `hdm-catalog.js` — alt = название авто

### Остаточные риски
- Скрытые JS-шаблоны `#car-item` могут иметь пустой/старый alt до клонирования — JS перезаписывает.
- `b-team.php` целиком закомментирован — alts не в live DOM, пока блок не включат.

---

## 5. Catalog SSR

**Статус:** done (с оговорками)

### Что изменилось
- `page-51.php` (каталог Китая): SSR H1, счётчик, первые карточки лотов со ссылками, SEO-текст (ACF), список производителей — по образцу Korea/Japan.
- `js/api/cars-catalog.js`: если в HTML уже есть `.car-loaded` и нет клиентских фильтров/pagination `pn>1` — **не** делаем начальный AJAX, чтобы не стирать SSR (пагинация инициализируется с `skipInitCallback`).
- Japan (`page-45`) и Korea (`page-48`) уже имели SSR-карточки; теперь они сохраняются при первом paint без фильтров.

### Остаточные риски
- Фильтры / `pn>1` по-прежнему идут через AJAX (ожидаемо).
- Полный SSR всех страниц пагинации без rewrite не делался.
- `page-46` (статистика) и часть landing-preview блоков по-прежнему JS-only — вне минимального scope.
- После деплоя проверить `/avto-iz-kitaya/catalog/` view-source: карточки и ссылки в HTML.

---

## Файлы (сводка)

| Файл | Пункты |
|------|--------|
| `SEO-REPORT.md` | отчёт |
| `wp-content/themes/proautospec/images/og-default.jpg` | 1 |
| `wp-content/themes/proautospec/rank-math.php` | 1, 2, 3 |
| `wp-content/themes/proautospec/header.php` | 4 |
| `wp-content/themes/proautospec/front-page.php` | 4 |
| `wp-content/themes/proautospec/page-kompaniya.php` | 4 |
| `wp-content/themes/proautospec/page-avto-iz-yaponii.php` | 4 |
| `wp-content/themes/proautospec/page-spectehnika.php` | 4 |
| `wp-content/themes/proautospec/template-parts/landing/b-team.php` | 4 |
| `wp-content/themes/proautospec/template-parts/loops/avto.php` | 4 |
| `wp-content/themes/proautospec/page-45.php` | 4 |
| `wp-content/themes/proautospec/page-48.php` | 4 |
| `wp-content/themes/proautospec/page-41.php` | 4 |
| `wp-content/themes/proautospec/page-gruzoviki.php` | 4 |
| `wp-content/themes/proautospec/page-51.php` | 4, 5 |
| `wp-content/themes/proautospec/js/api/cars-catalog.js` | 5 |
| `wp-content/themes/proautospec/js/api/cars-catalog-*.js`, `hdm-catalog.js` | 4 |

**Коммиты / push:** не создавались (по запросу).

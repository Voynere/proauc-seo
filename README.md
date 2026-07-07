# proauc-seo

Репозиторий исходников сайта [proauc.ru](https://proauc.ru) — WordPress с кастомной темой и SEO-настройками.

## Стек

- **CMS:** WordPress
- **Тема:** `proautospec` (на базе Picostrap / Bootstrap 5)
- **SEO:** Rank Math и связанные правки в теме
- **Хостинг:** FASTPANEL, Ubuntu 24.04, Nginx + Apache

## Что в репозитории

В Git попадает код WordPress, плагины, тема и конфигурация сайта. Не версионируются (см. `.gitignore`):

| Исключено | Причина |
|-----------|---------|
| `assets/`, `api/` | большие статические каталоги (~2.8 ГБ), синхронизируются с сервера отдельно |
| `wp-content/uploads/` | медиафайлы |
| `wp-content/cache/`, `wp-content/upgrade/` | runtime-кэш и временные файлы |
| `wp-config.php`, `.htaccess` | секреты и серверная конфигурация |
| `*.log`, `node_modules/` | логи и зависимости |
| `seov/` | локальные отчёты и планы, не для prod/git |

## Продакшен

| Параметр | Значение |
|----------|----------|
| Сервер | `188.120.251.205` |
| SSH | `ssh proauc` |
| Document root | `/var/www/proauc_ru_usr/data/www/proauc.ru` |
| Nginx config | `/etc/nginx/fastpanel2-available/proauc_ru_usr/proauc.ru.conf` |

## Зоны ответственности

| Зона | Кто | Файлы (примеры) |
|------|-----|-----------------|
| **Каталог / API** | другой разработчик | `functions.php` (slug, transients, `parseData`), `page-48.php`, `js/api/cars-catalog.js` |
| **SEO и техфичи темы** | мы | `rank-math.php`, `inc/blog-seo.php`, `inc/blog-articles.php`, `category.php`, шаблоны блога |

**Прод — source of truth для каталога.** При расхождении тянем с прода, не правим чужой код:

```bash
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/functions.php \
    wp-content/themes/proautospec/functions.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/page-48.php \
    wp-content/themes/proautospec/page-48.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/js/api/cars-catalog.js \
    wp-content/themes/proautospec/js/api/cars-catalog.js
```

Деплой SEO — точечный `scp` **только наших** файлов; три файла каталога на прод не перезаписываем.

## Синхронизация с сервера

На сервере нет `rsync`, используется `tar` по SSH:

```bash
mkdir -p ~/Projects/proauc-seo
ssh proauc 'cd /var/www/proauc_ru_usr/data/www/proauc.ru && tar czf - \
  --exclude="wp-content/uploads" \
  --exclude="wp-content/cache" \
  --exclude="wp-config.php" \
  --exclude=".htaccess" \
  --exclude="node_modules" \
  --exclude="*.log" \
  --exclude="wp-content/upgrade" \
  .' | tar xzf - -C ~/Projects/proauc-seo/
```

Каталоги `assets/` и `api/` при необходимости подтягиваются отдельно:

```bash
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/assets ~/Projects/proauc-seo/
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/api ~/Projects/proauc-seo/
```

## Деплой SEO на прод

```bash
THEME=wp-content/themes/proautospec
ROOT=/var/www/proauc_ru_usr/data/www/proauc.ru

for f in rank-math.php header.php footer.php home.php single.php category.php \
         loops/cards.php css/app.css spec.csv \
         inc/blog-seo.php inc/blog-articles.php inc/seo-settings.php; do
  scp "$THEME/$f" "proauc:$ROOT/$THEME/$f"
done
```

`functions.php` — только при изменении SEO-части (например `generate_sitemap_lots`); **не перезаписывать** каталожные правки другого разработчика без sync с прода.

После CSS/JS — сбросить кэш Autoptimize в админке WP.

## Структура проекта

```
├── wp-content/
│   ├── themes/proautospec/   # основная тема сайта
│   │   ├── rank-math.php     # SEO-фильтры Rank Math
│   │   ├── inc/blog-seo.php  # блог: schema, CTA, FAQ
│   │   ├── inc/blog-articles.php  # seed-статьи P3 (волны 1–5)
│   │   ├── inc/seo-settings.php   # админка «Яндекс Метрика» (ACF)
│   │   └── inc/metrika-reports.php # SEO-дашборд Stat API
│   └── plugins/              # плагины (Rank Math, CF7, ACF и др.)
├── wp-admin/, wp-includes/   # ядро WordPress
├── assets/                   # локально, не в Git
├── api/                      # локально, не в Git
├── seov/                     # локальные отчёты, не в Git
└── sitemap_*.xml             # карты сайта
```

## Статус (07.07.2026)

**Проверка прода:** каталог (`functions.php`, `page-48.php`, `cars-catalog.js`) — **без изменений** от другого разработчика (MD5 совпадают). SEO-файлы синхронизированы.

**Сделано 07.07**

- Исправлен **post-sitemap**: BYD Seal и новые статьи попадают в карту после публикации (сброс кэша Rank Math + хук `transition_post_status`)
- **SEO-дашборд Метрики** в админке: просмотры блога/каталога, органика, utm dzen (`inc/metrika-reports.php`) — нужен OAuth-токен
- **IndexNow**: модуль включён, 14 URL отправлены (HTTP 202), авто-ping при публикации (`inc/seo-indexing.php`)
- **Перелинковка** «Читайте также» по кластеру контента
- `robots.txt`: Sitemap → `sitemap_index.xml`
- Блог на проде: **13 publish**, 8 `future` (волны 4–5 по расписанию)

**Предыдущая сессия (06.07)**

**Сделано сегодня**

- P3 блог: **21 статья** в seed (волны 1–5), публикация по расписанию (`future` → `publish`)
- Волна 4 на проде: BYD Seal (live), Sorento / PC200 / доставка ДВ — по датам 09–15.07
- Волна 5 в seed: Alphard, X-Trail, Vezel, Carnival, BYD vs Zeekr — 18–31.07
- Гео-сниппеты: `/avto-iz-yaponii/`, `/avto-iz-korei/`, `/spectehnika/`, `/kontaktyi/`
- `sitemap_lots.xml`: **517 URL** (`generate_sitemap_lots`, лимит 340/страна)
- Админка: пункт меню **Яндекс Метрика** (ACF) — OAuth-токен + проверка API
- Клиентский отчёт: `seov/SEO-отчет-прогресс.md` + PDF; черновики Дзена: `seov/dzen/`
- Каталог на проде — не трогаем (зона другого разработчика)

**Посты блога:** URL без `/blog/` в пути (например `/obzor-byd-seal-iz-kitaya/`).

**Следующая сессия**

1. Вставить OAuth-токен Метрики в админке → подключить отчёты API в SEO-дашборд
2. Индексация блога в Вебмастере, 1-й съём позиций Topvisor
3. Публикация 2 адаптаций в Дзене (при доступе к каналу)
4. Статьи волны 4–5 выходят по расписанию автоматически

## Лицензия

Ядро WordPress и большинство плагинов распространяются под GPL. См. `license.txt` в корне и лицензии отдельных компонентов.

## Память агента (RAG)

Локальный поиск по знаниям проекта (без внешних зависимостей, SQLite FTS5):

```bash
# Поиск
python3 .cursor/rag/rag.py query "деплой на proauc.ru"

# Сохранить решение для следующих сессий
python3 .cursor/rag/rag.py remember "Текст" --tag decision

# Пересобрать индекс
python3 .cursor/rag/rag.py index
```

- `.cursor/memory/` — факты и решения
- `.cursor/knowledge/` — документация (SEO, сервер, тема)
- `.cursor/rules/agent-memory.mdc` — правило для Cursor Agent

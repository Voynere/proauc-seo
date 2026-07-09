# proauc-seo

Репозиторий кастомного кода сайта [proauc.ru](https://proauc.ru). Модель такая же, как у [atk-ved-custom-code](https://github.com/Voynere/atk-ved-custom-code) и [ferma-dv-custom-code](https://github.com/Voynere/ferma-dv-custom-code): в git попадает только то, что мы разрабатываем сами.

## Что в репозитории

- `wp-content/themes/proautospec/` — тема сайта (SEO, блог, шаблоны)
- `.cursor/` — память агента и документация проекта
- `scripts/` — деплой и служебные скрипты

## Что не в репозитории

- WordPress core (`wp-admin/`, `wp-includes/`, корневые `wp-*.php`)
- сторонние плагины (Rank Math, CF7, ACF, Autoptimize и др.)
- `assets/`, `api/` (~2.8 ГБ статики на сервере)
- uploads, кэш, языковые пакеты, бэкапы, логи
- чувствительные файлы: `wp-config.php`, `.htaccess`
- сгенерированные файлы: `sitemap_*.xml`, файлы верификации поисковиков
- **`seov/`** — локальная SEO-база, отчёты и планы

## Стек

- **CMS:** WordPress (на сервере, не в git)
- **Тема:** `proautospec` (Picostrap / Bootstrap 5)
- **SEO:** Rank Math + правки в теме (`rank-math.php`, `inc/blog-seo.php`)
- **Хостинг:** FASTPANEL, Ubuntu 24.04, Nginx + Apache

## Продакшен

| Параметр | Значение |
|----------|----------|
| Сервер | `188.120.251.205` |
| SSH | `ssh proauc` |
| Document root | `/var/www/proauc_ru_usr/data/www/proauc.ru` |
| Site user | `proauc_ru_usr` |

## Зоны ответственности

| Зона | Кто | Файлы |
|------|-----|-------|
| **Каталог / API** | другой разработчик | `functions.php`, `page-48.php`, `js/api/cars-catalog.js` |
| **SEO и тема** | мы | `rank-math.php`, `inc/blog-*.php`, `inc/seo-settings.php`, шаблоны |

**Прод — source of truth для каталога.** GitHub Actions не перезаписывает каталожные файлы. Перед локальной работой подтягивать их с сервера:

```bash
THEME=wp-content/themes/proautospec
ROOT=/var/www/proauc_ru_usr/data/www/proauc.ru
for f in functions.php page-48.php js/api/cars-catalog.js; do
  scp "proauc:$ROOT/$THEME/$f" "$THEME/$f"
done
```

## Локальная копия

Полная копия сайта может лежать локально в этой папке (core, uploads, плагины, `assets/`, `api/`), но git отслеживает только кастомную тему. Остальное игнорируется через `.gitignore`.

Синхронизация с сервера:

```bash
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

## Деплой

GitHub Actions при пуше в `main` (или вручную через `workflow_dispatch`) заливает `wp-content/themes/proautospec/` на сервер через rsync.

Required GitHub secrets:

- `SERVER_HOST` — `188.120.251.205`
- `SERVER_USER` — `root` (или пользователь с SSH-доступом)
- `SERVER_PORT` — `22`
- `SERVER_SSH_KEY` — приватный SSH-ключ
- `SERVER_PATH` — `/var/www/proauc_ru_usr/data/www/proauc.ru`

Ручной точечный деплой (как раньше):

```bash
THEME=wp-content/themes/proautospec
ROOT=/var/www/proauc_ru_usr/data/www/proauc.ru
for f in rank-math.php header.php footer.php home.php single.php category.php \
         loops/cards.php css/app.css spec.csv \
         inc/blog-seo.php inc/blog-articles.php inc/seo-settings.php \
         inc/metrika-reports.php inc/seo-indexing.php; do
  scp "$THEME/$f" "proauc:$ROOT/$THEME/$f"
done
```

После CSS/JS — сбросить кэш Autoptimize в админке WP.

Пересборка sitemap лотов: `https://proauc.ru/?sitemap-lots-create=1`

## Структура темы

```
wp-content/themes/proautospec/
├── rank-math.php          # SEO-фильтры Rank Math
├── inc/blog-seo.php       # блог: schema, CTA, FAQ
├── inc/blog-articles.php  # seed-статьи
├── inc/seo-settings.php   # админка «Яндекс Метрика»
├── inc/metrika-reports.php
└── inc/seo-indexing.php   # IndexNow
```

Подробнее: [docs/CUSTOM_SCOPE.md](docs/CUSTOM_SCOPE.md)

## Память агента (RAG)

```bash
python3 .cursor/rag/rag.py query "деплой на proauc.ru"
python3 .cursor/rag/rag.py remember "Текст" --tag decision
python3 .cursor/rag/rag.py index
```

## Лицензия

Ядро WordPress и сторонние плагины распространяются под GPL. Кастомная тема — по условиям проекта.

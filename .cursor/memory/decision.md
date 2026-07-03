# Decision


## 2026-07-03 04:10 UTC

Не трогать правки другого программиста на проде (functions.php slug/кэш каталога, page-48.php -SERIES, cars-catalog.js). Их зона — каталог/API. Наша зона — SEO и мелкие техфичи темы.

## 2026-07-03 04:13 UTC

Прод — source of truth для каталога. При расхождении: scp functions.php, page-48.php, cars-catalog.js с прода; наши SEO-правки подстраиваем поверх, чужой код каталога не меняем.

# Motorhome import tool

External importer for camper vans / motorhomes into the **«Авто в наличии»** section on [proauc.ru](https://proauc.ru/avto-v-nalichii/).

Pipeline: **source adapter → normalizer → pricing → media sideload → WP writer** (wp-cli or REST API).

## Setup

```bash
cd tools/motorhome-import
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cp config.example.yaml config.yaml
# Edit config.yaml for prod credentials when ready
```

Run on **architect** (local or prod SSH) per project convention.

## Translation (JP / KO → RU)

Titles/grades are translated in `finalize_listing()` via `motorhome_import/translate.py` (dictionary maps, no external API):

- **Fujicars** — JP dictionary + katakana transliteration → `Автодом ван-кон/кабина-кон …`
- **Encar** — KO brand/model/badge map → `Автодом ван-кон Grand Starex` / `Staria` (filter-friendly)
- **Bobaedream** — uses the same helpers when JP/KO text is present

| Source | Example in | Example out |
|--------|------------|-------------|
| JP | キャンピングカー バンコン ハイエース | Автодом ван-кон Hiace |
| KO | 현대 더 뉴 그랜드 스타렉스 캠핑카 | Автодом ван-кон Grand Starex |
| KO grade | 캠핑카 / 4WD 캠핑카 | Автодом / Автодом 4WD |

Demo one title/grade:

```bash
python -m motorhome_import translate-demo \
  --title 'キャンピングカー キャブコンｶﾑﾛｰﾄﾞ ﾅｯﾂRV ｸﾚｿﾝﾎﾞﾔｰｼﾞｭX 4WD' \
  --grade '無'

python -m motorhome_import translate-demo \
  --title '현대 더 뉴 그랜드 스타렉스 4WD 캠핑카' \
  --grade '캠핑카'
```

Retranslate existing prod posts (`_source` fujicars/bobaedream/encar):

```bash
python -m motorhome_import retranslate --config config.yaml --sources encar --dry-run
python -m motorhome_import retranslate --config config.yaml --sources encar --no-dry-run
```

Uses wp-cli (`wordpress.wp_path` or SSH) to update `post_title` and `properties_grade`.

## Usage

Dry-run Fujicars (no WP writes, prints JSON with `price_rub`):

```bash
python -m motorhome_import run --source fujicars --dry-run --limit 1
```

Dry-run Bobaedream with detail enrichment (gallery, specs, description):

```bash
python -m motorhome_import run --source bobaedream --dry-run --limit 1
```

With explicit config:

```bash
python -m motorhome_import run --source fujicars --dry-run --limit 2 --config config.example.yaml
```

Real import (requires `wp` CLI on prod path, `ssh_host`, or REST credentials):

```bash
python -m motorhome_import run --source fujicars --no-dry-run --limit 5
```

Probe source HTML/API structure:

```bash
python -m motorhome_import probe-bobaedream
python -m motorhome_import probe-bobaedream --parse --limit 2
python -m motorhome_import probe-encar
```

## Sources

| Source | Status | Notes |
|--------|--------|-------|
| **fujicars** | List + detail | `body=9` camping cars at `/search/list_en` |
| **bobaedream** | List + detail | Camp filter `?features=camp`, UTF-8, ~70/page, `?page=N` |
| **encar** | API list + detail | Ryvuss list + readside detail; Badge.캠핑카 server filter |

### Fujicars discovery (2026-07-09)

- Marketing site: `https://www.fujicars.jp/english/` — no inventory
- Inventory: `https://www.fujicars.jp/search/list_en?view=1&body=9&sort=publish2`
- Detail: `https://www.fujicars.jp/search/detail/{id}`
- Card selectors: `div.carDetailBox`, `li.carName`, `li.carGrade`, `li.carModelYearMilage`, `li.carPrice`
- Detail specs: `table.specTable`, gallery: `ul.slide_selector img`

### Bobaedream discovery (2026-07-09)

- Camp URL: `https://www.bobaedream.co.kr/cyber/CyberCar.php?features=camp`
- Encoding: **UTF-8** (not EUC-KR)
- List rows: `div.list-inner` → cells `.mode-cell.{thumb,title,year,fuel,km,price,seller}`
- Detail: `/cyber/CyberCar_view.php?no={id}&gubun=K|I`
- Detail selectors: `div.info-price div.title-area`, `div.detail-section table`, `div.detail-explanation div.explanation-box`, `div.gallery-view img`
- Pagination: server-side `?page=N` (~70 listings/page)
- **Playwright not required** for list or detail pages

### Encar discovery (2026-07-09, detail 2026-07-21)

- List UI: `https://www.encar.com/dc/dc_carsearchlist.do` — EUC-KR JS shell
- **List API (no Playwright):** `GET https://api.encar.com/search/car/list/general`
  - Params: `count=true`, `q=…`, `sr=|ModifiedDate|offset|limit`
  - Images: `https://ci.encar.com` + `Photos[].location` (list) / `photos[].path` (detail)
  - Detail URL: `https://fem.encar.com/cars/detail/{Id}`
- **Detail API (no Playwright):** `GET https://api.encar.com/v1/readside/vehicle/{Id}`
  - Specs: mileage, displacement, fuel, color, seats, body
  - Gallery: up to ~30 photos; description in `contents.text`
- **Camping filter (server-side):** exact Badge nodes
  - `(And.Hidden.N._.(Or.Badge.캠핑카._.Badge.4WD 캠핑카._.Badge.캠핑카/이동사무차.))` ≈ 229 listings
  - Keyword/Category nodes still 404/0; `camping_only` keeps client `캠핑` safety net
  - Optional `use_model_groups: true` broadens to van platforms (off by default)

## ACF mapping

Field keys exported from prod → `acf_mapping.yaml` (2026-07-09).

| ACF field | Meta key | Field key |
|-----------|----------|-----------|
| properties.price | `properties_price` | `field_661462d473b69` |
| properties.year | `properties_year` | `field_6614220aac3bc` |
| properties.engine-type | `properties_engine-type` | `field_661db79a84eea` |
| properties.drive-type | `properties_drive-type` | `field_66142592f4494` (values: `front`, `rear`, `4wd`) |
| photos gallery | `photos` | `field_661db2fc197a5` |

Select choices for engine-type, drive-type, param-name are in `acf_mapping.yaml`.

## Pricing (RUB)

Uses prod `get-price.php` (same as `page-car-lot.php`):

```
price_rub = result.sum (USD) × USDRUB_system
```

Params: `country=japan|korea`, `price` (JPY or KRW), `year`, `volume` (engine liters).

- Fujicars → `country=japan`, `price_jpy`
- Bobaedream / Encar → auto `country=korea` when `price_krw` present

Config: `import.pricing.enabled`, `import.pricing.api_url`. Fallback: `import.jpy_to_rub_rate`.

## Image sideload

1. Download URLs to temp dir (`/tmp/motorhome-import/`)
2. Import via **wp-cli** (local path or `ssh_host`), or **REST** media endpoint
3. Set featured image (first) + ACF `photos` gallery (attachment IDs)
4. **Dry-run**: skip upload, log URLs in output (`wp_result.media`)

## Config

See `config.example.yaml`:

- `wordpress.wp_path` — WP root for wp-cli on prod
- `wordpress.ssh_host` — e.g. `proauc` for remote wp-cli / media import
- `wordpress.user` / `app_password` — REST API fallback
- `import.pricing` — landed-cost API settings
- `import.sideload_images` — enable/disable media pipeline
- `import.fetch_details` — fetch detail pages/API (Fujicars, Bobaedream, Encar)
- `sources.fujicars.body_type` — `9` = camping cars
- `sources.encar.api_query` — Ryvuss query (default: camping Badge Or)
- `sources.encar.camping_only` — client `캠핑` safety net
- `sources.encar.use_model_groups` — optional van-platform broaden (off by default)

## Dedup

Post meta: `_source` + `_source_id` (Fujicars detail ID, e.g. `5181`).

## Open blockers

1. **End-to-end prod import** — run `--no-dry-run` on prod with wp-cli + SSH tested
2. **ACF parameters repeater** — flat properties/photos work; repeater rows need `update_field()` or eval-file
3. **Encar** — list+detail via API done; end-to-end `--no-dry-run` still pending

## Layout

```
tools/motorhome-import/
├── README.md
├── acf_mapping.yaml        # Prod ACF field keys + select choices
├── requirements.txt
├── config.example.yaml
└── motorhome_import/
    ├── cli.py              # CLI: run, probe-bobaedream, probe-encar
    ├── schema.py           # Canonical listing schema
    ├── acf.py              # ACF meta builder from mapping
    ├── pricing.py          # get-price.php → price_rub
    ├── media.py            # Download + wp media sideload
    ├── normalizer.py       # Shared parsers / mappers
    ├── translate.py        # JP→RU title and grade translation
    ├── wp_writer.py        # Dry-run + wp-cli/REST writer
    └── adapters/
        ├── fujicars.py     # List + detail
        ├── bobaedream.py   # List + detail
        └── encar.py        # API list + readside detail + probe
```

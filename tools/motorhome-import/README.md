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
| **encar** | API list (beta) | Ryvuss API via `api.encar.com`; camping filter TBD |

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

### Encar discovery (2026-07-09)

- List UI: `https://www.encar.com/dc/dc_carsearchlist.do` — EUC-KR JS shell
- **API (works without Playwright):** `GET https://api.encar.com/search/car/list/general`
  - Params: `count=true`, `q=(And.Hidden.N._.CarType.N.)`, `sr=|ModifiedDate|offset|limit`
  - Images: `https://ci.encar.com` + `Photos[].location`
  - Detail URL: `https://www.encar.com/dc/dc_cardetailview.do?carid={Id}`
- **Camping filter blocker:** no confirmed Ryvuss node for `캠핑카` (Keyword/BadgeGroup/Category queries return 0 or 404)
- **Workaround:** `camping_only` + client filter on Badge/Model containing `캠핑`, or `model_groups` (스타리아, 그랜드스타렉스, 카운티, …)
- Detail page specs: not implemented (may need Playwright)

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
- `import.fetch_details` — fetch detail pages (Fujicars, Bobaedream)
- `sources.fujicars.body_type` — `9` = camping cars
- `sources.encar.api_query` — Ryvuss query string
- `sources.encar.camping_only` — filter to campers

## Dedup

Post meta: `_source` + `_source_id` (Fujicars detail ID, e.g. `5181`).

## Open blockers

1. **End-to-end prod import** — run `--no-dry-run` on prod with wp-cli + SSH tested
2. **ACF parameters repeater** — flat properties/photos work; repeater rows need `update_field()` or eval-file
3. **Encar camping filter** — no server-side filter confirmed; using model-group heuristic
4. **Encar detail** — list-only from API; detail specs not fetched yet

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
    ├── wp_writer.py        # Dry-run + wp-cli/REST writer
    └── adapters/
        ├── fujicars.py     # List + detail
        ├── bobaedream.py   # List + detail
        └── encar.py        # API list + probe
```

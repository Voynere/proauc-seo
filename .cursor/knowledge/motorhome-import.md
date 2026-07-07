# Motorhome import (авто в наличии)

Planning doc for importing motorhomes / camping cars from Korean and Japanese marketplaces into the **«Авто в наличии»** section on proauc.ru.

**Status:** discovery complete (2026-07-07). No scrapers in repo yet. Next session: Phase 0 tasks.

## Target section

| Key | Value |
|-----|-------|
| URL | https://proauc.ru/avto-v-nalichii/ |
| CPT | `avto` |
| Category | ID **1** (default «в наличии» listing) |
| List template | `page-avto-v-nalichii.php` |
| Card loop | `template-parts/loops/avto.php` |
| Single template | `single-avto.php` |
| SEO override | `rank-math.php` → `/avto-v-nalichii/` description |

Other `avto` listings use category archives (`archive.php`) with per-category `category__in`; motorhomes go into category 1 on the main «в наличии» page.

## ACF fields (current schema)

From theme templates — **export from prod ACF in Phase 0** to confirm field keys and select options.

| Field | Type / usage |
|-------|----------------|
| `properties` | Group: `year`, `capacity`, `mileage`, `engine-type` (select), `drive-type` (select), `grade`, `price` (RUB) |
| `photos` | Gallery (attachment IDs) — used in `single-avto.php` slider |
| `parameters` | Repeater: `param-name` (select), `param-value` (text) |
| `year` | Top-level field (shown in H1 on single) |
| Legacy | `old-id`, `old-photos`, `old-slug`, `old-params` — archive posts only; **do not use** for new imports |

Card loop (`avto.php`) reads `properties` + featured thumbnail or legacy `old-*` fields.

## Data sources

| Source | Market | Filter / URL | Language | Notes |
|--------|--------|--------------|----------|-------|
| **Fujicars** | Japan | English site | EN | Start here — simpler HTML, good for first adapter |
| **Bobaedream** | Korea | Camp / camping filter | KO | Korean marketplace, camp category |
| **Encar** | Korea | Camping car filter | KO | Strategy **TBD**: check prod `/api/` Korea endpoints first; scraper only if API unsuitable |

**No existing scrapers** for these sources in this repo.

## Architecture (recommended)

Separate **external importer** (not inside WordPress theme, not inside `/api/` auction pipeline):

```
[Source adapter] → [Normalizer] → [WP writer via WP-CLI] → post_type=avto, cat=1
```

### Components

1. **Adapters** — one per source (Fujicars, Bobaedream, Encar); fetch listing + detail pages or API JSON.
2. **Normalizer** — map source fields → canonical schema (`title`, `properties.*`, `parameters[]`, `photos[]`, source URL).
3. **WP writer** — `wp post create` / `wp post meta update` / media sideload; or small PHP script bootstrapping WP.
4. **Dedup** — post meta `_source` (e.g. `fujicars`) + `_source_id` (listing ID on source site); upsert by meta query before create.
5. **Images** — download to local temp → `wp media import` → attach to gallery `photos`; serve from WP uploads (mirror, do not hotlink).

### Boundaries

| Zone | Rule |
|------|------|
| `/api/` auction catalog (Japan/Korea/China) | **Do not modify** — other dev, prod source of truth |
| Theme `functions.php`, `page-48.php`, `js/api/*` | **Do not modify** for import |
| CPT `avto` + ACF on prod | Write via importer only |
| This repo | Importer code under e.g. `tools/motorhome-import/` (TBD in Phase 1) |

## Implementation phases

### Phase 0 — Discovery (next session)

- [ ] Sync `/api/` from prod (reference only; check if Encar/camping data already exists in Korea API).
- [ ] Export ACF field group for `avto` from prod (`acf export` or JSON sync).
- [ ] Sample 3–5 live listings per source; document HTML/API field mapping.
- [ ] Confirm category ID 1 and slug structure for new singles (`/avto/...` or custom).

### Phase 1 — Skeleton

- [ ] Create `tools/motorhome-import/` with CLI entrypoint, config, logging.
- [ ] Canonical listing schema (JSON or PHP array).
- [ ] WP writer stub + dedup meta keys.
- [ ] Dry-run mode (no WP writes).

### Phase 2 — Fujicars adapter

- [ ] List + detail fetch, normalizer, image mirror.
- [ ] First end-to-end import to staging/local WP.

### Phase 3 — Bobaedream adapter

- [ ] Camp filter URLs, KO text handling, same pipeline.

### Phase 4 — Encar adapter

- [ ] Decision: reuse Korea API vs dedicated scraper.
- [ ] Camping car filter integration.

### Phase 5 — Ops

- [ ] Cron or systemd timer on server (or GitHub Action → SSH).
- [ ] Stale listing cleanup (source gone → draft/trash).
- [ ] Monitoring / alert on import failures.

## WP-CLI sketch

```bash
# Dedup check
wp post list --post_type=avto --meta_key=_source_id --meta_value=FUJICARS_12345 --format=ids

# Create (after media sideload)
wp post create --post_type=avto --post_title='...' --post_status=publish \
  --post_category=1 --porcelain
wp post meta update <ID> _source fujicars
wp post meta update <ID> _source_id FUJICARS_12345
# ACF: use acf update or update_field() in bootstrap script
```

ACF field updates are easier via a small PHP bootstrap (`wp eval-file`) than raw CLI.

## Related files

| Path | Role |
|------|------|
| `wp-content/themes/proautospec/page-avto-v-nalichii.php` | Main listing page |
| `wp-content/themes/proautospec/template-parts/loops/avto.php` | Card template |
| `wp-content/themes/proautospec/single-avto.php` | Single motorhome/car |
| `wp-content/themes/proautospec/archive.php` | Category archives for other `avto` categories |
| `wp-content/themes/proautospec/rank-math.php` | SEO for `/avto-v-nalichii/` |

## Open questions

1. **Encar** — does prod Korea `/api/` already expose camping cars?
2. **Pricing** — RUB «цена в РФ»: fixed formula from source price or manual?
3. **Category** — motorhomes only in cat 1, or dedicated subcategory/archive later?
4. **Staging** — local Docker WP vs import directly to prod (prefer staging first).

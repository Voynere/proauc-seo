"""CLI entry point."""

from __future__ import annotations

import argparse
import json
import logging
import sys
from pathlib import Path

from .adapters import get_adapter
from .config import load_config
from .http import HttpClient
from .pricing import apply_pricing
from .wp_writer import WordPressWriter

DEFAULT_CONFIG = Path(__file__).resolve().parent.parent / "config.yaml"


def setup_logging(level: str) -> None:
    logging.basicConfig(
        level=getattr(logging, level.upper(), logging.INFO),
        format="%(asctime)s %(levelname)s %(name)s: %(message)s",
    )


def enrich_listing(listing, config, http):
    from .normalizer import finalize_listing

    listing = finalize_listing(listing)
    listing = apply_pricing(
        listing,
        http=http,
        pricing_enabled=config.import_.pricing.enabled,
        pricing_api_url=config.import_.pricing.api_url,
        pricing_country=config.import_.pricing.country,
        jpy_to_rub_rate=config.import_.jpy_to_rub_rate,
    )
    return listing


def cmd_run(args: argparse.Namespace) -> int:
    config_path = Path(args.config)
    if not config_path.exists():
        logging.error("Config not found: %s (copy config.example.yaml)", config_path)
        return 1

    config = load_config(config_path)
    if args.dry_run is not None:
        config.import_.dry_run = args.dry_run
    if args.limit is not None:
        config.import_.limit = args.limit

    setup_logging(config.logging_level)
    logger = logging.getLogger("motorhome_import.cli")

    http = HttpClient(
        user_agent=config.http.user_agent,
        timeout=config.http.timeout_seconds,
        rate_limit=config.http.rate_limit,
    )

    try:
        adapter = get_adapter(args.source, config, http)
    except ValueError as exc:
        logger.error("%s", exc)
        return 1

    writer = WordPressWriter(config, http=http)
    results = []
    count = 0

    for listing in adapter.fetch_listings():
        listing = enrich_listing(listing, config, http)

        if args.source == "fujicars":
            from .adapters.fujicars import has_valid_price_jpy

            if listing.sold_out or not has_valid_price_jpy(listing):
                logger.info(
                    "Skipping %s — sold out or missing JPY price: %s",
                    listing.source_id,
                    listing.title[:80],
                )
                continue

        wp_result = writer.upsert(listing)
        count += 1
        if args.json or config.import_.dry_run:
            print(writer.format_listing_json(listing, wp_result))
            if not args.json:
                print("---")
        results.append({"source_id": listing.source_id, "title": listing.title, "wp": wp_result})

    logger.info("Processed %s listing(s) from %s", count, args.source)

    if args.output:
        Path(args.output).write_text(
            json.dumps(results, ensure_ascii=False, indent=2),
            encoding="utf-8",
        )

    return 0 if count > 0 else 2


def cmd_retranslate(args: argparse.Namespace) -> int:
    config_path = Path(args.config)
    if not config_path.exists():
        logging.error("Config not found: %s", config_path)
        return 1

    config = load_config(config_path)
    if args.dry_run is not None:
        config.import_.dry_run = args.dry_run

    setup_logging(config.logging_level)
    writer = WordPressWriter(config)
    results = writer.retranslate_existing_posts(
        sources=tuple(args.sources),
        dry_run=config.import_.dry_run,
    )
    changed = [r for r in results if r.get("changed")]
    print(json.dumps(results, ensure_ascii=False, indent=2))
    logging.getLogger("motorhome_import.cli").info(
        "Retranslate: %s post(s) scanned, %s changed, dry_run=%s",
        len(results),
        len(changed),
        config.import_.dry_run,
    )
    return 0


def cmd_backfill_photos(args: argparse.Namespace) -> int:
    config_path = Path(args.config)
    if not config_path.exists():
        logging.error("Config not found: %s", config_path)
        return 1

    config = load_config(config_path)
    if args.dry_run is not None:
        config.import_.dry_run = args.dry_run

    setup_logging(config.logging_level)
    writer = WordPressWriter(config)
    results = writer.backfill_photos_meta(
        sources=tuple(args.sources),
        dry_run=config.import_.dry_run,
    )
    fixed = [r for r in results if r.get("fixed")]
    print(json.dumps(results, ensure_ascii=False, indent=2))
    logging.getLogger("motorhome_import.cli").info(
        "Backfill photos: %s post(s) scanned, %s fixed, dry_run=%s",
        len(results),
        len(fixed),
        config.import_.dry_run,
    )
    return 0


def cmd_translate_demo(args: argparse.Namespace) -> int:
    from .translate import translate_grade, translate_title

    title = args.title
    grade = args.grade
    print(json.dumps({
        "title_before": title,
        "title_after": translate_title(title),
        "grade_before": grade,
        "grade_after": translate_grade(grade),
    }, ensure_ascii=False, indent=2))
    return 0


def cmd_probe_bobaedream(args: argparse.Namespace) -> int:
    """Fetch Bobaedream camp page and print structure summary."""
    config_path = Path(args.config)
    config = load_config(config_path) if config_path.exists() else load_config(
        Path(__file__).resolve().parent.parent / "config.example.yaml"
    )
    setup_logging(config.logging_level)

    http = HttpClient(
        user_agent=config.http.user_agent,
        timeout=config.http.timeout_seconds,
        rate_limit=config.http.rate_limit,
    )

    from .adapters.bobaedream import BobaedreamAdapter, probe_page

    url = args.url or "https://www.bobaedream.co.kr/cyber/CyberCar.php?features=camp"
    summary = probe_page(http, url, fetch_page2=not args.no_page2)
    print(json.dumps(summary, ensure_ascii=False, indent=2))

    if args.parse:
        adapter = BobaedreamAdapter(config=config, http=http)
        for i, listing in enumerate(adapter.fetch_listings()):
            listing = enrich_listing(listing, config, http)
            print(json.dumps(listing.to_dict(), ensure_ascii=False, indent=2))
            if args.limit and i + 1 >= args.limit:
                break
            print("---")

    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="motorhome_import",
        description="Import motorhomes into proauc.ru avto CPT",
    )
    parser.add_argument(
        "-c",
        "--config",
        default=str(DEFAULT_CONFIG),
        help="Path to config.yaml",
    )

    sub = parser.add_subparsers(dest="command", required=True)

    run_parser = sub.add_parser("run", help="Run import for a source")
    run_parser.add_argument(
        "-c",
        "--config",
        default=str(DEFAULT_CONFIG),
        help="Path to config.yaml",
    )
    run_parser.add_argument(
        "--source",
        required=True,
        choices=["fujicars", "bobaedream", "encar"],
        help="Data source adapter",
    )
    run_parser.add_argument(
        "--dry-run",
        action=argparse.BooleanOptionalAction,
        default=None,
        help="Print JSON without writing to WordPress",
    )
    run_parser.add_argument(
        "--limit",
        type=int,
        default=None,
        help="Max listings to process",
    )
    run_parser.add_argument(
        "--json",
        action="store_true",
        help="Always print full JSON per listing",
    )
    run_parser.add_argument(
        "--output",
        help="Write summary JSON to file",
    )
    run_parser.set_defaults(func=cmd_run)

    retranslate_parser = sub.add_parser(
        "retranslate",
        help="Retranslate titles/grades on existing WP posts (_source fujicars/bobaedream/encar)",
    )
    retranslate_parser.add_argument("-c", "--config", default=str(DEFAULT_CONFIG))
    retranslate_parser.add_argument(
        "--sources",
        nargs="+",
        default=["fujicars", "bobaedream", "encar"],
        choices=["fujicars", "bobaedream", "encar"],
    )
    retranslate_parser.add_argument(
        "--dry-run",
        action=argparse.BooleanOptionalAction,
        default=True,
        help="Preview changes without writing (default: true)",
    )
    retranslate_parser.set_defaults(func=cmd_retranslate)

    backfill_parser = sub.add_parser(
        "backfill-photos",
        help="Fix double-serialized ACF photos gallery on imported posts",
    )
    backfill_parser.add_argument("-c", "--config", default=str(DEFAULT_CONFIG))
    backfill_parser.add_argument(
        "--sources",
        nargs="+",
        default=["fujicars", "bobaedream"],
        choices=["fujicars", "bobaedream"],
    )
    backfill_parser.add_argument(
        "--dry-run",
        action=argparse.BooleanOptionalAction,
        default=True,
        help="Preview without writing (default: true)",
    )
    backfill_parser.set_defaults(func=cmd_backfill_photos)

    demo_parser = sub.add_parser("translate-demo", help="Show title/grade translation for one string")
    demo_parser.add_argument("--title", required=True)
    demo_parser.add_argument("--grade", default="無")
    demo_parser.set_defaults(func=cmd_translate_demo)

    probe_parser = sub.add_parser("probe-bobaedream", help="Probe Bobaedream camp HTML structure")
    probe_parser.add_argument("--url", help="Camp list URL")
    probe_parser.add_argument("--parse", action="store_true", help="Also run list parser")
    probe_parser.add_argument("--limit", type=int, default=2)
    probe_parser.add_argument("--no-page2", action="store_true", help="Skip page=2 pagination test")
    probe_parser.add_argument("-c", "--config", default=str(DEFAULT_CONFIG))
    probe_parser.set_defaults(func=cmd_probe_bobaedream)

    encar_probe = sub.add_parser("probe-encar", help="Probe Encar Ryvuss API and camping filter")
    encar_probe.add_argument("-c", "--config", default=str(DEFAULT_CONFIG))
    encar_probe.add_argument("--sample-size", type=int, default=5)
    encar_probe.set_defaults(func=cmd_probe_encar)

    return parser


def cmd_probe_encar(args: argparse.Namespace) -> int:
    config_path = Path(args.config)
    config = load_config(config_path) if config_path.exists() else load_config(
        Path(__file__).resolve().parent.parent / "config.example.yaml"
    )
    setup_logging(config.logging_level)

    http = HttpClient(
        user_agent=config.http.user_agent,
        timeout=config.http.timeout_seconds,
        rate_limit=config.http.rate_limit,
    )

    from .adapters.encar import probe_encar

    summary = probe_encar(http, sample_size=args.sample_size)
    print(json.dumps(summary, ensure_ascii=False, indent=2))
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    return args.func(args)


if __name__ == "__main__":
    sys.exit(main())

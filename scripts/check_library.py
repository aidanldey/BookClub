#!/usr/bin/env python3
"""Validate data/library.json — the Reading Room shelf.

Two jobs, in the spirit of validate_page.py: catch the structural mistakes a
schema would catch, and catch the two mistakes that actually get a club in
trouble — hosting something still in copyright, and shipping a link nobody
opened.

    python3 scripts/check_library.py
    python3 scripts/check_library.py --check-links

Link verdicts read the same as validate_page.py's: DEAD (404/410/no such host)
means the URL is wrong or made up; UNVERIFIED (403/429/timeout, or a network
policy in the way) means the checker was blocked and a human has to look.

Exit status is 1 when something is wrong, 0 otherwise. Warnings never fail the
run — they are judgment calls for the editor.
"""

import argparse
import json
import re
import sys
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from validate_page import probe  # noqa: E402  (same directory, same UA rules)

# 95 years after publication a US copyright expires. In 2026 that is everything
# published in 1930 or earlier. Bump this line each January.
US_PUBLIC_DOMAIN_BEFORE = 1931

REQUIRED = (
    "slug", "title", "author", "translator", "year_published", "rights",
    "rights_basis", "fan_page_slug", "source", "source_url", "shelf_file",
    "epub_url", "status",
)
SHELF_FILE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9 ._-]*\.epub$")
STATUSES = ("not-uploaded", "uploaded", "published")
SLUG = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


def load(path):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except FileNotFoundError:
        sys.exit(f"no such file: {path}")
    except json.JSONDecodeError as e:
        sys.exit(f"not valid JSON: {path} — {e}")


def check_structure(doc, fan_slugs):
    errors, warnings = [], []

    sources = doc.get("sources", {})
    books = doc.get("books")
    if not isinstance(books, list):
        return ["'books' must be a list"], warnings

    seen = set()
    for i, book in enumerate(books):
        where = f"books[{i}]"
        slug = book.get("slug", "")
        if slug:
            where = f"{where} ({slug})"

        for field in REQUIRED:
            if field not in book:
                errors.append(f"{where}: missing '{field}'")

        if slug in seen:
            errors.append(f"{where}: duplicate slug")
        seen.add(slug)

        if slug and not SLUG.match(slug):
            errors.append(f"{where}: slug must be lowercase words joined by hyphens")

        if book.get("status") not in STATUSES:
            errors.append(f"{where}: status must be one of {', '.join(STATUSES)}")

        if book.get("source") not in sources:
            errors.append(f"{where}: source '{book.get('source')}' is not in the sources block")

        # The rule the whole shelf rests on.
        year = book.get("year_published")
        if isinstance(year, int) and year >= US_PUBLIC_DOMAIN_BEFORE:
            errors.append(
                f"{where}: published {year} — not public domain in the US under the "
                f"pre-{US_PUBLIC_DOMAIN_BEFORE} rule. Remove it, or replace this check "
                "with the specific reason it is clear."
            )

        rights = (book.get("rights") or "").strip()
        if not rights:
            errors.append(f"{where}: rights statement is required — it renders on the page")
        elif isinstance(year, int) and 1900 < year < US_PUBLIC_DOMAIN_BEFORE and "United States" not in rights:
            warnings.append(
                f"{where}: published {year}, so it is likely US-only. Say 'in the United "
                "States' rather than implying worldwide public domain."
            )

        translator = book.get("translator")
        if translator and "translat" not in (book.get("rights_basis") or "").lower():
            warnings.append(
                f"{where}: has a translator but rights_basis doesn't account for the "
                "translation, which carries its own copyright."
            )

        fan = book.get("fan_page_slug")
        if fan and fan_slugs and fan not in fan_slugs:
            warnings.append(f"{where}: fan_page_slug '{fan}' isn't in data/books.json")
        if fan and fan != slug:
            warnings.append(
                f"{where}: fan_page_slug '{fan}' differs from the slug, so the fan page's "
                "'Read it free' button needs pinning by hand in wp-admin."
            )

        # Mirrors blc_reader_sanitize_shelf_file(): a bare .epub filename, never
        # a path. Anything else would be dropped by the theme on save.
        shelf_file = book.get("shelf_file")
        if shelf_file and not SHELF_FILE.match(shelf_file):
            errors.append(
                f"{where}: shelf_file '{shelf_file}' is not a plain .epub filename — "
                "it names a file in /shelf/, not a path or a URL"
            )

        if book.get("status") != "not-uploaded" and not (shelf_file or book.get("epub_url")):
            errors.append(
                f"{where}: status is '{book.get('status')}' but neither shelf_file nor "
                "epub_url says where the file is"
            )

        if shelf_file and slug:
            stem = shelf_file[: -len(".epub")].lower()
            if stem != slug and not stem.endswith(("_" + slug, "-" + slug)):
                warnings.append(
                    f"{where}: shelf_file '{shelf_file}' doesn't end in the slug, so the "
                    "theme won't adopt it automatically — name it in wp-admin."
                )

    return errors, warnings


def check_links(doc):
    urls = []
    for book in doc.get("books", []):
        for field in ("source_url", "epub_url"):
            value = book.get(field)
            if isinstance(value, str) and value.startswith(("http://", "https://")):
                urls.append((f"{book.get('slug')}.{field}", value))
    for key, source in doc.get("sources", {}).items():
        if isinstance(source.get("home"), str):
            urls.append((f"sources.{key}.home", source["home"]))

    if not urls:
        return [], [], 0

    with ThreadPoolExecutor(max_workers=8) as pool:
        results = list(pool.map(lambda u: probe(u[1]), urls))

    dead, unverified = [], []
    for (field, url), (verdict, detail) in zip(urls, results):
        if verdict == "DEAD":
            dead.append(f"{field}: {url} [{detail}]")
        elif verdict == "UNVERIFIED":
            unverified.append(f"{field}: {url} [{detail}]")
    return dead, unverified, len(urls)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("library", nargs="?", type=Path, default=Path("data/library.json"))
    ap.add_argument("--books", type=Path, default=Path("data/books.json"))
    ap.add_argument("--check-links", action="store_true", help="probe every URL in the file")
    args = ap.parse_args()

    doc = load(args.library)

    fan_slugs = set()
    if args.books.exists():
        fan_slugs = {b.get("slug") for b in load(args.books).get("books", [])}

    errors, warnings = check_structure(doc, fan_slugs)

    books = doc.get("books", [])
    unfiled = [b["slug"] for b in books if not (b.get("shelf_file") or b.get("epub_url"))]

    if args.check_links:
        dead, unverified, total = check_links(doc)
        if dead:
            errors.append(
                f"LINKS: {len(dead)} DEAD of {total} — a dead link means the edition was "
                "never opened. Fetch it, or clear the field."
            )
            errors.extend("  " + line for line in dead)
        if unverified:
            warnings.append(f"LINKS: {len(unverified)} of {total} unverified — check by hand:")
            warnings.extend("  " + line for line in unverified)
        if not dead and not unverified and total:
            print(f"LINKS: {total} OK")

    for line in warnings:
        print(f"warning: {line}")
    for line in errors:
        print(f"ERROR: {line}")

    print(
        f"\n{len(books)} books on the shelf, "
        f"{len(books) - len(unfiled)} with a file, {len(unfiled)} still to fetch."
    )
    if unfiled:
        print("to fetch: " + ", ".join(unfiled))

    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())

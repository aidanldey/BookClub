#!/usr/bin/env python3
"""Validate a fan page research file: schema shape, then link liveness.

    python3 scripts/validate_page.py data/pages/<slug>.json [--check-links]

Exits non-zero if anything fails. Link problems are reported in three buckets,
because they mean different things:

  DEAD        404/410 or DNS failure. Almost always a fabricated permalink.
              These must be removed before the page ships.
  UNVERIFIED  403/429/timeout. Reddit and Goodreads rate-limit and block
              automated requests, so this is usually the checker being blocked,
              not a bad link. Spot-check by hand.
  OK          200.

Uses jsonschema when installed; falls back to a built-in structural check that
covers required fields, unknown fields, enums, and types.
"""
import argparse
import json
import sys
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SCHEMA = ROOT / "research" / "fan-page.schema.json"
UA = "Mozilla/5.0 (compatible; BookClubLinkCheck/1.0)"


# ---------------------------------------------------------------- schema

def check_schema(doc, schema):
    """Return a list of error strings."""
    try:
        import jsonschema
    except ImportError:
        return _fallback_check(doc, schema, schema, "$")
    validator = jsonschema.Draft202012Validator(schema)
    return [
        f"{'$.' + '.'.join(str(p) for p in e.absolute_path) if e.absolute_path else '$'}: {e.message}"
        for e in sorted(validator.iter_errors(doc), key=lambda e: list(e.absolute_path))
    ]


def _resolve(node, root):
    while "$ref" in node:
        ref = node["$ref"]
        if not ref.startswith("#/"):
            return node
        target = root
        for part in ref[2:].split("/"):
            target = target[part]
        node = {**target, **{k: v for k, v in node.items() if k != "$ref"}}
    return node


_TYPES = {"object": dict, "array": list, "string": str, "integer": int,
          "boolean": bool, "number": (int, float), "null": type(None)}


def _fallback_check(node, schema, root, path):
    """Minimal Draft-2020-12 subset: type, required, additionalProperties, enum."""
    errors = []
    schema = _resolve(schema, root)
    types = schema.get("type")
    if types:
        types = [types] if isinstance(types, str) else types
        expected = tuple(_TYPES[t] for t in types if t in _TYPES)
        # bool is a subclass of int; don't let True satisfy "integer"
        ok = isinstance(node, expected) and not (
            isinstance(node, bool) and bool not in expected
        )
        if not ok:
            errors.append(f"{path}: expected {'/'.join(types)}, got {type(node).__name__}")
            return errors
    if "enum" in schema and node not in schema["enum"]:
        errors.append(f"{path}: {node!r} not one of {schema['enum']}")
    if isinstance(node, dict):
        props = schema.get("properties", {})
        for key in schema.get("required", []):
            if key not in node:
                errors.append(f"{path}: missing required field '{key}'")
        if schema.get("additionalProperties") is False:
            for key in node:
                if key not in props:
                    errors.append(f"{path}: unknown field '{key}'")
        for key, value in node.items():
            if key in props:
                errors.extend(_fallback_check(value, props[key], root, f"{path}.{key}"))
    elif isinstance(node, list) and "items" in schema:
        for i, item in enumerate(node):
            errors.extend(_fallback_check(item, schema["items"], root, f"{path}[{i}]"))
    return errors


# ---------------------------------------------------------------- editorial

def check_editorial(doc):
    """Rules the schema can't express. Warnings, not hard failures."""
    warnings = []
    voices = doc.get("reader_voices") or []
    if len(voices) < 6:
        warnings.append(f"only {len(voices)} reader voices (want 6-12)")
    angles = {v.get("angle") for v in voices}
    for angle, why in [
        ("critical", "no critical voice - a page where everyone agrees reads like marketing"),
        ("converted-skeptic", "no converted-skeptic voice - these convert browsers better than praise"),
        ("reading-experience", "no reading-experience voice"),
    ]:
        if angle not in angles:
            warnings.append(why)
    for i, v in enumerate(voices):
        words = len(v.get("quote", "").split())
        if words > 60:
            warnings.append(f"reader_voices[{i}] is {words} words - trim to 1-3 sentences")
    if not (doc.get("book_quotes") or []):
        warnings.append("no book quotes")
    notes = doc.get("research_notes") or {}
    if len(notes.get("sources") or []) < 15:
        warnings.append(f"only {len(notes.get('sources') or [])} sources (prompt asks for 15+)")
    if not notes.get("gaps"):
        warnings.append("research_notes.gaps is empty across 15 sections - suspicious; "
                        "it usually means fields were filled that shouldn't have been")
    filled = [k for k in doc if k not in ("book", "research_notes") and doc.get(k)]
    if len(filled) < 8:
        warnings.append(f"only {len(filled)} sections filled - too thin to cherry-pick from")
    return warnings


# ---------------------------------------------------------------- links

def collect_urls(node, path="$", found=None):
    """Every URL in the document, with the field it came from."""
    if found is None:
        found = []
    if isinstance(node, dict):
        for key, value in node.items():
            if isinstance(value, str) and value.startswith(("http://", "https://")):
                found.append((f"{path}.{key}", value))
            else:
                collect_urls(value, f"{path}.{key}", found)
    elif isinstance(node, list):
        for i, item in enumerate(node):
            if isinstance(item, str) and item.startswith(("http://", "https://")):
                found.append((f"{path}[{i}]", item))
            else:
                collect_urls(item, f"{path}[{i}]", found)
    return found


def probe(url, timeout=15):
    req = urllib.request.Request(url, headers={"User-Agent": UA}, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return ("OK", resp.status)
    except urllib.error.HTTPError as e:
        if e.code in (404, 410):
            return ("DEAD", e.code)
        return ("UNVERIFIED", e.code)
    except Exception as e:  # DNS failure, TLS, timeout, proxy refusal
        text = str(e)
        if "Tunnel connection failed" in text or "ProxyError" in text:
            return ("UNVERIFIED", "proxy-blocked")
        if "NameResolution" in text or "nodename nor servname" in text:
            return ("DEAD", "no-such-host")
        return ("UNVERIFIED", type(e).__name__)


def check_links(doc):
    urls = collect_urls(doc)
    if not urls:
        return [], []
    with ThreadPoolExecutor(max_workers=8) as pool:
        results = list(pool.map(lambda u: probe(u[1]), urls))
    dead, unverified = [], []
    for (field, url), (verdict, detail) in zip(urls, results):
        if verdict == "DEAD":
            dead.append(f"{field}: {url} [{detail}]")
        elif verdict == "UNVERIFIED":
            unverified.append(f"{field}: {url} [{detail}]")
    return dead, unverified


# ---------------------------------------------------------------- main

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("page", type=Path)
    ap.add_argument("--check-links", action="store_true",
                    help="probe every URL (slow; run before publishing)")
    args = ap.parse_args()

    doc = json.loads(args.page.read_text())
    schema = json.loads(SCHEMA.read_text())

    failed = False

    errors = check_schema(doc, schema)
    if errors:
        failed = True
        print(f"SCHEMA: {len(errors)} error(s)")
        for e in errors[:40]:
            print(f"  - {e}")
        if len(errors) > 40:
            print(f"  ... {len(errors) - 40} more")
    else:
        print("SCHEMA: ok")

    warnings = check_editorial(doc)
    if warnings:
        print(f"EDITORIAL: {len(warnings)} warning(s)")
        for w in warnings:
            print(f"  - {w}")
    else:
        print("EDITORIAL: ok")

    if args.check_links:
        dead, unverified = check_links(doc)
        total = len(collect_urls(doc))
        if dead:
            failed = True
            print(f"LINKS: {len(dead)} DEAD of {total} - remove these, they are "
                  f"probably fabricated")
            for d in dead:
                print(f"  - {d}")
        if unverified:
            print(f"LINKS: {len(unverified)} unverified of {total} "
                  f"(blocked or rate-limited; spot-check by hand)")
            for u in unverified[:15]:
                print(f"  - {u}")
            if len(unverified) > 15:
                print(f"  ... {len(unverified) - 15} more")
            if sum("proxy-blocked" in u for u in unverified) > total / 2:
                print("  note: most failures are proxy refusals, so this run "
                      "checked nothing. Re-run where outbound HTTP is allowed.")
        if not dead and not unverified:
            print(f"LINKS: all {total} ok")
    else:
        print("LINKS: skipped (pass --check-links)")

    print("\nFAIL" if failed else "\nPASS")
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())

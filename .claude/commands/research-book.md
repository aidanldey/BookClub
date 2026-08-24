---
description: Research one book's fan page and file the JSON in data/pages/
argument-hint: <slug>  (e.g. east-of-eden, or "list" to see the queue)
allowed-tools: Read, Write, Edit, Bash, Glob, Grep, WebSearch, WebFetch
---

Research the fan page for the book with slug `$1` and produce
`data/pages/$1.json`.

## 1. Resolve the book

Read `data/books.json` and find the entry whose `slug` is `$1`.

- If `$1` is empty or `list`, print the queue grouped by `status` (slug, title,
  author) and stop.
- If there's no exact slug match, don't guess — show the closest 3–5 slugs by
  title similarity and stop.
- If the entry's `status` is already `researched` or later, say so, show when it
  was researched, and ask whether to re-research before doing anything. A
  re-research overwrites a file someone may have already edited.

## 2. Load the rules

Read, in this order:

1. `research/FAN_PAGE_PROMPT.md` — the research instructions
2. `research/QUESTIONS.md` — the 15 question groups
3. `research/SOURCING.md` — attribution and quoting rules
4. `research/fan-page.schema.json` — the output shape

Take the block between the rulers in `FAN_PAGE_PROMPT.md` as your instructions,
substituting the book's `title` and `author` for `{{BOOK_TITLE}}` and
`{{AUTHOR}}`. Then append every variant block named in the book's
`prompt_variants` array — the variants are at the bottom of that file:

| flag | append |
|---|---|
| `translated` | **Translated work** |
| `series` | **Series page** |
| `divisive` | **Divisive book** |

If the user passed extra words after the slug (`$ARGUMENTS` beyond `$1`), treat
them as an additional instruction — e.g. `/research-book dune quick pass` means
also append the **Quick pass** variant.

## 3. Do the research

Run the research with WebSearch and WebFetch. Follow the prompt's source targets
(15+ threads across 4+ venues) and its search patterns; vary them rather than
running the same query with different words.

Hard rules, restated because they are the ones that matter:

- **Never invent a reader quote.** Every `reader_voices` entry is a real comment
  with a working permalink you actually fetched. No composites, no
  "representative" comments, no cleaned-up paraphrase presented as a quote.
- **Handles only.** Public username, never a real name or other identifying
  detail. Use they/them for commenters unless their comment makes their pronouns
  clear.
- **Leave gaps empty.** A null field is a finding. A plausible guess is a
  liability.
- **Keep in-copyright book quotes to a sentence or two.**

Research all 15 groups. Ten are `[always]`, five are `[cherry-pick]` — fill the
cherry-pick groups with whatever is really there and let them be thin. Don't pad
a group to make the page look complete; the editor is going to cut 2–5 groups
anyway and needs to see which ones actually had material.

## 4. Write the file

Write the JSON to `data/pages/$1.json`. Set:

- `research_notes.researched_at` to today's date
- `research_notes.sources` to every thread you used, with URL and roughly how
  many comments you read
- `research_notes.gaps` to the core questions that found no real answer
- `research_notes.strongest_sections` to the 3–4 sections that came back
  richest, best first

## 5. Validate

Run:

```bash
python3 scripts/validate_page.py data/pages/$1.json --check-links
```

- **Schema errors** → fix the JSON and re-run until clean.
- **DEAD links** → remove the quote or finding that carries the link. Do not
  substitute a different link for the same quote; a dead permalink usually means
  the quote itself isn't real, so the whole entry goes.
- **Unverified links** → open two or three with WebFetch and confirm the quoted
  text actually appears on the page. If a quote isn't there, drop it and check
  the rest. If the run says most failures are proxy refusals, note in your report
  that link checking didn't actually run.
- **Editorial warnings** → judgment call. Fix the fixable ones (a missing
  critical voice usually means searching `"<title>" overrated` and
  `"<title>" didn't like`); leave the ones that reflect the book honestly, and
  say which you're leaving and why.

## 6. File it

Update the book's `status` in `data/books.json` to `researched`, preserving the
file's formatting (2-space indent, key order).

Commit both files on the current branch:

```
research(<slug>): fan page research for <Title>
```

Do not push unless asked.

## 7. Report

Print a short summary for the editor:

- Sources used and roughly how many reader comments you read
- `strongest_sections` and why those came back richest
- Which groups were thin or empty, and whether that's the book or the search
- Validation status: schema, dead links removed, unverified links spot-checked
- Anything you want a human to check before this page ships

Keep it under 20 lines. The JSON is the deliverable; the summary is for deciding
whether to trust it.

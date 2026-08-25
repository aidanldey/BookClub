# BookClub — Fan Page Research Kit

Everything needed to turn a book title into a populated fan page: the questions
that make a page worth reading, a reusable research prompt, a structured output
format, and the sourcing rules that keep reader quotes trustworthy.

```
.claude/commands/
  research-book.md      /research-book <slug> — the whole pipeline, one command
research/
  QUESTIONS.md          15 question groups, by page section
  FAN_PAGE_PROMPT.md    the prompt — parameterized on title, with variants
  fan-page.schema.json  the JSON shape the research returns
  SOURCING.md           attribution, privacy, and quoting rules
scripts/
  validate_page.py      schema + link + editorial checks
  check_library.py      rights + link checks for the Reading Room shelf
  preview_fan_page.php  render a page to static HTML, no WordPress needed
  preview_reader.php    render the reader and the Reading Room, likewise
  make_test_epub.py     build the fixture book the reader preview opens
data/
  books.json            the 50 books queued for pages
  pages/<slug>.json     research output, one file per book
  library.json          the public-domain shelf, with the rights basis for each
wordpress/
  README.md             install + publishing guide
  theme-files/          drop-in templates for the BookLoversClub theme
  preview/              design fixture and generated previews
```

## The workflow

From a Claude Code session:

```
/research-book list              # see the queue and where each book stands
/research-book east-of-eden      # research one book end to end
/research-book dune quick pass   # extra words become prompt variants
```

The command resolves the slug, assembles the prompt with the right variants,
runs the research, writes `data/pages/<slug>.json`, validates it, flips the
book's `status` to `researched`, and commits. It reports what it found and what
a human should check.

Then, per book:

```
researched → [human review] → drafted → [render] → published
```

The render step is `wordpress/theme-files/` — a `fan_page` post type whose
template reads the research JSON straight out of post meta. The page leads with
the book cover and one representative quote, then runs whichever of the 15
sections came back with content. See `wordpress/README.md` to install it, and
preview any page without a WordPress install:

```bash
php scripts/preview_fan_page.php data/pages/<slug>.json wordpress/preview/preview.html
```

Track that in the `status` field of `data/books.json`. It's what makes a
50-book queue resumable across sessions.

**Start with three books by hand before batching.** Pick one easy
(`project-hail-mary`), one translated (`crime-and-punishment`), one divisive
(`a-little-life`), and read the JSON critically. The prompt will need tuning —
most likely the consensus labels get applied too loosely and
`craft.standout_detail` comes back adjectival despite the instruction. Fix the
prompt before running it 47 more times.

## The gate: verify every permalink

The dominant failure mode of this pipeline is a plausible-looking permalink that
404s, or resolves to a real thread that doesn't contain the quoted text. A page
full of dead links is worse than a page with no reader voices at all.

```bash
python3 scripts/validate_page.py data/pages/<slug>.json --check-links
```

Three buckets, meaning different things:

- **DEAD** (404/410, no such host) — almost always a fabricated permalink. Remove
  the entry that carries it. Don't swap in a different link for the same quote:
  a dead permalink usually means the quote isn't real either.
- **UNVERIFIED** (403/429/timeout) — Reddit and Goodreads block automated
  requests, so this is usually the checker being blocked. Spot-check by hand.
- **OK** — 200.

The script also runs editorial checks the schema can't express: too few reader
voices, no critical voice, over-long quotes, an empty `gaps` array across 15
sections. Those are warnings, not failures — judgment calls for the editor.

`jsonschema` is used if installed; otherwise a built-in fallback covers required
fields, unknown fields, types, and enums. No dependencies required.

## What to look for in review

- Does `craft.standout_detail` name something specific, or is it "beautiful
  prose" with extra words?
- Is there a genuine critical voice in `reader_voices`, or a hedged compliment?
- Are `debates.side_a` / `side_b` both steelmanned, or is one a strawman?
- Does `research_notes.gaps` say anything? Zero gaps across 15 sections means
  fields got filled that shouldn't have been.
- Do `strongest_sections` match what you see when you read the file?

## Page sections

15 groups; a typical page renders 10–13 of them. Researching all 15 and cutting
is the point — the surplus is what lets each page lead with whatever that book is
actually best at. *Dune* leads with setting, *Stoner* with reception history,
*Catch-22* with craft. You can only make that call with the full set in hand.

| Section | Field | Tier |
|---|---|---|
| Header + at-a-glance | `book` | always |
| Why readers love it | `resonance` | always |
| What readers point to | `craft` | always |
| Reader favorites | `characters` | always |
| Lines readers keep | `book_quotes` | always |
| From the club | `reader_voices` | always |
| The place | `setting` | cherry-pick |
| What you've heard vs. what it is | `misconceptions` | cherry-pick |
| Contested | `debates` | always |
| The book's life | `reception` | cherry-pick |
| About the author | `author` | cherry-pick |
| The fandom | `fandom` | cherry-pick |
| Before you start | `before_you_start` | always |
| Run it in your club | `club_kit` | always |
| If you loved this | `if_you_loved_this` | always |

`research_notes` doesn't render — it's the editor's audit trail.

## What makes these pages good

**Specificity.** "Beautiful prose" is a non-finding. "Readers quote the same
three sentences about the Salinas Valley, and half of them mention reading it
aloud" is a page.

**Real readers.** `reader_voices` is the difference between a fan page and a
Wikipedia stub. Every quote is a real, linkable comment — see `SOURCING.md`,
which is not optional.

**Disagreement.** A page where everyone loves everything reads like marketing.
The critical voice, the divisive character, and the live "overrated" argument
are load-bearing.

**Honest gaps.** Empty fields are fine. Invented ones poison the whole site.

## The Reading Room

Ten of the 50 books in the queue are out of copyright. Those the club can hand
over outright rather than link to, so there's a second shelf: `/library/`, where
a book opens in the browser at `/read/<slug>/`. A fan page whose slug matches a
library book grows a "Read it free" button without anyone wiring it up.

The reader is [foliate-js](https://github.com/johnfactotum/foliate-js) vendored
into the theme — the EPUB is unzipped and laid out entirely client-side, so
there's no conversion step and no third-party embed. It remembers where each
reader stopped, and "link to this spot" copies a URL that opens on the same
sentence, which is the thing a book club actually does with a book.

`data/library.json` is the shelf plan: which edition, which translation, and the
specific reason each one is in the public domain. That last field is the point —
**a translation carries its own copyright**, so Dostoevsky is free but the
translation most readers know is not.

```bash
python3 scripts/check_library.py --check-links
```

It fails the run on a book published after the US public-domain cutoff, a
missing rights statement, or a dead link, and warns on the judgment calls — a
translator with no rights basis, or a life-plus-70 country the "public domain"
wording glosses over.

Setup, the publishing steps, and how the reader behaves are in
`wordpress/README.md`. Preview it without a WordPress install:

```bash
python3 scripts/make_test_epub.py && php scripts/preview_reader.php && php -S localhost:8000
```

## Refresh

Re-run a page yearly, or whenever an adaptation lands — a new series shifts a
fandom's conversation within weeks, and a page quoting only pre-adaptation
threads goes stale fast. `research_notes.researched_at` drives that.

## Running the research elsewhere

If you're not in a Claude Code session, copy the block between the rulers in
`research/FAN_PAGE_PROMPT.md`, substitute the title and author, append the
variants named in the book's `prompt_variants`, and paste it into any assistant
with web search. Save the JSON to `data/pages/<slug>.json` and run the validator
yourself.

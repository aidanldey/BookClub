# BookClub — Fan Page Research Kit

Everything needed to turn a book title into a populated fan page: the questions
that make a page worth reading, a reusable research prompt, a structured output
format, and the sourcing rules that keep reader quotes trustworthy.

```
research/
  QUESTIONS.md          the question bank, grouped by page section
  FAN_PAGE_PROMPT.md    the drop-in prompt — replace {{BOOK_TITLE}} and run
  fan-page.schema.json  the JSON shape the research returns
  SOURCING.md           attribution, privacy, and quoting rules
data/
  books.json            the 50 books queued for pages, with per-book variants
```

## How to use it

1. Pick a book from `data/books.json`.
2. Open `research/FAN_PAGE_PROMPT.md`, copy the block between the rulers, and
   replace `{{BOOK_TITLE}}` / `{{AUTHOR}}`.
3. Append any variant blocks listed in that book's `prompt_variants` field —
   `translated`, `series`, `divisive`. They're at the bottom of the prompt file.
4. Run it against a research agent with web search.
5. Save the JSON to `data/pages/<slug>.json` and set the book's `status` to
   `researched`.

## The short version

If you just want something to paste into a chat window:

> Research **{{BOOK_TITLE}}** by **{{AUTHOR}}** for a book club fan page. Search
> r/books, r/suggestmeabook, r/TrueLit, the book's own subreddit, Goodreads, and
> StoryGraph — at least 15 threads. Tell me: (1) the single reason readers most
> often give for loving it, (2) what they say it did to them emotionally and
> when in life it lands hardest, (3) the craft element they praise and the
> *specific* thing about it, (4) the 3–6 most-named favorite characters and the
> exact reason each is loved, plus the one that divides readers, (5) the 5–10
> most-quoted lines with context and spoiler flags, (6) 6–12 short real reader
> comments with username, platform, date, and permalink — including one critical
> take, one convert-from-skeptic, and one about the reading experience, (7) the
> argument the fandom keeps having, steelmanned both ways, (8) who bounces off
> it, content warnings, and the audiobook verdict, (9) 4–6 read-next pairings
> with reasons. Label each finding `near-universal` / `common` / `minority` /
> `singular`. **Never invent a reader quote** — if you can't link it, drop it,
> and tell me what you couldn't find.

## What makes these pages good

Four things, in order:

**Specificity.** "Beautiful prose" is a non-finding. "Readers quote the same
three sentences about the Salinas Valley, and half of them mention reading it
aloud" is a page.

**Real readers.** The `reader_voices` section is the difference between a fan
page and a Wikipedia stub. Every quote is a real, linkable comment — see
`SOURCING.md`, which is not optional.

**Disagreement.** A page where everyone loves everything reads like marketing.
The critical voice, the divisive character, and the live "overrated" argument
are load-bearing.

**Honest gaps.** Empty fields are fine. Invented ones poison the whole site.

## Page sections

Each question group in `QUESTIONS.md` maps to one section of the page:

| Section | Source |
|---|---|
| Header + at-a-glance | `book` |
| Why readers love it | `resonance` |
| What readers point to | `craft` |
| Reader favorites | `characters` |
| Lines readers keep | `book_quotes` |
| From the club | `reader_voices` |
| Contested | `debates` |
| Before you start | `before_you_start` |
| If you loved this | `if_you_loved_this` |

`research_notes` doesn't render — it's the editor's audit trail.

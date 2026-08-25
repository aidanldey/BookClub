# Intern guide: building fan pages and stocking the Reading Room

Two jobs, and this walks through both of them start to finish.

**Job A — Fan pages.** Turn a book into a page at
`bookloversclub.com/books/<slug>/`: what readers love, the lines they quote, the
arguments they keep having about it.

**Job B — The Reading Room.** Put a public-domain book on
`bookloversclub.com/read/<slug>/` so members can read it in the browser.

You don't need to have read the book. You do need to be careful about two
things, and the whole guide comes back to them: **don't invent quotes**, and
**don't upload anything still in copyright.**

---

## Before your first day's work

Make sure you have all of these. Ask for whatever's missing before you start —
finding out halfway through that you can't upload a file wastes an afternoon.

- [ ] A WordPress login for bookloversclub.com
- [ ] The way files get onto the server's `/shelf/` folder (SFTP details, or the
      name of the person who does it)
- [ ] The project folder on your computer, opened in a terminal
- [ ] Claude Code access, if you're doing the research step yourself
- [ ] Python 3 and PHP installed — check with `python3 --version` and `php -v`

**Save as Draft, not Publish.** Everything you make gets reviewed before it goes
live. In WordPress use **Save Draft**, then tell whoever's reviewing. Nobody
expects you to have the last word on what ships.

---

## Four rules

These aren't style preferences. Breaking one damages the site in a way that's
hard to undo, so they come before speed, and before finishing.

### 1. Every reader quote is real, and you can click through to it

The reader voices are the reason these pages are worth reading. One fabricated
quote — found once, by anyone — makes every other quote on the site suspect.

- If there's no working link to the original comment, the quote doesn't ship.
- Don't stitch two comments together. Don't smooth out someone's typo. Don't
  write a quote that "captures what people say."
- If a link is dead, delete the whole entry. Don't go find a different link for
  the same quote — a dead link usually means the quote isn't real either.

### 2. Usernames only, and nothing else about the person

Attribute to the public handle: `u/booknerd_42`, not a real name. Never add a
location, a job, a school, or anything else, even if their profile shows it.
Never link two accounts together.

Use **they/them** for commenters unless their comment makes their pronouns
obvious. A username tells you nothing.

### 3. Never upload a book that's still in copyright

The Reading Room hands over the actual file. Anyone who opens a book can save
it. That's fine for a book whose copyright has expired, and it's straightforward
infringement for one that hasn't. There's a test in Job B, Step 1 — use it every
time, including for books you're sure about.

### 4. Empty is fine. Invented is not.

If the research found nothing for a section, leave it empty. A blank section is
honest and the page just gets shorter. A section filled with plausible-sounding
filler is the thing that ruins the site.

### When to stop and ask a human

Not a sign you're doing badly — these are genuinely above your pay grade:

- You're not certain a book is public domain, for any reason at all
- A commenter's quote touches self-harm, abuse, addiction, or a recent death
- Someone asks to have their quote removed *(remove it immediately, then log it
  in `data/quote-removals.md` and tell someone — don't reply to them)*
- A tool prints an error the troubleshooting section doesn't cover
- Something feels off and you can't say why

---

# Job A — Publish a fan page

Roughly 45–90 minutes per book, most of it in Step 3 reading the research.

## A1. Pick a book from the queue

Open the project in **Claude Code** — these `/` commands go there, not in a
normal terminal — and type:

```
/research-book list
```

That prints the 50 books and where each one stands. Take one marked
`not-researched`. Note its **slug** — the short hyphenated name like
`east-of-eden`. You'll type it a lot; it's the same in every step.

If someone assigned you a book, use theirs.

## A2. Run the research

```
/research-book east-of-eden
```

This takes a while. It searches Reddit, Goodreads and book forums, reads through
threads, and writes `data/pages/east-of-eden.json`. When it finishes it prints a
summary: which sections came back richest, which were thin, and anything it
wants you to check.

**Read that summary.** It's telling you where to look in the next step.

> If you weren't given Claude Code access, someone will hand you the JSON file
> instead. Start at A3.

## A3. Read the research critically

This is the actual job. Everything else is typing.

Open `data/pages/<slug>.json` in a text editor. It's a big structured file —
you're reading the values, not the punctuation. Work down this list:

**Reader voices** — the section that matters most.

- [ ] Click **every single permalink.** Every one.
- [ ] Does the page open, and is the quoted text actually on it? Use your
      browser's find (Ctrl-F / Cmd-F) with a distinctive phrase from the quote.
- [ ] Is the quote accurate, or has it been tidied up? Trimming with `…` is
      fine. Changed words are not.
- [ ] Any real names, locations, or identifying details? Remove them.
- [ ] Is there a genuine **critical** voice — someone who didn't like it? A page
      where everyone loves everything reads like an advertisement. If there's
      no real criticism, say so when you hand the page over.
- [ ] Anything too personal to lift onto a public page? See "when to ask."

Delete any entry that fails. Deleting is normal — expect to cut a couple.

**Book quotes** — lines from the book itself.

- [ ] Do they appear in the actual book? Quote sites are full of
      misattributions. Search the book's text, or find a reader citing the
      chapter.
- [ ] For a book still in copyright: a sentence or two each, and around ten
      quotes on the page at most.
- [ ] Translated book? The translator gets named. A famous line in translation
      is that *translator's* line.

**Everything else** — a quick pass:

- [ ] Does `craft.standout_detail` name something specific, or is it "beautiful
      prose" dressed up? Specific stays; vague gets cut.
- [ ] In `debates`, are both sides argued fairly, or is one a strawman?
- [ ] Is `research_notes.gaps` empty? Fifteen sections and zero gaps means
      something got filled in that shouldn't have been. Look harder.

## A4. Run the checker

```bash
python3 scripts/validate_page.py data/pages/east-of-eden.json --check-links
```

You'll get three lines:

| Line | What it means | What to do |
|---|---|---|
| `SCHEMA: ok` | The file is shaped correctly | Nothing |
| `SCHEMA: n error(s)` | A field is missing or the wrong type | Fix what it names, run it again |
| `EDITORIAL: n warning(s)` | Judgment calls — too few voices, no critical voice, a quote that runs long | Fix what you can, mention the rest when you hand it over |
| `LINKS: all n ok` | Every link resolved | Nothing |
| `LINKS: n DEAD` | Those links 404 | **Delete the entries carrying them.** Don't swap in new links |
| `LINKS: n unverified` | Reddit and Goodreads block automated checking | Open a few by hand and confirm the quote is there |

Keep going until SCHEMA is clean and there are no DEAD links. Warnings are
allowed to survive — just be able to say why.

## A5. Look at the page

```bash
php scripts/preview_fan_page.php data/pages/east-of-eden.json wordpress/preview/preview.html
```

Open `wordpress/preview/preview.html` in a browser. This is what the real page
will look like. Read it as a visitor: does it flow, or does a thin section leave
an awkward hole? It also prints how many sections rendered.

## A6. Publish it in WordPress

1. **Fan Pages → Add New.**
2. **Title:** the book title exactly — *East of Eden*, not *East Of Eden (1952)*.
3. **Slug:** must match the JSON filename. Check it in the sidebar; WordPress
   sometimes adds a `-2`.
4. **Featured Image:** the book cover. Public-domain book? Use the Standard
   Ebooks cover — it's free to use. Anything still in copyright? **Ask first**;
   don't pull cover art off Google Images on your own.
5. **Research Data (JSON):** open `data/pages/<slug>.json`, select all, copy,
   and paste the whole thing into that box. All of it, including the outer `{`
   and `}`.
6. **Fan Page Details → Representative quote:** optional. Left blank, the page
   picks a good line by itself. Pin one only if you've spotted a better one.
7. **Save Draft.** Preview it. Then tell your reviewer it's ready.

If the JSON box shows a red error after saving, the paste was incomplete — copy
it again, making sure you got the first and last characters.

## A7. Mark it done

Open `data/books.json` and find your book. The research step already moved it
from `not-researched` to `researched`; now that a page exists in WordPress,
change it to `"status": "drafted"`. Change nothing else — not the spacing, not
the key order.

Then save your work:

```bash
git add data/
git commit -m "research(east-of-eden): fan page research for East of Eden"
```

Ask before pushing, unless you've been told otherwise.

---

# Job B — Add a book to the Reading Room

About 20 minutes once you've done one.

## B1. Prove it's public domain

Work through this in order. **Any "no" or "not sure" ends the process** — bring
it to a human instead.

**Question 1: Was it first published before 1931?**
Not the edition — the original. *Frankenstein* is 1818 even if you're holding a
2018 paperback.

- Yes → keep going.
- No → stop. It's not going on the shelf.

**Question 2: Is it translated?**

A translation has its own separate copyright, and this is where interns and
professionals alike get caught. Dostoevsky died in 1881, but a translation
published in 2002 is protected until long after you retire.

- Not translated → keep going.
- Translated, and the *translation* was published before 1931 → keep going, and
  write the translator's name down. You'll need it twice.
- Translated more recently, or you can't find out when the translation was made
  → stop.

Safe translators you'll see a lot: **Constance Garnett** (Russian), **Isabel
Hapgood** (French), **Samuel Butler** (Greek). Names to watch out for:
**Pevear and Volokhonsky**, **Emily Wilson**, **Ann Goldstein** — all modern, all
in copyright.

**Question 3: Is it already in the plan?**

Open `data/library.json`. If the book is listed there, the rights work is done
for you — the `rights` and `rights_basis` fields are what you'll copy into
WordPress. If it isn't listed, add it after you're finished (Step B7).

## B2. Download the file

Go to [standardebooks.org](https://standardebooks.org) and find the book. Their
editions are carefully typeset and free to rehost, so they're the first choice.

Click the **EPUB** download link — and then watch what happens:

> ⚠️ **The trap.** Standard Ebooks shows a "Your Download Has Started!" page
> asking for a donation, and the file downloads in the background a second
> later. If you use *File → Save Page As* on that page, you save the donation
> page instead of the book. It'll even be named `something.epub`. The reader
> will refuse it with "That file is not a valid EPUB."
>
> **Check your download:** a real book is **1–3 MB**. If the file is under
> 100 KB, you saved the wrong thing. Look in your Downloads folder for the file
> that arrived on its own.

Not on Standard Ebooks? [gutenberg.org](https://www.gutenberg.org) has far more
books, rougher files. Take the "EPUB (no images)" version, and mention to
someone that it came from Gutenberg — those files carry a licence header that
needs handling.

## B3. Name the file

Keep the name Standard Ebooks gave it:

```
mary-shelley_frankenstein.epub
```

That name ends in the book's slug (`frankenstein`), which is what lets the site
connect the file to the page by itself. Two rules:

- The name must end in the slug, after a `-` or `_`. `mary-shelley_frankenstein.epub`
  and `frankenstein.epub` both work. `frankenstein-shelley.epub` does not.
- No spaces, no capitals, no `#` or `&`. Lowercase letters, numbers, hyphens,
  underscores.

## B4. Put it on the shelf

Upload it to the `/shelf/` folder at the site root, so it ends up at:

```
bookloversclub.com/shelf/mary-shelley_frankenstein.epub
```

Paste that URL into your browser. If the book downloads, you're set. If you get
"404 Not Found", it's in the wrong folder or misspelled.

## B5. Create the book in WordPress

1. **Reading Room → Add New.**
2. **Title:** the book title.
3. **Slug:** must match the fan page's slug if there is one — `frankenstein`.
   That match is what puts a "Read it free" button on the fan page.
4. **Featured Image:** the cover. The Standard Ebooks cover is free to use.
5. **The Edition** — fill in what you know, and **leave all three file fields
   empty**:
   - Author
   - Translator — only if there is one, and then always
   - First published — the original year
   - **Rights statement** — required, and it shows on the page. Copy it from
     `data/library.json`. If the book isn't there, use
     `Public domain in the United States.`
   - Source — `Standard Ebooks`
   - Source URL — the book's page you downloaded from
6. **Save Draft.**

Now look back at *The Edition*. It should say **Serving:** with your filename
and its size. The site found the file on the shelf by name and connected it —
you never type a path.

If it says "No EPUB attached", the name doesn't end in the slug. Either rename
the file on the shelf, or type the filename into **Shelf file** yourself — the
box lists what's actually in the folder.

## B6. Actually read it

Open the page and use it like a member would. Every one of these:

- [ ] The book opens, and you can see text within a few seconds
- [ ] **Contents** opens and lists the chapters; clicking one jumps there
- [ ] Turn several pages with the arrows and the `→` key
- [ ] **Text** → make it bigger, then smaller. The text reflows, nothing overlaps
- [ ] Switch the site to dark mode (the moon, top right). The book goes dark too
- [ ] The chapter name and percentage at the bottom update as you read
- [ ] **Reload the page.** It should reopen exactly where you were
- [ ] **On a phone**, open the same page and turn a few pages by swiping
- [ ] Check the front matter — is this the edition you meant? Watch for
      abridgements, and for a translator you didn't expect

If it says "This book would not open in your browser," go back to B2. It's
almost always the donation page saved instead of the book.

## B7. Write it down

If the book wasn't already in `data/library.json`, add an entry. Copy an
existing one and change the values — the fields are the same every time:

```json
{
  "slug": "dracula",
  "title": "Dracula",
  "author": "Bram Stoker",
  "translator": null,
  "year_published": 1897,
  "rights": "Public domain worldwide.",
  "rights_basis": "Published 1897; the author died in 1912.",
  "fan_page_slug": null,
  "source": "standard-ebooks",
  "source_url": "https://standardebooks.org/ebooks/…",
  "shelf_file": "bram-stoker_dracula.epub",
  "epub_url": null,
  "status": "uploaded",
  "note": null
}
```

`source_url` is the page you actually downloaded from — paste the real one out
of your browser's address bar, never a URL you assembled from the pattern.
`translator` and `note` take `null` when they don't apply — no quotes around it.
`rights_basis` is where you write down the answer to B1: the year it was
published, when the author died, and for a translation, when *that* was
published.

Then check your work:

```bash
python3 scripts/check_library.py
```

Nothing printed but the summary count means you're good. Anything starting with
`ERROR:` has to be fixed. Anything starting with `warning:` is worth reading.

```bash
git add data/library.json
git commit -m "library: add Dracula to the Reading Room"
```

---

## When something goes wrong

| What you see | What it means | What to do |
|---|---|---|
| "That file is not a valid EPUB." | You saved the donation page, not the book | Re-download. The file should be 1–3 MB (B2) |
| "This book would not open in your browser" + a number like 404 | The file isn't at that address | Check the filename on the shelf for typos (B4) |
| "No EPUB attached" in wp-admin | The filename doesn't end in the slug | Rename it, or pick it from the **Shelf file** list (B5) |
| The book opens but looks wrong — no chapters, cramped text | A rough source file | Try the Standard Ebooks edition instead; if it *is* that, flag it |
| `SCHEMA: n error(s)` | The JSON has a missing or misshapen field | Fix exactly what it names, run it again (A4) |
| `LINKS: n DEAD` | Those permalinks 404 | Delete the entries that carry them — never substitute (A4) |
| Red error under the Research Data box | The paste was cut short | Copy the whole file again, first `{` to last `}` (A6) |
| The fan page has no "Read it free" button | The two slugs don't match | Compare them; or pin the book in **Reading Room** on the fan page |
| `ERROR: ... not public domain in the US` | The book is too recent for the shelf | Stop. Bring it to a human (B1) |
| A page 404s right after you publish it | WordPress needs its links rebuilt | Settings → Permalinks → Save. Ask if you don't have access |

---

## The short version

Print this bit.

**Fan page**

1. `/research-book list` → pick one → `/research-book <slug>`
2. Click every permalink. Delete anything that doesn't check out
3. `python3 scripts/validate_page.py data/pages/<slug>.json --check-links`
4. `php scripts/preview_fan_page.php data/pages/<slug>.json wordpress/preview/preview.html`
5. Fan Pages → Add New → title, cover, paste the JSON → **Save Draft**
6. Set `status` to `drafted` in `data/books.json`, commit

**Reading Room book**

1. Published before 1931? Translation also before 1931? If not — stop
2. Download from Standard Ebooks. **Check it's 1–3 MB**
3. Keep the filename; it must end in the slug
4. Upload to `/shelf/`, then load the URL to confirm it's there
5. Reading Room → Add New → details + rights statement, file fields empty →
   **Save Draft** → confirm it says "Serving:"
6. Read it: contents, page turns, text size, dark mode, reload, phone
7. Add it to `data/library.json`, run `python3 scripts/check_library.py`, commit

---

## Where the rest is written down

- `research/SOURCING.md` — the quoting and attribution rules in full. Worth
  reading once, properly, before your first page
- `research/QUESTIONS.md` — the 15 questions each page tries to answer
- `wordpress/README.md` — how the site is put together, if you're curious
- `data/library.json` — the shelf plan, with the rights reasoning for each book
- `data/quote-removals.md` — where removed quotes get logged

**One last thing.** If you're unsure whether a quote is real, or whether a book
is free to host, the answer is to ask. Nobody will mind the question. Everybody
would mind the alternative.

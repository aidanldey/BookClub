# Fan pages and the Reading Room for the BookLoversClub theme

Two drop-in modules for the theme. **Fan pages** turn a
`data/pages/<slug>.json` research file into a published page. The **Reading
Room** hosts public-domain editions and reads them in the browser, so a fan
page for a book whose copyright has expired can hand the reader the book
itself. They install independently — the fan page templates work without the
reader, and the reader works without any research JSON.

Both are styled to the existing BookLoversClub identity system —
Warm Clay accent, Lora/Inter/Space Mono, the dog-ear card fold, and the
lamplight dark mode. No new colors, fonts, or spacing values are introduced;
every rule in `fan-page.css` and `reader.css` resolves to a token already
defined in the theme's `style.css` — including the type inside the book, which
the reader injects into the EPUB at runtime from the same variables.

A fan page opens with the book cover and one representative quote, then runs the
research sections beneath it.

```
theme-files/                       ← copy these into wp-content/themes/bookloversclub/
  single-fan_page.php              the fan page itself
  archive-fan_page.php             the /books/ index
  inc/fan-page.php                 post type, research-JSON data layer, helpers
  template-parts/card-fan.php      fan page archive card
  template-parts/fan/*.php         one file per fan page section
  assets/css/fan-page.css          new components, built on the theme's tokens
  assets/js/fan-page.js            spoiler reveals + section-nav highlighting

  single-library_book.php          the reader — /read/<slug>/
  archive-library_book.php         the Reading Room — /library/
  inc/reader.php                   post type, EPUB uploads, fan page linking
  template-parts/card-library.php  Reading Room card
  template-parts/fan/read-cta.php  the "Read it free" button on a fan page
  assets/css/reader.css            reader and Reading Room components
  assets/js/reader.js              the reader (ES module)
  assets/js/library.js             "where you left off" on cards and buttons
  assets/vendor/foliate-js/        the EPUB engine, MIT — see its README
preview/
  sample-data.json                 design fixture (see the warning below)
  sample-cover.svg                 placeholder cover
  theme-style.css                  copy of the theme stylesheet, for previewing only
  preview.html                     generated — open it in a browser
  reader.html, library.html        generated — serve them, don't open off disk
  test-book.epub                   generated fixture for the reader
```

## Install

1. **Copy** everything under `theme-files/` into your theme folder, preserving
   the directory structure. Nothing overwrites an existing theme file.

2. **Load the modules.** Add these to the bottom of `functions.php` — either
   line works on its own:

   ```php
   require get_template_directory() . '/inc/fan-page.php';
   require get_template_directory() . '/inc/reader.php';
   ```

3. **Extend the reading ribbon** (optional, one line). In `header.php`, add
   `'fan_page'` to the post types that get the bookmark-ribbon progress bar:

   ```php
   <?php if ( is_singular( array( 'post', 'book_review', 'fan_page' ) ) ) : ?>
   ```

   The body class is already applied by `inc/fan-page.php`; this is only the
   markup side.

4. **Flush permalinks** — visit Settings → Permalinks and click Save. Fan pages
   then live at `/books/<slug>/` with an index at `/books/`; readable books live
   at `/read/<slug>/` with the Reading Room at `/library/`.

## Publishing a page

1. **Fan Pages → Add New.** The post title is the book title.
2. **Set the Featured Image** to the book cover. The theme crops it to the
   existing 3:4.5 `blc-cover-large` size, same as book reviews.
3. **Paste the research JSON** into the *Research Data (JSON)* box — the whole
   contents of `data/pages/<slug>.json`. Everything below the hero renders from
   it. Invalid JSON is kept as-typed and flagged rather than silently dropped.
4. **Optionally pin a representative quote** in *Fan Page Details*. Left blank,
   the hero uses the first non-spoiler `tattoo_tier` line from `book_quotes`,
   then falls back to the first non-spoiler quote. The hero never shows a
   spoiler.
5. The post body (block editor) is optional. Anything you write there renders
   between the hero and the sections, with the theme's drop cap — a good place
   for a human introduction in the club's own voice.

## What renders, and when

The always-on sections render whenever they hold content; the cherry-pick
sections drop out entirely when empty, so a thinly-researched book produces a
short page rather than a page of empty headings. The section nav is built from
whatever actually rendered, and only appears when there's more than one section.

Order is set in one place — the `$parts` array in `single-fan_page.php`.
Reordering that array reorders the page. To lead *Dune* with setting or
*Stoner* with reception, move the entry up.

Spoilers are hidden behind a click on every field the schema marks
`spoiler: true`, plus character `signature_moment`, which is plot by
definition. With JavaScript off, spoilers stay hidden.

## Publishing a book to the Reading Room

**Public domain only.** An EPUB served over HTTP is a file anyone who opens the
reader can download — there is no view-only mode, and there is no DRM here. A
book still in copyright must not be published to this shelf, whatever the
robots.txt says. `data/library.json` carries the shelf plan, the rights basis
for each edition, and the rules; `python3 scripts/check_library.py` enforces
the ones a script can.

1. **Fetch the edition.** [Standard Ebooks](https://standardebooks.org) is the
   best source — properly typeset EPUB 3, and its editorial work is dedicated to
   the public domain, so the files can be rehosted. Project Gutenberg has a much
   wider catalogue and rougher files; if you use it, take the version without the
   Project Gutenberg header, or strip it.

2. **Check the translation.** A translation has its own copyright. Dostoevsky is
   public domain; the Pevear and Volokhonsky translation is not. Use the old
   translations whose copyright has expired — Garnett, Hapgood, Butler — and name
   the translator on the page, because a reader arriving from a conversation
   about a modern translation will assume that is what they are getting.

3. **Upload the .epub** under Media. The theme allows the file type and fixes
   the mime check WordPress would otherwise fail it on. Note the attachment ID.

4. **Reading Room → Add New.** Title is the book title. Set the Featured Image
   to the cover. Fill in *The Edition*: the attachment ID, author, translator,
   year, a rights statement (**required** — it renders on the page), and the
   source with its URL.

5. **Match the slug to the fan page.** A library book whose slug matches a fan
   page's slug puts a "Read it free" button on that fan page automatically. If
   the slugs differ, pin the book in *Reading Room* on the fan page editor.

The posts list flags the two things that quietly break a book: a missing file
and a missing rights statement. A book with no file never appears in the
Reading Room or on a fan page.

## How the reader works

Rendering is [foliate-js](https://github.com/johnfactotum/foliate-js), vendored
under `assets/vendor/foliate-js` (MIT). It runs entirely in the browser: the
EPUB is fetched, unzipped, and laid out client-side. There is no conversion
step, no reader service, and no third-party embed — WordPress only stores the
file and says where it is.

- **Position** is stored as an EPUB CFI in `localStorage`, per book, per
  browser. It survives changing the type size, because a CFI describes a place
  in the text rather than a page number. It does not follow a reader between
  devices; that would need a user account and a REST route, and it is the
  obvious next thing to build.
- **"Link to this spot"** copies the current URL with `?loc=<cfi>` on it. Open
  that link and the reader starts there instead of where you left off — which
  is how you send someone the sentence rather than describing it.
- **Settings** — size, typeface, spacing, pages vs. scroll, justified vs.
  ragged — persist across every book. The book's own stylesheet still wins on
  everything the reader doesn't set, so a Standard Ebooks edition keeps its
  small caps and its drop caps.
- **Dark mode** follows the site's toggle, including inside the book: the
  reader watches `data-theme` on `<html>` and re-injects its colors, which are
  read from the theme's tokens at runtime.
- **Without JavaScript** the page still renders: title, provenance, and a
  download link. The reader is an enhancement, not the page.
- **Focus** goes fullscreen where the browser allows it, and falls back to a
  taller reader where it doesn't (iOS Safari).

Keyboard: <kbd>←</kbd>/<kbd>→</kbd> or <kbd>PageUp</kbd>/<kbd>PageDown</kbd> or
<kbd>Space</kbd> to turn, <kbd>Esc</kbd> to close a panel. On a touch screen the
page-turn arrows give way to swiping.

## Preview without WordPress

```bash
php scripts/preview_fan_page.php data/pages/east-of-eden.json wordpress/preview/preview.html
php scripts/preview_fan_page.php data/pages/dune.json out.html --cover=covers/dune.jpg
```

This shims the handful of WordPress functions the templates call and includes
the *real* template files, so the preview and the live page come from one
source. Output is a single self-contained HTML file — CSS, JS, and cover are
inlined — that you can open directly or send to someone. It prints which
sections rendered, which is the fastest way to see what a research file is
missing.

The reader has its own harness. It can't be a single self-contained file — the
reader is an ES module that fetches an EPUB, so both need a real server:

```bash
python3 scripts/make_test_epub.py     # build the fixture book
php scripts/preview_reader.php        # render reader.html and library.html
php -S localhost:8000                 # from the repo root
```

Then open <http://localhost:8000/wordpress/preview/reader.html>. The fixture is
five chapters with a nested contents tree, written for the purpose — long enough
to paginate, and shaped to catch the layout bugs that only appear on the seam
between two pages. `library.html` is the Reading Room over three fixture books,
one of them deliberately fileless.

Keep `preview/theme-style.css` in sync if you change the theme's `style.css`;
it exists only so the preview has the parent theme's tokens.

> **`preview/sample-data.json` is a design fixture, not research.** The book
> quotes are genuine public-domain Austen, but every entry in `reader_voices`
> is invented placeholder text pointing at `example.com`. It exists to exercise
> the template. Never publish it, and never copy its reader voices into a real
> page — see `research/SOURCING.md` for why that rule has no exceptions.

## Notes for whoever edits this next

- `blc_fan_get( $data, 'a.b.c' )` is the only way the templates read research
  data. It returns `null` for missing *or* empty values, so a section can branch
  on truthiness without `isset()` chains, and a partial research file can never
  fatal a page.
- Adding a section means: a new file in `template-parts/fan/`, a line in
  `blc_fan_sections()` with its render condition, and a line in the `$parts`
  array. The nav picks it up automatically.
- Reader quotes without a `permalink` are skipped at render time, not just at
  research time. An unverifiable quote never reaches the page.
- `research_notes` is deliberately never rendered — it's the editor's audit
  trail, and it contains the gaps list you don't want public.
- The reader's chrome is server-rendered in `single-library_book.php`;
  `reader.js` only wires up controls that are already in the markup. Adding a
  control means adding the button there and a listener in `bindControls()`.
- Don't edit anything under `assets/vendor/foliate-js`. Everything specific to
  this site lives in `reader.js` and `reader.css`, so updating the engine is a
  clean overwrite — see that directory's README for the pinned commit and the
  handful of API calls this theme depends on.
- The renderer's layout attributes (`gap`, `margin`, `max-inline-size`) are
  written straight into its own custom properties and read back with
  `parseFloat`. They need real units: `6%`, `28px`, `560px`. A bare number
  silently invalidates its grid template, and `38rem` is read as 38 pixels.
- `blc_reader_meta()` is the only way the templates read a library book, the
  same way `blc_fan_get()` is for research data. A missing field degrades to an
  unrendered line.

# Fan page template for the BookLoversClub theme

Drop-in templates that turn a `data/pages/<slug>.json` research file into a
published fan page, styled to the existing BookLoversClub identity system —
Warm Clay accent, Lora/Inter/Space Mono, the dog-ear card fold, and the
lamplight dark mode. No new colors, fonts, or spacing values are introduced;
every rule in `fan-page.css` resolves to a token already defined in the theme's
`style.css`.

The page opens with the book cover and one representative quote, then runs the
research sections beneath it.

```
theme-files/                     ← copy these into wp-content/themes/bookloversclub/
  single-fan_page.php            the page itself
  archive-fan_page.php           the /books/ index
  inc/fan-page.php               post type, research-JSON data layer, helpers
  template-parts/card-fan.php    archive card
  template-parts/fan/*.php       one file per page section
  assets/css/fan-page.css        new components, built on the theme's tokens
  assets/js/fan-page.js          spoiler reveals + section-nav highlighting
preview/
  sample-data.json               design fixture (see the warning below)
  sample-cover.svg               placeholder cover
  theme-style.css                copy of the theme stylesheet, for previewing only
  preview.html                   generated — open it in a browser
```

## Install

1. **Copy** everything under `theme-files/` into your theme folder, preserving
   the directory structure. Nothing overwrites an existing theme file.

2. **Load the module.** Add one line to the bottom of `functions.php`:

   ```php
   require get_template_directory() . '/inc/fan-page.php';
   ```

3. **Extend the reading ribbon** (optional, one line). In `header.php`, add
   `'fan_page'` to the post types that get the bookmark-ribbon progress bar:

   ```php
   <?php if ( is_singular( array( 'post', 'book_review', 'fan_page' ) ) ) : ?>
   ```

   The body class is already applied by `inc/fan-page.php`; this is only the
   markup side.

4. **Flush permalinks** — visit Settings → Permalinks and click Save. Fan pages
   then live at `/books/<slug>/` with an index at `/books/`.

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

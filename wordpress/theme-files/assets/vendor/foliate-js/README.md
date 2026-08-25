# foliate-js (vendored)

The EPUB rendering engine behind the BookLoversClub reader. Upstream:
<https://github.com/johnfactotum/foliate-js>, MIT licensed — see `LICENSE`.

**Pinned commit:** `78914aef4466eb960965702401634c2cb348e9b1`

## What's here, and what isn't

Only the modules on the EPUB code path are vendored. Upstream also ships
readers for PDF, MOBI/AZW3, FB2, and comic-book archives; `view.js` reaches for
those with dynamic `import()` calls that are only evaluated when a file of that
type is opened. Since `inc/reader.php` accepts nothing but EPUB, those branches
are unreachable, and leaving the modules out keeps the theme ~13 MB lighter
(pdf.js alone is most of that).

```
view.js            the <foliate-view> custom element — the public API
epub.js            EPUB container/OPF/NCX parsing
epubcfi.js         CFI parsing and generation (how reading positions are stored)
paginator.js       <foliate-paginator> — reflowable layout, columns and scroll
fixed-layout.js    <foliate-fxl> — pre-paginated books (comics, picture books)
progress.js        section/TOC progress mapping
overlayer.js       highlight and underline drawing (used by search results)
search.js          in-book search
text-walker.js     range/text traversal used by search and annotations
footnotes.js       popup footnote resolution
vendor/zip.js      zip.js — reads the EPUB container
vendor/fflate.js   inflate, pulled in by zip.js
```

## Updating

```bash
git clone --depth 1 https://github.com/johnfactotum/foliate-js /tmp/foliate-js
cp /tmp/foliate-js/{view,epub,epubcfi,paginator,fixed-layout,overlayer,progress,text-walker,search,footnotes}.js \
   wordpress/theme-files/assets/vendor/foliate-js/
cp /tmp/foliate-js/vendor/{zip,fflate}.js \
   wordpress/theme-files/assets/vendor/foliate-js/vendor/
cp /tmp/foliate-js/LICENSE wordpress/theme-files/assets/vendor/foliate-js/
```

Then update the pinned commit above, and re-check `assets/js/reader.js` against
upstream's `reader.js` — the parts of the API this theme leans on are
`view.open()`, `view.init()`, `renderer.setStyles()`, the `relocate` and `load`
events, and the `flow` / `gap` / `margin` / `max-inline-size` /
`max-column-count` attributes on the renderer.

**Do not edit these files.** Everything specific to BookLoversClub lives in
`assets/js/reader.js` and `assets/css/reader.css`, so an update is a clean
overwrite.

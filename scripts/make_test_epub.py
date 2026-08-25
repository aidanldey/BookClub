#!/usr/bin/env python3
"""Build a valid EPUB 3 for exercising the reader — no download required.

The point is a fixture with the shapes that break readers: a nested table of
contents, chapters long enough to paginate, block quotes and verse, a footnote,
a chapter with an em-dash-heavy paragraph that has to hyphenate, and enough
sections that the progress slider has somewhere to go. The prose is written for
this file, so there is nothing to attribute and nothing to license.

    python3 scripts/make_test_epub.py [out.epub]

Defaults to wordpress/preview/shelf/test-book.epub — the preview's own shelf
directory, standing in for /shelf/ on the live site.
"""

import sys
import zipfile
from pathlib import Path

TITLE = "A Test Book for the Reading Room"
AUTHOR = "BookLoversClub"
BOOK_ID = "urn:uuid:blc-test-book-0000-0000-000000000001"

CHAPTERS = [
    (
        "The Shape of a Page",
        [
            ("p", "A reader is a promise about attention. It says: the words will hold still, the line lengths will behave, and when you look up and back down again you will not have lost your place. Everything else — the settings, the contents, the little percentage in the corner — exists to keep that promise when the browser tries to break it."),
            ("p", "This chapter exists to be long. Pagination bugs do not appear in a paragraph; they appear on the seam between one screenful and the next, where a column break lands inside a line of verse, or a widow is left stranded, or the last line of a chapter is pushed onto a page of its own and the reader thinks the book has ended. So there is more here than anyone needs to read, and that is the point."),
            ("blockquote", "The book was there, and the light was there, and between them a person who had agreed, for an hour, to be somewhere else."),
            ("p", "Notice what the type does when you change its size. The column width should hold; the margins should not collapse; the words should not creep under the edge of the frame. If the layout is right, changing the size feels like changing the book, not like breaking it."),
            ("p", "Notice also what happens at the end of a paragraph that ends near the bottom of a column — whether the next paragraph begins cleanly on the following page, or whether a single orphaned line is left behind like a coat on a chair."),
        ],
    ),
    (
        "On Turning",
        [
            ("p", "There are two ways to move through a book on a screen, and the argument between them is older than the screen. One is the page: a fixed rectangle, turned. The other is the scroll: a continuous ribbon, dragged. Codex and volumen, arguing again in a browser window."),
            ("p", "The page knows where it ends. That is its whole advantage — you can see the shape of what remains, and finishing one is a small event. The scroll knows nothing but forward, which is why it is restful for reference and restless for reading."),
            ("verse", "Turn, and the light shifts.\nTurn, and the room is the same room.\nTurn, and you are further in\nthan you meant to be."),
            ("p", "A reader that offers both is not being indecisive. Some books, and some readers, and some phones held one-handed on a bus, want the ribbon. Most want the rectangle."),
        ],
    ),
    (
        "Where You Left Off",
        [
            ("p", "The hardest thing a reader does is remember. Not the text — the text is easy, it is a file — but the place. A place in a book is not a page number, because the page number changes when the type does. It is a position in the flow of the words themselves.[1]"),
            ("p", "This is why a reading position is stored as a canonical fragment identifier rather than a number: a description of where in the structure of the book you are, robust against every change of size, spacing, and screen. Set the type larger, and the percentage moves; the position does not."),
            ("p", "Which means the promise a reader makes can actually be kept. Close the tab in the middle of a sentence. Come back a week later on a different night with the lamp on and the type set bigger. The sentence will be waiting, mid-breath, exactly where you dropped it."),
            ("footnote", "[1] Page numbers were never about the words. They were about the paper."),
        ],
    ),
    (
        "The Club",
        [
            ("p", "Reading alone is the default and reading together is the achievement. Everything a book club does is an attempt to make a private act briefly public without spoiling what made it worth doing privately."),
            ("p", "Hence the small button at the bottom of this reader that copies a link to exactly this spot. It is the digital equivalent of turning your copy around on the table and pointing — here, this bit, read this bit and tell me I am not imagining it."),
            ("blockquote", "Nobody has ever recommended a book by summarising it. They recommend it by quoting one sentence and then watching your face."),
        ],
    ),
    (
        "Colophon",
        [
            ("p", "Set in whatever the reader prefers, on a page whose colours are borrowed from the site around it. Assembled by scripts/make_test_epub.py for the purpose of being opened, resized, navigated, closed, and opened again."),
            ("p", "Not a real book. A test of one."),
        ],
    ),
]

CSS = """/* Deliberately opinionated, so the reader's overrides have something to fight. */
html { font-family: Georgia, 'Times New Roman', serif; color: #111111; background: #ffffff; }
body { margin: 0; }
h1 { font-size: 1.6em; font-weight: normal; letter-spacing: 0.02em; margin: 2em 0 1em; }
p { margin: 0 0 1em; text-indent: 0; }
p + p { text-indent: 1.4em; margin-top: -1em; }
blockquote { margin: 1.5em 2em; font-style: italic; color: #333333; }
.verse { margin: 1.5em 2em; white-space: pre-wrap; font-style: italic; }
.footnote { font-size: 0.85em; color: #444444; border-top: 1px solid #cccccc; padding-top: 0.6em; margin-top: 2em; }
"""

CONTAINER = """<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="EPUB/package.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
"""


def chapter_xhtml(index, title, blocks):
    body = [f"    <h1>{title}</h1>"]
    for kind, text in blocks:
        escaped = text.replace("&", "&amp;").replace("<", "&lt;")
        if kind == "p":
            body.append(f"    <p>{escaped}</p>")
        elif kind == "blockquote":
            body.append(f"    <blockquote><p>{escaped}</p></blockquote>")
        elif kind == "verse":
            body.append(f'    <div class="verse">{escaped}</div>')
        elif kind == "footnote":
            body.append(f'    <aside class="footnote" epub:type="footnote"><p>{escaped}</p></aside>')
    joined = "\n".join(body)
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
  <head>
    <title>{title}</title>
    <link rel="stylesheet" type="text/css" href="../css/style.css"/>
  </head>
  <body epub:type="bodymatter">
    <section id="chapter-{index}" epub:type="chapter">
{joined}
    </section>
  </body>
</html>
"""


def nav_xhtml():
    items = []
    for i, (title, _) in enumerate(CHAPTERS, start=1):
        # Chapter one carries a sublist, so the contents tree is exercised.
        sub = ""
        if i == 1:
            sub = (
                "\n          <ol>\n"
                '            <li><a href="text/chapter-1.xhtml#chapter-1">The promise</a></li>\n'
                '            <li><a href="text/chapter-2.xhtml#chapter-2">…and the seam</a></li>\n'
                "          </ol>\n        "
            )
        items.append(f'        <li><a href="text/chapter-{i}.xhtml#chapter-{i}">{title}</a>{sub}</li>')
    listing = "\n".join(items)
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
  <head>
    <title>Contents</title>
  </head>
  <body>
    <nav epub:type="toc" id="toc">
      <h1>Contents</h1>
      <ol>
{listing}
      </ol>
    </nav>
  </body>
</html>
"""


def package_opf():
    manifest = [
        '    <item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>',
        '    <item id="css" href="css/style.css" media-type="text/css"/>',
    ]
    spine = []
    for i, _ in enumerate(CHAPTERS, start=1):
        manifest.append(
            f'    <item id="chapter-{i}" href="text/chapter-{i}.xhtml" media-type="application/xhtml+xml"/>'
        )
        spine.append(f'    <itemref idref="chapter-{i}"/>')
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="uid" xml:lang="en">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:identifier id="uid">{BOOK_ID}</dc:identifier>
    <dc:title>{TITLE}</dc:title>
    <dc:creator>{AUTHOR}</dc:creator>
    <dc:language>en</dc:language>
    <dc:rights>Public domain — written for this fixture.</dc:rights>
    <meta property="dcterms:modified">2026-01-01T00:00:00Z</meta>
  </metadata>
  <manifest>
{chr(10).join(manifest)}
  </manifest>
  <spine>
{chr(10).join(spine)}
  </spine>
</package>
"""


def build(out_path):
    out_path.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(out_path, "w", zipfile.ZIP_DEFLATED) as epub:
        # The mimetype entry must come first and be stored uncompressed.
        epub.writestr(
            zipfile.ZipInfo("mimetype"), "application/epub+zip", compress_type=zipfile.ZIP_STORED
        )
        epub.writestr("META-INF/container.xml", CONTAINER)
        epub.writestr("EPUB/package.opf", package_opf())
        epub.writestr("EPUB/nav.xhtml", nav_xhtml())
        epub.writestr("EPUB/css/style.css", CSS)
        for i, (title, blocks) in enumerate(CHAPTERS, start=1):
            epub.writestr(f"EPUB/text/chapter-{i}.xhtml", chapter_xhtml(i, title, blocks))
    return out_path


if __name__ == "__main__":
    target = Path(sys.argv[1] if len(sys.argv) > 1 else "wordpress/preview/shelf/test-book.epub")
    written = build(target)
    print(f"wrote {written} ({written.stat().st_size / 1024:.1f} KB, {len(CHAPTERS)} chapters)")

# Quote removals

Every reader quote that has been pulled from a fan page, and why. Re-research
overwrites `data/pages/<slug>.json` wholesale, so this file is the only thing
standing between a removed quote and its quiet return.

**Rule 5 in `research/SOURCING.md` has no exceptions:** when a commenter asks to
be removed, remove the quote. No verification, no negotiation, no reply asking
them to reconsider. Then log it here, in the same commit.

## How to log one

Add a row. Keep it factual and keep it minimal — this file is public, so it must
not record anything about the person beyond the handle that was already on the
page.

| Date | Book (slug) | Handle | Permalink | Reason |
|---|---|---|---|---|
| _(none yet)_ | | | | |

Reasons are one of: `takedown` (they asked), `editorial` (we decided against it),
`dead-link` (permalink stopped resolving), `misattributed` (the quote wasn't
theirs, or wasn't real).

## After a re-research

Before publishing a re-researched page, search the new JSON for every permalink
and handle in the table above. If one came back, delete that entry from the JSON
and note the date of the re-research in the row's Reason cell.

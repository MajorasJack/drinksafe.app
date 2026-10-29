# Drink Safe print collateral

Two stickers for venue toilets and one general flyer, taking their palette, type and mark
from the site so the printed material and drinksafe.app read as the same thing.

The stickers go up in venue toilets with the venue's permission. The flyer is for anyone
who picks it up, including a manager being asked about the stickers, so nothing in it is
addressed to a particular reader.

## Artwork values

| | Value | Where it comes from |
|---|---|---|
| Teal, primary | `#0D9488` | Darker stop of the shield gradient in `public/favicon.svg` |
| Teal, accent | `#2DD4BF` | Lighter stop of the same gradient |
| Navy ground | `#0B1220` | Shield stroke in `public/favicon.svg` |
| Body grey | `#94A3B8` | Slate 400, used for supporting lines on dark |
| Small print grey | `#64748B` | Slate 500 |
| Amber, warning | `#D97706` | Warning colour used on the site |
| Type | Instrument Sans 400 / 500 / 600 | `vite.config.ts`, self-hosted via Bunny |
| Mark | Lucide `shield-check`, teal rounded square | `public/favicon.svg`, `resources/js/components/BrandMark.vue` |

The in-app teal token is `oklch(0.65 0.15 195)`, a brighter shade than the gradient. Print
uses the gradient's `#0D9488` instead, because vivid cyan-greens are the colours that shift
most in CMYK and the darker tone survives the conversion.

CMYK starting points, for the printer to replace with their own profile conversion:

| Colour | Hex | C | M | Y | K |
|---|---|---|---|---|---|
| Teal, primary | `#0D9488` | 78 | 20 | 48 | 3 |
| Teal, accent | `#2DD4BF` | 62 | 0 | 32 | 0 |
| Navy ground | `#0B1220` | 89 | 79 | 54 | 74 |
| Amber, warning | `#D97706` | 8 | 63 | 100 | 1 |

The navy is a heavy four-colour build. Ask Solopress whether their press prefers a richer or
lighter black build for a flood of that size, and follow their answer over these figures.

The shield mark is drawn at 11mm or larger. Its stroke is 2.3 units at a 24 unit viewbox, so
below roughly 12mm the tick begins to fill in.

## Stickers

Two designs, 100 x 100mm square, giving a 94mm content area once the 3mm bleed and 3mm safe
margin are taken off. The size is set by the copy on sticker A, which fills that square at
9.5pt body with the steps at their longest wrap.

### Sticker A, cubicle door

Aimed at someone who may be frightened while reading it. Nobody is asked to dial 999
themselves; step three asks staff to call, which keeps the emergency route open without
putting the decision on the reader.

The signs line leads with feeling drunker than the amount drunk accounts for, on the advice
of someone who has been spiked and had no other symptom at all. Listing dizziness first
invites the reader to rule themselves out for lacking it.

Navy ground, white text.

```
▣  DRINK SAFE                         mark 11mm, wordmark 11pt / 600

Think your drink                      16pt / 600 / white
has been spiked?

Often the only sign is feeling far    8.5pt / 400 / #94A3B8
drunker than you should for what
you have had. Dizziness or
confusion can also be signs.

①  Tell bar staff now. They can       9.5pt / 500 / white
   help you get somewhere safe.       numerals #2DD4BF

②  Stay with people you trust.
   Do not leave on your own.

③  If you feel very unwell, ask
   staff to call for help.
                                      1pt rule, #0D9488
┌──────┐  Report it afterwards,       QR 22mm, dark modules
│  QR  │  anonymously. No names,      on a white square
│ 22mm │  no sign-up.                 8pt / 400 / #94A3B8
└──────┘  drinksafe.app               9.5pt / 600 / #2DD4BF
```

QR target: `https://drinksafe.app/submit-report`

### Sticker B, by the mirrors or hand dryers

For someone with a spare few seconds rather than someone in trouble. No emergency content,
so the QR can take the space instead.

Navy ground, white text.

```
▣  DRINK SAFE                         mark 11mm, wordmark 11pt / 600

Something happened                    17pt / 600 / white
on a night out?

      ┌──────────────┐                QR 30mm, dark modules on
      │      QR      │                a white square, sitting
      └──────────────┘                on the navy ground

Report it anonymously.                11pt / 500 / white
No names. No sign-up.

Or check what has been reported       8.5pt / 400 / #94A3B8
at a venue before you go.

drinksafe.app                         10pt / 600 / #2DD4BF
```

QR target: `https://drinksafe.app`

## Flyer

One design, A5, double sided. Front is useful whether or not the reader ever opens the app;
back is what to do and who to call. Both sides carry the QR, because the front is the side
facing up when it is left on a bar and it needs an action of its own.

The type runs larger than a screen layout would suggest. These are read at arm's length in a
badly lit room, and at 11pt the page was both hard work to read and two thirds empty.

```
FRONT                                   BACK

▣ Drink Safe        mark 17mm          If it happens             30pt / 600
                    wordmark 15pt
Often the only sign is    13.5pt / 400
                                       feeling far drunker than  #94A3B8
                                       you should for what you
                                       have had. Dizziness or
                                       confusion can also be
                                       signs.

                                       1 Tell bar staff. They    14pt / 500
Looking out for     38pt / 600           can help you get
each other                               somewhere safe.
                                       2 Stay with people you
A free, anonymous   13.5pt / 400         trust. Do not leave
place to report                          on your own.
drink spiking at                       3 If you feel very
UK venues, and to                        unwell, ask staff to
check a venue                            call for help.
before you go.
                                       ── WHO CAN HELP ──        11pt / 600
── FIVE THINGS      11pt / 600                                   teal, tracked
   THAT HELP        teal, tracked       Emergency        999
                                        Non-emergency    101     label 11pt grey
1 Never leave your  14.5pt / 400        Crimestoppers            number 16pt / 600
  drink unattended,                       0800 555 111           two columns
  even for a moment.                    Victim Support
2 Watch your drink                        08 08 16 89 111
  being prepared and                    Rape Crisis
  carried to you.                         0808 500 2222
3 If your drink                         The Survivors Trust
  tastes or smells                        08088 010 818
  unusual, do not                       Samaritans       116 123
  drink it.                             Mind
4 Stay with trusted                       0300 123 3393
  friends and look
  out for each other.                  ┌──────┐ Report it       QR 25mm
5 If you feel unwell                   │  QR  │ anonymously.    11.5pt
  or suspect you                       │ 25mm │ No names,
  have been spiked,                    └──────┘ no sign-up.
  tell someone                                  drinksafe.app   13pt / 600
  immediately and
  seek help.                           Drink Safe is an         8pt / #64748B
                                       informational platform.
┌──────┐ Report a venue                It does not replace
│  QR  │ anonymously, or               the police.
│ 25mm │ check one before
└──────┘ you go.
         drinksafe.app
```

QR target, both sides: `https://drinksafe.app/submit-report`

QR target: `https://drinksafe.app/submit-report`

The five tips, the support numbers and the small print are taken word for word from the About
page and the footer disclaimer, so the print and the site cannot drift apart. If the site's
wording changes, the artwork changes with it.

The line about what being spiked feels like is on the site as well, as the fifth Staying Safe
tip. The print drops "of being spiked" from it, because on both pieces a heading directly
above already says so, and the list item on the site has nothing above it to lean on.

## Business card

One design, 85 x 55mm, double sided, navy ground. Front is the mark, wordmark and the same
"Looking out for each other" line as the flyer. Back is the home page QR (30mm, the same
`qr-home-34mm.svg` as sticker B) with "Report it. Check a venue." and the URL, so a card
handed over in conversation has one action on it.

3mm bleed and 3mm safe margin, as the stickers. Ordered and set up like the flyer, on 350gsm
matt or silk stock.

## Poster

One design, A4 and A3, single sided, for partners to print themselves. Most of those prints
come off an office laser printer in black and white, where a navy flood prints streaky and
uses a lot of toner, so the poster is the one piece on a white ground.

Navy type on white throughout. Teal appears only in the mark, the "Safe" of the wordmark and
the rules, and never carries anything the type does not already say, so a greyscale print
loses nothing. The supporting greys are darker than on the other pieces (`#334155` rather
than `#94A3B8`) because a laser printer renders light greys as a visible dot pattern.

The copy is sticker A's headline, signs line and three steps, the flyer back's Who Can Help
numbers, and the site footer's disclaimer, all word for word.

```
▣  DRINK SAFE                         mark 16mm, wordmark 20pt / 600

Think your drink                      38pt / 600 / navy
has been spiked?

Often the only sign is feeling far    14pt / 400 / navy
drunker than you should for what you
have had. Dizziness or confusion can
also be signs.

1  Tell bar staff now. They can help  15pt / 500 / navy
   you get somewhere safe.            numerals navy / 600
2  Stay with people you trust. Do
   not leave on your own.
3  If you feel very unwell, ask staff
   to call for help.

WHO CAN HELP ───────────────          12pt / 600 navy, teal rule

Emergency          999   Non-emergency     101     label 12pt #334155
Crimestoppers  0800 555 111   Victim Support ...   number 15pt / 600
Rape Crisis  0808 500 2222   The Survivors Trust   two columns, ruled
Samaritans      116 123   Mind     0300 123 3393

                                      1pt rule, #0D9488
┌────────────┐                        QR 106mm on A4
│            │  Report it            16pt / 400 / navy
│     QR     │  anonymously.
│            │  No names,
│   106mm    │  no sign-up.
│            │  drinksafe.app        20pt / 600 / navy
│            │
│            │  Drink Safe is an     9pt / #334155, the
└────────────┘  informational ...    foot of the QR column
```

The disclaimer sits in the column beside the QR rather than below it. At a 106mm QR there is
no height left on A4 for a line of its own.

QR target: `https://drinksafe.app/submit-report`, or `https://drinksafe.app/go/<code>` on a
partner's copy.

The PDFs are trim size with no bleed, because office printers cannot print to the edge.
Everything sits 12mm inside the trim, clear of the 10mm most printers leave unprinted. Trade
print versions with bleed are not produced; a partner ordering from a print shop can send the
A4 or A3 file as it is, since the white ground needs no bleed.

The A3 is the A4 page enlarged by the A-series ratio (420 / 297, about 1.414), exactly as a
photocopier enlarges it. The two sizes therefore cannot drift apart, and the A3 type and QR
are 1.414 times their A4 size.

### QR size

QR codes are usually sized at one tenth of the reading distance, so a code read from 1m
should be at least 100mm across, quiet zone included.

| | Reading distance | Minimum | Placed at | Module |
|---|---|---|---|---|
| A4 | about 1m | 100mm | 106mm | 2865 micron |
| A3 | about 1.5m | 150mm | 150mm (106mm x 1.414) | 4051 micron |

106mm is the smallest A4 size whose enlargement still meets the A3 minimum. The symbol is
3-M (37 modules including the quiet zone), so each module is 106 / 37 = 2.865mm on A4, against
the 400 micron minimum.

## QR codes

Generated as vector at final size and committed to `print/qr/`.

| File | Target | Symbol | Size | Module |
|---|---|---|---|---|
| `qr-report-22mm.svg` | `/submit-report` | 3-M | 22mm | 595 micron |
| `qr-home-34mm.svg` | home | 2-M | 34mm | 1030 micron, placed at 30mm |
| `qr-report-25mm.svg` | `/submit-report` | 3-M | 25mm | 676 micron |
| `qr-report-106mm.svg` | `/submit-report` | 3-M | 106mm | 2865 micron, 4051 micron on the A3 poster |

Each file already contains its four module quiet zone, so the stated size is the full square
to place in the artwork and no extra clearance is needed. Modules are `#0B1220` on white.

The files are made with [segno](https://segno.readthedocs.io/) 1.6.6, which is not a project
dependency and runs without installing anything through `uvx`:

```
uvx --with segno==1.6.6 python -c "
import segno
qr = segno.make('https://drinksafe.app/submit-report', error='m', micro=False, boost_error=False)
qr.save('print/qr/qr-report-106mm.svg', scale=106 / qr.symbol_size()[0], unit='mm',
        dark='#0b1220', light='#fff', svgclass=None, lineclass=None, nl=False)
"
```

Each committed file ends with a newline added after saving. `boost_error=False` keeps the
error correction at M; without it segno raises the level whenever the data leaves room.

Every module is comfortably above the 400 micron mark where print QR codes start failing on
older phone cameras. Error correction is M, which tolerates around 15% damage, so a scuffed
or partly peeled sticker still scans.

The codes sit on white, not on the navy. An inverted QR scans on most modern phones but not
all, and a sticker that fails for one person in ten is not worth the aesthetic.

## Production

Two separate orders. The stickers and the flyer share no material or quantity, so they do not
need to be bought together.

### Stickers

| | |
|---|---|
| Size | 100 x 100mm square |
| Designs | 2 |
| Material | White vinyl |
| Lamination | Matt |
| Adhesive | Permanent |
| Cut | Square, or rounded corners if offered at no extra cost |

Matt lamination is not cosmetic. Toilet lighting is almost always directly overhead, and a
gloss laminate throws it straight back at the person trying to read the sticker.

Rounded corners lift less. Square is fine if it costs more.

### Flyer

| | |
|---|---|
| Size | A5, 148 x 210mm |
| Sides | 2 |
| Stock | 300gsm silk |

### Artwork setup, both orders

| | |
|---|---|
| Colour | CMYK |
| Resolution | 300dpi |
| Bleed | 3mm |
| Safe margin | 3mm stickers, 5mm flyer |
| Fonts | Outlined |
| Format | Print ready PDF |

These are standard UK trade values rather than figures quoted from Solopress. Download their
template for the exact product before the artwork is finalised and follow it wherever it
disagrees, because their per-product templates are the authority on their own presses.

Instrument Sans is free to install, but the fonts are outlined anyway so nothing can
substitute on someone else's machine.

## Producing the files

The artwork is HTML rendered to PDF, so the type and colours stay tied to the same values the
site uses and the files can be rebuilt whenever the copy changes.

```
node print/artwork/render.mjs
```

That writes PDFs and 300dpi PNG proofs to `print/output/`. The A3 poster has no proof of its
own, because it is the A4 proof enlarged.

```
node print/artwork/render.mjs <code>
```

With a partner code, only the A4 poster is rendered, as `print/output/poster-a4-<code>.pdf`,
with the QR from `print/qr/partners/<code>.svg`. The code may only contain lowercase letters,
digits and hyphens. The render stops if that partner's QR file does not exist, and any render
stops if a QR image fails to load, so a missing or broken SVG can't produce a poster with a
blank square where the QR should be. A partner render writes only the PDF, with no PNG proof.

Sources are in `print/artwork/`, with the fonts copied alongside them so nothing depends on what
is installed locally. `print/` is git-ignored, so add new artwork, QR and output files with
`git add -f`.

Each page is rendered 0.2mm larger than trim plus bleed. Chromium rounds the PDF media box
down to whole points, and without the margin a 106mm page comes out at 105.83mm, which would
leave the bleed a hair under 3mm and could trip a preflight check.

The poster is rendered at exact trim instead. Chromium sizes PDF pages in steps of 0.96pt, so
the A4 comes out at 210.2 x 297.0mm and the A3 at 297.0 x 420.2mm, a white edge of a fifth of a
millimetre that no printer will notice.

Two things the renderer cannot do, both of which Solopress handle as a matter of course:

1. **The PDFs are RGB.** Chromium has no CMYK output. Solopress convert on receipt. The CMYK
   figures above are there so the conversion can be checked rather than assumed.
2. **Fonts are embedded as subsets, not outlined.** Embedded subsets are accepted by every UK
   trade printer and Instrument Sans is licensed for it. Ask for outlines only if preflight
   objects.

## Things to confirm before ordering

1. **Venue permission.** The stickers go up with the venue's agreement. That conversation
   happens in person and is not something the artwork covers.
2. **Ask for Angela.** Deliberately left off. Naming it only helps in venues that take part,
   and a sticker promising a scheme whose staff have never heard of it is worse than saying
   nothing. A variant for participating venues can be added later.
3. **Quantities.** Not needed to finalise the artwork, but they decide which Solopress product
   tier is cheapest.
4. **A proof on the real material.** The navy flood and the teal are both worth seeing on
   vinyl before committing to a full run.

## Related

`brand-amber`, `brand-amber-light`, `brand-amber-dark` and `brand-teal-light` are used in
`resources/js/pages/Home.vue` and `resources/js/components/map/HeatmapToggle.vue` but are
defined only in `design_files/tailwind.config.js`, the Tailwind v3 prototype config that the
Laravel build does not read. Those classes emit no CSS, so the home page search button and the
police card currently render unstyled. The amber in this document comes from the prototype
config and is the intended value; fixing the app is separate work.

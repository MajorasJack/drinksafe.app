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

Navy ground, white text.

```
▣  DRINK SAFE                         mark 11mm, wordmark 11pt / 600

Think your drink                      16pt / 600 / white
has been spiked?

Sudden dizziness, confusion or        8.5pt / 400 / #94A3B8
losing your balance can be signs.

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

## QR codes

Generated as vector at final size and committed to `print/qr/`.

| File | Target | Symbol | Size | Module |
|---|---|---|---|---|
| `qr-report-22mm.svg` | `/submit-report` | 3-M | 22mm | 595 micron |
| `qr-home-34mm.svg` | home | 2-M | 34mm | 1030 micron, placed at 30mm |
| `qr-report-25mm.svg` | `/submit-report` | 3-M | 25mm | 676 micron |

Each file already contains its four module quiet zone, so the stated size is the full square
to place in the artwork and no extra clearance is needed. Modules are `#0B1220` on white.

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

That writes PDFs and 300dpi PNG proofs to `print/output/`. Sources are in `print/artwork/`,
with the fonts copied alongside them so nothing depends on what is installed locally.

Each page is rendered 0.2mm larger than trim plus bleed. Chromium rounds the PDF media box
down to whole points, and without the margin a 106mm page comes out at 105.83mm, which would
leave the bleed a hair under 3mm and could trip a preflight check.

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

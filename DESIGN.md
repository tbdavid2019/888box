---
name: 888 BOX
description: A warm-paper asset platform for uploading, organizing, sharing, and managing images, video, audio, and files.
colors:
  saffron: "#ffb010"
  saffron-hover: "#f2a600"
  deep-blue: "#17324d"
  clay: "#a9471d"
  warm-cream: "#fff9e8"
  canvas: "#fff0cc"
  surface-soft: "#fff2d1"
  surface-raised: "#f7e9c5"
  surface-white: "#fffefa"
  ink: "#2b251a"
  muted: "#655a43"
  border: "#9b8350"
  border-strong: "#6b5b38"
  link-hover: "#6b3e08"
  success: "#166534"
  success-bg: "#dcfce7"
  warning: "#92400e"
  warning-bg: "#fef3c7"
  danger: "#991b1b"
  danger-bg: "#fee2e2"
  info: "#075985"
  info-bg: "#e0f2fe"
  purple: "#6b21a8"
  purple-bg: "#f3e8ff"
typography:
  ui:
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', '888box Gen Jyuu Gothic', sans-serif"
  mono:
    fontFamily: "'888box JetBrains Mono', '888box Maple Mono', ui-monospace, monospace"
  icon-font:
    fontFamily: "'Material Symbols Outlined'"
  brand-number:
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', '888box Gen Jyuu Gothic', sans-serif"
    fontSize: "3.4rem"
    fontWeight: 900
    lineHeight: 1
    letterSpacing: "-0.06em"
  brand-word:
    fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', '888box Gen Jyuu Gothic', sans-serif"
    fontSize: "2.7rem"
    fontWeight: 900
    letterSpacing: "0.08em"
  section-navigation:
    fontFamily: "inherit"
    fontSize: "0.85rem"
    fontWeight: 600
rounded:
  metadata-field: "6px"
  control: "8px"
  section-tab: "8px"
  section-rail: "12px"
  queue-panel: "20px"
  upload-dropzone: "28px"
  portal-card: "32px"
  asset-icon: "16px"
  pill: "999px"
spacing:
  nav-rail-inset: "4px"
  nav-link-gap: "4px"
  nav-link-inline: "12px"
  control-padding: "12px 28px"
  card-padding: "30px"
  dropzone-padding: "34px 20px"
components:
  button-primary:
    backgroundColor: "{colors.saffron}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "{spacing.control-padding}"
  button-primary-hover:
    backgroundColor: "{colors.saffron-hover}"
    textColor: "{colors.ink}"
  button-secondary:
    backgroundColor: "{colors.warm-cream}"
    textColor: "{colors.deep-blue}"
    rounded: "{rounded.control}"
    padding: "{spacing.control-padding}"
  input-text:
    backgroundColor: "{colors.surface-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.metadata-field}"
  section-tab-active:
    backgroundColor: "{colors.saffron}"
    textColor: "{colors.ink}"
    rounded: "{rounded.section-tab}"
    padding: "0 12px"
  asset-card:
    backgroundColor: "{colors.warm-cream}"
    textColor: "{colors.ink}"
    rounded: "{rounded.portal-card}"
    padding: "{spacing.card-padding}"
  status-success:
    backgroundColor: "{colors.success-bg}"
    textColor: "{colors.success}"
  status-warning:
    backgroundColor: "{colors.warning-bg}"
    textColor: "{colors.warning}"
  status-danger:
    backgroundColor: "{colors.danger-bg}"
    textColor: "{colors.danger}"
  status-info:
    backgroundColor: "{colors.info-bg}"
    textColor: "{colors.info}"
  status-purple:
    backgroundColor: "{colors.purple-bg}"
    textColor: "{colors.purple}"
  portal-masthead:
    backgroundColor: "{colors.saffron}"
    textColor: "{colors.ink}"
    padding: "30px 20px 26px"
  asset-type-icon:
    backgroundColor: "{colors.surface-soft}"
    textColor: "{colors.deep-blue}"
    rounded: "{rounded.asset-icon}"
    size: "52px"
  asset-icon-image:
    backgroundColor: "{colors.info-bg}"
    textColor: "{colors.info}"
  asset-icon-video:
    backgroundColor: "{colors.purple-bg}"
    textColor: "{colors.purple}"
  asset-icon-file:
    backgroundColor: "{colors.success-bg}"
    textColor: "{colors.success}"
  asset-icon-audio:
    backgroundColor: "{colors.warning-bg}"
    textColor: "{colors.warning}"
---

# Design System: 888 BOX

## Overview

**Creative North Star: "The Warm-Paper Asset Desk"**

888 BOX is an operational asset desk for four asset types. The portal puts a universal upload surface and the image, video, file, and audio centers in the first view; routed centers, administration, and shared asset pages retain their established task layouts. Saffron marks primary actions and the current section, navy carries headings, links, and section navigation, and clay identifies upload targets.

Warm cream panels sit on a warm canvas with ink copy and muted supporting text. The shared UI uses the system sans stack declared in `static/css/fonts.css`. Global theme rules carry the paper surfaces, shared navigation, semantic state pairs, and focus treatment across the updated routes while local page CSS supplies each task's spacing and geometry.

**Key Characteristics:**

- Warm canvas, cream panels, and dark ink text.
- Saffron mastheads and primary actions; navy hierarchy, links, and section navigation.
- Four asset centers with route-specific upload, management, and share layouts.
- Explicit foreground/background pairs for semantic messages, asset-type chips, and portal center icons.

## Colors

The palette translates the confirmed 2MD reference at https://2md.aiurl.tw/ into a light, warm-paper interface.

### Primary

- **Saffron** (#ffb010): Primary actions, the public masthead, and the active section tab; use ink text on this fill.
- **Saffron hover** (#f2a600): Hover fill for primary actions.
- **Deep blue** (#17324d): Headings, links, focus accents, table headers, and the interior section rail.
- **Clay** (#a9471d): Upload-target border and secondary warm accent.

### Neutral

- **Warm canvas** (#fff0cc): Page background.
- **Warm cream** (#fff9e8): Main cards, panels, and controls such as secondary buttons.
- **Soft paper** (#fff2d1): Subtle panels, hover surfaces, and dropzone base.
- **Raised paper** (#f7e9c5): Disabled controls and progress tracks.
- **Clear paper** (#fffefa): Form field background.
- **Ink** (#2b251a): Main copy and text on saffron.
- **Muted ink** (#655a43): Supporting copy, hints, and disabled text.
- **Border** (#9b8350) and **strong border** (#6b5b38): Surface outlines and form-control borders.

### Semantic Pairings

- **Success:** #166534 on #dcfce7.
- **Warning:** #92400e on #fef3c7.
- **Danger:** #991b1b on #fee2e2.
- **Info:** #075985 on #e0f2fe.
- **Purple status:** #6b21a8 on #f3e8ff.

### Asset-Type Icon Pairings

The portal center icons use these same semantic pairs, with a matching border; the adjacent center title keeps each type identifiable:

- **Image:** #075985 on #e0f2fe.
- **Video:** #6b21a8 on #f3e8ff.
- **File:** #166534 on #dcfce7.
- **Audio:** #92400e on #fef3c7.

**The Ink-on-Saffron Rule.** Primary actions and the active section tab use ink on saffron; hover shifts the fill to saffron-hover.

**The Paired-State Rule.** Keep each semantic foreground with its matching background and label the state in text or an icon.

**The Contrast Rule.** Meet WCAG 2.2 AA: normal text needs at least 4.5:1 contrast, large text needs at least 3:1 under SC 1.4.3, and essential control indicators need at least 3:1 against adjacent colors under SC 1.4.11. [WCAG 2.2](https://www.w3.org/TR/WCAG22/).

## Typography

**Display Font:** system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "888box Gen Jyuu Gothic", sans-serif
**Body Font:** system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "888box Gen Jyuu Gothic", sans-serif
**Label Font:** The same UI stack; navigation and form labels keep their local sizes and weights.
**Utility Mono:** `888box JetBrains Mono`, with `888box Maple Mono` as the RSS stylesheet fallback for code-like text. Use these for RSS code and file previews, not interface copy.
**Icon Font:** `Material Symbols Outlined` is reserved for the portal brand glyph; action icons use the existing Lucide SVG set.

**Character:** The system sans stack keeps upload, management, and sharing screens plain to scan across English and Traditional Chinese content. The 888 BOX wordmark uses the same stack with a compact, heavy treatment.

### Hierarchy

- **Portal brand number** (900, 3.4rem, line-height 1, letter-spacing -0.06em): The “888” part of the home-page wordmark.
- **Portal brand word** (900, 2.7rem, letter-spacing 0.08em): The “BOX” part of the home-page wordmark.
- **Portal card title** (700, 1.5rem): Asset-center titles in the portal grid.
- **Section navigation** (600, 0.85rem desktop; 0.82rem at the responsive breakpoint): Interior site and admin navigation.
- **Upload prompt** (700, 1.2rem; supporting copy 0.83rem): The universal upload surface.
- **Body and form text:** Inherit the shared UI stack and route-level sizing.

**The UI-Stack Rule.** Keep the declared system UI stack for interface text; the variable-font specimen in the direction contract is not the shipped UI font.

## Layout

The portal centers its content in a 1000px-wide region. Its universal upload panel precedes a four-column grid with two 220px rows; each asset-center card spans two columns, producing two cards per row on desktop. The page changes to a single-column grid at 768px.

Interior pages use a full-width shared header with a 56px minimum height. At widths up to 820px, its asset navigation becomes a horizontally scrollable row and the header actions remain independently scrollable. Keep the section rail navy with an unmistakable saffron active tab.

The shared theme defines color and navigation consistency, not one universal page template. Retain each route's form, table, media, and share structure.

**Evidence boundary:** These values were extracted from `static/css/theme-888.css`, `config/themes.php`, `static/css/fonts.css`, `config/theme_helper.php`, and the existing route styles. This environment has no PHP runtime, Docker, browser, or headless renderer, so this document does not claim visual screenshot approval.

## Elevation & Depth

The interface uses warm tonal layering with restrained shadows. Paper surfaces and borders do most of the separation work; shadows appear on panels, the masthead, primary actions, and dialogs.

### Shadow Vocabulary

- **Panel and card resting shadow** (`0 8px 22px rgba(79, 58, 14, 0.10)`): Shared cards, upload panels, dialogs, and management surfaces.
- **Masthead shadow** (`0 4px 14px rgba(79, 58, 14, 0.14)`): Shared interior header.
- **Primary action shadow** (`0 3px 8px rgba(150, 98, 0, 0.18)`): Primary buttons.
- **Dialog shadow** (`0 16px 40px rgba(79, 58, 14, 0.18)`): Modal content.
- **Portal icon hover** (`0 8px 18px rgba(79, 58, 14, 0.12)`): The asset-type icon tile when its card is hovered.
- **Upload target hover / drag-over** (`0 12px 30px rgba(79, 58, 14, 0.14)`): The universal upload dropzone.
- **Focus ring** (`0 0 0 3px rgba(23, 50, 77, 0.18)`): Text fields when focused; this is a focus affordance rather than surface elevation.

Keyboard-operable controls use a 3px navy `:focus-visible` outline with a 3px offset. The section rail uses a saffron 3px outline with a 2px offset so focus remains visible against navy. Asset icons are decorative; their linked card receives the navy focus outline.

## Shapes

The implementation has several route-specific radii rather than one global radius. The shared section rail is 12px; its links and the video/audio action buttons use 8px. The metadata fields use 6px, the portal upload target uses 28px, the portal cards use 32px, and the upload queue panel uses 20px. Preserve these observed shapes when extending the corresponding component; inspect that route's CSS before assigning a radius to a new component.

Borders are generally one pixel, with the upload target using its existing dashed outline. Use the warm border for surfaces and the stronger brown border for controls. Portal asset-type icon tiles are 52px square with 16px corners. Pill geometry is reserved for existing chips and rounded controls.

## Components

### Buttons

Primary actions are tactile and legible; secondary actions read as quiet paper controls.

- **Primary:** Ink on saffron; the audio/video action button uses 12px 28px padding, an 8px radius, and the primary-action shadow.
- **Hover / Focus:** Primary hover uses #f2a600 and keeps ink text. Keyboard focus uses the navy 3px outline with a 3px offset; preserve a visible focus treatment.
- **Secondary:** Warm cream with navy text and a navy outline; hover changes the surface to soft paper.

### Cards and Containers

- **Portal asset cards:** Warm cream with a #9b8350 border, 32px corners, and 30px padding.
- **Shared panels:** Warm cream with the #9b8350 border and the panel shadow.
- **Upload targets:** Soft paper with a clay dashed border; hover switches to cream and a navy border.

### Inputs and Fields

- **Style:** Clear-paper background, ink text, and the stronger #6b5b38 border. Route-level field radii remain local; for example, the video/audio batch metadata fields use 6px.
- **Focus:** Navy border and a 3px navy focus ring.
- **Disabled:** Raised-paper fill, muted text, and the warm border; disabled controls stay visibly disabled without reduced opacity.

### Chips and Messages

- **Asset type:** Use the defined pair for each type; keep the type label visible so meaning does not rely on color.
- **Status:** Success, warning, danger, info, and purple status use the semantic pairs listed above. Messages retain readable text and an explicit label or icon.

### Asset-Type Icons

Portal center icons are 52px square with 16px corners and a 26px line icon. Image uses blue/info, video purple, file green/success, and audio brown/warning foreground/background pairs, each with a border matching its foreground. Resting icons have no shadow; card hover adds the warm portal-icon shadow. The icon remains decorative because the center title supplies its meaning.

### Navigation

The shared header uses a saffron surface with ink branding and utility actions. Its deep-blue section rail contains cream links; the active section uses saffron with ink text and no extra active-state shadow. Navigation focus uses a saffron 3px outline with a 2px offset. At mobile widths, the section row scrolls horizontally.

## Do's and Don'ts

### Do:

- **Do** use ink on saffron for primary actions and the active section tab.
- **Do** keep links navy on warm surfaces and use the defined semantic foreground/background pairs.
- **Do** use the specified semantic pairs for asset-type icons and keep their text titles visible.
- **Do** preserve existing page layouts and the responsive section navigation.
- **Do** meet WCAG 2.2 AA text and non-text contrast thresholds.

### Don't:

- **Don't** use color alone to communicate upload, warning, error, or asset-type state.
- **Don't** replace the shared system UI stack or introduce a radius or shadow without checking the route CSS.
- **Don't** reintroduce obsolete dark-theme accents into shared controls or surfaces.

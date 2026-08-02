## Context

`view.php` presently streams PDFs through an authorized route, but text files fall into the generic fallback. EPUB files load `epub.js` from a CDN URL that now returns 404 and ask the reader to fetch a masked storage URL that redirects to S3 without CORS headers. Public assets can also be password-protected, so preview delivery must remain behind the existing authorization check.

## Goals / Non-Goals

**Goals:**

- Provide a readable preview for small, text-like files without interpreting their content as HTML.
- Load EPUB books from a same-origin, authorized URL and use a version-pinned working reader script.
- Make sharing the page itself the default copy action while retaining explicit direct and embed formats.

**Non-Goals:**

- Editing documents, rendering Office files, or adding full-text search.
- Altering upload validation, storage buckets, access-control settings, or database records.
- Proxying arbitrary remote URLs.

## Decisions

### Reuse the share endpoint for inline document delivery

An explicit query mode on the already-authorized `view.php` request will stream only the current asset. This follows the proven PDF pattern, keeps remote credentials server-side, and prevents an EPUB reader from requesting the S3 URL directly. A general public proxy endpoint was rejected because it could be abused to fetch arbitrary remote content.

### Render text with a same-origin fetch and `textContent`

The page will request the authorized inline text mode and place the result in a `<pre>` element with `textContent`. A small fixed preview limit prevents a very large text upload from consuming the page or PHP memory. Injecting the text into HTML or using an iframe was rejected because it would weaken script/content isolation or give a poorer readable presentation.

### Use a pinned EPUB.js distribution with a graceful failure state

The page will load EPUB.js 0.3.88 from the verified jsDelivr distribution and pass it the same-origin inline asset URL. The reader area will display a download fallback if the script or book fails to load. Vendoring the large third-party bundle was deferred to avoid an unrelated binary asset update in this lightweight PHP project.

### Separate share links from direct/embed links

The first tab will be the share-page URL. Direct URL, Markdown, HTML, and BBCode remain available only as explicitly named output formats. This retains use cases such as image embedding while preventing accidental publication of storage URLs.

## Risks / Trade-offs

- [Large text documents are not fully previewed] → Enforce a documented preview cap and leave the download control available.
- [The external EPUB library CDN is unavailable] → Render a clear failure state with a download fallback; the reader source is version-pinned and verified before release.
- [Remote storage is slow] → Stream through the current server path with existing timeout behavior; only supported asset types can use it.

## Migration Plan

1. Deploy the `view.php` update and regression contract test.
2. Validate a local and S3-backed text asset, then the existing S3 EPUB through public share URLs.
3. Roll back by reverting the feature commit; no data migrations or stored assets change.

## Open Questions

- None.

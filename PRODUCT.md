# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Inferred from repository flows: people who need one place to upload, organize, share, and manage personal media and documents, plus administrators who maintain the service.

## Product Purpose

Inferred from the product copy and routes: 888 BOX is a unified personal asset platform for image, video, audio, and document hosting. It gives users direct upload, processing, sharing, and viewing flows while giving administrators type-specific management tools.

## Positioning

Inferred from implementation: one lightweight PHP deployment supports multiple stored asset types through a unified API, shared storage model, and dedicated user and administration surfaces.

## Operating Context

Users enter through the public portal or a dedicated upload center, upload a file or a remote URL, receive a shareable asset page, and return to manage or consume it. Administrators use separate image, video, audio, file, user, and settings screens.

## Capabilities and Constraints

- The app is a lightweight PHP application with vanilla JavaScript and static CSS.
- It supports image, video, audio, and document upload, remote ingestion, sharing, viewing, podcast RSS, and administration.
- Public and legacy upload entrypoints remain active and compatible.
- The full public, upload, view/share, and administrative interface is in scope for a unified theme update.

## Brand Commitments

- Product name: 888 BOX.
- Maintain the existing logo, product content, and functional routes.
- Use the visual character of https://2md.aiurl.tw/ as a reference direction where applicable.

## Evidence on Hand

- Public portal: `index.php`.
- Upload centers: `upload_image.php`, `upload_video.php`, `upload_audio.php`, `upload_file.php`.
- Asset viewer/share page: `view.php`.
- Administration surfaces: `admin/`.
- Existing style sheets: `static/css/`.

## Product Principles

- Inferred: make every asset type immediately understandable and actionable.
- Inferred: keep upload, sharing, and administration flows direct and dependable.
- User confirmed: apply one coherent visual system across public, upload, administrative, and sharing surfaces.
- Inferred: preserve a lightweight deployment and frontend architecture.

## Accessibility & Inclusion

- All updated colour combinations and interactive states must meet WCAG readable-contrast requirements.

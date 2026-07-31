## ADDED Requirements

### Requirement: Public pages provide installable PWA metadata
The system SHALL expose a root-scoped web app manifest and PWA metadata on the portal, public upload centres, and unified asset viewer. The manifest MUST identify 192px and 512px install icons and a 512px maskable icon.

#### Scenario: User opens a public entry page
- **WHEN** a user loads the portal, an upload centre, or an asset viewer over HTTPS
- **THEN** the document includes the configured manifest, theme color, and Apple touch metadata

#### Scenario: Browser loads the manifest
- **WHEN** a compatible browser requests the web app manifest
- **THEN** it receives application name, standalone display mode, and the required install icon definitions

### Requirement: Public UI registers a root-scoped service worker
The system SHALL register a root-scoped service worker from each public entry page when the browser supports service workers.

#### Scenario: Compatible browser loads a public page
- **WHEN** service workers are supported and the page is served from a secure context
- **THEN** the browser registers `/sw.js` with root scope

#### Scenario: Unsupported browser loads a public page
- **WHEN** the browser does not support service workers
- **THEN** the public page remains fully usable without a registration error

### Requirement: Offline caching protects private and dynamic data
The service worker MUST cache only the offline shell and public static UI assets. It MUST NOT cache non-GET requests, API requests, admin pages, generated or user-uploaded assets, or asset-delivery routes.

#### Scenario: Browser requests a static UI asset
- **WHEN** a GET request targets a public asset below `/static/`
- **THEN** the service worker serves a cached response when available and stores a successful public response for later use

#### Scenario: Browser submits an upload or calls an API
- **WHEN** a request is non-GET or targets a dynamic API or asset-delivery route
- **THEN** the service worker passes the request directly to the network without writing it to a cache

### Requirement: Offline navigations receive an actionable fallback
The system SHALL return a dedicated offline page when a top-level public navigation fails because the network is unavailable.

#### Scenario: User opens a public page while offline
- **WHEN** a navigation request cannot reach the network after the service worker is installed
- **THEN** the browser receives the dedicated offline page explaining that uploads and live asset data require a connection

## ADDED Requirements

### Requirement: Eligible browsers expose an in-page PWA installation action
The system SHALL show an in-page installation action on public PWA pages after a compatible browser emits `beforeinstallprompt`. The action MUST not appear when the application is already running in standalone display mode.

#### Scenario: Browser permits installation
- **WHEN** a compatible browser emits `beforeinstallprompt` on a public PWA page
- **THEN** the page displays a clearly labelled `安裝 888box` action

#### Scenario: Application is already installed
- **WHEN** the application is running in standalone display mode
- **THEN** the system does not display the in-page installation action

### Requirement: Installation action opens the native browser confirmation
The system SHALL defer the browser install event and invoke its native prompt only from the user's click on the in-page installation action.

#### Scenario: User chooses to install
- **WHEN** the user taps the `安裝 888box` action after the browser has emitted an install event
- **THEN** the system opens the browser's native installation confirmation

#### Scenario: Installation completes or is dismissed
- **WHEN** the application is installed, or the user closes the in-page action
- **THEN** the in-page installation action is removed from the current page

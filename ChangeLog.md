# CHANGELOG MODULE KREA2FACTORAUTH FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## [1.29.20] - 2026-11-21

### Security
- Require successful Google provider authentication and matching, unexpired module callback session state before accepting a Google login.
- Prevent OAuth return requests from falling back to password authentication and fail closed when the authenticated user cannot be loaded.
- Add automated PHP authentication regression checks, history secret scanning, dependency auditing, and a private vulnerability reporting policy.
- Exclude local credentials, keys, logs, backups, and business-data directories from source control.

## [1.29.19] - 2026-10-07

### Fixed
- Display the generated exception password alongside the existing copy button in password-reset result messages.

## [1.29.18] - 2026-07-13

### Fixed
- Restored the same internal Dolibarr page after session-timeout authentication instead of allowing the configured landing page to replace the interrupted destination.
- Preserved the interrupted page through module-managed Google OAuth login while rejecting external return URLs and removing authentication or state-changing query parameters.

## [1.29.17] - 2026-07-11

### Fixed
- Restored the entity-specific activation state required by dependent modules to detect Krea2FactorAuth Google login availability correctly.

### Changed
- Documented the distinction between global login bootstrap constants and per-entity Krea2FactorAuth activation when diagnosing Google login availability.

## [1.29.16] - 2026-07-11

### Fixed
- Blocked password-expiration removal when TOTP enrollment is inactive and the user has no configured Google 2FA email.
- Prevented inactive TOTP mode from creating or honoring mandatory TOTP setup as the fallback for password-expiration removal.

## [1.29.15] - 2026-07-10

### Changed
- Hid Access Management TOTP-specific columns, filters, sorting, and row actions when TOTP enrollment mode is off.
- Cleared stale TOTP list sort/filter state when the TOTP column is hidden.

## [1.29.14] - 2026-07-10

### Changed
- Kept password expiration enabled when disabling it would leave a user without enforced local TOTP or user-configured Google 2FA; the action now requires TOTP setup first instead of clearing expiration.
- Distinguished system-level Google 2FA availability from user-level Google 2FA setup in Access Management, so users missing the module Google login email no longer appear as having an active Google path.

### Security
- Blocked password-expiration removal for 2FA-exempt users until the exemption is removed and a real second factor is active.

## [1.29.13] - 2026-07-10

### Changed
- Preserved second-factor enforcement when password expiration is disabled: users without local TOTP now become mandatory for TOTP setup unless configured Google 2FA is already available.
- Marked configured Google 2FA as mandatory in Access Management when password expiration is off and local TOTP is inactive, and blocked local password login in that state.

## [1.29.12] - 2026-07-10

### Fixed
- Applied the same Google 2FA availability rule to login requests, login buttons, user-card credential rows, Google email extrafield visibility, and Access Management policy checks.

## [1.29.11] - 2026-07-10

### Fixed
- Hid Access Management Google 2FA columns, filters, sorting, and row actions when module Google login is not active.

## [1.29.10] - 2026-07-10

### Changed
- Removed the detailed functional coverage and operational control tables from the module About page.

## [1.29.9] - 2026-07-10

### Changed
- Suppressed password-expiration detail when the Access Management password-validity pill is locked, so locked users show only the higher-priority locked state.

## [1.29.8] - 2026-07-10

### Changed
- Restored Access Management list values to visual pills with exactly one selected pill per status, TOTP, Google 2FA, and password-validity column.
- Prioritized login-lock visibility in the password-validity column while keeping TOTP state focused on enrollment policy.

## [1.29.7] - 2026-07-10

### Changed
- Rendered Access Management list status, TOTP, Google 2FA, and password-validity values as plain text instead of pills.
- Moved the visible locked state from the TOTP column to the password-validity column.

## [1.29.6] - 2026-07-10

### Changed
- Renamed the Access Management password-expiration column header to a shorter password-validity label across maintained translations and restored the header to the standard Dolibarr list-title font sizing.

## [1.29.5] - 2026-07-10

### Changed
- Allowed native manual password changes for 2FA-exempt users once password expiration is enabled, while keeping password-history reuse protection active.

## [1.29.4] - 2026-07-10

### Fixed
- Loaded Dolibarr's date library in the authentication manager so password-expiration checks can safely calculate expiry dates during login and CLI validation paths.

## [1.29.3] - 2026-07-10

### Changed
- Removed the large Krea2FactorAuth bottom panel from the user card now that Access Management state is summarized in the native credentials table.
- Simplified the user-card credentials table summary to plain text and removed the duplicated Access Management link row.
- Redirected legacy current-user setup links to the standalone 2FA enrollment page.

## [1.29.2] - 2026-07-10

### Added
- Added Access Management summary rows to the native Dolibarr user-card credentials table, including TOTP policy, Google 2FA email state, and password expiration date.

## [1.29.1] - 2026-07-10

### Changed
- Made the Access Management password-expiration column sortable by active expiration date while keeping users without password expiration at the end.
- Reduced the password-expiration column header sizing for compact Access Management layouts.

## [1.29.0] - 2026-07-10

### Added
- Added per-user password expiration controls to Access Management, with a forced password-change page for expired local Dolibarr passwords.
- Added global password age and retained password-history settings.
- Added a dedicated password-history table and password-change trigger to retain recent hashes for managed users.

### Security
- Prevented managed users from reusing retained password hashes when changing an expired password or when an administrator manually edits their local Dolibarr password.

## [1.28.10] - 2026-07-10

### Changed
- Limited the Access Management page reached from the Users left menu to the Access Management admin tab only.

## [1.28.9] - 2026-07-10

### Changed
- Renamed the managed-users admin tab, table title, and Users submenu entry to Access Management, with matching Portuguese, Spanish, French, and German translations.

## [1.28.8] - 2026-07-10

### Changed
- Moved the managed-users left-menu entry under Home > Users and Groups > Users instead of attaching it directly to Users and Groups.

## [1.28.7] - 2026-07-10

### Fixed
- Treated active per-user Google 2FA setup as satisfying mandatory 2FA policy so users with configured Google login emails are not forced into TOTP enrollment.

## [1.28.6] - 2026-07-10

### Fixed
- Persisted managed-users filters in the administrator session so row action buttons keep the active filters until the remove-filter control is clicked.
- Increased CSS specificity and cache-busted the enrollment stylesheet so the standalone verification-code boxes override Dolibarr `input.flat` sizing reliably.

## [1.28.5] - 2026-07-10

### Fixed
- Widened the standalone enrollment verification-code inputs while keeping compact sizing for login and very small screens.

## [1.28.4] - 2026-07-10

### Fixed
- Preserved managed-users list filters, sorting, page, and limit after row action buttons are submitted; filters are now cleared only through the remove-filter control.

## [1.28.3] - 2026-07-10

### Fixed
- Clarified TOTP enrollment instructions so users scan the QR code from an authenticator app instead of opening the `otpauth://` provisioning URI in an Android camera or browser QR reader.

## [1.28.2] - 2026-07-10

### Fixed
- Completed Portuguese (Portugal), Spanish, French, and German translations for Google OAuth public pages, managed-users controls, exception-password messages, module setup labels, and confirmation dialogs.
- Localized Google OAuth callback failure messages through Dolibarr translation keys instead of hardcoded English login messages.
- Aligned package metadata so the generated DoliStore installer uses the full semantic version filename `module_krea2factorauth-1.28.2.zip`.

## [1.28.1] - 2026-07-10

### Fixed
- Excluded internal maintainer files and macOS metadata from generated installer ZIP packages.

## [1.28.0] - 2026-07-10

### Added
- Added Dolibarr list sorting support for the managed-users TOTP 2FA and Google 2FA columns.

## [1.27.3] - 2026-07-10

### Fixed
- Made Google login email extrafield provisioning idempotent so existing installations no longer emit duplicate-column, duplicate-metadata, or missing-index SQL errors.

## [1.27.2] - 2026-07-10

### Fixed
- Replaced the managed-users menu permission expression with a Dolibarr-safe administrator check so Users and Groups pages load without menu evaluator warnings.

## [1.27.1] - 2026-07-10

### Changed
- Moved the managed-users administration tab immediately before the About tab.

## [1.27.0] - 2026-07-10

### Added
- Moved managed-user policy management into its own module administration tab.
- Added a Users-menu managed-users entry visible only to Dolibarr super administrators.

### Changed
- Renamed the setup tab and managed-users policy column to TOTP 2FA.

### Security
- Dropped the obsolete managed-users action alias that had no dedicated handler in the moved controller.

## [1.26.4] - 2026-07-10

### Changed
- Matched the exempt-user generated password reset control to Dolibarr small inline button styling.

## [1.26.3] - 2026-07-10

### Changed
- Removed exempt-user recovery controls and administrator enrollment hints from the user-card 2FA panel.

## [1.26.2] - 2026-07-10

### Fixed
- Kept exempt-user generated password resets on the Dolibarr user card edit form after completion.

## [1.26.1] - 2026-07-10

### Changed
- Opened managed-users login links to Dolibarr user cards in a new browser tab.

## [1.26.0] - 2026-07-10

### Changed
- Replaced manual password editing for 2FA-exempt users on the Dolibarr user card with a generated password reset control.
- Switched exception password rotation to Dolibarr's configured password generator rules and blocked empty-generator configurations.
- Rendered managed-users inactive action placeholders as empty disabled buttons matching the normal action footprint.

## [1.25.5] - 2026-07-10

### Changed
- Standardized managed-users status, policy, and Google 2FA pills to the same fixed dimensions.
- Aligned the generated-password copy button to the right side of the Dolibarr warning message.

## [1.25.4] - 2026-07-10

### Changed
- Replaced the visible generated-password input in Dolibarr warning messages with a discrete copy-to-clipboard button.

## [1.25.3] - 2026-07-10

### Fixed
- Moved generated exception passwords into Dolibarr warning messages with a selectable password field and removed the custom dismiss-password form.
- Preserved managed-users scroll position when row action buttons submit through confirmation or Google email modals.
- Rendered disabled black placeholders for unavailable managed-users action slots and suppressed the Optional policy pill when a user is exempt.

## [1.25.2] - 2026-07-10

### Fixed
- Merged the managed-users Require 2FA actions into a single fixed action column instead of rendering separate shield columns for optional and exempt users.

## [1.25.1] - 2026-07-10

### Fixed
- Added a managed-users header link back to the setup settings section so list scroll restoration does not make the configuration form appear missing.

## [1.25.0] - 2026-07-10

### Added
- Added a fixed-position managed-users Google action button to save or clear module-owned Google login email associations.
- Added a JavaScript modal for entering comma-separated Google login emails with server-side validation before saving.

### Fixed
- Prevented managed-users GET pagination and sorting from sending a non-tokenized action parameter that triggers Dolibarr CSRF protection.
- Stabilized managed-users action button positions by rendering fixed action slots in every row.

## [1.24.3] - 2026-07-10

### Fixed
- Prevented explicitly 2FA-exempt users from seeing the mandatory enrollment page or self-enrollment form.

## [1.24.2] - 2026-07-10

### Fixed
- Restored Dolibarr managed-users pagination arrows and page navigation by using the expected limit-plus-one list fetch and a dedicated navigation form.

## [1.24.1] - 2026-07-10

### Fixed
- Changed generated exception passwords from transient event text to a persistent session-scoped notice so administrators have time to copy the password securely.

## [1.24.0] - 2026-07-10

### Added
- Added a managed users action icon for already-exempt users to generate, save, and show a new 96-character Dolibarr exception password without changing the 2FA policy state.

## [1.23.0] - 2026-07-10

### Added
- Added mandatory 96-character Dolibarr password generation when a super administrator creates a 2FA exception.
- Added confirmation prompts before applying a 2FA exception because the action now rotates the user's Dolibarr password and invalidates active sessions.

### Changed
- Updated the about page exception summary to document generated-password rotation for 2FA-exempt accounts.

## [1.22.4] - 2026-07-10

### Changed
- Updated the module about page with feature coverage and operational control summaries for the current 2FA, Google login, managed-user, exception, and TOTP protection behavior.

## [1.22.3] - 2026-07-10

### Fixed
- Replaced the managed users filter-row anchor jump with Dolibarr page_y scroll restoration so search and reset keep the clicked scroll position.

## [1.22.2] - 2026-07-10

### Changed
- Kept the setup page positioned on the managed users filter row after submitting list search or reset filters.
- Moved the managed users search and reset filter buttons to the left side of the list row to match Dolibarr list conventions.

## [1.22.1] - 2026-07-10

### Changed
- Reworked the managed users table to use Dolibarr-native list filters, pagination state, and sortable list headers.

## [1.22.0] - 2026-07-10

### Added
- Added searchable visible-user filters to the managed users table.
- Added Google 2FA status to the managed users table using the module Google login mapping policy.
- Allowed explicit super-administrator 2FA exceptions to bypass local TOTP enforcement for already enrolled users.

### Changed
- Changed the managed users table to list all users visible in the current entity scope, including users without 2FA enrollment.

## [1.21.7] - 2026-07-10

### Changed
- Moved mandatory 2FA exception management into row icons on the managed users table instead of using a separate user assignment form.
- Expanded the managed users table during global mandatory enrollment mode so visible unenrolled users can be exempted directly from their table row.

## [1.21.6] - 2026-07-10

### Added
- Added explicit super-administrator managed user exceptions for accounts that must remain unenrolled while global mandatory 2FA enrollment is enabled.

### Fixed
- Prevented mandatory enrollment redirects and optional enrollment prompts from applying to explicitly exempt unenrolled users.
- Aligned the package build configuration version with the module descriptor version.

## [1.21.5] - 2026-07-09

### Fixed
- Preserved the selected login entity through the module Google OAuth callback so a Google login started from entity 3 opens the Dolibarr session in entity 3.

### Changed
- Routed all module-initiated Google logins through the module callback so shared entity-1 credentials and entity restoration are applied consistently in both standard-email and user-card-email modes.

## [1.21.4] - 2026-07-09

### Fixed
- Fixed Google OAuth credential lookup so every login entity uses the shared provider client ID and secret stored in entity 1.

### Changed
- Updated Google OAuth setup guidance to describe the shared entity-1 credential model.

## [1.21.3] - 2026-07-09

### Fixed
- Loaded the selected login entity before bootstrapping the module Google login bridge so OAuth credentials and module activation are checked against the entity chosen on the login form.

## [1.21.2] - 2026-07-09

### Fixed
- Forced the module Google login bridge when user-card email matching is enabled, even if Dolibarr's native Google OAuth mode is present in the authentication chain.
- Removed Dolibarr's native Google login block from the module login template when the module-owned Google button is rendered, with a JavaScript fallback to prevent the core callback URI from being sent to Google by mistake.

## [1.21.1] - 2026-07-09

### Fixed
- Restored the Google login enable and allowed-domain settings block outside entity 1 while hiding advanced OAuth setup sections there.
- Prevented secondary-entity Google login setup saves from modifying OAuth credential constants.

## [1.21.0] - 2026-07-09

### Added
- Added an independent-scope current-entity activation toggle to the setup page.

### Changed
- Restricted the all-entity activation scope switch and full entity activation status table to entity 1.
- Hid the activation scope block from secondary entities while all-entity activation is active.

## [1.20.3] - 2026-07-09

### Changed
- Restricted shared Krea2FactorAuth security setup and shared Google login policy controls to entity 1 in multicompany deployments.
- Kept Google OAuth client credentials editable per entity to match Dolibarr core OAuth credential resolution.
- Kept secondary entities limited to current-entity login branding controls on the main setup page.

## [1.20.2] - 2026-07-09

### Changed
- Changed the `krea2factorauth_email` user-card Google icon from colored to monochrome while preserving the field layout.

## [1.20.1] - 2026-07-09

### Fixed
- Replaced the default file icon on the `krea2factorauth_email` user extrafield with a scoped Google G icon on user cards.
- Added cache busting for authenticated module CSS and JavaScript assets so user-card UI changes are loaded immediately after deployment.

## [1.20.0] - 2026-07-09

### Added
- Added support for multiple comma-separated addresses in the `krea2factorauth_email` Google login user extrafield.

### Changed
- Changed the Google login email user extrafield declaration to `varchar(255)` so Dolibarr does not enforce single-email input validation.

## [1.19.5] - 2026-07-09

### Fixed
- Enforced the Google login email option and extrafield declaration as entity-0 global metadata so company-specific constants or extrafield rows cannot hide `krea2factorauth_email`.

## [1.19.4] - 2026-07-09

### Fixed
- Fixed the Google login email user extrafield visibility condition so `krea2factorauth_email` appears on the user card when the option is enabled.

## [1.19.3] - 2026-07-09

### Changed
- Removed the visible user extrafield technical-name hint from the Google login email option row.

## [1.19.2] - 2026-07-09

### Fixed
- Translated the Portuguese Google login email option labels, help text, callback mode notes, and denial message.

## [1.19.1] - 2026-07-09

### Security
- Rejected inactive Dolibarr users inside Krea2FactorAuth before returning delegated authentication success, including Google OAuth return flows.
- Filtered module-owned Google login email matching to active Dolibarr users only.

## [1.19.0] - 2026-07-09

### Added
- Added an optional Google login policy that requires the Google identity email to match the `krea2factorauth_email` user extrafield instead of Dolibarr's standard user email fields.
- Added a module-owned Google OAuth callback for user-extrafield email matching while keeping Dolibarr's native callback for the default Google login mode.
- Added activation/update migration logic that creates the `krea2factorauth_email` user extrafield and hides it from the user card unless the module and user-card Google login email option are enabled.

### Changed
- Updated Google login setup guidance to show the active redirect URI and the separate redirect URI required when user-card email matching is enabled.

## [1.18.9] - 2026-07-09

### Changed
- Changed Google OAuth login handling so a successful Google-authenticated login skips the local authenticator-code challenge while preserving module domain and login-lock policy checks.
- Updated Google login setup and public policy wording to describe the Google-authenticated TOTP bypass accurately.

## [1.18.8] - 2026-07-09

### Fixed
- Removed transient Google OAuth return parameters from the optional enrollment skip form action and redirect target so skipping the reminder cannot replay the Google login callback URL.

## [1.18.7] - 2026-07-09

### Fixed
- Fixed the optional enrollment skip button so it always submits a Dolibarr CSRF token before the module records the session-level skip state.

## [1.18.6] - 2026-07-09

### Fixed
- Added a JavaScript-backed Google login submit fallback that explicitly posts the token-protected login form to the module bridge when the Google button is clicked.

## [1.18.5] - 2026-07-09

### Changed
- Moved the module Google login button below the primary TOTP submit button with a localized separator and Google-style pill button presentation.

## [1.18.4] - 2026-07-09

### Fixed
- Added a module-owned token-protected Google login bridge so the login button starts Dolibarr's native Google OAuth redirect without falling back to password authentication.
- Included module public entrypoints in package builds so Google login and Google Auth Platform public links are deployed with the module.

## [1.18.3] - 2026-07-09

### Fixed
- Restored Google login initiation to Dolibarr's token-protected login form flow so CSRF protection is satisfied before the OAuth redirect starts.

## [1.18.2] - 2026-07-09

### Fixed
- Changed the module Google login control to use a direct Dolibarr OAuth callback link so clicking it immediately starts the Google redirect flow.

## [1.18.1] - 2026-07-09

### Fixed
- Made the module Google login button submit the OAuth redirect flag directly so it cannot fall back to normal password-login behavior when clicked.

## [1.18.0] - 2026-07-09

### Added
- Added generic public Google OAuth application information, privacy policy, and terms of service pages inside the module.
- Added module-generated application homepage, privacy policy, and terms of service links to the Google setup tab for Google Auth Platform configuration.

### Changed
- Replaced remaining company-specific user-facing wording with generic module wording for reusable deployments.
- Renamed the packaged default logo asset and helper references to remove company-specific naming.

## [1.17.0] - 2026-07-09

### Added
- Added complete Dolibarr language catalogs for Portuguese (Portugal), Spanish, French, and German, including the Google OAuth setup guide and credential management labels.

## [1.16.0] - 2026-07-09

### Added
- Added an explanatory Google setup guide with direct Google Cloud Console and official documentation links to the Google login setup tab.
- Added copy-ready Google OAuth setup values, including the Dolibarr redirect URI, application type, scopes, and multicompany credential scope notes.

### Changed
- Moved the Google login settings section to the top of the Google setup page so the primary module controls are immediately visible.

## [1.15.0] - 2026-07-09

### Added
- Added direct Google OAuth credential setup to the Krea2FactorAuth Google login tab using Dolibarr-native `OAUTH_GOOGLE-Login_*` constants.
- Added current-entity credential status, provider key, scopes, and client secret rotation controls for Google login.

## [1.14.0] - 2026-07-09

### Added
- Added a Google login setup tab that appears only when the Dolibarr installation exposes the native Google OAuth login provider.
- Added module-controlled Google login enablement, OAuth credential status checks, callback URL guidance, and an optional allowed-domain policy.
- Added Google login flow integration that delegates primary identity validation to Dolibarr core while preserving Krea2FactorAuth TOTP enforcement after Google authentication.

## [1.13.0] - 2026-07-09

### Security
- Invalidated active Dolibarr sessions when super administrators reset or disable a user's two-factor recovery state.
- Enforced POST-only token validation inside module-owned two-factor and admin policy actions.
- Added persistent TOTP failure throttling and last-timeslice replay rejection during login verification.

## [1.12.26] - 2026-07-09

### Changed
- Marked the module package metadata as a release build and aligned the package version with the module descriptor.

## [1.12.25] - 2026-07-09

### Fixed
- Smoothed login challenge image changes by preloading the next entity image before swapping it into the fixed image slot.

## [1.12.24] - 2026-07-09

### Fixed
- Fixed the login challenge image slot dimensions so switching between entity images cannot resize the login table.

## [1.12.23] - 2026-07-09

### Fixed
- Precomputed login challenge image URLs for active entities so entities without a configured image immediately use the packaged shield fallback when selected.

## [1.12.22] - 2026-07-09

### Changed
- Used the packaged `img/shield.png` image as the login challenge fallback when the selected entity has no configured login image.

## [1.12.21] - 2026-07-09

### Changed
- Replaced the packaged login challenge image with the entity-specific `login-logo-entity-N.png` file served from the shared module document directory.
- Updated login entity changes to refresh the challenge image without reloading the authentication page.

## [1.12.20] - 2026-07-09

### Changed
- Stored login logo files in the default module document directory under `DOL_DATA_ROOT` for all entities while keeping `KREA2FACTORAUTH_LOGIN_LOGO_FILE` entity-scoped.
- Migrated legacy entity-specific login logo files into the default module document directory when resolving, replacing, or deleting a logo.

## [1.12.19] - 2026-07-09

### Fixed
- Made the configured login logo entity-scoped by storing and resolving `KREA2FACTORAUTH_LOGIN_LOGO_FILE` per selected entity instead of entity 0.
- Removed login logo constants from global configuration migration and module activation defaults so entity 0 does not own logo configuration.

## [1.12.18] - 2026-07-09

### Changed
- Balanced the spacing above and below the Bota Fogo challenge image before the username input.

## [1.12.17] - 2026-07-09

### Changed
- Reordered the Bota Fogo challenge image row to render above the login username input.

## [1.12.16] - 2026-07-09

### Changed
- Moved the Bota Fogo challenge image outside the Authenticator code container so it renders as a separate visual row above the TOTP panel.

## [1.12.15] - 2026-07-09

### Added
- Added the Bota Fogo challenge image above the login TOTP prompt without changing the configured login logo.

## [1.12.14] - 2026-07-09

### Fixed
- Removed the login logo block from the branded login template and stopped client-side login logo replacement and fallback injection.

## [1.12.13] - 2026-07-09

### Fixed
- Added a Dolibarr-native login template wrapper so the generated login logo source is the module-uploaded logo before the page renders.
- Registered the module template part in the login bootstrap constants so existing multicompany login contexts can load the wrapper without manual module reactivation.

## [1.12.12] - 2026-07-09

### Fixed
- Restored the original light grey login background as the authoritative fallback when an entity has no configured background color.
- Prevented stale global login background constants from replacing the module fallback after the all-entity color selector was removed.

## [1.12.11] - 2026-07-09

### Fixed
- Added header-level login background protection so Dolibarr and multicompany background images do not paint before the module login UI loads.
- Added guarded login branding repair for late multicompany AJAX logo and background updates without restoring client-side reload behavior.

## [1.12.10] - 2026-07-09

### Fixed
- Prevented Dolibarr and browser autofill styles from painting blue backgrounds inside the login, password, and security code fields.
- Reasserted the module-uploaded login logo after multicompany logo refreshes so Dolibarr's default logo cannot replace it.
- Restored stable module login background handling after entity changes and prevented multicompany background images from overriding it.
- Preserved explicit current-entity background color changes even when the setup checkbox was still on its default state.

## [1.12.9] - 2026-07-09

### Fixed
- Restored login JavaScript for six-box TOTP auto-advance and focus handling without reintroducing client-side navigation.
- Allowed multicompany entity changes to update the login logo while synchronizing module background colors from the selected entity.
- Reapplied stronger login focus styles to suppress Dolibarr's default underlined input focus state.

## [1.12.8] - 2026-07-09

### Fixed
- Restored the branded module login UI with server-rendered CSS, logo, background color, and six-box TOTP input while keeping login JavaScript disabled.

## [1.12.7] - 2026-07-09

### Fixed
- Switched the login challenge to a native Dolibarr input with no module CSS, logo preload, inline script, or module JavaScript in the login rendering path.

## [1.12.6] - 2026-07-09

### Fixed
- Removed inline login bootstrap JavaScript from the login page.
- Removed login logo visibility rules that depended on module JavaScript to reveal the logo.

## [1.12.5] - 2026-07-09

### Fixed
- Removed the external module JavaScript include from the login page so login rendering cannot be blocked by module-side browser behavior.
- Kept the login logo visible without relying on module JavaScript branding classes.

## [1.12.4] - 2026-07-09

### Fixed
- Removed all client-side login entity navigation from the module JavaScript to eliminate login page reload loops.

## [1.12.3] - 2026-07-09

### Fixed
- Prevented login reload loops by restricting entity-change reloads to real user interactions with the entity selector.
- Added the server-rendered login entity to the login branding bootstrap so restored browser state cannot trigger repeated reloads.

## [1.12.2] - 2026-07-09

### Changed
- Simplified login background setup to edit only the current entity override while preserving stored overrides for other entities.

### Fixed
- Forced login entity changes to perform a full page reload from native and Select2 selection events so the selected entity logo and background are rendered from the correct server context.
- Added cache busting to login CSS and JavaScript includes so updated entity-change handlers and branding styles are not hidden by browser cache.

## [1.12.1] - 2026-07-09

### Fixed
- Forced the configured module login logo to remain authoritative when Dolibarr core scripts replace the login image during entity refreshes.
- Added preload and mutation-observer recovery for the configured module login logo, with shield fallback if the module logo endpoint fails.

## [1.12.0] - 2026-07-09

### Added
- Added a configurable login page background color with #eef3f7 preserved as the default.
- Added per-entity login background color overrides applied when the selected login entity reloads.

### Changed
- Moved login background styling to a module CSS variable so Dolibarr entity transitions keep a stable configured background.

## [1.11.2] - 2026-07-09

### Fixed
- Replaced the deleted-login-logo fallback with a shield preview in setup.
- Displayed a shield fallback on the login form when no configured logo exists and Dolibarr only provides its default logo.
- Allowed existing entity-specific login logos to remain visible when no module login logo override is configured.

## [1.11.1] - 2026-07-09

### Fixed
- Reloaded the login page when the selected entity changes so Dolibarr rebuilds the login context and logo state for the selected entity.

## [1.11.0] - 2026-07-09

### Changed
- Refactored the configurable login image from a company/entity selector icon into a top login logo.
- Updated login logo validation to allow real logo form factors instead of square icon-only uploads.
- Enlarged the setup preview into a logo-sized preview area and defaulted it to the packaged Degema logo when no custom logo is uploaded.

### Fixed
- Restored Dolibarr's standard globe icon beside the multicompany entity selector.
- Prevented Dolibarr entity-switch logo updates from becoming visible on the branded login form.
- Kept existing uploaded legacy login-company-icon files readable through the new login logo loader.

## [1.10.4] - 2026-07-09

### Changed
- Enlarged the setup-page login company icon preview so uploaded icons and the shield fallback are readable.

### Fixed
- Revalidated the global login bootstrap path so the module login renders when entity 1 is inactive but another entity has the module active.
- Forced the multicompany login selector fallback to render as the shield icon and hid the core Dolibarr logo until the Degema login branding is applied.

## [1.10.3] - 2026-07-09

### Changed
- Kept the login authenticator-code challenge visible for every selected entity while preserving inactive-entity server-side bypass.

### Fixed
- Split global login bootstrap from real entity activation scope so login hooks render in inactive entities without making them enforce 2FA.
- Prevented multicompany login entity transitions from replacing the module login background and Degema logo.
- Preserved the shield fallback for the login entity selector when no company icon is configured.

## [1.10.2] - 2026-07-09

### Fixed
- Showed the entity activation scope block from every active module entity instead of requiring entity 1.
- Kept the login company icon delete option visible whenever an icon is configured, even if the preview URL cannot be resolved.

## [1.10.1] - 2026-07-09

### Fixed
- Kept the Krea2FactorAuth login provider globally loadable when the module is active in any entity, while preserving per-entity TOTP enforcement.
- Hid and disabled the login authenticator-code challenge when the selected login entity does not have the module active.
- Kept authenticated-page hooks inactive in entities where the module is not active, even when the global login bootstrap is present.

## [1.10.0] - 2026-07-09

### Added
- Added an admin-managed login company icon upload with validation for type, size, dimensions, and near-square form factor.
- Added a controlled public endpoint for serving the uploaded login company icon from Dolibarr document storage.

### Changed
- Replaced the login company selector icon with the uploaded icon when configured, or a shield fallback when no icon is configured.
- Aligned entity activation scope controls vertically and normalized the setup Apply and Save button styling.

## [1.9.0] - 2026-07-08

### Added
- Added a three-mode enrollment prompt policy with optional, mandatory, and off states.
- Added a dedicated current-user 2FA enrollment page for mandatory setup routing.

### Changed
- Routed mandatory unenrolled users to the dedicated enrollment page instead of Dolibarr's user card.
- Removed info icons from the about page module information footer.
- Updated the enrollment prompt setup control to persist a string mode while preserving the legacy optional-modal flag for compatibility.

## [1.8.0] - 2026-07-08

### Added
- Added a local Kreativität Works logo asset for the module about page.

### Changed
- Replaced setup helper text blocks with Dolibarr clickable tooltip icons.
- Removed the entity column from the managed 2FA users table because enrollment is global per user.
- Reworked the about page footer to use the compact KreaTax-style module information block.

### Fixed
- Restored the optional enrollment warning modal path by keeping the setup switch available and re-enabling the stored modal setting.

## [1.7.0] - 2026-07-08

### Added
- Added Dolibarr-native pagination to the managed 2FA policy users table.
- Added an AGENTS.md maintainer policy file for production module work.

### Changed
- Reworked the entity activation scope row so the current mode is left-aligned and the scope switcher is right-aligned on the same row.
- Rebuilt the about page with the same branded information layout used by KreaProducts and updated the module description content.
- Sorted setup entity activation rows by entity number.

### Fixed
- Fixed login blank page by restoring the missing entity normalization method used by the authentication provider.

## [1.6.2] - 2026-07-08

### Fixed
- Fixed first-login blank-page risk by redirecting implicit current-user card requests before output and by enforcing mandatory 2FA setup from the pre-output header hook.
- Preserved the optional enrollment modal on implicit current-user card landings so unenrolled users still receive the skip-capable warning after login.
- Prevented user-card 2FA panel render failures from blanking the whole Dolibarr page by logging the module error and showing a standard Dolibarr error message.

## [1.6.1] - 2026-07-08

### Added
- Added a setup control to switch module activation scope between all entities and independent per-entity activation.

### Fixed
- Fixed blank user-card landing after login by routing the Tools menu through the current-user 2FA panel redirect.
- Fixed managed-user lock action icons so they reflect the current locked or unlocked state.
- Removed login-time migration from the manager constructor and added legacy per-entity read fallback for safer authentication.

## [1.6.0] - 2026-07-08

### Added
- Added global optional enrollment modal and user enrollment reset settings in the setup page.
- Added reset confirmation modals before regenerating or resetting 2FA enrollment.
- Added entity activation status visibility in entity 1 when multiple active entities exist.

### Changed
- Moved Krea2FactorAuth enrollment and policy user parameters to global user scope so one enrolled authenticator applies across entities.
- Migrated older entity-scoped Krea2FactorAuth user parameters and setup constants to global scope while preserving existing values.

## [1.5.3] - 2026-07-08

### Changed
- Kept users visible in the managed 2FA policy list after clearing mandatory enrollment by preserving a module policy-list marker.
- Replaced managed-user state labels with styled pills and changed row actions to icon-only controls.

### Removed
- Removed the redundant "2FA user policy" heading above the managed users table.

## [1.5.2] - 2026-07-08

### Removed
- Removed the admin setup add-user selector form and its direct `add_policy_user` action from the 2FA user policy section.

## [1.5.1] - 2026-07-08

### Changed
- Replaced the optional unenrolled-user footer warning with a centered enrollment modal that links to setup and supports skipping for the current session.

## [1.5.0] - 2026-07-08

### Added
- Added embedded 2FA enrollment and status panel at the bottom of Dolibarr's native user card.
- Added user-card hook handling for TOTP secret regeneration, activation, and super-administrator recovery disable actions.

### Changed
- Moved optional warning links, mandatory setup redirects, admin policy links, and the Tools menu entry to the native user card 2FA panel.
- Converted the legacy custom 2FA setup page into a compatibility redirect to the user card panel.
- Removed the separate 2FA user tab registration so the workflow stays on Dolibarr's standard user card.

## [1.4.0] - 2026-07-08

### Added
- Added super-administrator managed 2FA policy states for mandatory enrollment, login lock, unlock, enrollment reset, and mandatory-state clearing.
- Added post-login optional enrollment warning for users who have not enabled 2FA.
- Added server-side mandatory setup redirect so policy-managed users marked as not validated can only continue to their own 2FA setup page.
- Added active-session lock enforcement for users locked by 2FA policy.

### Changed
- Replaced the old admin recovery table with a managed user policy table and per-user reset, lock, unlock, and clear-mandatory actions.
- Marked mandatory setup users as not validated in module policy while keeping Dolibarr core user status enabled so enrollment can complete.

### Fixed
- Overrode Dolibarr focused input and Select2 underline rules on the custom login and TOTP inputs.

## [1.3.3] - 2026-07-08

### Fixed
- Corrected the Dolibarr login wrapper offset so login, password, entity, TOTP, and submit controls share the same card centerline.

## [1.3.2] - 2026-07-08

### Changed
- Restored the multicompany entity selector to the simple inline globe icon style and removed the heavy label/tile treatment.

## [1.3.1] - 2026-07-08

### Changed
- Centered the six-digit TOTP inputs on the login and enrollment screens.
- Removed the divider below the Degema logo on the login screen.
- Restyled the multicompany entity selector into a branded login control.

## [1.3.0] - 2026-07-08

### Added
- Added packaged Degema branding for the 2FA login and enrollment screens.

### Changed
- Rebuilt the login challenge styling, replacing the Dolibarr login logo with the Degema logo from the module layer.
- Reworked the enrollment screen, status header, recovery panel, verification inputs, and activation button into a Degema-branded interface.

## [1.2.0] - 2026-07-08

### Added
- Added a shared six-digit TOTP code input with paste handling and automatic focus movement for login and enrollment verification.

### Changed
- Redesigned the user 2FA setup view with a status summary, device-style enrollment panel, QR tile, and manual secret block.
- Kept server-side TOTP verification compatible with both the legacy `totp_code` field and the new digit-array input.

## [1.1.5] - 2026-07-08

### Changed
- Replaced the enrolled user's self-disable form with recovery-only guidance on the 2FA setup page.

### Security
- Restricted 2FA deactivation to Dolibarr super administrators after enrollment.

## [1.1.4] - 2026-07-08

### Changed
- Moved the module navigation entry from a dedicated top menu to Dolibarr's Tools/Utilities menu.
- Added activation cleanup to replace stale module menu rows with the current Tools/Utilities entry.

### Security
- Disabled the obsolete `totp2fa_secret` user extrafield metadata so legacy TOTP secrets are no longer rendered on the Dolibarr user card.

## [1.1.3] - 2026-07-08

### Changed
- Added an enforcement warning on the user 2FA setup page when Dolibarr is not configured to run `krea2factorauth` first in the authentication chain.

## [1.1.2] - 2026-07-08

### Changed
- Clarified the user setup page when an administrator views another user's 2FA status by showing current session user, target user, and the required self-enrollment path.
- Added a shortcut from administrator-owned views back to the current user's own 2FA setup page.

## [1.1.1] - 2026-07-08

### Changed
- Made the 2FA status switch on the user setup page link to the relevant enrollment or disable form when the current user can act.
- Clarified administrator views so disabled 2FA does not appear directly enrollable for another user's account.

## [1.1.0] - 2026-07-08

### Added
- Added the `krea2factorauth` login provider to enforce TOTP after Dolibarr primary authentication.
- Added a login-page hook that injects the authenticator-code input without overriding core templates.
- Added user self-enrollment with local SVG QR generation and verified secret storage in `user_param`.
- Added administrator recovery to disable 2FA for locked-out users.

### Changed
- Replaced generated setup placeholders with production 2FA configuration.
- Pinned Composer dependencies to PHP 7.1-compatible TOTP and local QR-code libraries.
- Updated packaging configuration to include enrollment pages, Composer files, local vendor dependencies, and GPL license files.

## 1.0

Initial version

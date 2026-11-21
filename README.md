# Krea2FactorAuth for Dolibarr ERP & CRM

Krea2FactorAuth adds TOTP-based two-factor authentication to Dolibarr login.

The module delegates the primary password check to Dolibarr authentication providers, then verifies the user's TOTP code when a 2FA secret is enrolled. Successful Google OAuth logins are the explicit exception and skip the local authenticator-code challenge.

## Requirements

- Dolibarr 19 or later
- PHP 7.1 or later
- Composer dependencies installed in this module directory

## Install

From `htdocs/custom/krea2factorauth`:

```bash
composer install --no-dev
```

Enable the module from Dolibarr module setup, then configure Dolibarr authentication mode in `htdocs/conf/conf.php` with this provider first:

```php
$dolibarr_main_authentication = 'krea2factorauth,dolibarr';
```

Module activation and user enrollment are not enough by themselves. If this line is missing, commented, or starts with `dolibarr`, Dolibarr will authenticate the password before the 2FA provider can enforce TOTP.

If LDAP is the primary password provider, set the module primary authentication modes to `ldap` from the module setup page and keep `krea2factorauth` first in Dolibarr authentication mode.

## Google Login

When the installed Dolibarr core exposes the native `googleoauth` login provider, the module adds a Google login setup tab.

Configure Google OAuth credentials directly from the Krea2FactorAuth Google login tab in entity 1. The page stores them in Dolibarr's native `OAUTH_GOOGLE-Login_*` constants for entity 1, using provider key `Login`, and all login entities reuse that shared client ID and secret. Module-initiated Google login always uses the module callback so the shared credentials, selected login entity, domain policy, and optional user-card login email policy are enforced before the local authenticator-code challenge is skipped.

The Google login tab provides public module-generated URLs for the Google Auth Platform application homepage, privacy policy, and terms of service fields. These pages are generic to the organization operating the Dolibarr instance and do not hardcode a customer or company name.

Keep `krea2factorauth` first in `htdocs/conf/conf.php`. Do not put `googleoauth` before this module, otherwise Google authentication can run outside the module's Google-domain and lock-policy checks.

Optional allowed domains may be configured in the Google login tab. The allow-list is enforced against the Dolibarr user OAuth email (`email_oauth2`) when present, otherwise the Dolibarr user email.

## User Enrollment

Users enroll from the standalone module enrollment page. Their native Dolibarr user card shows a security summary in the credentials table; administrators manage policies from Access Management. The secret is stored in Dolibarr `user_param` with `entity = 0`, intentionally global per user, and encrypted with Dolibarr's reversible encryption helper. Policy access still requires visibility of the target user in the selected entity.

If an administrator opens another user's user card, the module shows the target user's 2FA status but does not display the enrollment QR code. The target user must sign in and complete QR verification from their own session.

After enrollment, users cannot disable their own 2FA. Recovery deactivation is restricted to Dolibarr super administrators, which Dolibarr represents as admin users attached to entity 0.

TOTP code entry uses a module-branded six-digit verification surface on both login and enrollment. The server still accepts the legacy `totp_code` field for compatibility.

## Admin Recovery

Super administrators can disable 2FA for a locked-out user from the target user's native Dolibarr user card. The module setup page manages mandatory enrollment and login locks. Normal entity administrators cannot disable enrolled 2FA accounts and cannot enroll a new authenticator device for another user; the target user must complete enrollment from their own authenticated session.

## License

GPL-3.0-or-later. See `COPYING` and `LICENSE`.

## Security and Validation

Read [SECURITY.md](SECURITY.md) for secure configuration and private vulnerability reporting. Google OAuth proves the Google account identity; administrators must enforce any required Google multi-factor policy in their Google account or Workspace settings.

Run the synthetic authentication regression suite without a database or external service:

```bash
composer install --no-dev --no-plugins --no-scripts
php tests/authentication_provider_test.php
```

GitHub Actions also checks PHP syntax, scans the complete Git history for secrets, and audits the locked Composer dependencies. See [ChangeLog.md](ChangeLog.md) for release changes. Repository publication does not deploy or update an installed Dolibarr instance.

# Krea2FactorAuth for Dolibarr

Krea2FactorAuth adds authenticator-app verification and centralized access policies to Dolibarr. Users keep their existing password provider and complete a six-digit, time-based one-time password (TOTP) challenge after enrolling an authenticator.

## Features

- TOTP enrollment with a QR code and verification from the user's own session.
- A second-factor challenge during password login.
- Administrator controls for mandatory enrollment and login locks.
- Two-factor status on native Dolibarr user cards.
- Optional Google OAuth login, domain restrictions and shared OAuth configuration.
- Recovery by a Dolibarr super administrator when a user loses access to an authenticator.

Successful Google OAuth login skips the module's local TOTP challenge. Any required Google multi-factor policy must be enforced in Google or Google Workspace.

## Requirements

- Dolibarr 19 or later and PHP 7.1 or later.
- Composer to install the module's locked dependencies.
- A TOTP-compatible authenticator application for password-based two-factor login.
- Dolibarr's native `googleoauth` provider for optional Google login.

## Installation

1. Place the module at `htdocs/custom/krea2factorauth`.
2. Install dependencies from that directory:

   ```bash
   composer install --no-dev
   ```

3. Enable **Krea2FactorAuth** in Dolibarr's module administration.
4. Configure Dolibarr's authentication order in `htdocs/conf/conf.php` with this provider first:

   ```php
   $dolibarr_main_authentication = 'krea2factorauth,dolibarr';
   ```

5. Review the module's access policies and complete enrollment with a test account before requiring it for your users.

The provider order is essential. Enabling the module and enrolling a user do not enforce TOTP if `krea2factorauth` is missing or another password provider authenticates first.

If LDAP checks your passwords, select `ldap` as the primary authentication mode in the module setup and keep `krea2factorauth` first in Dolibarr's authentication list.

## User enrollment and recovery

Users open the module's enrollment page, scan the QR code in their authenticator and verify a code to finish enrollment. An administrator viewing another user's card sees that user's status, without receiving their enrollment QR code.

Enrolled users cannot disable their own two-factor authentication. A Dolibarr super administrator, represented by an administrator attached to entity 0, can deactivate it from the affected user's card so the user can enroll again. Ordinary company administrators cannot disable an enrolled account or enroll an authenticator on another user's behalf.

## Google login

When Dolibarr provides the native Google OAuth provider, the module adds a **Google login** setup tab.

1. Configure the Google OAuth client from that tab in company/entity 1. These credentials are shared by the login entities.
2. Use the callback and application-policy URLs provided by the setup page in the Google application configuration.
3. Configure allowed domains and any login-email policy you require.
4. Keep `krea2factorauth` first in `htdocs/conf/conf.php`, including before `googleoauth`.

Module-initiated Google login uses its own callback to apply the selected company, domain and login-email policies. The domain allow-list checks the user's OAuth email when present, otherwise the Dolibarr user email.

## Development and security

Run the synthetic authentication regression suite without a database or external service:

```bash
composer install --no-dev --no-plugins --no-scripts
php tests/authentication_provider_test.php
```

Read [SECURITY.md](SECURITY.md) for secure configuration and vulnerability reporting, and [ChangeLog.md](ChangeLog.md) for release changes.

## License

GPL-3.0-or-later. See [COPYING](COPYING) and [LICENSE](LICENSE).

Maintained by [Kreativität Works](https://kreativitat.com).

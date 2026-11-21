# Security policy

Kreativität Works maintains security fixes for the latest Krea2FactorAuth release. Update older installations before reporting a problem that may already be fixed.

## Report a vulnerability privately

Use [GitHub private vulnerability reporting](https://github.com/kreativitat/krea2factorauth/security/advisories/new). If that form is unavailable, contact [mail@kreativitat.com](mailto:mail@kreativitat.com) to arrange a private report.

Include the module, Dolibarr and PHP versions, relevant configuration with secrets removed, the impact, and minimal reproduction steps using synthetic data. Do not post credentials, OAuth tokens, authenticator secrets, recovery passwords, customer records, or exploitable security details in public issues or pull requests. Never send live credentials by email.

## Operate securely

- Keep `krea2factorauth` first in Dolibarr's authentication-provider configuration. A password or Google provider placed before it can bypass module policy. Use the module-managed Google login button and callback.
- Google OAuth proves control of the Google identity; it does not establish that Google required a second factor. Enforce Google account MFA through the identity provider when relying on that authentication path.
- Use HTTPS, a maintained Dolibarr/PHP deployment, protected session cookies, and restricted administrator access. Keep the latest module security fixes and locked Composer dependencies installed.
- Keep instance configuration, uploads, backups, logs, OAuth credentials and TOTP secrets outside this repository and out of public web paths. Restrict filesystem and database access, and preserve the Dolibarr encryption configuration when restoring backups.
- Review entity activation and access policies, enrollment, login locks, and super-administrator recovery access after configuration changes. Validate upgrades in an isolated environment before deploying them.

## Repository checks

GitHub Actions checks PHP syntax, synthetic authentication regressions, locked dependency advisories, and the full reachable Git history for supported secret patterns. Jobs use read-only repository permissions, pinned tooling, and no production credentials. Secret-scan output is redacted and is not uploaded as an artifact.

These checks supplement review; they do not prove that a deployment is free of vulnerabilities or sensitive data. If a credential reaches Git history, revoke or rotate it promptly. Removing the current file alone does not remove the credential from history or existing copies.

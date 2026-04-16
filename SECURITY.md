# Security Policy

## Supported Use

This repository is an admin frontend template for CodeIgniter 4 that proxies requests to a backend API.
Forks are responsible for reviewing their own deployment-specific risks, secrets, third-party integrations, and downstream code changes.

## Reporting a Vulnerability

- Do not open a public GitHub issue for undisclosed vulnerabilities.
- Report security issues privately to the maintainers through the security contact or private channel used by your team.
- Include reproduction steps, affected routes/files, impact, and whether the issue depends on local configuration.

## Threat Model Notes

- Authentication tokens are stored in the PHP session, not in browser storage.
- This template assumes the backend API is the source of truth for authorization and business rules.
- Public deployments should enable HTTPS, CSP, secure cookies, and strict secret handling in `.env`.
- Google login depends on a valid `GOOGLE_CLIENT_ID` and backend verification of Google-issued ID tokens.

## Fork Responsibilities

- Rotate any real credentials before publishing a fork.
- Review CSP/CDN choices against your own compliance requirements.
- Keep Composer and npm dependencies patched.
- Re-run the test, lint, and static-analysis checks after customizations.

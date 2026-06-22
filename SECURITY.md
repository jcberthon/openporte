# Security Policy

## Reporting Vulnerabilities

**Please report security vulnerabilities via GitHub's private security
advisory:**  
https://github.com/jcberthon/openporte/security/advisories/new

This is the only channel for security reports. Do not open public issues for
security vulnerabilities.

For vulnerabilities with a significant impact on WordPress.org users, we will
coordinate with the WordPress.org security team before public disclosure.

## Supported Versions

**We only support the latest released version.**

- If a vulnerability affects an older version but **not** the latest version,
  the fix is to **upgrade**.
- We do not backport security fixes to older versions.
- We do not maintain parallel version support.

## Scope

**In scope:** the OpenPorte plugin PHP code and JavaScript (admin UI).

**Out of scope:**

- The bundled ALTCHA widget (`public/altcha.min.js`) — report upstream at
  https://github.com/altcha-org/altcha
- WordPress core and any third-party plugins integrated with OpenPorte
- Theoretical weaknesses with no realistic exploit path — open a regular
  GitHub issue instead

## Disclosure Process

1. You report via the private advisory link above
2. We acknowledge receipt usually within 2 working days
3. We verify the vulnerability
4. We release a fix in the next patch version (timeline: best effort)
5. Public disclosure happens with the fix release via GitHub advisory

## Important Context

This is a **one-person maintained open source project** on which I work
during my personal free time, excluding annual or sick leave.

This project is **AI-assisted**: AI tools help draft code, tests, and
documentation, but all security decisions, the review of any AI-suggested
change, and final responsibility for what ships remain mine.

- Security fixes are handled on a **"best I can, when I can"** basis
- Complex processes or lengthy coordination are not feasible
- Clear, concise reports with reproduction steps are greatly appreciated
- Contributions (fixes, tests) alongside reports are very welcome

Thank you for helping keep OpenPorte secure.

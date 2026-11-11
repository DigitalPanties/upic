# Security policy

## Supported versions

The `main` branch is the development target. This is a reference library and demo, not an authenticated design repository.

## Reporting vulnerabilities

Please **do not post exploitable details in public issues**. Use GitHub's **Private vulnerability reporting** feature under the repository's Security settings, if the maintainer enables it. Otherwise contact the maintainer privately using a channel they publish on the repository profile.

## Operational considerations

- Treat all design codes and decoded JSON as untrusted inputs.
- CRC32 only detects accidental errors; it does **not** authenticate codes or prove ownership.
- Do not grant drawer items, accounts, entitlements or transfers based on a supplied code alone.
- Rate-limit public API and SVG rendering routes, and set resource limits appropriate to your server.
- Use a current supported PHP release. The optional contour generator is for maintainers, not a web endpoint.

# Publishing the open-source UPIC GitHub repository

The original Panty Maker SVG creator has authorized including the cut SVGs, pattern SVGs, and related reference/contour data **as MIT-licensed source code**. No separate art license is required for this project's original SVG files. Third-party contributions must also be MIT-compatible and authorized by their contributors.

## Publish

1. Create a repository at https://github.com/new (suggested name: `upic`). Select **Public** to publish the MIT-licensed code, or Private if you prefer to stage a review; there is no longer an SVG licensing prerequisite to going public.
2. Upload the extracted repository contents; do not upload only the ZIP file. Avoid checking GitHub's auto-generate README or license options, as both already exist.
3. Check **Actions** for green SVG-registry, PHP registry, syntax, and regression checks.
4. Optionally enable branch protection requiring the CI workflow and pull request approval, and enable private security reporting in repository settings.
5. Keep the GitHub project identity and maintainer contact information current. Add a `CODEOWNERS` file if you want automatic reviewers (requires your actual GitHub username/team).

## Git command-line alternative

```bash
git init
git branch -M main
git add .
git commit -m "Publish UPIC open-source code and SVG registry"
git remote add origin https://github.com/YOUR-USERNAME/upic.git
git push -u origin main
```

If using a local Git checkout rather than the ZIP, skip `git init` and commit the new files in your existing history. This package contains no secrets or live remote by default.

## Release checklist

```bash
python3 tools/svg_registry.py check
php tests/registry_contract.php
php tests/run.php
```

- Verify original codes still render without modification.
- Confirm `assets/svg/registry.json` matches the compiled `assets/artwork.json`.
- When a source SVG changes, run `python3 tools/svg_registry.py build` and review the compiled diff.
- Check that every newly committed source SVG includes its registry entry and appropriate rendered examples.
- Retain attribution and the MIT license for forks/distributions; Panty Lane marks are not automatically licensed.

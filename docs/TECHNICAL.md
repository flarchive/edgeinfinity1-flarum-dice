# Technical documentation

[简体中文](TECHNICAL.zh-CN.md) · [Introduction](../README.md) · [Lifecycle](LIFECYCLE.md)

## Architecture

| Entry | Responsibility |
| --- | --- |
| `extend.php` | Integration, assets, locales and service provider |
| `src/DiceSyntax.php` | Inclusive integer ranges and parser exclusions |
| `src/ConfigureFormatter.php` | Raw placeholder HTML with ledger-derived attributes |
| `src/RollPolicy.php` | Save-time rolls, FIFO reuse and edit provenance |
| `src/RenderDice.php` | Result binding and dormant-ledger recovery |
| `src/DiceConfig.php`, `src/ValidateSettings.php` | Defaults, normalization, validation |
| `src/DiceServiceProvider.php` | Model hooks and integration |
| `migrations/` | Nullable result column retained on uninstall |
| `resources/defaults.json` | Shared defaults |
| `js/src/forum/`, `js/src/common/`, `js/src/admin/` | Enhancement, previews, effects, icons, settings |
| `less/`, `assets/`, `locale/` | Styles, artwork, English and Chinese |

Parsing/rendering never rolls. The formatter's base HTML contains original placeholder text; the browser enhances only full `.Post-body` / `.Post-preview` regions, excluding editors and summaries. Keeping result text/icons out of base text prevents truncated excerpts from containing half a component. Whitespace and explicit plus signs are preserved.

`posts.edge_dice` is authoritative; client result attributes are not trusted. New values use PHP `random_int` in the save path. See [lifecycle](LIFECYCLE.md) for atomic insertion, edit reconciliation and trust limits.

## Development

Requires PHP `^8.3` and Flarum `^2.0.2`; no site-specific extension. From the extension root:

    composer install --prefer-dist --no-interaction --no-plugins
    FLARUM_VENDOR_DIR="$PWD/vendor" php tests/run.php
    cd js
    npm ci
    npm test
    npm run build

For an existing Flarum checkout, point `FLARUM_VENDOR_DIR` to its vendor directory. Database integration checks belong on an isolated test site. Unit checks need no saved posts/settings. Use Node 24, the available toolchain used for this repository's reproducible build. Commit bundles/maps under `js/dist`; end users do not need Node. Composer derives public versions from Git refs, never a fixed `version` field.

## Verification and distribution

PHP tests cover syntax, exclusions, reuse, invalid ledgers, limits and excerpt markup; JavaScript tests cover effects and normalization. Browser checks use actual Flarum components for previews, tooltips, bilingual settings, themes and narrow viewports.

Recorded testing uses Flarum 2.0.2 and Chromium. Physical phones, Safari and a fresh stock installation remain additional compatibility checks; declared ranges do not imply every combination was tested. Screenshots use transient illustrative data, not real posts.

Sources, built assets, tests and bilingual docs are included. `.gitattributes` excludes development-only material from distribution archives. This preparation creates no tag, Release, Packagist submission or publishing workflow. See [release procedure](RELEASING.md).

Repository Checks run PHP 8.3/8.4 and Node 24 tests/build on pushes and pull requests. They have read-only permissions and no release or Packagist steps.

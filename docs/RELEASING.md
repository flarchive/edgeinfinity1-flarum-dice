# Release process

[简体中文](RELEASING.zh-CN.md) · [Technical documentation](TECHNICAL.md)

Version 0.1.0 is the first stable release. The Composer package is [edgeinfinity/flarum-dice](https://packagist.org/packages/edgeinfinity/flarum-dice). Composer derives versions from immutable Git tags; composer.json must not contain a fixed version.

## Preparing a release

1. Review both introductions, screenshots, artwork credits and compatibility requirements. Preserve the owner's introduction.
2. Run Composer validation, PHP/JS tests, publication-script tests and a clean Node 24 build; compare committed bundles/maps. CI covers PHP 8.3/8.4. Record combinations not tested, including physical mobile and Safari.
3. When runtime code changes, verify create/edit/reuse/removal, strict editing, previews, summaries and disable/reinstall behavior on an isolated supported Flarum instance.
4. Advance js/package.json and both root version fields in js/package-lock.json; update CHANGELOG and installation examples. Keep runtime files independent of local archives.
5. After owner authorization, push the reviewed commit and wait for successful Checks. Create a new immutable version tag and publish its GitHub Release. Never move a published tag to fix a release.

## Packagist automation

The package must be registered on Packagist once. Configure repository Actions variable PACKAGIST_USERNAME and encrypted secret PACKAGIST_TOKEN for a package maintainer. Credentials never belong in source, URLs or logs. The update workflow can use a safe API token.

Publishing a GitHub Release runs the reusable Checks workflow against its tag, then calls the Packagist update API. It verifies both source and distribution references against that exact Git commit in Composer's P2 installation index. The package-details API may remain cached and is not used as the indexing gate.

To retry an existing release, run **Actions → Publish to Packagist → Run workflow** from main and enter its existing tag (for example v0.1.0). Retrying only rechecks and synchronizes; it does not create or move a tag or release.

Local publication-script tests:

    node --test .github/scripts/update-packagist.test.mjs

## Local path installations

A site's path-version override does not publish anything. When intentionally advancing it, keep the root requirement, path override, lock and installed records consistent. Preserve all other packages and deployment modes. Never overwrite production with another site's vendor, configuration or database.

#!/usr/bin/env bash
#
# Bump the version of laravel-eloquentize-client and publish it.
#
#   ./eloquentize-release-bump.sh patch|minor|major [--dry-run] [--yes] [--skip-checks]
#
# What it does (and stops at the first problem):
#   1. checks we are on `main`, with a clean tree, and not behind origin/main
#   2. computes the next vX.Y.Z from the latest vX.Y.Z tag
#   3. runs pest + phpstan (unless --skip-checks)
#   4. asks for confirmation (unless --yes)
#   5. pushes main, creates the annotated tag, pushes it
#   6. creates the GitHub Release (if `gh` is available): the "Update Changelog"
#      workflow then writes the CHANGELOG.md entry on main from the release notes
#
# Packagist picks the new tag up through its GitHub webhook.
# It never commits anything: commit your work first, the tree must be clean.

set -euo pipefail

REQUIRED_BRANCH="main"
REMOTE="origin"

BUMP=""
DRY_RUN=0
ASSUME_YES=0
SKIP_CHECKS=0

die() { echo "✗ $*" >&2; exit 1; }
info() { echo "→ $*"; }

for arg in "$@"; do
    case "$arg" in
        patch|minor|major) BUMP="$arg" ;;
        --dry-run) DRY_RUN=1 ;;
        --yes|-y) ASSUME_YES=1 ;;
        --skip-checks) SKIP_CHECKS=1 ;;
        -h|--help) sed -n '2,19p' "$0"; exit 0 ;;
        *) die "Unknown argument: $arg (use patch|minor|major, --dry-run, --yes, --skip-checks)" ;;
    esac
done

[ -n "$BUMP" ] || die "Give the bump type: patch, minor or major."

cd "$(dirname "$0")"
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Not inside a git repository."

# 1. branch / tree / remote state ------------------------------------------------
branch="$(git rev-parse --abbrev-ref HEAD)"
[ "$branch" = "$REQUIRED_BRANCH" ] || die "You are on '$branch', releases are made from '$REQUIRED_BRANCH'."

[ -z "$(git status --porcelain)" ] || die "Working tree is not clean. Commit or stash first:
$(git status --short)"

info "Fetching $REMOTE..."
git fetch --quiet --tags "$REMOTE"

behind="$(git rev-list --count "HEAD..$REMOTE/$REQUIRED_BRANCH")"
ahead="$(git rev-list --count "$REMOTE/$REQUIRED_BRANCH..HEAD")"
[ "$behind" -eq 0 ] || die "$REQUIRED_BRANCH is $behind commit(s) behind $REMOTE/$REQUIRED_BRANCH. Pull first."

# 2. next version -----------------------------------------------------------------
# only strict vX.Y.Z tags (ignores e.g. v1.0.0-php7.4)
latest="$(git tag --list 'v*' | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -n1 || true)"
[ -n "$latest" ] || die "No vX.Y.Z tag found, create the first one by hand."

IFS=. read -r major minor patch <<<"${latest#v}"
case "$BUMP" in
    major) major=$((major + 1)); minor=0; patch=0 ;;
    minor) minor=$((minor + 1)); patch=0 ;;
    patch) patch=$((patch + 1)) ;;
esac
next="v${major}.${minor}.${patch}"

git rev-parse -q --verify "refs/tags/$next" >/dev/null && die "Tag $next already exists."

info "Latest tag : $latest"
info "Next tag   : $next ($BUMP)"
info "To publish : $ahead commit(s) ahead of $REMOTE/$REQUIRED_BRANCH, tag $next on $(git rev-parse --short HEAD)"

if [ "$DRY_RUN" -eq 1 ]; then
    echo "Dry run: nothing was run, tagged or pushed."
    exit 0
fi

# 3. checks -----------------------------------------------------------------------
if [ "$SKIP_CHECKS" -eq 0 ]; then
    info "Running tests..."
    vendor/bin/pest --no-coverage
    info "Running PHPStan..."
    vendor/bin/phpstan analyse --no-progress
fi

# 4. confirmation -----------------------------------------------------------------
if [ "$ASSUME_YES" -eq 0 ]; then
    read -r -p "Push $REQUIRED_BRANCH and release $next? [y/N] " answer
    [[ "$answer" =~ ^[Yy]$ ]] || die "Aborted."
fi

# 5. push + tag -------------------------------------------------------------------
git push "$REMOTE" "$REQUIRED_BRANCH"
git tag -a "$next" -m "Release $next"
git push "$REMOTE" "$next"

# 6. GitHub release (triggers the CHANGELOG workflow) ---------------------------------
if command -v gh >/dev/null 2>&1; then
    gh release create "$next" --verify-tag --title "$next" --generate-notes
    info "GitHub release created. The workflow will commit CHANGELOG.md on $REQUIRED_BRANCH: run 'git pull' in a minute."
else
    echo "! gh not installed: create the release by hand on GitHub (title '$next') so the CHANGELOG gets updated."
fi

info "Done: $next pushed. Packagist updates through its webhook."

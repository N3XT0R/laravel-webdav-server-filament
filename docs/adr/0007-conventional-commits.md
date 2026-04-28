# 0007. Conventional Commits

## Status

Accepted

## Context

This project is a versioned Laravel package with a public extension surface. Commit history is the
primary source of truth for what changed between releases, who introduced a change, and whether a
release requires a major, minor, or patch version bump.

Without a commit message convention, several problems appear over time:

- release notes must be assembled manually from free-form messages
- semantic versioning decisions depend on human recall rather than structured signals
- reviewers cannot quickly distinguish feature work, bug fixes, breaking changes, and maintenance
  from the commit list alone
- tooling for automated changelogs, release drafts, or version bumps cannot be applied

The Conventional Commits specification (v1.0.0) provides a lightweight, machine-readable, and
human-readable convention that maps directly onto Semantic Versioning and aligns with the way this
project already thinks about package evolution.

## Decision

All commits to this repository must follow the Conventional Commits v1.0.0 specification.

The governing reference is: https://www.conventionalcommits.org/en/v1.0.0/

### Required format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Type

The `type` is mandatory and must be one of the following:

| Type       | When to use                                                       | SemVer signal |
|------------|-------------------------------------------------------------------|---------------|
| `feat`     | A new feature visible to users or package consumers              | MINOR         |
| `fix`      | A bug fix                                                         | PATCH         |
| `docs`     | Documentation changes only                                        | —             |
| `style`    | Formatting, whitespace, code style — no behavior change           | —             |
| `refactor` | Code restructuring without adding features or fixing bugs         | —             |
| `perf`     | Performance improvements                                          | PATCH         |
| `test`     | Adding or correcting tests                                        | —             |
| `build`    | Build system, dependency, or tooling changes                      | —             |
| `ci`       | CI configuration changes                                          | —             |
| `chore`    | Maintenance tasks that do not fit another type                    | —             |

### Scope

Scope is optional. When present it must be a noun in parentheses describing the part of the
codebase the commit touches. Examples: `feat(auth)`, `fix(storage)`, `docs(adr)`.

### Description

The description is mandatory. It must immediately follow the `type[scope]:` prefix, written as a
short, imperative-mood summary in lowercase. No trailing period.

### Body

Optional. Separated from the description by a blank line. Used to explain the motivation or
additional context that does not fit in the description.

### Footers

Optional. Separated from the body (or description when no body is present) by a blank line.
Each footer token uses a hyphen-separated key, for example `Reviewed-by` or `Refs`.

### Breaking changes

Breaking changes must be signaled in one of two ways — or both:

- append `!` after the type and optional scope: `feat(auth)!: remove legacy endpoint`
- include a `BREAKING CHANGE:` footer (uppercase, exactly as written) with a description of what
  breaks and why

Both forms correlate with a MAJOR version bump in SemVer. They may be combined when the description
benefits from both the immediate `!` signal and the longer footer explanation.

### Examples

```
feat(auth): add digest authentication support

fix(storage): correct path encoding for non-ASCII filenames

docs(adr): add ADR 0007 for conventional commits

feat(auth)!: replace basic auth with token-based authentication

BREAKING CHANGE: BasicAuthAuthenticator has been removed. Consumers must
switch to TokenAuthenticator or provide a custom AuthenticatorInterface
implementation.

chore(ci): update PHP matrix to include 8.4
```

## Consequences

Positive consequences:

- commit history becomes scannable without opening individual diffs
- breaking changes are always explicitly flagged rather than discovered after release
- semantic version decisions can be derived directly from the commit type list
- changelog entries (see ADR 0006) can be cross-checked against commit signals
- tooling for automated release drafts or version bumps becomes applicable

Trade-offs:

- authors must learn and apply the format consistently
- short or ambiguous commits require more thought about the correct type
- enforcing the convention in CI requires additional tooling if linting is added later

Rejected alternatives:

- **Free-form commit messages** — rejected because they make release preparation, semantic
  versioning, and history navigation more costly.
- **GitHub squash-merge title convention only** — rejected because individual commits within a
  branch also carry review and bisect value and should be readable on their own.

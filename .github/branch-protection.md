# Branch Strategy & Protection

## Branch Strategy

- `main` is production. CD builds on pushes to `main`; real deploys stay disabled while
  `prod-ready: false`.
- `develop` is the integration branch. Feature work lands here first.
- `feature/**` branches are short-lived work branches created from `develop`.

Normal flow:

```text
feature/<task> -> develop -> main
```

CI runs on pushes to `develop`, and on pull requests targeting `main` or `develop`. Feature
branches should open PRs into `develop`; direct feature-branch pushes are not wired to the
reusable CI workflow because that workflow promotes successful push builds to `main`.

## `main` Protection

Configure under **Settings -> Branches -> Add rule** or GitHub Rulesets. For private repos,
this requires the GitHub account/organization plan to support branch protection.

| Setting | Value |
|---------|-------|
| Branch name pattern | `main` |
| Require a pull request before merging | Enabled |
| Required approvals | `0` for automated promotion, or `1` if we choose manual promotion |
| Dismiss stale approvals | Enabled |
| Require status checks | Enabled |
| Required status checks | `pipeline / Code Style (Pint)`, `pipeline / Security Audit`, `pipeline / Tests (MySQL)`, `pipeline / Production Build Check` |
| Require branches to be up to date | Enabled |
| Allow force pushes | Disabled |
| Allow deletions | Disabled |

If the reusable CI workflow is expected to push `develop` into `main`, the token behind
`GH_PAT` must be allowed to do that under the selected branch-protection/ruleset model.

## `develop` Protection

Recommended:

| Setting | Value |
|---------|-------|
| Branch name pattern | `develop` |
| Require status checks | Enabled |
| Required status checks | `pipeline / Code Style (Pint)`, `pipeline / Security Audit`, `pipeline / Tests (MySQL)`, `pipeline / Production Build Check` |
| Allow force pushes | Disabled |
| Allow deletions | Disabled |

Keep `develop` easier to work with than `main`, but do not allow force pushes once multiple
people are contributing.

## Current Limitation

If applying protection to `godiah/modelhub` while it is a private repo returns:

```text
Upgrade to GitHub Pro or make this repository public to enable this feature.
```

Until that is resolved, enforce the branch strategy by convention and keep all local work on
`develop`.

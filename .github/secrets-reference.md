# GitHub Actions Secrets & Variables

Set these under **Settings -> Secrets and variables -> Actions** in `godiah/modelhub`.

## Required Repository Secrets

| Secret | Description |
|--------|-------------|
| `GH_PAT` | GitHub Personal Access Token with `repo` scope. Used by the reusable CI workflow for repository writes such as promotion/auto-merge. |
| `GHCR_PULL_TOKEN` | GitHub PAT with `read:packages` scope. Required by the reusable CD workflow and used by the production server to pull ModelHub images from GHCR. |
| `PROD_SSH_HOST` | Production server IP or hostname. TBD — no server provisioned yet. |
| `PROD_SSH_USER` | SSH deploy user. Use `root` unless a separate deploy user is provisioned. |
| `PROD_SSH_KEY` | Private ED25519 deploy key. The public key must be present in the server user's `authorized_keys`. |

`GITHUB_TOKEN` is auto-provided by Actions for GHCR image pushes. Do not create it manually.

## Application Secrets

These values are sensitive, but the production app reads them from `/opt/modelhub/.env` on
the server. Do not commit them.

| Value | Description |
|-------|-------------|
| `APP_KEY` | Generate with `php artisan key:generate --show`. |
| `DB_PASSWORD` | Production database password. |
| `REDIS_PASSWORD` | Redis password if Redis auth is enabled. |
| `MAIL_USERNAME` | SMTP username. |
| `MAIL_PASSWORD` | SMTP password. |

## Optional Repository Variables

| Variable | Example | Description |
|----------|---------|-------------|
| `APP_ENV` | `production` | Documentation/visibility only unless referenced by a workflow. |
| `APP_URL` | `https://modelhub.example.com` | Production URL once the domain is chosen. |

The current CD gate is hardcoded in `.github/workflows/cd.yml` as `prod-ready: false`.
Changing a `PROD_READY` variable will not enable deploys unless the workflow is later changed
to read that variable.

## Deploy Key Pair

Generate a project-specific key pair locally:

```bash
ssh-keygen -t ed25519 -C "modelhub-deploy" -f ~/.ssh/modelhub_deploy
```

Add the public key to the server:

```bash
ssh-copy-id -i ~/.ssh/modelhub_deploy.pub root@PROD_SERVER_IP
```

Add the private key contents to GitHub as `PROD_SSH_KEY`:

```bash
cat ~/.ssh/modelhub_deploy
```

## GHCR Pull Token

1. GitHub -> Settings -> Developer settings -> Personal access tokens -> Tokens classic.
2. Create a token with `read:packages`.
3. Save it as the repository secret `GHCR_PULL_TOKEN`.
4. Also place it in `/opt/modelhub/.env` as `GHCR_PULL_TOKEN=<token>` so the server can run
   `docker login ghcr.io`.

The repo may contain a temporary `GHCR_PULL_TOKEN` during pre-production setup so the reusable
workflow can start. Replace it with a dedicated package-read token before provisioning the
server.

Rotate all production secrets before go-live.

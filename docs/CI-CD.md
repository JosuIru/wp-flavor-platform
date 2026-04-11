# CI/CD Pipeline Documentation

This document describes the Continuous Integration and Continuous Deployment (CI/CD) pipeline for Flavor Platform.

## Table of Contents

- [Overview](#overview)
- [Workflows](#workflows)
- [Local Development](#local-development)
- [Branch Protection](#branch-protection)
- [Secrets Configuration](#secrets-configuration)
- [Troubleshooting](#troubleshooting)

## Overview

The CI/CD pipeline is built on GitHub Actions and includes:

- **CI Pipeline**: Runs on every push and pull request
- **Release Pipeline**: Creates releases when tags are pushed
- **Security Pipeline**: Weekly security scans
- **Preview Pipeline**: Creates preview builds for pull requests

### Pipeline Flow

```
Push/PR → CI → Tests → Build → (tag) → Release
                ↓
            Security Scan (weekly)
```

## Workflows

### CI Workflow (`ci.yml`)

**Triggers:**
- Push to `master` or `develop`
- Pull requests to `master`

**Jobs:**

| Job | Description | Duration |
|-----|-------------|----------|
| `php-lint` | PHP syntax check and PHPCS | ~2 min |
| `js-lint` | ESLint and Stylelint | ~1 min |
| `php-tests` | PHPUnit with MySQL | ~5 min |
| `js-tests` | Jest with coverage | ~2 min |
| `e2e-tests` | Playwright tests (PRs only) | ~10 min |
| `build` | Build production assets | ~2 min |

**Example output:**

```
✓ PHP Lint          2m 15s
✓ JS/CSS Lint       1m 03s
✓ PHP Tests         4m 45s
✓ JS Tests          1m 58s
✓ Build             1m 32s
```

### Release Workflow (`release.yml`)

**Triggers:**
- Push tags matching `v*` (e.g., `v3.5.0`, `v3.6.0-beta.1`)

**Jobs:**

| Job | Description |
|-----|-------------|
| `validate` | Extract version, validate plugin file |
| `build` | Build production assets, create ZIP |
| `release` | Create GitHub Release with assets |
| `notify` | Post release summary |

**Creating a release:**

```bash
# Create and push tag
git tag -a v3.6.0 -m "Release version 3.6.0"
git push origin v3.6.0

# Or use the release script
npm run release:patch  # 3.5.0 → 3.5.1
npm run release:minor  # 3.5.0 → 3.6.0
npm run release:major  # 3.5.0 → 4.0.0
```

### Security Workflow (`security.yml`)

**Triggers:**
- Every Monday at 00:00 UTC
- Push to `master`
- PRs that modify dependency files

**Jobs:**

| Job | Description |
|-----|-------------|
| `php-security` | Composer audit, Snyk scan |
| `js-security` | npm audit, Snyk scan |
| `code-security` | WordPress security checks |
| `summary` | Generate security report |

### Preview Workflow (`preview.yml`)

**Triggers:**
- Pull request opened, synchronized, or reopened

**Features:**
- Creates a preview ZIP of the plugin
- Comments on the PR with download link
- Runs quick validation tests

## Local Development

### Running CI Locally

**Prerequisites:**
- Docker and Docker Compose
- Node.js 20+
- PHP 8.1+
- Composer

**Run all tests:**

```bash
# Install dependencies
composer install
npm ci

# Run linting
npm run lint

# Run PHP tests
composer test

# Run JS tests
npm run test:js

# Build assets
npm run build:prod
```

### E2E Tests with Docker

```bash
# Start WordPress environment
docker-compose up -d

# Wait for WordPress to be ready
sleep 30

# Run E2E tests
npx playwright test

# View test report
npx playwright show-report

# Stop environment
docker-compose down
```

### WordPress Test Suite

```bash
# Install WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root root localhost latest

# Run PHPUnit
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration
```

## Branch Protection

The `master` branch is protected with these rules:

### Required Checks

All these checks must pass before merging:

- `CI Status`
- `PHP Lint`
- `JS/CSS Lint`
- `PHP Tests`
- `JS Tests`
- `Build`

### Settings

- Require pull request reviews (1 approval)
- Dismiss stale reviews on new commits
- Require branches to be up to date
- No force pushes
- No deletions

See [branch-protection.md](../.github/branch-protection.md) for detailed setup instructions.

## Secrets Configuration

### Required Secrets

Configure these in GitHub repository settings (Settings > Secrets and variables > Actions):

| Secret | Description | Required |
|--------|-------------|----------|
| `CODECOV_TOKEN` | Codecov upload token | Optional |
| `SNYK_TOKEN` | Snyk API token | Optional |

### Getting Tokens

**Codecov:**
1. Go to [codecov.io](https://codecov.io)
2. Add your repository
3. Copy the upload token

**Snyk:**
1. Go to [snyk.io](https://snyk.io)
2. Account Settings > API Token
3. Copy the token

## Troubleshooting

### Common Issues

#### "MySQL connection refused"

The MySQL service might not be ready. The CI includes health checks, but if issues persist:

```yaml
# In ci.yml, increase the wait time
- name: Wait for MySQL
  run: sleep 10
```

#### "WordPress test suite not found"

The test suite installation might have failed:

```bash
# Clean and reinstall
rm -rf /tmp/wordpress-tests-lib /tmp/wordpress
bash bin/install-wp-tests.sh wordpress_test root root localhost latest
```

#### "Node modules cache miss"

If npm ci fails with cache issues:

```bash
# Clear npm cache
npm cache clean --force
rm -rf node_modules
npm ci
```

#### "Playwright browser not found"

Install browsers in CI:

```bash
npx playwright install --with-deps chromium
```

### Debugging CI

**View CI logs:**
1. Go to Actions tab in GitHub
2. Click on the failing workflow run
3. Expand the failing job
4. Check the step logs

**Run workflow manually:**
1. Go to Actions tab
2. Select the workflow
3. Click "Run workflow"
4. Select branch and run

**Download artifacts:**
1. Go to the workflow run
2. Scroll to "Artifacts"
3. Download the relevant artifact

### Performance Tips

**Speed up CI:**

1. Use caching effectively
2. Run independent jobs in parallel
3. Use matrix builds for multiple PHP/Node versions
4. Skip unnecessary tests with `[skip ci]` in commit message

**Reduce costs:**

1. Use `concurrency` to cancel redundant runs
2. Limit E2E tests to PRs only
3. Use self-hosted runners for heavy workloads

## Best Practices

### Commit Messages

Follow conventional commits:

```
feat: add new VBP block
fix: resolve mobile layout issue
chore(deps): update dependencies
docs: improve CI documentation
```

### Pull Request Workflow

1. Create feature branch from `develop`
2. Make changes and commit
3. Push and create PR
4. Wait for CI to pass
5. Get code review approval
6. Merge to `develop`
7. Periodically merge `develop` to `master`
8. Tag for release

### Release Process

1. Ensure all tests pass on `develop`
2. Create PR from `develop` to `master`
3. Review and merge
4. Create version tag: `git tag v3.6.0`
5. Push tag: `git push origin v3.6.0`
6. Release workflow creates GitHub Release automatically

## Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [WordPress Plugin Testing](https://developer.wordpress.org/plugins/plugin-basics/testing/)

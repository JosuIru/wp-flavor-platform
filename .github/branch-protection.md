# Branch Protection Configuration

This document describes the recommended branch protection rules for the Flavor Platform repository.

## Master Branch Protection

Configure these settings in GitHub repository settings under "Branches" > "Branch protection rules":

### Rule: `master`

#### Protect matching branches

- [x] **Require a pull request before merging**
  - [x] Require approvals: `1`
  - [x] Dismiss stale pull request approvals when new commits are pushed
  - [x] Require review from Code Owners (if CODEOWNERS file exists)
  - [ ] Require approval of the most recent reviewable push

- [x] **Require status checks to pass before merging**
  - [x] Require branches to be up to date before merging
  - Required status checks:
    - `CI Status` (from ci.yml)
    - `PHP Lint`
    - `JS/CSS Lint`
    - `PHP Tests`
    - `JS Tests`
    - `Build`

- [x] **Require conversation resolution before merging**

- [x] **Require signed commits** (optional, recommended for enterprise)

- [ ] **Require linear history** (optional)

- [x] **Do not allow bypassing the above settings**

- [x] **Restrict who can push to matching branches**
  - Allow specific people: repository admins only

- [ ] **Allow force pushes** - **DISABLED**

- [ ] **Allow deletions** - **DISABLED**

## Develop Branch Protection

### Rule: `develop`

- [x] **Require a pull request before merging**
  - [x] Require approvals: `1`
  - [ ] Dismiss stale pull request approvals when new commits are pushed

- [x] **Require status checks to pass before merging**
  - Required status checks:
    - `PHP Lint`
    - `JS/CSS Lint`
    - `PHP Tests`
    - `JS Tests`

- [ ] **Restrict who can push to matching branches**

- [ ] **Allow force pushes** - **DISABLED**

- [ ] **Allow deletions** - **DISABLED**

## Tag Protection

### Rule: `v*`

Tags matching `v*` should only be created by:
- Repository administrators
- GitHub Actions (for automated releases)

## Setting Up Branch Protection

### Via GitHub UI

1. Go to repository Settings
2. Click on "Branches" in the left sidebar
3. Click "Add branch protection rule"
4. Enter `master` as the branch name pattern
5. Configure the settings as described above
6. Click "Create"

### Via GitHub CLI

```bash
# Install GitHub CLI if needed
# https://cli.github.com/

# Authenticate
gh auth login

# Set branch protection for master
gh api repos/{owner}/{repo}/branches/master/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"contexts":["CI Status","PHP Lint","JS/CSS Lint","PHP Tests","JS Tests","Build"]}' \
  --field enforce_admins=false \
  --field required_pull_request_reviews='{"dismiss_stale_reviews":true,"require_code_owner_reviews":false,"required_approving_review_count":1}' \
  --field restrictions=null \
  --field allow_force_pushes=false \
  --field allow_deletions=false
```

## CODEOWNERS File

Create `.github/CODEOWNERS` to automatically request reviews:

```
# Default owners for everything
* @gailu-labs

# PHP code
*.php @gailu-labs

# JavaScript/CSS
*.js @gailu-labs
*.css @gailu-labs

# Documentation
*.md @gailu-labs
docs/ @gailu-labs

# CI/CD
.github/ @gailu-labs
```

## Recommended Workflow

1. **Feature branches**: `feature/description`
2. **Bug fix branches**: `fix/description`
3. **Hotfix branches**: `hotfix/description`

### Flow

```
feature/new-feature
       |
       v
   develop (PR required, 1 approval)
       |
       v
    master (PR required, 1 approval, all checks must pass)
       |
       v
   v1.0.0 (tag triggers release)
```

## Emergency Procedures

In case of critical security fixes:

1. Create hotfix branch from `master`
2. Apply fix
3. Get emergency approval from admin
4. Merge directly to `master` (admin can bypass if needed)
5. Create hotfix tag
6. Cherry-pick to `develop`

Note: Document any branch protection bypasses in the PR description.

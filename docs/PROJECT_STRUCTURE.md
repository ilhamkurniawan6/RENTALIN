# Project Structure

This repository is organized as a single app under `src/`, but the codebase is split by responsibility so a team can work in parallel with minimal overlap.

## Logical Ownership

### Frontend
Own the user-facing pages, layouts, and styling.

Current locations:
- `src/pages/home/`
- `src/pages/browse/`
- `src/pages/item-detail/`
- `src/pages/login/`
- `src/pages/register/`
- `src/pages/dashboard/`
- `src/pages/admin-dashboard/`
- `src/pages/add-item/`
- `src/styles/`
- `src/assets/`

### Backend
Own the server-side PHP logic, API endpoints, auth, and persistence.

Current locations:
- `src/pages/api/`
- `src/services/`
- `database.sql`
- `migration_dual_role.sql`

### Shared Data and State
Own reusable client-side data and storage helpers.

Current locations:
- `src/mock/`
- `src/store/`

### Infra / Tooling
Own repository configuration, CI, test runners, and developer scripts.

Current locations:
- `.github/`
- `package.json`
- `playwright.config.js`
- `scripts/`
- `DEV_SETUP.md`
- `.editorconfig`
- `.gitattributes`
- `.gitignore`

### Operational Files
Own storage and runtime assets.

Current locations:
- `storage/`
- `uploads/`
- `src/logs/`

## Working Convention

When a teammate asks "where should I edit this?", use this rule:
- UI and page behavior: frontend
- API, DB, auth, and validation: backend
- Shared state and mocks: shared data
- GitHub workflow, tests, and setup: infra

## Conflict Avoidance

Keep changes small and local to one area whenever possible.
- One feature, one branch.
- Avoid editing the same page or API file from multiple branches in parallel.
- If a change touches both frontend and backend, split it into two commits or two PR-ready chunks when practical.
- Update docs whenever the behavior of a shared flow changes.

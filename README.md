
# Student Rental Marketplace UI (Vanilla)

Production-style static frontend for a student rental marketplace.
The project is implemented as a multi-page app using plain HTML, CSS, and JavaScript.

Original design reference:
https://www.figma.com/design/J4dbPSIOT0QUZQInE9Hcr6/Student-Rental-Marketplace-UI

## Features

- Multi-page user flow: home, browse, detail, auth, dashboard, add-item, admin dashboard
- Shared data module and localStorage persistence for custom items
- Mobile-friendly layout across main pages
- Relative routing suitable for static hosting

## Project Structure

`src/` contains all app pages and scripts.

- `src/pages/home/`
- `src/pages/browse/`
- `src/pages/item-detail/`
- `src/pages/login/`
- `src/pages/register/`
- `src/pages/dashboard/`
- `src/pages/admin-dashboard/`
- `src/pages/add-item/`
- `src/mock/`, `src/store/`, and `src/services/` (shared data and storage modules)

## Run Locally

Option 1:
- Open `index.html` directly (auto-redirect to home page).

Option 2 (recommended):
- Serve folder with a local static server (for consistent browser behavior).

Example with Python:

```bash
python -m http.server 5500
```

Then open:

`http://localhost:5500/`

Option 3 (for PHP login flow):
- Run with PHP built-in server from repository root.

```bash
php -S localhost:8000
```

Then open:

`http://localhost:8000/`

## Deploy To GitHub Pages

Workflow file is included at `.github/workflows/deploy-pages.yml`.

Before first deploy:

1. Push repository to GitHub.
2. In repository settings, open `Settings > Pages`.
3. Set source to `GitHub Actions`.
4. Push to `main` branch to trigger deployment.

## Repository Hygiene

- `.editorconfig` for consistent formatting
- `.gitattributes` for line ending normalization
- `.gitignore` for system/editor/temp files
- `CONTRIBUTING.md` for collaboration rules
- `ATTRIBUTIONS.md` for third-party credits

## Pre-Push Checklist

1. Verify all links across pages are working.
2. Verify add-item flow persists data (localStorage) and appears in browse/detail/dashboard.
3. Test responsive layout on mobile width.
4. Confirm no console errors in browser devtools.
  
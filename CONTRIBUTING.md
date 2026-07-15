# Contributing Guide

Thanks for contributing to this project.

## Development Notes

- This is a plain HTML/CSS/JS project under `src/`.
- Keep paths relative across pages.
- Reuse shared modules in `src/mock/`, `src/store/`, and `src/services/` where possible.

## Coding Standards

- Use UTF-8 and LF line endings.
- Keep indentation to 2 spaces.
- Prefer clear naming for files and variables.
- Avoid adding dead links (`href="#"`) unless explicitly marked as placeholder.

## Pull Request Checklist

- Verify all page links work.
- Test key flows: `home -> browse -> item-detail -> dashboard -> add-item`.
- If editing forms, validate both happy path and invalid input.
- Update `README.md` if user-facing behavior changes.
- Keep attribution information in `ATTRIBUTIONS.md` intact.

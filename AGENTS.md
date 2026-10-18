# Project instructions for agents


## MVC(S) Architecture

This project uses strict MVC(S) writing style.

- (M)odels are for database calls and low level things like dealing with A/R, PDO, the database directly. Models are the ONLY group that may connect to a database, 
- (V)iews are for views. These are `dumb`, they are the visual layout and present information and allow actions back to the application 
- (C)ontrollers are thin and for routing to business logic (service) and light model usage. 
- (S)ervices are for business logic and do the majority of the heavy lifting, they do not connect to the database directly, they use models to do so. 


## Constraints

- Work only within the area assigned to you. If you find problems outside your area, report them to the user.
- You may find partially rendered code and code inconsistent with your constraints. Any and all code in your area of expertise is to be changed to reflect industry standards and align with this file.
- Treat all code as if you did not write it. Strive for properly created code, fix sub-par code.
- The word 'widget' was removed entirely from v3 and it's been sneaking back in.  Remove the word when you come across it. They are `blocks` or `code snippits`. 

## Local Dev Server
- Use localhost port 80 
- MySQL: see .env

# default URL
SITE_URL=http://localhost

## Migrations

- First part of filename is:  YYYY-MM-DD-HHMMSS Using a sequence is unaceptable (EG: 2026-09-08-000001). If hand-numbering, use the full hour, minute and second. If two files have the same date/time the migration will not run.
- Second part of filename is: What it does. EG: `CreateMarketPlaceInstallsTable`.php
- 1st and 2nd parts are joined with an `_` underscore.
- EG: `2026-09-11-121006_CreateMarketPlaceInstallsTable.php`

- On this test server, insert seeds manually after migrations run.

## Wrapping up coding tasks

1. lint `php -l` all files touched
2. Psalm tests
3. Write and run PHPUnit tests
4. PHPStan Level 8 is expected. Test and fix as needed.
5. Test/exercise the code.


## Documentation

Use these based on the user's requests or your need for code:

- `CODING-STANDARDS.md`: coding standards and the mandatory pre-push checklist (php -l, PHPStan level 8, Psalm taint analysis, PHPUnit). Run through `composer lint` / `composer phpstan` / `composer psalm` / `composer test` before pushing. CI enforces them.


## Configuration

Two settings, and which one to read is decided by where the value is edited.

- Everything stored in the database is read with `settings()->get()`:
  `$app->settings()->get('CMS.siteName')`. Never `$app->get()` for a
  settings-table key.
- `$app->get()` is for deployment config from `.env`: database credentials,
  `SESSION_ENCRYPTION_KEY`, `SITE_URL`, `APP_ENV`, `FORCE_HTTPS`.
- `SITE_URL` is env only, read as `$app->get('siteUrl')`. It is not a database
  setting and has no admin UI field.

## Naming Conventions:

- database: consistent with the package/plugin as applicable.
EG: recently 'redirects' and 'incoming_broken_links' in the database, 'redirects' and 'redirects_links' should have been the names. This way they group in the database.
- Files: pretty much the same. name the files so 1: they don't conflict with others, and 2: so anyone can look at a file and get a good sense of what it's for (complemented by which folder it's in).
- Controllers: {package}AdminController, {package}PublicController.
- Models: Models/{Entity}.php, named for the row it represents (EG: Post, Page, RedirectLink)
- Services: Service/{package}Service (if singleton)

in General, follow this rule, when there's two services {package} is flexible especially for integrating composer packages, etc.   Just use common sense with edge cases.

For Plugins:
- create AGENTS.md
- README.md
- Other documentation files as needed. (ask user)

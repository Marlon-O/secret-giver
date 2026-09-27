# Secret Santa Generator: Design & Plan

## Context
A Secret Santa app for family and friends where **nobody signs up**. The organizer creates a group and shares **one group link**. Each person opens the link, taps their name, and sees who they're buying for. A name locks to the first device that claims it, and the organizer can reset a name if someone taps the wrong one. The first version includes **exclusions**, a **budget and exchange date**, **wishlists**, and support for **multiple groups**.

Stack: **Laravel** (latest, installed with `laravel new`), SQLite, Blade, Tailwind (the default Vite setup), Alpine.js, and Pest. It lives at `~/Herd/secret-santa` and Herd serves it at `http://secret-santa.test`. The environment is already checked: PHP 8.4, and `composer`, `laravel` and `herd` are all available.

## Identity without accounts
- **Group link:** `/g/{slug}`. The slug is 12 random characters and can't be guessed. Everyone uses this link.
- **Admin link:** `/g/{slug}/admin/{adminToken}`. The token is 40 random characters, stored as a hash and compared with `hash_equals`. Only the organizer has this link. The browser keeps a "My groups" list of admin links in `localStorage`, which covers multiple groups, and the admin page also shows a "Save this link" copy box. Losing the admin link means losing admin access; that's the trade-off for having no accounts.
- **Claims:** tapping a name opens a dialog asking "Are you Alice?". Confirming atomically sets `claim_token_hash`, but only where it is still null, so two people can't claim the same name at once. It also sets a Laravel-encrypted cookie `santa_{slug}` = `{participantId}|{token}` that lasts one year. When someone returns, the cookie takes them straight to their reveal page.
- **The organizer can't see who got whom.** The admin page shows only who has claimed a name and who has written a wishlist, so the organizer can play too.

## Data model (3 migrations)
- `groups`: id, slug (unique), admin_token_hash, name, budget (string, nullable, e.g. "$30"), exchange_date (date, nullable), drawn_at (nullable), timestamps
- `participants`: id, group_id (FK, cascade), name, assigned_to_id (nullable self-FK), claim_token_hash (nullable), claimed_at (nullable), wishlist (text, nullable), timestamps; unique(group_id, name)
- `exclusions`: id, group_id, giver_id, receiver_id, unique(giver_id, receiver_id). Checking "both ways" for a couple creates two rows.

## Draw algorithm (`app/Services/SecretSantaDrawer.php`)
A pure class with `draw(array $ids, array $forbiddenPairs): array<giverId, receiverId>`:
- **Search:** randomized backtracking, with givers shuffled and candidate receivers shuffled. No one can draw themselves or anyone they're excluded from.
- **Impossible rules:** if no valid assignment exists, it throws `ImpossibleDrawException`, and the admin page shows "These exclusions make a draw impossible. Remove one and try again."
- **Scale:** easily fast enough for family-sized groups (under 100 people).
- **Saving:** the result is written in one DB transaction that also sets `drawn_at`.
- **Minimum size:** a draw needs at least 3 people.

## Pages & routes (`routes/web.php`)
| Route | Purpose |
|---|---|
| `GET /` | Landing page: a "Start a group" form (group name, budget, date, names with add/remove rows) and a "My groups" list from localStorage |
| `POST /groups` | Create the group, then redirect to the admin link |
| `GET /g/{slug}/admin/{token}` | Admin: edit names, budget/date and exclusions (before the draw); **Draw** button; after the draw, share the link (copy button and native share sheet), see claim and wishlist status, reset a claim, **Undo draw** (clears assignments and unlocks editing, with a warning) |
| `POST/PATCH/DELETE …/admin/{token}/…` | Participant, exclusion, draw, undo-draw and reset-claim actions (middleware `EnsureAdminToken`) |
| `GET /g/{slug}` | Group page: "Waiting for the organizer to draw" before the draw; after it, a name grid with claimed names greyed out. If this device's cookie matches a claim, it redirects to `/me` |
| `POST /g/{slug}/claim/{participant}` | Atomic claim that sets the cookie (rate-limited) |
| `GET /g/{slug}/me` | Reveal: a "Tap to reveal" gift animation, then the match's name, budget, date and **match's wishlist**, plus an editor for **my own wishlist** |
| `PATCH /g/{slug}/me/wishlist` | Save my wishlist (checks the cookie) |

Code layout: controllers `GroupController`, `AdminController`, `ClaimController`; `Group`/`Participant`/`Exclusion` models; a `ResolvesClaim` helper that reads the cookie, verifies the token hash and returns the participant or null.

## UI
Designed mobile-first, since most people will open the link on their phones. The look is festive and warm but clean: a deep green and cranberry palette, a display font for headings, and a gift-box reveal animation done in CSS and Alpine. All output goes through Blade's escaping, including names and wishlists. I'll use the frontend-design skill when building the views.

## Implementation steps
1. **Set up the project:** `cd ~/Herd && laravel new secret-santa --pest --database=sqlite --no-interaction`, then move the session there with `change_directory`, then `git init` and commit the scaffold.
2. **Save the design:** copy this design to `docs/superpowers/specs/2026-09-27-secret-santa-design.md` and commit it.
3. **Build with TDD:** write the tests before each piece.
   - Migrations and models.
   - `SecretSantaDrawer` with Pest unit tests (below).
   - Group creation and admin token auth.
   - Admin actions: participants, exclusions, draw, undo, reset claim.
   - Claim flow and cookie.
   - Reveal page and wishlist.
4. **Build the views:** layout, landing page, admin, group grid, reveal, and the localStorage "My groups" list.
5. **Run the full test suite and verify by hand** (below).

## Verification
- **Unit tests (`SecretSantaDrawer`):**
  - Run the draw 500 times with random exclusions: nobody draws themselves, nobody draws an excluded person, and the result is a bijection.
  - An impossible set of rules throws `ImpossibleDrawException`.
- **Feature tests:**
  - Creating a group redirects to the admin link.
  - A wrong admin token returns 404.
  - The admin page HTML never contains assignment names.
  - A second claim on the same name is rejected.
  - The claim cookie redirects to `/me`.
  - A reset claim frees the name.
  - Editing a wishlist without the claim cookie returns 403.
  - Editing is locked after the draw.
- **By hand at `http://secret-santa.test`,** using the in-app browser:
  1. Create a group of 5 people with a couple excluded.
  2. Draw.
  3. Open the group link, claim a name and reveal the match.
  4. Open the link in a second browser tab or profile and confirm that name is locked.
  5. Reset the claim from the admin page and confirm the name is free again.
  6. Check the layout at mobile width.

## Later (not in v1)
- **Public hosting:** the family needs a public URL, through Laravel Cloud, Forge, or `herd share` for a quick test.
- **Docker:** a production image and compose file.
- **"Copy to next year":** automatically excludes last year's matches.

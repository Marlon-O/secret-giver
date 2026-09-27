# Secret Giver

A no-signup Secret Giver draw for family and friends.

1. The organizer adds everyone's name, and optionally a budget, an exchange date, and rules (e.g. couples don't draw each other).
2. The organizer draws names and shares **one group link**.
3. Each person opens the link, taps their own name, and flips their gift tag to see who they're buying for. They can also leave a wishlist for their Giver.

## How identity works without accounts

- **Organizer link** (`/g/{slug}/admin/{token}`): only its hash is stored. It's also remembered in the organizer's browser (localStorage) under "Your groups".
- **Claiming a name** locks it to that device with a one-year encrypted cookie. Other devices see the name as taken. If someone picks the wrong name, the organizer can **Reset** it.
- **The organizer never sees who got whom**, so they can take part too.

## Running locally

Served by Laravel Herd at http://secret-giver.test.

```bash
composer install
npm install && npm run build   # or: npm run dev
php artisan migrate
```

## Tests

```bash
./vendor/bin/pest
```

The design spec is in `docs/superpowers/specs/2026-09-27-secret-giver-design.md`.

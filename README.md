# Falcons Academy — Website & Leader Referral Competition

Laravel 12 rebuild of [falcons-organization.com](https://falcons-organization.com). It replaces the old React/Express site. The app has three parts:

- **Public website.** Bilingual English/Arabic with full RTL. It opens with a founder-led hero, followed by scanners, leader profiles, courses and free courses.
- **Leader Referral Competition.** Leaders get a code and QR, participants register, admins review, points are scored and the leaderboard and timer run live.
- **Admin control room.** Role-based: Super Admin, plus Admin/Reviewer with permissions you can configure.

Stack: Laravel 12 · PHP 8.2+ · Blade + Alpine.js · Tailwind CSS v4 (Vite) · MySQL/MariaDB (SQLite for local) · bacon/bacon-qr-code.
Nothing else is required: no WebSocket server, no Redis, no queue worker.

---

## Quick start (local)

```bash
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
touch database/database.sqlite          # or configure MySQL in .env
php artisan migrate --seed              # content + super admin + competition with a ready round
php artisan storage:link
php artisan serve
```

- The seeder prints the super admin's email and password. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` to choose them yourself.
- To load demo leaders and registrations locally, run `SEED_DEMO=true php artisan db:seed --class=DemoCompetitionSeeder`.
- To run the tests: `php artisan test`. There are 42 tests: service rules plus full HTTP flows and permission boundaries.

## Production checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=https://falcons-organization.com`. Configure MySQL in `.env`.
2. Run `composer install --no-dev --optimize-autoloader`, then `npm ci && npm run build`.
3. Run `php artisan migrate --force && php artisan db:seed --force`. The seed is idempotent and safe to re-run.
4. Run `php artisan storage:link`. Admin uploads go to `storage/app/public`.
5. Run `php artisan config:cache route:cache view:cache`.
6. **Scheduler cron.** Add this line (one line, every minute):
   `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
   It finishes rounds whose timer reached zero, even when nobody has the site open. Expired rounds are also finished lazily on every request, so the cron is a safety net.
7. **Free-course videos.** The 13 MP4s are large, so they are git-ignored. Copy `video-1.mp4 … video-13.mp4` from the old repository (`public/videos/free-courses/`) into `storage/app/private/free-courses/`. They are streamed only to sessions that entered the invite code.
8. Serve over HTTPS and set `SESSION_SECURE_COOKIE=true`.

---

## What changed from the old site

**Requested changes (from the screenshots)**

| # | Request | Implementation |
|---|---|---|
| 1 | Remove the "Join Elite Traders" stats card from the hero | Removed. The numbers now live in a slim stats band below the hero |
| 2 | Founder at the top, Salah Abo El-Magd style | Split-screen editorial hero: headline, founder quote and CTA on the dark side; full-height studio portrait bleeding to the edge. Vision & Mission sits directly below |
| 3 | Redesign scanners; a new scanner is coming | Interactive showcase: a numbered list with a large preview on desktop, and swipe cards on mobile. Fully data-driven — see "Adding the new scanner" |
| 4 | Remove Amr Samir; every leader gets a profile page | Amr removed. `/leaders/{slug}` shows a large portrait, role, city and bio, with previous/next navigation and a "Biography coming soon" placeholder until bios are added |
| 5 | Delete Rank Progression entirely | Removed: section, data, assets and navigation |
| 6 | Same courses, modern layout | Trading/Marketing tabs, each a 4-level ladder with expandable highlights and full curriculum |
| 7 | Heavy "Join the Movement" animation | Replaced with light reveal-on-scroll (opacity/transform only). Respects `prefers-reduced-motion` |
| 8 | Rebuild the footer | New four-column footer with brand, social links, site map, risk disclaimer |
| 9 | Slow, laggy gallery carousel | Lightweight grid with lazy-loaded WebP thumbnails. Full-size images load only when the lightbox opens. Keyboard and swipe support |

**Problems fixed from the old codebase**

- The free-courses invite code (`1000000`) was hard-coded in the JavaScript bundle, and the videos were public. It is now checked server-side, stored hashed, changeable in Settings, and videos stream only to unlocked sessions.
- A database password was committed to the repository (`create-database.sql`, `server/.env.example`). **Rotate that MySQL password.** It is not used anywhere in the new app.
- Image paths broke between `/img/` and `/storage/images/`, with about 15 "fix-image" scripts working around it. All media is now optimised WebP with thumbnails, served from a single path. Admin uploads are resized and converted automatically.
- Scanner descriptions never split into paragraphs (they split on `\n\n` but the data used `\n`). The dead "SHARK" scanner entry with missing images is gone.
- Hard-coded WhatsApp numbers in several components are replaced by one editable setting.

---

## Competition rules (as implemented)

```
Leader → unique code (LDR-XXXXXXXX, CSPRNG, DB-unique) → QR → /register?ref=CODE
      → participant form → PENDING → admin review → ACCEPTED (+1) / REJECTED
      → score = COUNT(accepted registrations for the leader in the round) → live leaderboard
```

- **States.** Registrations go PENDING → ACCEPTED or PENDING → REJECTED, and decisions are final. Rounds go DRAFT → READY → RUNNING ⇄ PAUSED → FINISHED. Invalid transitions are rejected by the backend.
- **Before accepting a registration, the backend verifies:**
  - the competition exists and is active
  - the round is running and registration is enabled
  - the timer has not expired
  - the code exists and the leader is active and belongs to the competition
  - the participant is not a duplicate
  - the form is valid
- **Duplicates.** Phone numbers are normalised, so `010…`, `+20 10…` and Arabic digits are all treated as the same number. Emails are lower-cased. Checks run once per competition or once per round, depending on the competition setting. DB unique indexes back this up at round level.
- **Accept is atomic.** One transaction locks the row, verifies it is PENDING, updates it, recalculates the cached score from source rows, and writes the audit log. Any failure rolls everything back.
- **Ranking.** Leaders are ranked by accepted registrations (descending). Ties go to the leader whose latest counted participant submitted earlier (they reached the score first). Final fallback: earlier sign-up. The same order is used everywhere.
- **Timer.** The server is the source of truth: `ends_at` while running, `remaining_seconds` while paused. Browsers count down with `performance.now()` and resync on every poll, so refreshing the page or changing the device clock has no effect.
- **New round.** The current round is finished and its results kept. Every leader starts the new round at 0, and past rounds stay browsable.
- **Real-time.** Lightweight polling with version counters. Unchanged polls return a tiny payload, and polling pauses in hidden tabs. The admin dashboard refreshes every 6 seconds, the public leaderboard every 8, and the participant's result page every 10.
- **Audit log.** Append-only. The model refuses updates and deletes, and there is no route to modify entries.

## Roles & permissions

| | Super Admin | Admin / Reviewer |
|---|---|---|
| View & search registrations, accept, reject | ✓ | per permission |
| View leaders, create & edit leaders | ✓ | per permission |
| Leaderboard | ✓ | per permission |
| Pause / resume / add or remove time | ✓ | only with *timer control* |
| Start, finish or new round; open or close registration; recalculate | ✓ | never |
| Delete leaders, admins, settings, audit log, competitions | ✓ | never |
| Edit website content (scanners, team, courses, gallery) | ✓ | only with *content* |

Every permission is enforced on the server through policies and gates. Hidden buttons are only cosmetic. Logins, registrations, code lookups and polling are rate-limited. All forms use CSRF protection, and there is a honeypot on public forms.

## Content management

Everything visible on the homepage is editable in **Admin → Website**, in both languages. Arabic fields fall back to English when left empty.

- **Adding the new scanner.** Go to Admin → Scanners → *Add scanner*. Fill in the name, tagline, summary, description, timeframe, targets, method, accent colour, features and images. It appears in the showcase and gets its own `/scanners/{slug}` page. No code changes are needed.
- **Leader bios.** Go to Admin → Team profiles → *Edit* → Biography. Leave a blank line between paragraphs, and start a line with `•` or `-` to make a bullet.
- **Course images.** The live site's course covers are stored on the old API server and were not in the repository. Upload them in Admin → Courses.
- **Gallery.** The first 5 academy photos and 8 gift photos are migrated. The 7 academy photos added later on the live server can be uploaded in Admin → Gallery.

## Project map

```
app/Services      RegistrationService (submit/accept/reject), RoundService (timer + state machine),
                  ScoreService (score & ranking), LeaderService, ReferralCodeGenerator, QrCodeService,
                  AuditLogger, ImageOptimizer
app/Enums         RegistrationStatus, RoundStatus, CompetitionStatus, Role, Permission
app/Policies      RegistrationPolicy, RoundPolicy, LeaderPolicy (+ gates in AppServiceProvider)
app/Http          Public, leader and admin controllers; form requests; security middleware
resources/views   partials/home/* (homepage sections), competition/*, leader/*, admin/*, components/*
lang/ar.json      Arabic translations
tests/Feature     CompetitionServicesTest (business rules), HttpFlowTest (end-to-end + permissions)
```

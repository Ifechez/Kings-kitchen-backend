# Deploying the Kings' Kitchen backend to Vercel (Option A: full Vercel)

This is a **separate Vercel project** from the frontend — two projects,
two dashboards, one Laravel API, one React SPA. The API's URL becomes the
`VITE_API_URL` the frontend already points at.

This uses Vercel's own (recently published) container-Function support:
Laravel runs inside FrankenPHP, packaged as a Docker image, deployed as a
Vercel Function. It's the officially documented path as of writing —
not the old, unofficial `vercel-php` dev-server runtime.

## What changed from your cPanel version
- **Storage:** product images now upload to **Vercel Blob** instead of
  `public/uploads/`. The container's filesystem is wiped on every new
  instance, so local disk storage can't survive between requests.
  (`app/Services/VercelBlobStorage.php`, wired into
  `Admin/ProductController.php` and `Product::full_image_url`.)
- **Database:** your MySQL needs to be reachable from the public internet.
  A typical cPanel MySQL is firewalled to the same server, so this guide
  uses **TiDB Cloud Starter** — free tier, wire-compatible with MySQL, no
  query/migration changes needed.
- **CORS:** `config/cors.php` now exists (it didn't before) and allows
  `kingskitchen.vercel.app` plus its Vercel preview URLs.
- **Sessions/cache:** set to `array` (in-memory, per-instance) since your
  auth is Bearer-token, not cookie/session-based — nothing relies on
  server-side sessions. The one tradeoff: rate limiting (which uses cache)
  won't track across container instances until this moves to a real
  external store like Redis. Worth revisiting once you're past initial
  launch, not a blocker now.
- **Trusted proxies:** added in `bootstrap/app.php` so Laravel correctly
  detects HTTPS behind Vercel's edge.

## Prerequisites
- Docker Desktop (or any running Docker daemon) — needed once, to build
  and test locally before your first deploy. After that, Vercel builds
  from git on its own.
- Vercel CLI: `npm install -g vercel`
- A GitHub repo for this backend folder (recommended over drag-and-drop,
  so pushes auto-deploy)

## 1. Set up the database (TiDB Cloud Starter)
1. Create a free account at tidbcloud.com, create a **Starter** cluster
   (25 GB free, MySQL-wire-compatible).
2. Cluster → **Connect** → copy the host, port (usually `4000`), username,
   and password. Create a database named `kingskitchen`.
3. Note: TiDB requires TLS. The `.env.example` in this zip already points
   `MYSQL_ATTR_SSL_CA` at the Debian image's public CA bundle, which
   verifies TiDB's certificate out of the box — you shouldn't need to
   download TiDB's own CA bundle, but their Connect dialog has it if the
   default doesn't work.

## 2. Set up file storage (Vercel Blob)
This has to happen inside the Vercel project, so do step 3 first, then
come back: **Project → Storage → Create Database → Blob → Connect to
Project**. This automatically injects `BLOB_READ_WRITE_TOKEN` as an env
var — you don't need to copy it manually.

## 3. Create the Vercel project
```bash
cd kk-backend-vercel   # this folder
git init && git add -A && git commit -m "Vercel backend"
# push to a new GitHub repo, then:
vercel link
```
Or import the GitHub repo directly from the Vercel dashboard. Keep
`Dockerfile.vercel`, `Caddyfile`, and `vercel.json` at the project root —
Vercel auto-detects the container service from `vercel.json`.

## 4. Environment variables
In **Project Settings → Environment Variables**, set everything from
`.env.example` in this zip for Production (DB_HOST, DB_USERNAME,
DB_PASSWORD, PAYSTACK keys, FRONTEND_URL, etc.) — `BLOB_READ_WRITE_TOKEN`
is already set from step 2.

Generate `APP_KEY` locally first:
```bash
php artisan key:generate --show
```
and add the output as `APP_KEY` in Vercel's env vars.

## 5. First deploy
```bash
vercel deploy --prod
```
Vercel gives you a URL like `kingskitchen-api.vercel.app`. Now go back
into env vars and set `APP_URL` to that exact URL, then redeploy so
Laravel picks it up:
```bash
vercel deploy --prod
```

## 6. Run migrations + seed
Migrations should NOT run automatically on container start (multiple
instances could start at once and race each other). Run them once,
manually, from your own machine, pointed at TiDB over the internet:
```bash
# temporarily export the same DB_* / MYSQL_ATTR_SSL_CA values from
# Vercel into your local shell or a local .env, then:
php artisan migrate --force
php artisan db:seed --force   # optional — loads sample menu + admin user
```
TiDB Cloud is public, so this works from anywhere with internet access —
no SSH into a server required.

## 7. Point the frontend at it
In the **frontend's** Vercel project, set `VITE_API_URL` to
`https://kingskitchen-api.vercel.app/api` and redeploy the frontend.

## 8. Test
- `https://kingskitchen-api.vercel.app/up` → Laravel's default health
  check, should return 200.
- Log into `/admin` on the frontend, add a product with an image, confirm
  it shows up (this is the actual Blob upload path — the one part of this
  I can't test end-to-end without live TiDB/Blob credentials, so it's the
  first thing worth checking).

## Honest caveats
- **Cold starts.** Container Functions spin down when idle and take a
  moment to start back up on the next request — noticeable as one slow
  request after quiet periods. Vercel's Fluid Compute reduces this but
  doesn't eliminate it. A dedicated PHP host doesn't have this at all.
- **Execution time limit.** Every request has a hard cap (300s default on
  Hobby, configurable higher on Pro) — fine for a REST API like this one,
  but worth knowing if you ever add something slow (bulk exports, etc.).
- **Plan/pricing.** Container Functions bill under Vercel's normal
  Function pricing, and free-tier usage limits apply same as any
  function — check your current plan's limits in the dashboard before
  going live, since I can't see your account from here.
- This whole path is genuinely newer/less battle-tested than "Laravel on
  a real PHP host." It'll work, but if anything about the container setup
  misbehaves in ways this guide didn't anticipate, that's the tradeoff
  for having the whole stack on Vercel.

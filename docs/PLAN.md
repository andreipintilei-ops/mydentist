# MyDentist — Romanian dental clinic directory (Laravel 13 + Vue 3 + shadcn-vue)

## Context

Build a "mini yellow-pages" for dental clinics in Romania from scratch at `D:\Andrei\Herd\Mydentist` (currently empty). The product's value is **organic search traffic**: pages like *"stomatolog în Iași"* or *"implant dentar București"* must rank, so every public page is server-rendered HTML with structured data, and the interactive map/filter layer is progressive enhancement — never the source of content.

Confirmed decisions:
- **MySQL 8** — spatial via native `POINT SRID 4326` + `SPATIAL INDEX`
- **Hybrid rendering** — Blade for public/SEO pages, Inertia + Vue 3 for the authenticated admin and clinic-owner panel
- **Tailwind 4 + shadcn-vue** as the single design system across both worlds
- **Leaflet + OpenStreetMap** tiles, lazy-loaded
- **Seed with fictional demo clinics in Iași & București**; clinics self-register + claim listings thereafter
- **Full admin dashboard**

### Key finding: the official starter kit is already our stack

`laravel new mydentist --vue` ([Laravel 13 starter kits](https://laravel.com/docs/13.x/starter-kits)) ships **Inertia 3 + Vue 3 Composition API + TypeScript + Tailwind 4 + shadcn-vue + Laravel Fortify** (login, registration, password reset, email verification, 2FA) with a collapsible sidebar layout and Wayfinder type-safe routing. That is the admin dashboard shell, the auth system, and the component library in one command — a large chunk of Phase 1 and Phase 6 disappears. Note it's **Inertia 3**, not 2.

### Environment facts verified on this machine

- Herd PHP **8.4.22** (`C:\Users\Webis\.config\herd\bin\php84.bat`) — meets Laravel 13's PHP 8.3+ floor; has `intl`, `gd`, `redis`, `pdo_mysql`
- ⚠️ PATH `php` resolves to **XAMPP PHP 8.0** (`C:\xampp\php\php.exe`) — cannot run Laravel 13. Every command must use Herd's PHP.
- Node **26.3.0**, npm 11.13.0, Composer 2.10.1

---

## The one real tension: shadcn-vue is Vue, but public pages are Blade

shadcn components can't be imported into Blade. But shadcn is **not a runtime library** — it's copy-pasted Tailwind class strings over CSS variables. That makes it portable:

1. **Shared theme.** shadcn-vue writes its tokens into `resources/css/app.css` as CSS variables (`--background`, `--foreground`, `--primary`, `--border`, `--radius`, plus the `.dark` overrides) exposed to Tailwind 4 via `@theme inline`. Blade pages load that same compiled stylesheet, so `bg-background`, `text-muted-foreground`, `border-border` work identically in Blade. **One theme, two renderers.**

2. **Static primitives → Blade mirrors.** Port the non-interactive shadcn components to Blade with the identical class strings: `<x-ui.button>`, `<x-ui.card>`, `<x-ui.badge>`, `<x-ui.input>`, `<x-ui.separator>`. shadcn's `cva` variant maps become a PHP `match` on `$variant`/`$size`. Zero JS.

3. **Interactive primitives → Vue islands.** Only the handful that genuinely need JS on public pages (Combobox for city autocomplete, Sheet for the mobile filter drawer, Dialog for the photo lightbox, Select for sorting) mount as islands using the *real* shadcn-vue components from `resources/js/components/ui/`.

⚠️ **Tailwind 4 gotcha:** Tailwind 4 auto-detects sources but will not scan `resources/views` by default in this setup. Add `@source "../views";` to `app.css` or every Blade utility class gets purged in production — a build that looks fine in dev and ships unstyled.

Net result: public pages ship ~25–35KB of JS and look pixel-identical to the admin.

---

## Architecture

### Two rendering worlds

| Surface | Stack | Why |
|---|---|---|
| Public pages (`/`, city, service, clinic profiles) | **Blade** + Vue islands | Complete HTML on first byte, no Node process in production, best LCP/TTFB |
| Interactive search (`/cauta`) | Blade shell + full Vue island | `noindex` — this is the app, not the SEO surface |
| Admin + owner panel (`/admin`, `/cont`) | **Inertia 3 + Vue 3 + shadcn-vue** | SPA ergonomics where SEO is irrelevant; starter kit gives it to us |

**Island mechanism** — a second Vite entry, `resources/js/public.ts`, scans for `[data-vue]` and mounts the named component with props from `data-props`:

```ts
const registry = {
  ClinicMap:     () => import('./islands/ClinicMap.vue'),
  SearchFilters: () => import('./islands/SearchFilters.vue'),
  CityCombobox:  () => import('./islands/CityCombobox.vue'),
}
document.querySelectorAll<HTMLElement>('[data-vue]').forEach(async el => {
  const comp = (await registry[el.dataset.vue!]()).default
  createApp(comp, JSON.parse(el.dataset.props ?? '{}')).mount(el)
})
```

### URL taxonomy (Romanian — this is the SEO backbone)

| Pattern | Example | Indexed |
|---|---|---|
| `/` | home | ✅ |
| `/stomatologi/{oras}` | `/stomatologi/iasi` | ✅ |
| `/stomatologi/{oras}/{serviciu}` | `/stomatologi/iasi/implant-dentar` | ✅ *if ≥3 clinics* |
| `/stomatologi/judetul-{judet}` | `/stomatologi/judetul-iasi` | ✅ |
| `/clinica/{slug}` | `/clinica/dental-art-iasi` | ✅ |
| `/servicii/{serviciu}` | `/servicii/ortodontie` | ✅ |
| `/cauta?...` | filtered map search | ❌ `noindex, follow` |
| `/adauga-clinica`, `/cont/*`, `/admin/*` | | ❌ |

Rules that stop this becoming a crawl trap:
- **Only the taxonomy above is indexable.** Every faceted combination lives under `/cauta` and is `noindex`. This single decision prevents infinite facet URLs — the most common way directory sites destroy their own crawl budget.
- **Thin-content guard:** `city × service` pages render only at ≥3 published clinics (configurable); below that → 301 to the city page. Backed by the cached `city_service_stats` table.
- **Pagination** at `?pagina=N` is self-canonical (never canonical-to-page-1), with real `<a href>` links.
- **`slug_history`** table + middleware issues 301s on rename, so earned rankings survive.
- City-slug collisions (Romania repeats commune names) resolve to `{oras}-{judet}`.

### Database (MySQL 8)

Charset `utf8mb4`, collation **`utf8mb4_0900_ai_ci`** — set explicitly in `config/database.php`. It is accent- *and* case-insensitive, so `Iași` matches `iasi` with no `unaccent` equivalent. This solves the diacritics problem that would otherwise wreck Romanian search on MySQL.

Tables:
- `counties` — id, name, slug, auto_code (IS, B, CJ…)
- `cities` — id, county_id, name, slug, type, lat, lng, population
- `clinics` — id, name, slug, city_id, address, lat, lng, `location`, phone, email, website, description, status (`draft|pending|published|rejected`), `cnas_contract` (bool — a major real-world filter for Romanian dental), `is_demo` (bool), claimed_by, verified_at, rating_avg, rating_count
- `services` — implant dentar, ortodonție, albire dentară, protetică, endodonție, chirurgie orală, stomatologie pediatrică, parodontologie
- `clinic_service`, `opening_hours`, `clinic_photos`, `reviews`, `claims`, `slug_history`
- `city_service_stats` — (city_id, service_id, clinic_count), rebuilt nightly; powers the thin-content guard and sitemaps without N+1 counts

**Spatial column — note the axis-order footgun.** MySQL applies EPSG axis order to SRID 4326, meaning **latitude first**. Reversing it puts every clinic in the Gulf of Guinea, and it's the #1 bug in MySQL geo code:

```sql
ALTER TABLE clinics
  ADD COLUMN location POINT SRID 4326 NOT NULL
    GENERATED ALWAYS AS (ST_SRID(POINT(lat, lng), 4326)) STORED,  -- lat FIRST
  ADD SPATIAL INDEX clinics_location_spatial (location);
```
`lat`/`lng` are `DECIMAL(10,7) NOT NULL DEFAULT 0` (the generated column must be `NOT NULL` to be spatially indexed); a validation rule blocks `published` until geocoded.

**Radius search that actually uses the index** — `ST_Distance_Sphere` alone cannot, so prefilter with a PHP-computed bbox via `MBRContains` (index-backed), then refine and sort:

```sql
SELECT c.*, ST_Distance_Sphere(c.location, ST_SRID(POINT(?, ?), 4326)) AS distance_m
FROM clinics c
WHERE MBRContains(ST_GeomFromText(?, 4326), c.location)   -- bbox, uses SPATIAL INDEX
  AND ST_Distance_Sphere(c.location, ST_SRID(POINT(?, ?), 4326)) <= ?
  AND c.status = 'published'
ORDER BY distance_m LIMIT 20;
```
Encapsulated in `Clinic::scopeNear($lat, $lng, $radiusKm)` so axis order lives in exactly one place. `ST_Distance_Sphere` returns **meters**.

Text search v1: `FULLTEXT` on `clinics(name, description, address)` + `LIKE` fallback. MySQL has no trigram/typo tolerance — a deliberate v1 limitation; if it bites, add Laravel Scout + Meilisearch rather than fighting MySQL.

### Roles

A `role` enum on `users` (`admin | moderator | clinic_owner | user`) plus Laravel policies — no extra package. (Your Smilesoft project hand-rolls RBAC too, so this matches your existing habit.) `ClinicPolicy` gates owner edits to clinics they've successfully claimed.

### Structured data & meta

- Clinic pages: JSON-LD `Dentist` (a `MedicalBusiness`) with `address`, `geo`, `openingHoursSpecification`, `telephone`, `priceRange`, and `aggregateRating` **only when real reviews exist**
- Listing pages: `ItemList` + `BreadcrumbList`
- One `SeoMeta` value object + `<x-seo-head>` Blade component renders title/description/canonical/OG/JSON-LD consistently
- `<html lang="ro">`, self-referencing canonicals on every indexable page
- `/sitemap.xml` index → `sitemap-orase.xml`, `sitemap-servicii.xml`, `sitemap-clinici-{n}.xml` (10k URLs/chunk) with real `lastmod`
- `robots.txt` disallows `/cauta`, `/admin`, `/cont`

### Core Web Vitals guardrails

Leaflet lazy-loads via `IntersectionObserver` (click-to-load on mobile), inside a fixed aspect-ratio box to prevent CLS. The **clinic list is always in the HTML** — the map only visualizes it. Photos convert to WebP on upload with explicit `width`/`height` and `loading="lazy"`.

---

## ⚠️ Demo data must never reach production

The seeded clinics are fictional. Fictional dental practices with fictional ratings, indexed by Google, would mislead patients and violate Google's spam policies for medical/local content. Therefore:
- Every seeded clinic carries `is_demo = true`
- A global scope excludes `is_demo` rows from public queries and sitemaps whenever `APP_ENV === 'production'`
- `php artisan clinics:purge-demo` wipes them, run as a pre-launch step

Names will be plainly invented (`Dental Art Studio`, `Clinica Stomatologică NovaDent`, `SmileLab Copou`) and must not match real Iași/București practices.

---

## Implementation phases

**Phase 1 — Scaffold.** `laravel new mydentist --vue` (Inertia 3 + Vue + TS + Tailwind 4 + shadcn-vue + Fortify). Add the `public.ts` Vite entry and `@source "../views"` to `app.css`. MySQL connection with `utf8mb4_0900_ai_ci`. Herd site at `mydentist.test`. All artisan/composer via `C:\Users\Webis\.config\herd\bin\php84.bat`.

**Phase 2 — Design system bridge.** Publish the shadcn-vue components we need (`button card badge input select dialog sheet table dropdown-menu form sonner command tabs avatar chart`). Build the Blade mirrors under `resources/views/components/ui/`. Verify a Blade button and a Vue button render identically in light and dark mode.

**Phase 3 — Geography + taxonomy.** Migrations + seeders for `counties` and `cities` (full Romanian SIRUTA set with lat/lng, so the taxonomy scales beyond the two demo cities). Slug generation with collision handling. `services` seeder with the 8 specialties.

**Phase 4 — Clinic domain + demo seed.** `clinics` + spatial column + pivots + hours + photos. `scopeNear()`, `scopePublished()`, `scopeInCity()`. Geocoding service against Nominatim (queued, cached, 1 req/s). **`DemoClinicSeeder`: ~70 fictional clinics — ~40 Iași (centred 47.1585, 27.6014), ~30 București (44.4268, 26.1025)** — with plausible street addresses, jittered coordinates within city bounds, varied service mixes, opening hours, CNAS flags, and a handful of reviews.

**Phase 5 — Public Blade pages.** Home, city, city×service, county, service, clinic profile. `SeoMeta` + `<x-seo-head>`. JSON-LD builders. Thin-content guard + `slug_history` 301 middleware. Server-rendered pagination.

**Phase 6 — Search + map islands.** `/cauta` Blade shell + `SearchFilters.vue` / `ClinicMap.vue` / `CityCombobox.vue`. JSON endpoint returning clinics within the viewport bbox. Leaflet + markercluster, lazy-loaded. Filters: city, service, CNAS, rating, open-now, radius. shadcn `Sheet` for the mobile filter drawer.

**Phase 7 — Registration, claims, owner panel.** Public `/adauga-clinica` → `pending`. Claim flow: request → email verification (prefer an address on the clinic's own domain) → admin approval → owner assignment. Inertia owner panel at `/cont` for hours, services, photos, description.

**Phase 8 — Admin dashboard** (`/admin`, on the starter kit's sidebar layout):
- **Overview** — KPI cards (clinics by status, pending moderation, open claims, unmoderated reviews) + a registrations-over-time chart via shadcn-vue `Chart`
- **Clinics** — DataTable with server-side search/sort/filter/pagination, bulk actions, edit form with a Leaflet map picker for lat/lng, services multi-select, opening-hours editor, drag-reorder photo uploads
- **Moderation queue** — approve/reject pending clinics with a reason, notifying the submitter
- **Claims** — review evidence, approve/reject, assign ownership
- **Reviews** — moderate, hide, reply
- **Users** — role assignment
- **Taxonomy** — counties, cities, services CRUD
- **SEO tools** — regenerate sitemaps, rebuild `city_service_stats`, list thin-content pages, purge demo data
- Forms via vee-validate + zod, feedback via `sonner` toasts

**Phase 9 — SEO plumbing + launch.** Sitemap generator + scheduler, `robots.txt`, nightly stats rebuild, 404/410 handling, Search Console verification, `deploy.sh` modeled on [Smilesoft's](../Smilesoft/deploy.sh) (git pull → composer → npm build → migrate → `optimize:clear` + re-cache).

---

## Files this creates (representative)

- `app/Models/{Clinic,City,County,Service,Claim,Review,OpeningHour,ClinicPhoto}.php`
- `app/Support/Seo/{SeoMeta.php,JsonLd/DentistSchema.php,JsonLd/ItemListSchema.php}`
- `app/Services/{GeocodingService.php,ClinicSearchService.php}`
- `app/Http/Controllers/Public/{HomeController,CityController,ServiceController,ClinicController,SearchController}.php`
- `app/Http/Controllers/Admin/{DashboardController,ClinicController,ModerationController,ClaimController,ReviewController,UserController}.php`
- `app/Http/Middleware/RedirectLegacySlug.php`, `app/Policies/ClinicPolicy.php`
- `app/Console/Commands/{GenerateSitemaps,RebuildCityServiceStats,PurgeDemoClinics}.php`
- `resources/views/public/**`, `resources/views/components/ui/**` (Blade shadcn mirrors)
- `resources/js/islands/*.vue`, `resources/js/pages/admin/**`, `resources/js/components/ui/**`
- `database/seeders/{CountySeeder,CitySeeder,ServiceSeeder,DemoClinicSeeder}.php`

---

## Verification

1. **Spatial correctness first** — a test asserting `Clinic::near(47.1585, 27.6014, 10)` returns seeded Iași clinics and excludes every București one. If axis order is wrong this fails immediately, which is the point.
2. **SEO smoke tests** — assert `/stomatologi/iasi` returns 200 with clinic names present in the *raw* body (`assertSee`, no JS), a self-referencing canonical, and valid `ItemList` JSON-LD; assert a 2-clinic `city × service` page 301s to the city page.
3. **`curl` the real thing** — `curl -s https://mydentist.test/stomatologi/iasi | grep -c clinic-card` confirms content exists without JavaScript. This is the definitive check that the whole SEO strategy works.
4. **Production build styling check** — `npm run build`, then load a Blade page from the built assets to confirm `@source "../views"` kept the Blade utilities. Dev mode will *not* catch this.
5. **Theme parity** — load `/` and `/admin` side by side in light and dark mode; the Blade and Vue buttons/cards must be indistinguishable.
6. **Browser check** — run via Herd, use the browser tools on the city page and `/cauta`: map lazy-loads, markers cluster, filters update the list, mobile `Sheet` drawer works at 375px.
7. **Rich Results Test** — paste a clinic URL into Google's validator; expect a clean `Dentist` entity.
8. **Lighthouse** on a city page — LCP < 2.5s, CLS < 0.1 with the map present.
9. `php artisan sitemap:generate` → fetch `/sitemap.xml`, confirm chunking, `lastmod`, and that **no `is_demo` clinic appears**.

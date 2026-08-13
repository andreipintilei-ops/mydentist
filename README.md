# MyDentist

Director de clinici stomatologice din România — un „mini yellow-pages" cu hartă,
filtre de căutare și pagini optimizate pentru căutări de tipul *„stomatolog în Iași"*.

## Stack

| Strat | Tehnologie |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Pagini publice (SEO) | Blade, randate integral pe server |
| Panou admin / clinici | Inertia 3 + Vue 3 + TypeScript |
| UI | Tailwind 4 + shadcn-vue |
| Bază de date | MySQL 8+ (coloană spațială `POINT SRID 4326`) |
| Hartă | Leaflet + OpenStreetMap |

### De ce două moduri de randare

Valoarea produsului stă în traficul organic, așa că fiecare pagină publică trebuie
să livreze HTML complet la prima cerere — fără să depindă de JavaScript și fără un
proces Node de SSR în producție. Paginile publice sunt Blade; doar componentele
cu adevărat interactive (harta, filtrele, autocomplete-ul de orașe) sunt montate
ca *islands* Vue prin `resources/js/public.ts`.

Panoul autentificat nu are miză SEO, deci folosește Inertia + Vue clasic.

Ambele lumi împart aceleași token-uri de temă din `resources/css/app.css`.
Componentele Blade din `resources/views/components/ui/` sunt oglinzi ale
componentelor shadcn-vue din `resources/js/components/ui/` — aceleași șiruri de
clase, copiate manual. Ruta `/_ui-parity` (doar local) le afișează una lângă alta
pentru comparație.

## Instalare locală

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Creează cele două scheme:

```sql
CREATE DATABASE mydentist CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE mydentist_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

Apoi:

```bash
php artisan migrate --seed
npm run build
```

## Detalii care nu sunt evidente

**Colația `utf8mb4_0900_ai_ci` nu e opțională.** Este insensibilă la diacritice
*și* la majuscule, deci un utilizator care scrie „iasi" fără diacritice găsește
totuși „Iași". Fără ea ar fi nevoie de o coloană normalizată separată. Există un
test care pică dacă cineva o schimbă.

**Testele rulează pe MySQL real, nu pe SQLite.** Stratul de date depinde de
funcții pe care SQLite nu le are: colația de mai sus, coloana generată
`POINT SRID 4326` cu `SPATIAL INDEX` și `ST_Distance_Sphere`. Pe SQLite suita ar
fi verde în timp ce aplicația reală ar fi stricată.

**MySQL folosește ordinea latitudine-întâi pentru SRID 4326.** Inversarea pune
toate clinicile în Golful Guineei. Ordinea este încapsulată într-un singur loc,
în scope-ul `Clinic::near()`.

**Pe Windows, `php` din PATH poate fi o versiune veche (XAMPP).** Laravel 13 cere
PHP ≥ 8.3, iar plugin-ul Wayfinder apelează `php artisan` în timpul build-ului.
`vite.config.ts` detectează automat cel mai nou PHP din Herd și îl pune în față
în PATH, deci `npm run build` merge fără să modifici PATH-ul global.

## Comenzi utile

```bash
php artisan test
./vendor/bin/pint
npm run lint
npm run build
```

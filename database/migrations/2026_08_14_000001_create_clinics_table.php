<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // The /clinica/{slug} URL segment. Globally unique and permanent once
            // published; renames are handled by slug_history + a 301 middleware
            // (Phase 5) so earned rankings survive.
            $table->string('slug')->unique();

            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('address');

            // DECIMAL(10,7) NOT NULL DEFAULT 0: the generated `location` column
            // must be NOT NULL to be spatially indexed, and a generated column
            // cannot be NOT NULL if its inputs can be NULL. 0/0 is the Gulf of
            // Guinea — harmless as a placeholder because a validation rule blocks
            // `published` until the clinic is geocoded to real coordinates.
            $table->decimal('lat', 10, 7)->default(0);
            $table->decimal('lng', 10, 7)->default(0);
            // A STORED generated `location POINT` column is added below via raw
            // SQL — the Laravel schema builder cannot express it, and the exact
            // form differs between MariaDB and MySQL 8 (see below).

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();

            // draft     — owner is still editing, never public
            // pending   — submitted, awaiting moderation
            // published — live and indexable
            // rejected  — moderation declined, kept for the audit trail
            $table->enum('status', ['draft', 'pending', 'published', 'rejected'])
                ->default('pending');

            // Whether the clinic has a contract with CNAS (the national health
            // insurance house). A major real-world filter for Romanian dental
            // patients, so it is a first-class column, not an attribute.
            $table->boolean('cnas_contract')->default(false);

            // Fictional seed data. A global scope hides these in production so
            // invented practices never reach Google (see Clinic model + the
            // clinics:purge-demo command).
            $table->boolean('is_demo')->default(false);

            $table->foreignId('claimed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Denormalised rating aggregates, recomputed from reviews. Kept on the
            // row so listing pages never N+1 an AVG() across thousands of reviews.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            $table->timestamps();

            // Listing pages filter by (status, city); sitemaps and the demo scope
            // filter by is_demo. Index the hot paths.
            $table->index(['status', 'city_id']);
            $table->index('is_demo');
        });

        // Generated STORED spatial column + SPATIAL INDEX for index-backed
        // radius search. The AXIS ORDER is the footgun and it differs by engine —
        // this migration and Clinic::scopeNear() are the ONLY two places it is
        // expressed, and they must agree:
        //
        //   MariaDB 10.4 — geometry is planar and has no SRID enforcement.
        //     ST_Distance_Sphere() expects POINT(LONGITUDE, LATITUDE), so the
        //     column stores POINT(lng, lat). No SRID clause (unsupported here).
        //
        //   MySQL 8 — SRID 4326 is geographic and uses EPSG axis order, i.e.
        //     LATITUDE FIRST: POINT(lat, lng) wrapped in ST_SRID(..., 4326).
        //
        // Get either one backwards and every clinic lands in the ocean off West
        // Africa — the classic geo bug.
        if (DB::getDriverName() === 'mysql') {
            // MySQL 8: SRID 4326 is geographic and enforces EPSG axis order, so
            // POINT(lat, lng). The column is a STORED generated column derived
            // from lat/lng — nothing to maintain in application code.
            DB::statement('ALTER TABLE clinics
                ADD COLUMN location POINT SRID 4326 NOT NULL
                    GENERATED ALWAYS AS (ST_SRID(POINT(lat, lng), 4326)) STORED AFTER lng,
                ADD SPATIAL INDEX clinics_location_spatial (location)');
        } else {
            // MariaDB 10.4: ST_Distance_Sphere expects POINT(lng, lat). MariaDB
            // forbids NOT NULL on a generated column, yet a SPATIAL INDEX requires
            // NOT NULL — so `location` is a REAL column kept in sync by triggers
            // rather than a generated one. The triggers are the MariaDB equivalent
            // of MySQL's GENERATED ALWAYS clause.
            DB::statement('ALTER TABLE clinics ADD COLUMN location POINT NOT NULL AFTER lng');
            DB::unprepared('CREATE TRIGGER clinics_location_bi BEFORE INSERT ON clinics
                FOR EACH ROW SET NEW.location = POINT(NEW.lng, NEW.lat)');
            DB::unprepared('CREATE TRIGGER clinics_location_bu BEFORE UPDATE ON clinics
                FOR EACH ROW SET NEW.location = POINT(NEW.lng, NEW.lat)');
            DB::statement('ALTER TABLE clinics ADD SPATIAL INDEX clinics_location_spatial (location)');
        }
    }

    public function down(): void
    {
        // Triggers are dropped with the table, but be explicit in case of a
        // partial rollback on MariaDB.
        DB::unprepared('DROP TRIGGER IF EXISTS clinics_location_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS clinics_location_bu');
        Schema::dropIfExists('clinics');
    }
};

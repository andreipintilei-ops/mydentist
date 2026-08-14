<?php

namespace Database\Seeders;

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\OpeningHour;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fictional demo clinics for Iași and București so the site has something to
 * render before real clinics register.
 *
 * EVERYTHING this seeds carries is_demo = true. A production global scope hides
 * these rows and `php artisan clinics:purge-demo` deletes them before launch —
 * invented dental practices must never reach Google. See the Clinic model.
 *
 * Idempotent: it wipes existing demo clinics first, so re-running never piles up
 * duplicates.
 */
class DemoClinicSeeder extends Seeder
{
    private const RO_NAMES = [
        'Andrei Popescu', 'Maria Ionescu', 'Elena Dumitru', 'Ioana Radu', 'Mihai Georgescu',
        'Ana Stoica', 'Cristina Munteanu', 'Alexandru Marin', 'Gabriela Nistor', 'Vlad Popa',
        'Raluca Diaconu', 'George Constantin', 'Diana Florea', 'Bogdan Matei', 'Simona Oprea',
    ];

    private const REVIEW_BODIES = [
        'Personal amabil și foarte profesionist. Recomand cu încredere!',
        'Am făcut un implant aici și totul a decurs fără durere. Mulțumesc echipei.',
        'Cabinet modern, curat, iar doctorul mi-a explicat pe îndelete tratamentul.',
        'Programare respectată la fix, prețuri corecte. Revin cu siguranță.',
        'Copilul meu nu se mai teme de dentist datorită doamnei doctor. Excelent!',
        'Tratament de ortodonție reușit, rezultatele se văd după câteva luni.',
        'Recepție prietenoasă și explicații clare pentru fiecare procedură.',
    ];

    public function run(): void
    {
        $iasi = City::where('slug', 'iasi')->first();
        $bucuresti = City::where('slug', 'bucuresti')->first();

        if (! $iasi || ! $bucuresti) {
            $this->command?->warn('DemoClinicSeeder skipped: run CitySeeder first (iasi/bucuresti missing).');

            return;
        }

        // Idempotent reset — demo rows only. Reviews/hours/pivots cascade.
        Clinic::query()->where('is_demo', true)->delete();

        $services = Service::all();

        $this->seedCity($iasi, 40, $services);
        $this->seedCity($bucuresti, 30, $services);

        $this->command?->info('Seeded 70 demo clinics (40 Iași, 30 București).');
    }

    private function seedCity(City $city, int $count, $services): void
    {
        Clinic::factory()
            ->count($count)
            ->demo()
            ->inCity($city)
            ->create()
            ->each(function (Clinic $clinic) use ($services): void {
                $this->attachServices($clinic, $services);
                $this->attachHours($clinic);
                $this->attachReviews($clinic);
                $this->varyStatus($clinic);
            });
    }

    private function attachServices(Clinic $clinic, $services): void
    {
        $clinic->services()->sync(
            $services->random(fake()->numberBetween(2, 6))->pluck('id')->all()
        );
    }

    /**
     * Mon–Fri open, Saturday shorter, Sunday closed — with a bit of variation so
     * the demo doesn't look copy-pasted. Some clinics split for a lunch break.
     */
    private function attachHours(Clinic $clinic): void
    {
        $opensWeekday = fake()->randomElement(['08:00', '09:00', '10:00']);
        $closesWeekday = fake()->randomElement(['18:00', '19:00', '20:00']);
        $hasLunchBreak = fake()->boolean(30);

        $rows = [];

        for ($day = 0; $day <= 4; $day++) { // Mon–Fri
            if ($hasLunchBreak) {
                $rows[] = $this->hourRow($clinic->id, $day, $opensWeekday, '13:00');
                $rows[] = $this->hourRow($clinic->id, $day, '14:00', $closesWeekday);
            } else {
                $rows[] = $this->hourRow($clinic->id, $day, $opensWeekday, $closesWeekday);
            }
        }

        // Saturday: often a short morning, sometimes closed.
        if (fake()->boolean(70)) {
            $rows[] = $this->hourRow($clinic->id, 5, '09:00', fake()->randomElement(['13:00', '14:00']));
        } else {
            $rows[] = $this->hourRow($clinic->id, 5, null, null, true);
        }

        // Sunday closed.
        $rows[] = $this->hourRow($clinic->id, 6, null, null, true);

        OpeningHour::insert($rows);
    }

    private function hourRow(int $clinicId, int $day, ?string $opens, ?string $closes, bool $closed = false): array
    {
        $now = Carbon::now();

        return [
            'clinic_id' => $clinicId,
            'day_of_week' => $day,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'is_closed' => $closed,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function attachReviews(Clinic $clinic): void
    {
        $count = fake()->numberBetween(0, 6);

        for ($i = 0; $i < $count; $i++) {
            Review::create([
                'clinic_id' => $clinic->id,
                'author_name' => fake()->randomElement(self::RO_NAMES),
                // Demo clinics skew positive (3–5) so ratings look plausible.
                'rating' => fake()->numberBetween(3, 5),
                'body' => fake()->randomElement(self::REVIEW_BODIES),
                'status' => 'published',
                'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            ]);
        }

        $clinic->recalculateRating();
    }

    /**
     * Most demo clinics are published; a few sit in pending/draft so the admin
     * moderation queue and status filters have something to show.
     */
    private function varyStatus(Clinic $clinic): void
    {
        $status = fake()->randomElement(array_merge(
            array_fill(0, 8, ClinicStatus::Published),
            [ClinicStatus::Pending, ClinicStatus::Draft],
        ));

        if ($status !== ClinicStatus::Published) {
            $clinic->update(['status' => $status]);
        }
    }
}

<?php

namespace Database\Factories;

use App\Enums\ClinicStatus;
use App\Models\City;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    protected $model = Clinic::class;

    /**
     * Invented brand components. Combined at random into names like
     * "Clinica Stomatologică NovaDent" or "SmileLab Studio" — deliberately
     * fictional so demo data never collides with a real Romanian practice.
     */
    private const PREFIXES = [
        'Clinica Stomatologică', 'Cabinet Stomatologic', 'Centrul Dentar', 'Clinica Dentară',
    ];

    private const BRANDS = [
        'NovaDent', 'SmileLab', 'DentaArt', 'OrthoPlus', 'DentaExpert', 'BrightDent',
        'ProDent', 'DentalCare', 'MediSmile', 'ClearDent', 'VitalDent', 'PerfectSmile',
        'DentaVita', 'OraSmile', 'EliteDent', 'DentaLux', 'SanoDent', 'ArtDent',
    ];

    private const SUFFIXES = ['', ' Studio', ' Center', ' Clinic', ''];

    public function definition(): array
    {
        $city = City::query()->inRandomOrder()->first();

        $name = $this->inventedName();

        // Default coordinates sit on the city centre (jittered) so a clinic made
        // straight from the factory is already mappable; ::inCity() refines it.
        [$lat, $lng] = $this->jitter($city?->lat ?? 45.9432, $city?->lng ?? 24.9668);

        return [
            'name' => $name,
            // Random suffix keeps slugs unique without a DB round-trip per row.
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'city_id' => $city?->id,
            'address' => fake()->streetAddress(),
            'lat' => $lat,
            'lng' => $lng,
            'phone' => '+40 '.fake()->numerify('7## ### ###'),
            'email' => fake()->safeEmail(),
            'website' => fake()->boolean(60) ? 'https://'.fake()->domainName() : null,
            'description' => fake()->paragraph(4),
            'status' => ClinicStatus::Published,
            'cnas_contract' => fake()->boolean(55),
            'is_demo' => false,
            'rating_avg' => 0,
            'rating_count' => 0,
        ];
    }

    /**
     * Place the clinic in a specific city, jittered within roughly $spreadKm of
     * its centre so demo pins scatter across the map instead of stacking.
     */
    public function inCity(City $city, float $spreadKm = 4.0): static
    {
        return $this->state(function () use ($city, $spreadKm) {
            [$lat, $lng] = $this->jitter($city->lat, $city->lng, $spreadKm);

            return ['city_id' => $city->id, 'lat' => $lat, 'lng' => $lng];
        });
    }

    public function demo(): static
    {
        return $this->state(['is_demo' => true]);
    }

    public function status(ClinicStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    /**
     * Offset a coordinate by a uniform random amount within ~$spreadKm.
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    private function jitter(float $lat, float $lng, float $spreadKm = 4.0): array
    {
        $latDelta = $spreadKm / 111.32;
        $lngDelta = $spreadKm / (111.32 * max(cos(deg2rad($lat)), 0.01));

        return [
            round($lat + fake()->randomFloat(6, -$latDelta, $latDelta), 7),
            round($lng + fake()->randomFloat(6, -$lngDelta, $lngDelta), 7),
        ];
    }

    private function inventedName(): string
    {
        $prefix = fake()->boolean(60) ? fake()->randomElement(self::PREFIXES).' ' : '';
        $brand = fake()->randomElement(self::BRANDS);
        $suffix = fake()->randomElement(self::SUFFIXES);

        return trim($prefix.$brand.$suffix);
    }
}

<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\County;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * Curated set: every county seat plus the larger municipii/orașe, with
     * real coordinates.
     *
     * This is deliberately NOT the full SIRUTA register (~13,000 localities).
     * Hand-writing that many rows would mean inventing coordinates, and wrong
     * coordinates are worse than absent ones for a map-driven directory. The
     * schema is ready for a full import whenever a licensed SIRUTA dataset is
     * dropped in — see `cities:import` in the project notes.
     *
     * Columns: [county auto_code, name, type, lat, lng, population]
     */
    public const CITIES = [
        // ---- County seats ----
        ['AB', 'Alba Iulia', 'municipiu', 46.0733, 23.5805, 63536],
        ['AR', 'Arad', 'municipiu', 46.1866, 21.3123, 145078],
        ['AG', 'Pitești', 'municipiu', 44.8565, 24.8692, 155383],
        ['BC', 'Bacău', 'municipiu', 46.5670, 26.9146, 144307],
        ['BH', 'Oradea', 'municipiu', 47.0465, 21.9189, 196367],
        ['BN', 'Bistrița', 'municipiu', 47.1341, 24.4914, 78877],
        ['BT', 'Botoșani', 'municipiu', 47.7486, 26.6694, 106847],
        ['BV', 'Brașov', 'municipiu', 45.6579, 25.6012, 237589],
        ['BR', 'Brăila', 'municipiu', 45.2692, 27.9575, 154686],
        ['B', 'București', 'municipiu', 44.4268, 26.1025, 1716961],
        ['BZ', 'Buzău', 'municipiu', 45.1500, 26.8333, 115494],
        ['CS', 'Reșița', 'municipiu', 45.3008, 21.8890, 58393],
        ['CL', 'Călărași', 'municipiu', 44.2058, 27.3306, 65181],
        ['CJ', 'Cluj-Napoca', 'municipiu', 46.7712, 23.6236, 286598],
        ['CT', 'Constanța', 'municipiu', 44.1598, 28.6348, 263688],
        ['CV', 'Sfântu Gheorghe', 'municipiu', 45.8667, 25.7833, 54312],
        ['DB', 'Târgoviște', 'municipiu', 44.9250, 25.4570, 72071],
        ['DJ', 'Craiova', 'municipiu', 44.3302, 23.7949, 269506],
        ['GL', 'Galați', 'municipiu', 45.4353, 28.0080, 217851],
        ['GR', 'Giurgiu', 'municipiu', 43.9037, 25.9699, 54655],
        ['GJ', 'Târgu Jiu', 'municipiu', 45.0417, 23.2744, 75696],
        ['HR', 'Miercurea Ciuc', 'municipiu', 46.3600, 25.8017, 38966],
        ['HD', 'Deva', 'municipiu', 45.8833, 22.9000, 55632],
        ['IL', 'Slobozia', 'municipiu', 44.5639, 27.3661, 45891],
        ['IS', 'Iași', 'municipiu', 47.1585, 27.6014, 290422],
        ['IF', 'Buftea', 'oras', 44.5606, 25.9469, 22178],
        ['MM', 'Baia Mare', 'municipiu', 47.6567, 23.5850, 123738],
        ['MH', 'Drobeta-Turnu Severin', 'municipiu', 44.6369, 22.6597, 86475],
        ['MS', 'Târgu Mureș', 'municipiu', 46.5386, 24.5514, 134290],
        ['NT', 'Piatra Neamț', 'municipiu', 46.9275, 26.3708, 85055],
        ['OT', 'Slatina', 'municipiu', 44.4300, 24.3708, 70293],
        ['PH', 'Ploiești', 'municipiu', 44.9469, 26.0367, 180540],
        ['SM', 'Satu Mare', 'municipiu', 47.7900, 22.8800, 91520],
        ['SJ', 'Zalău', 'municipiu', 47.1911, 23.0572, 56202],
        ['SB', 'Sibiu', 'municipiu', 45.7983, 24.1256, 147245],
        ['SV', 'Suceava', 'municipiu', 47.6514, 26.2556, 92121],
        ['TR', 'Alexandria', 'municipiu', 43.9739, 25.3331, 45434],
        ['TM', 'Timișoara', 'municipiu', 45.7489, 21.2087, 250849],
        ['TL', 'Tulcea', 'municipiu', 45.1667, 28.8000, 73707],
        ['VS', 'Vaslui', 'municipiu', 46.6407, 27.7276, 55407],
        ['VL', 'Râmnicu Vâlcea', 'municipiu', 45.1047, 24.3750, 98776],
        ['VN', 'Focșani', 'municipiu', 45.6961, 27.1864, 79315],

        // ---- Iași county: the launch market, so covered a little deeper ----
        ['IS', 'Pașcani', 'municipiu', 47.2500, 26.7261, 33745],
        ['IS', 'Târgu Frumos', 'oras', 47.2078, 27.0106, 10475],
        ['IS', 'Hârlău', 'oras', 47.4297, 26.9008, 10906],

        // ---- Ilfov: the București metro belt, the other launch market ----
        ['IF', 'Voluntari', 'oras', 44.4917, 26.1833, 42944],
        ['IF', 'Otopeni', 'oras', 44.5500, 26.0667, 17510],
        ['IF', 'Popești-Leordeni', 'oras', 44.3833, 26.1667, 29264],
        ['IF', 'Bragadiru', 'oras', 44.3742, 25.9758, 14988],
        ['IF', 'Pantelimon', 'oras', 44.4500, 26.2167, 26261],
        ['IF', 'Chitila', 'oras', 44.5083, 25.9833, 14184],

        // ---- Other sizeable towns, for taxonomy breadth ----
        ['CJ', 'Turda', 'municipiu', 46.5667, 23.7833, 47744],
        ['CJ', 'Dej', 'municipiu', 47.1400, 23.8783, 33497],
        ['SB', 'Mediaș', 'municipiu', 46.1667, 24.3500, 47204],
        ['MS', 'Reghin', 'municipiu', 46.7750, 24.7050, 33281],
        ['BC', 'Onești', 'municipiu', 46.2500, 26.7667, 39172],
        ['NT', 'Roman', 'municipiu', 46.9275, 26.9269, 50713],
        ['VS', 'Bârlad', 'municipiu', 46.2278, 27.6689, 55837],
        ['HD', 'Hunedoara', 'municipiu', 45.7500, 22.9000, 60525],
        ['HD', 'Petroșani', 'municipiu', 45.4167, 23.3667, 37160],
        ['TM', 'Lugoj', 'municipiu', 45.6886, 21.9033, 40361],
        ['SV', 'Fălticeni', 'municipiu', 47.4600, 26.3000, 25396],
        ['SV', 'Rădăuți', 'municipiu', 47.8442, 25.9175, 28118],
        ['PH', 'Câmpina', 'municipiu', 45.1272, 25.7367, 32935],
        ['CT', 'Mangalia', 'municipiu', 43.8167, 28.5833, 36364],
        ['CT', 'Medgidia', 'municipiu', 44.2500, 28.2667, 39780],
        ['CT', 'Năvodari', 'oras', 44.3167, 28.6000, 34669],
        ['BV', 'Făgăraș', 'municipiu', 45.8450, 24.9731, 30714],
        ['BV', 'Săcele', 'municipiu', 45.6167, 25.6833, 30798],
        ['GL', 'Tecuci', 'municipiu', 45.8500, 27.4333, 34871],
        ['AG', 'Câmpulung', 'municipiu', 45.2683, 25.0472, 31767],
        ['AG', 'Curtea de Argeș', 'municipiu', 45.1408, 24.6772, 27359],
        ['MM', 'Sighetu Marmației', 'municipiu', 47.9333, 23.8833, 37640],
        ['BH', 'Salonta', 'municipiu', 46.8000, 21.6500, 17042],
        ['AB', 'Aiud', 'municipiu', 46.3117, 23.7222, 22876],
        ['AB', 'Sebeș', 'municipiu', 45.9600, 23.5700, 27019],
    ];

    public function run(): void
    {
        $counties = County::pluck('id', 'auto_code');

        // Romania reuses town names across counties. Any name appearing more
        // than once gets its county appended so the two never fight over the
        // same /stomatologi/{oras} URL. Computed up-front from the dataset so
        // BOTH colliding towns get a suffix — suffixing only the second one
        // would hand the bare slug to whichever happened to seed first.
        $nameCounts = array_count_values(array_column(self::CITIES, 1));

        foreach (self::CITIES as [$code, $name, $type, $lat, $lng, $population]) {
            $countyId = $counties[$code] ?? null;

            if (! $countyId) {
                $this->command?->warn("Skipping {$name}: unknown county code {$code}.");

                continue;
            }

            $slug = Str::slug($name);

            if ($nameCounts[$name] > 1) {
                $slug .= '-'.Str::slug(CountySeeder::COUNTIES[$code]);
            }

            City::updateOrCreate(
                ['county_id' => $countyId, 'name' => $name],
                [
                    'slug' => $slug,
                    'type' => $type,
                    'lat' => $lat,
                    'lng' => $lng,
                    'population' => $population,
                ],
            );
        }
    }
}

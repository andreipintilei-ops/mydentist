<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * The dental specialties users actually search for in Romanian.
     *
     * The descriptions are not decoration: each city × service landing page
     * uses its service description as the page lede, which is what stops
     * /stomatologi/iasi/implant-dentar and /stomatologi/cluj-napoca/implant-dentar
     * from reading as near-duplicate pages to a crawler.
     *
     * Columns: [name, description]
     */
    public const SERVICES = [
        [
            'Implant dentar',
            'Înlocuirea rădăcinii unui dinte lipsă cu un implant din titan, peste care se fixează o coroană. Soluție de durată pentru unul sau mai mulți dinți pierduți.',
        ],
        [
            'Ortodonție',
            'Alinierea dinților și corectarea mușcăturii cu aparate dentare fixe, mobile sau gutiere transparente, atât pentru copii cât și pentru adulți.',
        ],
        [
            'Albire dentară',
            'Tratamente profesionale de albire în cabinet sau la domiciliu, pentru îndepărtarea petelor și redarea nuanței naturale a dinților.',
        ],
        [
            'Protetică dentară',
            'Reconstrucția dinților deteriorați sau lipsă prin coroane, punți, fațete și proteze fixe ori mobile.',
        ],
        [
            'Endodonție',
            'Tratamentul canalelor radiculare, folosit pentru a salva un dinte afectat de carie profundă sau infecție, fără extracție.',
        ],
        [
            'Chirurgie orală',
            'Extracții simple și chirurgicale, inclusiv măsele de minte incluse, precum și intervenții de adiție osoasă înaintea implanturilor.',
        ],
        [
            'Stomatologie pediatrică',
            'Consultații și tratamente adaptate copiilor, cu accent pe prevenție, sigilări și o primă experiență fără teamă la dentist.',
        ],
        [
            'Parodontologie',
            'Diagnosticul și tratamentul afecțiunilor gingiilor și ale țesuturilor care susțin dintele, de la gingivită la parodontită avansată.',
        ],
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $index => [$name, $description]) {
            Service::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $index,
                ],
            );
        }
    }
}

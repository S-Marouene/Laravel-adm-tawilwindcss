<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/** Idempotent seeder for the rescue certificate (شهادة نجدة وإنقاذ) document checklist. */
class RescueDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $i => $doc) {
            DocumentType::updateOrCreate(
                ['service_key' => DocumentType::SERVICE_RESCUE_CERTIFICATE, 'name_ar' => $doc['ar']],
                [
                    'name_fr' => $doc['fr'],
                    'name_en' => $doc['en'],
                    'is_conditional' => $doc['conditional'] ?? false,
                    'sort_order' => $i + 1,
                    'services' => [DocumentType::SERVICE_RESCUE_CERTIFICATE],
                ],
            );
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية للمتضرر',
                'fr' => "Copie de la carte d'identité nationale de la victime",
                'en' => "Copy of the national identity card of the victim",
                'conditional' => false,
            ],
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية للأب أو الأم (إذا كان المتضرر إبنًا دون 18 سنة)',
                'fr' => "Copie de la carte d'identité nationale du père ou de la mère (si la victime est mineure de moins de 18 ans)",
                'en' => "Copy of the national identity card of the father or mother (if the victim is under 18)",
                'conditional' => true,
            ],
            [
                'ar' => 'طلب شهادة نجدة وإنقاذ للزوج + نسخة من بطاقة التعريف الوطنية للزوجة المنصوص عليها حرم أو مضمون زواج جديد + نسخة من بطاقة التعريف الوطنية للزوج',
                'fr' => "Demande de certificat de secours et de sauvetage pour l'époux + copie de la CIN de l'épouse (muharram) ou attestation de nouveau mariage + copie de la CIN de l'époux",
                'en' => "Rescue certificate request for the spouse + copy of the wife's national ID card (muharram) or new marriage certificate + copy of the husband's national ID card",
                'conditional' => true,
            ],
            [
                'ar' => 'مطبوعة شهادة نجدة وإنقاذ',
                'fr' => 'Formulaire de certificat de secours et de sauvetage',
                'en' => 'Rescue and relief certificate form',
                'conditional' => false,
            ],
        ];
    }
}

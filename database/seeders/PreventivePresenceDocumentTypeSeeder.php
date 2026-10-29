<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/** Idempotent seeder for the preventive presence document checklist. */
class PreventivePresenceDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $i => $doc) {
            DocumentType::updateOrCreate(
                ['service_key' => DocumentType::SERVICE_PREVENTIVE_PRESENCE, 'name_ar' => $doc['ar']],
                [
                    'name_fr' => $doc['fr'],
                    'name_en' => $doc['en'],
                    'sort_order' => $i + 1,
                    'services' => [DocumentType::SERVICE_PREVENTIVE_PRESENCE],
                ],
            );
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar' => 'مطبوعة طلب القيام بخدمة',
                'fr' => 'Formulaire de demande de prestation',
                'en' => 'Service request form',
            ],
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية',
                'fr' => "Copie de la carte d'identité nationale",
                'en' => "Copy of the national identity card",
            ],
        ];
    }
}

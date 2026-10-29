<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/** Idempotent seeder for the works notification (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء) document checklist. */
class WorksDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $i => $doc) {
            DocumentType::updateOrCreate(
                ['service_key' => DocumentType::SERVICE_WORKS_NOTIFICATION, 'name_ar' => $doc['ar']],
                [
                    'name_fr' => $doc['fr'],
                    'name_en' => $doc['en'],
                    'is_conditional' => $doc['conditional'] ?? false,
                    'sort_order' => $i + 1,
                    'services' => [DocumentType::SERVICE_WORKS_NOTIFICATION],
                ],
            );
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar' => 'بطاقة إرشادات يتم سحبها من مقر الإدارة الجهوية للحماية المدنية أو من مقر فرقة الحماية المدنية المعنية',
                'fr' => "Fiche consultative à retirer du siège de la Direction Régionale de la Protection Civile ou du siège de l'unité concernée",
                'en' => "Consultation card withdrawn from the Regional Directorate of Civil Protection or the relevant Civil Protection unit",
                'conditional' => false,
            ],
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية لمالك البناية، إذا كان شخصًا طبيعيًا، أو للممثل القانوني، إذا كان شخصًا معنويًا',
                'fr' => "Copie de la CIN du propriétaire du bâtiment (personne physique) ou de son représentant légal (personne morale)",
                'en' => "Copy of the national ID card of the building owner (if a natural person) or of the legal representative (if a legal entity)",
                'conditional' => false,
            ],
            [
                'ar' => 'مضمون من الإشعار القانوني بالرائد الرسمي للجمهورية التونسية لعقد تأسيس الشخص المعنوي',
                'fr' => "Extrait de l'annonce légale au Journal Officiel de la République Tunisienne constitutive de la personne morale",
                'en' => "Extract of the legal notice in the Official Gazette of the Tunisian Republic establishing the legal entity",
                'conditional' => true,
            ],
        ];
    }
}

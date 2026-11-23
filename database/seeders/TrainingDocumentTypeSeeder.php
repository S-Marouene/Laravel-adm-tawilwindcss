<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Idempotent seeder for the training / formation service
 * (خدمات التكوين والرسكلة بمقابل) document checklist.
 *
 * Documents come from the official procedure sheet:
 * 1. مطبوعة طلب القيام بخدمة (استمارة رسمية)
 * 2. نسخة من بطاقة تعريف وطنية للمترشح
 * 3. نسخة من شهادة تكوين (في صورة الرسكلة فقط)
 * 4. مطلب مشاركة في دورة تكوينية
 * 5. وصل خلاص معلوم الخدمة
 * 6. قائمة المشاركين + إذن شراء (للمؤسسات)
 */
class TrainingDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $i => $doc) {
            DocumentType::updateOrCreate(
                [
                    'service_key' => DocumentType::SERVICE_TRAINING,
                    'name_ar'     => $doc['ar'],
                ],
                [
                    'name_fr'        => $doc['fr'],
                    'name_en'        => $doc['en'],
                    'is_conditional' => $doc['conditional'] ?? false,
                    'sort_order'     => $i + 1,
                    'services'       => [DocumentType::SERVICE_TRAINING],
                ],
            );
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar'          => 'مطبوعة طلب القيام بخدمة',
                'fr'          => 'Formulaire de demande de service',
                'en'          => 'Service request form',
                'conditional' => false,
            ],
            [
                'ar'          => 'نسخة من بطاقة التعريف الوطنية للمترشح',
                'fr'          => "Copie de la carte d'identité nationale du candidat",
                'en'          => "Copy of the candidate's national identity card",
                'conditional' => false,
            ],
            [
                'ar'          => 'نسخة من شهادة التكوين السابقة (في صورة الرسكلة)',
                'fr'          => 'Copie du certificat de formation précédent (en cas de recyclage)',
                'en'          => 'Copy of previous training certificate (for recycling only)',
                'conditional' => true,
            ],
            [
                'ar'          => 'مطلب مشاركة في دورة تكوينية',
                'fr'          => 'Demande de participation à une session de formation',
                'en'          => 'Request to participate in a training session',
                'conditional' => false,
            ],
            [
                'ar'          => 'وصل خلاص معلوم الخدمة',
                'fr'          => 'Reçu de paiement des frais de service',
                'en'          => 'Service fee payment receipt',
                'conditional' => false,
            ],
            [
                'ar'          => 'قائمة المشاركين + إذن شراء (بالنسبة للمؤسسات)',
                'fr'          => "Liste des participants + bon de commande (pour les établissements)",
                'en'          => 'List of participants + purchase order (for institutions)',
                'conditional' => true,
            ],
        ];
    }
}

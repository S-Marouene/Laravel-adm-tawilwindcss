<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DocumentType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($this->documents() as $i => $doc) {
            DocumentType::create([
                'name_ar'        => $doc['ar'],
                'name_fr'        => $doc['fr'],
                'name_en'        => $doc['en'],
                'service_key'    => $doc['service'] ?? DocumentType::SERVICE_PREVENTION,
                'is_conditional' => $doc['conditional'] ?? false,
                'sort_order'     => $i + 1,
            ]);
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar' => 'بطاقة إرشادات (تُسحب من مقر الإدارة الجهوية للحماية المدنية)',
                'fr' => "Fiche d'instructions (à retirer au siège de la Direction Régionale de la Protection Civile)",
                'en' => 'Instruction card (to be collected from the Civil Protection Regional Directorate)',
            ],
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية لطالب الشهادة (إذا كان شخصًا طبيعيًا) أو للممثل القانوني (إذا كان شخصًا معنويًا)',
                'fr' => "Copie de la carte d'identité nationale du demandeur (personne physique) ou du représentant légal (personne morale)",
                'en' => "Copy of the national ID card of the applicant (natural person) or legal representative (legal entity)",
            ],
            [
                'ar' => 'إشهار قانوني مضمون منشور بالرائد الرسمي للجمهورية التونسية بالنسبة لتأسيس الشخص المعنوي',
                'fr' => "Annonce légale authentifiée publiée au Journal Officiel de la République Tunisienne pour la constitution d'une personne morale",
                'en' => "Certified legal notice published in the Official Gazette of the Tunisian Republic for the establishment of a legal entity",
            ],
            [
                'ar' => 'وثيقة تثبت تصرف الطالب في البناية',
                'fr' => "Document justifiant la jouissance du demandeur sur le bâtiment",
                'en' => "Document proving the applicant's control over the building",
            ],
            [
                'ar' => 'رسم بياني لموقع ومداخل وأجزاء البناية أو ما يقوم مقامه',
                'fr' => "Plan de situation du bâtiment, de ses accès et de ses parties, ou équivalent",
                'en' => "Site plan of the building, its accesses and parts, or equivalent",
            ],
            [
                'ar' => 'وصل خالص الإتاوة المستوجبة طبقًا للنموذج المرفق بالقرار',
                'fr' => "Quittance de paiement de la taxe due selon le modèle annexé au décret",
                'en' => "Receipt of payment of the required tax according to the model attached to the decree",
            ],
            [
                'ar' => 'وثيقة خاصة بالبنايات المعدة للسكن من النوعين الثالث والرابع',
                'fr' => "Document spécifique aux bâtiments d'habitation de type 3 et 4",
                'en' => "Specific document for residential buildings of types 3 and 4",
                'conditional' => true,
            ],
            [
                'ar' => 'نسخة من قرار الترخيص بالنسبة للبنايات المحتوية على مؤسسات خطرة أو مخلة بالصحة أو مزعجة',
                'fr' => "Copie de l'arrêté d'autorisation pour les bâtiments contenant des établissements dangereux, insalubres ou incommodes",
                'en' => "Copy of the authorization decree for buildings containing hazardous, unhealthy or inconvenient establishments",
                'conditional' => true,
            ],
            // Preventive presence service
            [
                'ar' => 'مطبوعة طلب القيام بخدمة',
                'fr' => 'Formulaire de demande de prestation',
                'en' => 'Service request form',
                'service' => DocumentType::SERVICE_PREVENTIVE_PRESENCE,
            ],
            [
                'ar' => 'نسخة من بطاقة التعريف الوطنية',
                'fr' => "Copie de la carte d'identité nationale",
                'en' => "Copy of the national identity card",
                'service' => DocumentType::SERVICE_PREVENTIVE_PRESENCE,
            ],
        ];
    }
}

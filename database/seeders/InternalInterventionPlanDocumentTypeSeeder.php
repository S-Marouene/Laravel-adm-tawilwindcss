<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Document types for the Internal Intervention Plan approval service
 * (المصادقة على مخطط التدخل الداخلي).
 *
 * No building-type pivot: the checklist is fixed for all dossiers of this service.
 */
class InternalInterventionPlanDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceKey = DocumentType::SERVICE_INTERNAL_INTERVENTION_PLAN;

        foreach ($this->documents() as $i => $doc) {
            $documentType = DocumentType::updateOrCreate(
                ['service_key' => $serviceKey, 'name_ar' => $doc['ar']],
                [
                    'name_fr'        => $doc['fr'],
                    'name_en'        => $doc['en'],
                    'is_conditional' => $doc['conditional'] ?? false,
                    'sort_order'     => $i + 1,
                    'services'       => [$serviceKey],
                ],
            );

            // No building-type pivot — fixed checklist for this service
            $documentType->buildingTypes()->sync([]);
        }
    }

    private function documents(): array
    {
        return [
            [
                'ar' => 'نسخة من قرار الترخيص في فتح مؤسسة مرتبة',
                'fr' => 'Copie de la décision d\'autorisation d\'ouverture d\'un établissement classé',
                'en' => 'Copy of the decision authorizing the opening of a classified establishment',
            ],
            [
                'ar' => 'نسخة من الملخص غير الفني لدراسة الأخطار',
                'fr' => 'Copie du résumé non technique de l\'étude des dangers',
                'en' => 'Copy of the non-technical summary of the hazard study',
            ],
            [
                'ar' => 'مثال موقعي 1/1000 يوضح محيط المؤسسة',
                'fr' => 'Plan de situation 1/1000 illustrant l\'environnement de l\'établissement',
                'en' => 'Site plan 1/1000 showing the establishment\'s surroundings',
            ],
            [
                'ar' => 'أمثلة هندسية بسلم 1/100',
                'fr' => 'Plans d\'architecture à l\'échelle 1/100',
                'en' => 'Architectural plans at scale 1/100',
            ],
            [
                'ar' => 'مثال إجمالي 1/200 لأماكن الآلات ومعدات السلامة',
                'fr' => 'Plan de masse 1/200 avec emplacement des machines et équipements de sécurité',
                'en' => 'Overall plan 1/200 showing machinery locations and safety equipment',
            ],
            [
                'ar' => 'تصريح للمؤسسات المرتبة من الصنف 3 (إعفاء من الوثائق 2 و3 و4)',
                'fr' => 'Déclaration pour établissements classés de 3ème catégorie (dispense des pièces 2, 3 et 4)',
                'en' => 'Declaration for class-3 establishments (waiver of items 2, 3 and 4)',
                'conditional' => true,
            ],
        ];
    }
}

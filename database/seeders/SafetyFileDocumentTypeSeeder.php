<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Idempotent seeder for the twin "safety file approval" services
 * (المصادقة على ملف السلامة المرفق بملف طلب رخصة البناء):
 *
 *   - habitation variant : buildings open to the public / residential
 *     buildings of great height (اللواء 2 من قانون عدد 11 لسنة 2009)
 *   - hazardous variant  : buildings containing classified establishments
 *     (dangerous, unhealthy or inconvenient — قرار وزير الداخلية المؤرخ في
 *      16 سبتمبر 2013)
 *
 * The building-type pivot is attached to EVERY building type: the strict
 * DocumentType::appliesToBuildingType() filter hides documents whose pivot
 * is empty, and both twins accept all building types (the differentiation
 * between the two services is the checklist, not the building type).
 */
class SafetyFileDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $serviceKey => $docs) {
            foreach ($docs as $i => $doc) {
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

                // Safety file services have a fixed checklist per variant —
                // the building type is NOT a consideration, so no pivot is needed.
                $documentType->buildingTypes()->sync([]);
            }
        }
    }

    private function documents(): array
    {
        return [
            // ── Twin A — habitation (public / residential high-rise buildings) ──
            DocumentType::SERVICE_SAFETY_FILE_HABITATION => [
                [
                    'ar' => 'مثال موقعي مجسم على مستخرج التهيئة العمرانية',
                    'fr' => 'Exemple de plan de situation reporté sur l\'extrait du plan d\'aménagement',
                    'en' => 'Site plan example drawn on the urban planning map extract',
                ],
                [
                    'ar' => 'مثال جملي 1/500',
                    'fr' => 'Exemple de plan de masse 1/500',
                    'en' => 'Site layout plan example 1/500',
                ],
                [
                    'ar' => 'مثال مواقع المآوي 1/200',
                    'fr' => 'Exemple de plan des abris 1/200',
                    'en' => 'Shelter locations plan example 1/200',
                ],
                [
                    'ar' => 'أمثلة تصميم الطوابق والقطع والواجهات 1/100',
                    'fr' => 'Exemples de plans des étages, coupes et façades 1/100',
                    'en' => 'Floor plans, sections and facades examples 1/100',
                ],
                [
                    'ar' => 'دراسة سلامة',
                    'fr' => 'Étude de sécurité',
                    'en' => 'Safety study',
                ],
            ],

            // ── Twin B — hazardous (dangerous / unhealthy / nuisance establishments) ──
            DocumentType::SERVICE_SAFETY_FILE_HAZARDOUS => [
                [
                    'ar' => 'قرار الترخيص في فتح مؤسسة مرتبة',
                    'fr' => 'Décision d\'autorisation d\'ouverture d\'un établissement classé',
                    'en' => 'Decision authorizing the opening of a classified establishment',
                ],
                [
                    'ar' => 'الملخص غير الفني لدراسة الأخطار',
                    'fr' => 'Résumé non technique de l\'étude des dangers',
                    'en' => 'Non-technical summary of the hazard study',
                ],
                [
                    'ar' => 'مثال موقعي 1/1000',
                    'fr' => 'Exemple de plan de situation 1/1000',
                    'en' => 'Site plan example 1/1000',
                ],
                [
                    'ar' => 'أمثلة هندسية 1/100',
                    'fr' => 'Exemples de plans d\'architecture 1/100',
                    'en' => 'Architectural plans examples 1/100',
                ],
                [
                    'ar' => 'مثال إجمالي 1/200',
                    'fr' => 'Exemple de plan de masse 1/200',
                    'en' => 'Site layout plan example 1/200',
                ],
            ],
        ];
    }
}

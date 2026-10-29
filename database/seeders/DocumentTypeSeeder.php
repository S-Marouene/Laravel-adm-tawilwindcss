<?php

namespace Database\Seeders;

use App\Models\BuildingType;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DocumentType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $buildingTypeIds = BuildingType::pluck('id')->all();

        foreach ($this->documents() as $i => $doc) {
            $theme = DocumentType::create([
                'name_ar'        => $doc['ar'],
                'name_fr'        => $doc['fr'],
                'name_en'        => $doc['en'],
                'service_key'    => $doc['service'] ?? DocumentType::SERVICE_PREVENTION,
                'services'       => [$doc['service'] ?? DocumentType::SERVICE_PREVENTION],
                'is_conditional' => $doc['conditional'] ?? false,
                'sort_order'     => $i + 1,
            ]);

            // ── DONNÉES DE TEST : périmètre aléatoire par type de bâtiment ──
            // Le document n'apparaît chez l'utilisateur que si SON type est coché ici.
            if (($doc['service'] ?? DocumentType::SERVICE_PREVENTION) === DocumentType::SERVICE_PREVENTION
                && ! empty($buildingTypeIds)) {
                $count = max(1, (int) ceil(count($buildingTypeIds) * random_int(30, 100) / 100));
                $theme->buildingTypes()->sync(collect($buildingTypeIds)->random($count)->values()->all());
            }

            // ── DONNÉES DE TEST : un formulaire officiel téléchargeable pour la « بطاقة إرشادات » ──
            if ($i === 0) {
                $path = 'document-templates/fiche-instructions-onpc.pdf';
                Storage::disk('local')->put($path, $this->samplePdf());

                $theme->forceFill([
                    'template_path' => $path,
                    'template_name' => "Fiche d'instructions ONPC.pdf",
                ])->save();
            }
        }
    }

    /** Minimal but valid one-page PDF sample (test data for the downloadable form). */
    private function samplePdf(): string
    {
        $toWinAnsi = fn (string $s): string => mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        $esc = fn (string $s): string => addcslashes($toWinAnsi($s), "()\\");

        $content = "BT /F1 18 Tf 50 790 Td (ONPC — PROTECTION CIVILE) Tj ET\n";
        $content .= "BT /F2 10 Tf 50 772 Td (Fiche d'instructions / Carte d'informations officielle) Tj ET\n";

        $y = 740;
        $steps = [
            '1. Téléchargez cette fiche officielle (model fourni par l\'administration).',
            '2. Complétez les champs rendus sur votre dossier.',
            '3. Faites signer / tamponner la fiche par les personnes requises.',
            '4. Numérisez (scan) la fiche complétée en PDF, JPG ou PNG.',
            '5. Téléversez la version scannée dans la case réservée juste à côté.',
            '6. Formats acceptés : PDF, JPG, PNG, DOC, DOCX — Taille max : 10 Mo.',
        ];
        foreach ($steps as $step) {
            $content .= sprintf("BT /F2 11 Tf 50 %d Td (%s) Tj ET\n", $y, $esc($step));
            $y -= 22;
        }

        $y -= 10;
        $content .= sprintf("BT /F1 12 Tf 50 %d Td (Référence : ONPC/PRV/FICHE-001) Tj ET\n", $y);
        $y -= 22;
        $content .= sprintf("BT /F2 9 Tf 50 %d Td (Document de test généré par le seeder — remplaçable via l'interface d'administration.) Tj ET\n", $y);

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$obj\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

        return $pdf;
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

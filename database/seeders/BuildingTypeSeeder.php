<?php

namespace Database\Seeders;

use App\Models\BuildingActivity;
use App\Models\BuildingType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BuildingActivity::truncate();
        BuildingType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($this->types() as $i => $type) {
            $bt = BuildingType::create([
                'name_ar'        => $type['ar'],
                'name_fr'        => $type['fr'],
                'name_en'        => $type['en'],
                'has_activities' => $type['has_activities'],
                'sort_order'     => $i + 1,
            ]);

            foreach ($type['activities'] as $j => $act) {
                BuildingActivity::create([
                    'building_type_id' => $bt->id,
                    'name_ar'          => $act['ar'],
                    'name_fr'          => $act['fr'],
                    'name_en'          => $act['en'],
                    'sort_order'       => $j + 1,
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Reference data
    // -----------------------------------------------------------------------

    private function types(): array
    {
        return [
            // ── 1. مفتوحة للعموم ─────────────────────────────────────────
            [
                'ar' => 'مفتوحة للعموم',
                'fr' => 'Ouvert au public',
                'en' => 'Open to the public',
                'has_activities' => true,
                'activities' => $this->activitiesPublic(),
            ],

            // ── 2. صناعية ────────────────────────────────────────────────
            [
                'ar' => 'صناعية',
                'fr' => 'Industrielle',
                'en' => 'Industrial',
                'has_activities' => true,
                'activities' => $this->activitiesIndustrial(),
            ],

            // ── 3. سكنية ─────────────────────────────────────────────────
            [
                'ar' => 'سكنية',
                'fr' => 'Résidentielle',
                'en' => 'Residential',
                'has_activities' => false,
                'activities' => [],
            ],

            // ── 4. ذات علو شاهق ──────────────────────────────────────────
            [
                'ar' => 'ذات علو شاهق',
                'fr' => 'Grande hauteur',
                'en' => 'High-rise',
                'has_activities' => false,
                'activities' => [],
            ],
        ];
    }

    // ── Activities: مفتوحة للعموم ─────────────────────────────────────────
    private function activitiesPublic(): array
    {
        $ar = [
            'إداري','تجاري','إستشفائي','سياحي','رياضي','صناعي','سكني','بنايات شاهقة','تربوي','فلاحي',
            'ترفيهى','بيوت التعبد','صنع الحقائب','صناعة الأثاث المعدني','خزن الباطاط وإجراء بحوث',
            'مكتب بريد','ثقافيـــة','إتصالات','تكرير الفيتورة واستخراج الزيت','صحي',
            'توزيع المحروقات','إقامة عائلية','حمام','إستراحة','تركيب ألواح الفسيفساء و البلور',
            'اقامة جامعية','مبيت جامعي','ميناء ترفيهي','ديني','إعاشة الطلبة','إقامة الحفلات',
            'مطعم و حانة','مطعم','تكوين','محطة غسيل السيارات','إقامة','كفالة اجتماعية لفاقدي السند',
            'دار شباب','المشروبات الغازية','وصل السيارات','الجليز و الرخام','الاسلاك الفولاذية',
            'المصبرات الغذائية','تعبئة المشروبات الغازية و الكحولية','صنع وصل السيارات',
            'صنع الجليز و قص الرخام','صنع الاسلاك الفولاذية','تصبير السمك','محطات بيع المحروقات',
            'صناعة الورق','مسلخ لحوم حمراء','تصبير المنتوجات البحرية','تكييف الزيوت','تكوين فلاحي',
            'مختلفة','تركيب الفسيفساء','خزن الحليب و الياغرت','منشر صيانة السفن','الحرسانة المسلحة',
            'مسلخ الارانب','عرض و بيع','مقهى ومطعم','صحّي استشفائي','فضاء ألعاب آلية',
            'محطّة نقل المسافرين','متحف','الخياطة و الفصالة','الغسيل و التكملة',
            'الغسيل و المراقبة و التكملة و الخياطة','صناعة الاحذية','خزن مواد كيميائية',
            'مطعم جامعي','مستودع','مخازن الحبوب','خزن و توزيع الادوية','سجن','مكتب أرشيف',
            'الطباعة','محطة نقل','مسلخ دواجن','مكتبة','تحضير الاسفلت','معلم اثري','ضخ الغاز',
            'تجميع و رسكلة النفايات','مطعم سياحي مصنف','خزن وتوزيع منتوجات نستلي',
            'مركز تربصات وإصطياف','خزن الاسمدة الكميائية','ماوى سيارات','خزن الامونيتر',
            'صناعة الخشب','صناعة السيراميك','صناعة الخفاف','مستودع غاز بترول مسيل',
            'خزن وتجميع الحبوب','البناءات المعدنية والجلفنة','محطة توزيع الوقود',
            'مخازن تبريد','تربية الماشية','بيع المشروبات الغازية بالجملة','بيع اللوح ومشتقاته',
            'خزن المواد الاولية والمواد التامة الصنع','وكالة مبيت','بيع الاسلحة وذخيرة الصيد',
            'مواد تنظيف بالجملة','زراعة الكروم المعد لصناعة الخمور','خزن مادة الامونيتر الزراعي',
            'بيع الوح ومشتقاته','استغلال التين الشوكي','مركب فلاحي','محطات النقل','محطة سيارات الاجرة',
        ];

        // French/English equivalents — using same name where no standard translation exists
        $fr = [
            'Administratif','Commercial','Hospitalier','Touristique','Sportif','Industriel','Résidentiel','Bâtiments de grande hauteur','Éducatif','Agricole',
            'De divertissement','Lieux de culte','Fabrication de sacs','Fabrication de meubles métalliques','Stockage de pommes de terre et recherche',
            'Bureau de poste','Culturel','Télécommunications','Raffinage du pétrole et extraction d\'huile','Sanitaire',
            'Distribution de carburants','Hébergement familial','Hammam','Hôtel de repos','Pose de mosaïques et de cristaux',
            'Résidence universitaire','Foyer universitaire','Port de plaisance','Religieux','Restauration étudiante','Organisation de fêtes',
            'Restaurant et bar','Restaurant','Formation','Station de lavage de voitures','Hébergement','Aide sociale aux personnes sans soutien',
            'Maison de jeunes','Boissons gazeuses','Assemblage de voitures','Carrelage et marbre','Câbles en acier',
            'Conserves alimentaires','Embouteillage de boissons gazeuses et alcoolisées','Fabrication d\'assemblages automobiles',
            'Fabrication de carrelage et découpe de marbre','Fabrication de câbles en acier','Conserverie de poisson','Stations de vente de carburants',
            'Fabrication de papier','Abattoir de viandes rouges','Conserverie de produits marins','Conditionnement d\'huiles','Formation agricole',
            'Divers','Pose de mosaïques','Stockage de lait et yaourts','Chantier naval de maintenance','Béton armé',
            'Abattoir de lapins','Exposition et vente','Café et restaurant','Sanitaire hospitalier','Espace de jeux automatiques',
            'Gare routière de voyageurs','Musée','Couture et tailleur','Lavage et finition',
            'Lavage, contrôle, finition et couture','Fabrication de chaussures','Stockage de produits chimiques',
            'Restaurant universitaire','Entrepôt','Silos à grains','Stockage et distribution de médicaments','Prison','Bureau d\'archives',
            'Imprimerie','Gare de transport','Abattoir de volailles','Bibliothèque','Préparation d\'asphalte','Site archéologique','Pompage de gaz',
            'Collecte et recyclage de déchets','Restaurant touristique classé','Stockage et distribution produits Nestlé',
            'Centre de stages et de villégiature','Stockage d\'engrais chimiques','Garage','Stockage d\'ammonitrate',
            'Industrie du bois','Industrie de la céramique','Fabrication de chaussures légères','Dépôt de gaz de pétrole liquéfié',
            'Stockage et collecte de céréales','Constructions métalliques et galvanisation','Station de distribution de carburant',
            'Entrepôts frigorifiques','Élevage de bétail','Vente en gros de boissons gazeuses','Vente de planches et dérivés',
            'Stockage de matières premières et produits finis','Agence d\'hébergement','Vente d\'armes et munitions de chasse',
            'Produits d\'entretien en gros','Culture de vignes pour la vinification','Stockage d\'ammonitrate agricole',
            'Vente de planches et dérivés','Exploitation de figues de barbarie','Complexe agricole','Stations de transport','Station de taxis',
        ];

        return $this->zip3($ar, $fr, $fr); // en = fr for delegation names (no perfect EN equivalents)
    }

    // ── Activities: صناعية ───────────────────────────────────────────────
    private function activitiesIndustrial(): array
    {
        $ar = [
            'صناعة الملابس','صناعة الأقمشة','صناعة الإكسسوارات النسيجية','صناعة الأغذية المعلبة',
            'صناعة المشروبات','صناعة الحلويات','صناعة اللحوم والدواجن','صناعة الألبان',
            'صناعة المواد الكيميائية الأساسية','صناعة البلاستيك','صناعة المنظفات',
            'صناعة الأدوية والمستحضرات الصيدلانية','صناعة الحديد والصلب','صناعة الألمنيوم',
            'صناعة المعادن غير الحديدية','صهر المعادن','صناعة السيارات','صناعة قطع الغيار',
            'صناعة الدراجات النارية','صناعة الشاحنات والمركبات التجارية',
            'صناعة مواد البناء (الخرسانة، الطوب، الإسمنت)','بناء المنازل والمباني السكنية',
            'البناء الصناعي والتجاري','تشييد البنية التحتية','صناعة الأجهزة الإلكترونية الاستهلاكية',
            'صناعة المكونات الإلكترونية','صناعة الأجهزة المنزلية','صناعة الأثاث المنزلي',
            'صناعة الأثاث المكتبي','صناعة الأثاث المعدني والخشبي','صناعة المنتجات الخشبية الأخرى',
            'صناعة الورق','صناعة الكرتون','صناعة منتجات الورق المخصصة',
            'صناعة الأدوية والعقاقير','صناعة المستحضرات الصيدلانية',
            'صناعة اللقاحات والمستحضرات البيولوجية','الطاقة المتجددة (الطاقة الشمسية، الرياح)',
            'محطات توليد الكهرباء','صناعة النفط والغاز','صناعات المفاعلات النووية',
            'صناعة الطباعة (الكتب، الصحف، المجلات)',
            'صناعة تغليف المواد (الأغذية، الأدوية، منتجات تجارية)',
            'صناعة الزجاج','صناعة الخزف والسيراميك','صناعة الأدوات الزجاجية المنزلية والصناعية',
            'صناعة الأسمدة','صناعة المبيدات الحشرية','صناعة المواد الكيميائية الزراعية',
            'صناعة الأحذية','صناعة المنتجات الجلدية (حقائب، محافظ)','صناعة الملابس الجلدية',
            'صناعة السجائر','صناعة منتجات التبغ الأخرى','صناعة الطوب',
            'صناعة الأجهزة الطبية','صناعة الأدوات الجراحية','صناعة المعدات الصحية',
        ];

        $fr = [
            'Fabrication de vêtements','Fabrication de textiles','Fabrication d\'accessoires textiles',
            'Fabrication de conserves alimentaires','Fabrication de boissons','Fabrication de confiseries',
            'Industrie des viandes et volailles','Industrie laitière',
            'Fabrication de produits chimiques de base','Industrie plastique','Fabrication de détergents',
            'Fabrication de médicaments et préparations pharmaceutiques','Sidérurgie','Industrie de l\'aluminium',
            'Industrie des métaux non ferreux','Fusion de métaux','Industrie automobile','Fabrication de pièces détachées',
            'Fabrication de motos','Fabrication de camions et véhicules commerciaux',
            'Fabrication de matériaux de construction (béton, brique, ciment)','Construction de maisons et immeubles résidentiels',
            'Construction industrielle et commerciale','Construction d\'infrastructures',
            'Fabrication d\'appareils électroniques grand public',
            'Fabrication de composants électroniques','Fabrication d\'appareils électroménagers',
            'Fabrication de meubles de maison','Fabrication de meubles de bureau',
            'Fabrication de meubles métalliques et en bois','Fabrication d\'autres produits en bois',
            'Fabrication de papier','Fabrication de carton','Fabrication de produits papier spécialisés',
            'Fabrication de médicaments et drogues','Fabrication de préparations pharmaceutiques',
            'Fabrication de vaccins et préparations biologiques','Énergie renouvelable (solaire, éolienne)',
            'Centrales électriques','Industrie pétrolière et gazière','Industries des réacteurs nucléaires',
            'Industrie de l\'imprimerie (livres, journaux, magazines)',
            'Industrie de l\'emballage (alimentaire, pharmaceutique, commercial)',
            'Industrie du verre','Industrie de la céramique','Fabrication d\'articles en verre ménager et industriel',
            'Fabrication d\'engrais','Fabrication de pesticides','Fabrication de produits chimiques agricoles',
            'Fabrication de chaussures','Fabrication de produits en cuir (sacs, portefeuilles)','Fabrication de vêtements en cuir',
            'Fabrication de cigarettes','Fabrication d\'autres produits du tabac','Fabrication de briques',
            'Fabrication de dispositifs médicaux','Fabrication d\'instruments chirurgicaux','Fabrication d\'équipements sanitaires',
        ];

        return $this->zip3($ar, $fr, $fr);
    }

    /** Zips three arrays of equal length into [{ar, fr, en}] */
    private function zip3(array $ar, array $fr, array $en): array
    {
        $result = [];
        foreach ($ar as $i => $name) {
            $result[] = [
                'ar' => $name,
                'fr' => $fr[$i] ?? $name,
                'en' => $en[$i] ?? $name,
            ];
        }
        return $result;
    }
}

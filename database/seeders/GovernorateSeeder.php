b <?php

namespace Database\Seeders;

use App\Models\Delegation;
use App\Models\Governorate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks so we can safely truncate parent + child tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Delegation::truncate();
        Governorate::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($this->governorates() as $id => $names) {
            Governorate::create([
                'id'      => $id,
                'name_ar' => $names['ar'],
                'name_fr' => $names['fr'],
                'name_en' => $names['en'],
            ]);
        }

        foreach ($this->delegations() as $govId => $locales) {
            $count = count($locales['ar']);
            for ($i = 0; $i < $count; $i++) {
                Delegation::create([
                    'governorate_id' => $govId,
                    'name_ar'        => $locales['ar'][$i],
                    'name_fr'        => $locales['fr'][$i],
                    'name_en'        => $locales['en'][$i],
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Reference data
    // -----------------------------------------------------------------------

    private function governorates(): array
    {
        return [
            1  => ['ar' => 'الإدارة الجهوية بتونس',         'fr' => 'Direction régionale de Tunis',          'en' => 'Regional Directorate of Tunis'],
            2  => ['ar' => 'الإدارة الجهوية بأريانة',       'fr' => "Direction régionale de l'Ariana",       'en' => 'Regional Directorate of Ariana'],
            3  => ['ar' => 'الإدارة الجهوية بمنوبة',        'fr' => 'Direction régionale de la Manouba',     'en' => 'Regional Directorate of Manouba'],
            4  => ['ar' => 'الإدارة الجهوية ببن عروس',      'fr' => 'Direction régionale de Ben Arous',      'en' => 'Regional Directorate of Ben Arous'],
            5  => ['ar' => 'الإدارة الجهوية بنابل',         'fr' => 'Direction régionale de Nabeul',         'en' => 'Regional Directorate of Nabeul'],
            6  => ['ar' => 'الإدارة الجهوية ببنزرت',        'fr' => 'Direction régionale de Bizerte',        'en' => 'Regional Directorate of Bizerte'],
            7  => ['ar' => 'الإدارة الجهوية بزغوان',        'fr' => 'Direction régionale de Zaghouan',       'en' => 'Regional Directorate of Zaghouan'],
            8  => ['ar' => 'الإدارة الجهوية بسوسة',         'fr' => 'Direction régionale de Sousse',         'en' => 'Regional Directorate of Sousse'],
            9  => ['ar' => 'الإدارة الجهوية بالمنستير',     'fr' => 'Direction régionale de Monastir',       'en' => 'Regional Directorate of Monastir'],
            10 => ['ar' => 'الإدارة الجهوية بالمهدية',      'fr' => 'Direction régionale de Mahdia',         'en' => 'Regional Directorate of Mahdia'],
            11 => ['ar' => 'الإدارة الجهوية بصفاقس',        'fr' => 'Direction régionale de Sfax',           'en' => 'Regional Directorate of Sfax'],
            12 => ['ar' => 'الإدارة الجهوية بباجة',         'fr' => 'Direction régionale de Béja',           'en' => 'Regional Directorate of Béja'],
            13 => ['ar' => 'الإدارة الجهوية بجندوبة',       'fr' => 'Direction régionale de Jendouba',       'en' => 'Regional Directorate of Jendouba'],
            14 => ['ar' => 'الإدارة الجهوية بالكاف',        'fr' => 'Direction régionale du Kef',            'en' => 'Regional Directorate of Le Kef'],
            15 => ['ar' => 'الإدارة الجهوية بسليانة',       'fr' => 'Direction régionale de Siliana',        'en' => 'Regional Directorate of Siliana'],
            16 => ['ar' => 'الإدارة الجهوية بالقيروان',     'fr' => 'Direction régionale de Kairouan',       'en' => 'Regional Directorate of Kairouan'],
            17 => ['ar' => 'الإدارة الجهوية بسيدي بوزيد',  'fr' => 'Direction régionale de Sidi Bouzid',    'en' => 'Regional Directorate of Sidi Bouzid'],
            18 => ['ar' => 'الإدارة الجهوية بالقصرين',      'fr' => 'Direction régionale de Kasserine',      'en' => 'Regional Directorate of Kasserine'],
            19 => ['ar' => 'الإدارة الجهوية بقابس',         'fr' => 'Direction régionale de Gabès',          'en' => 'Regional Directorate of Gabès'],
            20 => ['ar' => 'الإدارة الجهوية بمدنين',        'fr' => 'Direction régionale de Médenine',       'en' => 'Regional Directorate of Médenine'],
            21 => ['ar' => 'الإدارة الجهوية بتطاوين',       'fr' => 'Direction régionale de Tataouine',      'en' => 'Regional Directorate of Tataouine'],
            22 => ['ar' => 'الإدارة الجهوية بقفصة',         'fr' => 'Direction régionale de Gafsa',          'en' => 'Regional Directorate of Gafsa'],
            23 => ['ar' => 'الإدارة الجهوية بتوزر',         'fr' => 'Direction régionale de Tozeur',         'en' => 'Regional Directorate of Tozeur'],
            24 => ['ar' => 'الإدارة الجهوية بقبلي',         'fr' => 'Direction régionale de Kébili',         'en' => 'Regional Directorate of Kébili'],
        ];
    }

    private function delegations(): array
    {
        return [
            1 => [
                'ar' => ['قرطاج','المدينة','باب البحر','باب سويقة','العمران','العمران الأعلى','التحرير','المنزه','حي الخضراء','باردو','السيجومي','الزهور','الحرائرية','سيدي حسين','الوردية','الكبارية','سيدي البشير','جبل الجلود','حلق الوادي','الكرم','المرسى'],
                'fr' => ['Carthage','La Médina','Bab El Bhar','Bab Souika','El Omrane','El Omrane Supérieur','Ettahrir','El Menzah','Cité El Khadra','Bardo','Séjoumi','Ezzouhour','El Hrairia','Sidi Hassine','El Ouardia','El Kabaria','Sidi El Béchir','Jebel Jelloud','La Goulette','Le Kram','La Marsa'],
                'en' => ['Carthage','La Médina','Bab El Bhar','Bab Souika','El Omrane','El Omrane Supérieur','Ettahrir','El Menzah','Cité El Khadra','Bardo','Séjoumi','Ezzouhour','El Hrairia','Sidi Hassine','El Ouardia','El Kabaria','Sidi El Béchir','Jebel Jelloud','La Goulette','Le Kram','La Marsa'],
            ],
            2 => [
                'ar' => ['أريانة المدينة','سكرة','رواد','قلعة الأندلس','سيدي ثابت','حي التضامن','المنيهلة'],
                'fr' => ['Ariana Ville','Soukra','Raoued','Kalâat el-Andalous','Sidi Thabet','Cité Ettadhamen','Mnihla'],
                'en' => ['Ariana Ville','Soukra','Raoued','Kalâat el-Andalous','Sidi Thabet','Cité Ettadhamen','Mnihla'],
            ],
            3 => [
                'ar' => ['منوبة','وادي الليل','طبربة','البطان','الجديدة','المرناقية','برج العامري','دوار هيشر'],
                'fr' => ['Manouba','Oued Ellil','Tébourba','El Battan','Jedaïda','Mornaguia','Borj El Amri','Douar Hicher'],
                'en' => ['Manouba','Oued Ellil','Tébourba','El Battan','Jedaïda','Mornaguia','Borj El Amri','Douar Hicher'],
            ],
            4 => [
                'ar' => ['بن عروس','المدينة الجديدة','المروج','حمام الأنف','حمام الشط','بومهل البساتين','الزهراء','رادس','مقرين','المحمدية','فوشانة','مرناق'],
                'fr' => ['Ben Arous','Nouvelle Médina','El Mourouj','Hammam-Lif','Hammam Chott','Bou Mhel el-Bassatine','Ezzahra','Radès','Mégrine','La Mohammedia','Fouchana','Mornag'],
                'en' => ['Ben Arous','Nouvelle Médina','El Mourouj','Hammam-Lif','Hammam Chott','Bou Mhel el-Bassatine','Ezzahra','Radès','Mégrine','La Mohammedia','Fouchana','Mornag'],
            ],
            5 => [
                'ar' => ['نابل','دار شعبان الفهري','بني خيار','قربة','منزل تميم','الميدة','قليبية','حمام الأغزاز','الهوارية','تاكلسة','سليمان','منزل بوزلفة','بني خلاد','قرمبالية','بوعرقوب','الحمامات'],
                'fr' => ['Nabeul','Dar Chaâbane El Fehri','Béni Khiar','Korba','Menzel Temime','El Mida','Kélibia','Hammam Ghezèze','El Haouaria','Takelsa','Soliman','Menzel Bouzelfa','Béni Khalled','Grombalia','Bouargoub','Hammamet'],
                'en' => ['Nabeul','Dar Chaâbane El Fehri','Béni Khiar','Korba','Menzel Temime','El Mida','Kélibia','Hammam Ghezèze','El Haouaria','Takelsa','Soliman','Menzel Bouzelfa','Béni Khalled','Grombalia','Bouargoub','Hammamet'],
            ],
            6 => [
                'ar' => ['بنزرت الشمالية','جرزونة','بنزرت الجنوبية','سجنان','جومين','ماطر','غزالة','منزل بورقيبة','تينجة','أوتيك','غار الملح','منزل جميل','العالية','رأس الجبل'],
                'fr' => ['Bizerte Nord','Jarzouna','Bizerte Sud','Sejnane','Joumine','Mateur','Ghezala','Menzel Bourguiba','Tinja','Utique','Ghar El Melh','Menzel Jemil','El Alia','Ras Jebel'],
                'en' => ['Bizerte Nord','Jarzouna','Bizerte Sud','Sejnane','Joumine','Mateur','Ghezala','Menzel Bourguiba','Tinja','Utique','Ghar El Melh','Menzel Jemil','El Alia','Ras Jebel'],
            ],
            7 => [
                'ar' => ['زغوان','الزريبة','بئر مشارقة','الفحص','الناظور','صواف'],
                'fr' => ['Zaghouan','Zriba','Bir Mcherga','El Fahs','En-Nadhour','Saouaf'],
                'en' => ['Zaghouan','Zriba','Bir Mcherga','El Fahs','En-Nadhour','Saouaf'],
            ],
            8 => [
                'ar' => ['سوسة المدينة','الزاوية القصيبة الثريات','سوسة الرياض','سوسة جوهرة','سوسة سيدي عبد الحميد','حمام سوسة','أكودة','القلعة الكبرى','سيدي بوعلي','هرقلة','النفيضة','بوفيشة','كندار','سيدي الهاني','مساكن','القلعة الصغرى'],
                'fr' => ['Sousse Médina','Zaouiet Sousse','Sousse Riadh','Sousse Jawhara','Sousse Sidi Abdelhamid','Hammam Sousse','Akouda','Kalâa Kebira','Sidi Bou Ali','Hergla','Enfidha','Bouficha','Kondar','Sidi El Hani','Msaken','Kalâa Seghira'],
                'en' => ['Sousse Médina','Zaouiet Sousse','Sousse Riadh','Sousse Jawhara','Sousse Sidi Abdelhamid','Hammam Sousse','Akouda','Kalâa Kebira','Sidi Bou Ali','Hergla','Enfidha','Bouficha','Kondar','Sidi El Hani','Msaken','Kalâa Seghira'],
            ],
            9 => [
                'ar' => ['المنستير','الوردانيـن','الساحليـن','زرمديـن','بنـي حسان','جمـال','بنبلة','المكنين','البقالطة','طبلبة','قصر هلال','قصيبة المديوني','صيادة لمطة بوحجر'],
                'fr' => ['Monastir','Ouerdanine','Sahline','Zermadine','Beni Hassan','Jemmal','Bembla','Moknine','Bekalta','Téboulba','Ksar Hellal','Ksibet el-Mediouni','Sayada-Lamta-Bou Hajar'],
                'en' => ['Monastir','Ouerdanine','Sahline','Zermadine','Beni Hassan','Jemmal','Bembla','Moknine','Bekalta','Téboulba','Ksar Hellal','Ksibet el-Mediouni','Sayada-Lamta-Bou Hajar'],
            ],
            10 => [
                'ar' => ['المهدية','بومرداس','أولاد الشامخ','شربان','هبيرة','السواسي','الجم','الشابة','ملولش','سيدي علوان','قصور الساف'],
                'fr' => ['Mahdia','Boumerdès','Ouled Chamekh','Chorbane','Hbira','Souassi','El Jem','Chebba','Melloulèche','Sidi Alouane','Ksour Essef'],
                'en' => ['Mahdia','Boumerdès','Ouled Chamekh','Chorbane','Hbira','Souassi','El Jem','Chebba','Melloulèche','Sidi Alouane','Ksour Essef'],
            ],
            11 => [
                'ar' => ['صفاقس المدينة','صفاقس الغربية','ساقية الزيت','ساقية الداير','صفاقس الجنوبية','طينة','عقارب','جبنيانة','العامرة','الحنشة','منزل شاكر','الغريبة','بئر علي بن خليفة','الصخيرة','المحرس','قـرقنـة'],
                'fr' => ['Sfax Médina','Sfax Ouest','Sakiet Ezzit','Sakiet Eddaïer','Sfax Sud','Tina','Agareb','Djebeniana','El Amra','El Hencha','Menzel Chaker','Ghraiba','Bir Ali Ben Khalifa','Skhira','Mahres','Kerkennah'],
                'en' => ['Sfax Médina','Sfax Ouest','Sakiet Ezzit','Sakiet Eddaïer','Sfax Sud','Tina','Agareb','Djebeniana','El Amra','El Hencha','Menzel Chaker','Ghraiba','Bir Ali Ben Khalifa','Skhira','Mahres','Kerkennah'],
            ],
            12 => [
                'ar' => ['باجة الشمالية','باجة الجنوبية','عمدون','نفزة','تبرسق','تيبار','تستور','قبلاط','مجاز الباب'],
                'fr' => ['Béja Nord','Béja Sud','Amdoun','Nefza','Testour','Téboursouk','Thibar','Goubellat','Medjez el-Bab'],
                'en' => ['Béja Nord','Béja Sud','Amdoun','Nefza','Testour','Téboursouk','Thibar','Goubellat','Medjez el-Bab'],
            ],
            13 => [
                'ar' => ['جندوبة','جندوبة الشمالية','بوسالم','طبرقـة','عين دراهم','فرنانة','غار الدماء','وادي مليز','بلطة بوعوان'],
                'fr' => ['Jendouba','Jendouba Nord','Bou Salem','Tabarka','Aïn Draham','Fernana','Ghardimaou','Oued Meliz','Balta-Bou Aouane'],
                'en' => ['Jendouba','Jendouba Nord','Bou Salem','Tabarka','Aïn Draham','Fernana','Ghardimaou','Oued Meliz','Balta-Bou Aouane'],
            ],
            14 => [
                'ar' => ['الكاف الغربية','الكاف الشرقية','نبـر','الطوريف','ساقية سيدي يوسف','تاجروين','قلعة سنان','القلعة الخصبة','الجريصة','القصور','الدهماني','السرس'],
                'fr' => ['Le Kef Ouest','Le Kef Est','Nebeur','Touiref','Sakiet Sidi Youssef','Tajerouine','Kalâat Senan','Kalâat Khasba','Jerissa','El Ksour','Dahmani','Es-Sers'],
                'en' => ['Le Kef Ouest','Le Kef Est','Nebeur','Touiref','Sakiet Sidi Youssef','Tajerouine','Kalâat Senan','Kalâat Khasba','Jerissa','El Ksour','Dahmani','Es-Sers'],
            ],
            15 => [
                'ar' => ['سليانة الشمالية','سليانة الجنوبية','بوعرادة','قعفور','العروسة','الكريب','بورويس','مكثر','الروحية','كسرى','برقو'],
                'fr' => ['Siliana Nord','Siliana Sud','Bou Arada','Gaâfour','El Aroussa','El Krib','Bargou','Makthar','Rouhia','Kesra','Bou Rouis'],
                'en' => ['Siliana Nord','Siliana Sud','Bou Arada','Gaâfour','El Aroussa','El Krib','Bargou','Makthar','Rouhia','Kesra','Bou Rouis'],
            ],
            16 => [
                'ar' => ['القيروان الشمالية','القيروان الجنوبية','الشبيكة','السبيخة','الوسلاتية','حفوز','العلا','حاجب العيون','نصر الله','الشراردة','بوحجلة','عين جلولة','منزل المهيري'],
                'fr' => ['Kairouan Nord','Kairouan Sud','Chebika','Sbikha','Oueslatia','Haffouz','El Alaa','Hajeb El Ayoun','Nasrallah','Cherarda','Bouhajla','Ain Jelloula','Menzel Mehiri'],
                'en' => ['Kairouan Nord','Kairouan Sud','Chebika','Sbikha','Oueslatia','Haffouz','El Alaa','Hajeb El Ayoun','Nasrallah','Cherarda','Bouhajla','Ain Jelloula','Menzel Mehiri'],
            ],
            17 => [
                'ar' => ['سيدي بوزيد الغربية','سيدي بوزيد الشرقية','جلمة','سبالة أولاد عسكر','بئر الحفي','سيدي علي بن عون','منزل بوزيان','المكناسي','سوق الجديد','المزونة','الرقاب','السعيدة','أولاد حفوز'],
                'fr' => ['Sidi Bouzid Ouest','Sidi Bouzid Est','Jelma','Cebbala Ouled Asker','Bir El Hafey','Sidi Ali Ben Aoun','Menzel Bouzaiane','Meknassy','Souk Jedid','Mazouna','Regueb','Es-Saïda','Ouled Haffouz'],
                'en' => ['Sidi Bouzid Ouest','Sidi Bouzid Est','Jelma','Cebbala Ouled Asker','Bir El Hafey','Sidi Ali Ben Aoun','Menzel Bouzaiane','Meknassy','Souk Jedid','Mazouna','Regueb','Es-Saïda','Ouled Haffouz'],
            ],
            18 => [
                'ar' => ['القصرين الشمالية','القصرين الجنوبية','الزهور','حاسي الفريد','سبيطلة','سبيبة','جدليان','العيون','تالة','حيدرة','فوسانة','فريانة','ماجل بلعباس'],
                'fr' => ['Kasserine Nord','Kasserine Sud','Ezzouhour','Hassi El Ferid','Sbeitla','Sbiba','Djedeliane','El Ayoun','Thala','Hidra','Foussana','Feriana','Majel Bel Abbès'],
                'en' => ['Kasserine Nord','Kasserine Sud','Ezzouhour','Hassi El Ferid','Sbeitla','Sbiba','Djedeliane','El Ayoun','Thala','Hidra','Foussana','Feriana','Majel Bel Abbès'],
            ],
            19 => [
                'ar' => ['قابس المدينة','قابس الغربية','قابس الجنوبية','غنوش','المطوية','منزل الحبيب','الحامة','مطماطة','مطماطة الجديدة','مارث','دخيلة توجان'],
                'fr' => ['Gabès Médina','Gabès Ouest','Gabès Sud','Ghannouche','El Metouia','Menzel El Habib','El Hamma','Matmata','Nouvelle Matmata','Mareth','Toujane'],
                'en' => ['Gabès Médina','Gabès Ouest','Gabès Sud','Ghannouche','El Metouia','Menzel El Habib','El Hamma','Matmata','Nouvelle Matmata','Mareth','Toujane'],
            ],
            20 => [
                'ar' => ['مدنين الشمالية','مدنين الجنوبية','بني خداش','بن قردان','جرجيس','جربة حومة السوق','جربة ميدون','جربة أجيم','سيدي مخلوف'],
                'fr' => ['Médenine Nord','Médenine Sud','Beni Khedache','Ben Guerdane','Zarzis','Houmt Souk','Midoun','Ajim','Sidi Makhlouf'],
                'en' => ['Médenine Nord','Médenine Sud','Beni Khedache','Ben Guerdane','Zarzis','Houmt Souk','Midoun','Ajim','Sidi Makhlouf'],
            ],
            21 => [
                'ar' => ['تطاوين الشمالية','تطاوين الجنوبية','الصمار','البئر الأحمر','غمراسن','ذهيبة','رمادة','بني مهيرة'],
                'fr' => ['Tataouine Nord','Tataouine Sud','Smâr','Bir Lahmar','Ghomrassen','Dhehiba','Remada','Beni Méhira'],
                'en' => ['Tataouine Nord','Tataouine Sud','Smâr','Bir Lahmar','Ghomrassen','Dhehiba','Remada','Beni Méhira'],
            ],
            22 => [
                'ar' => ['قفصة الشمالية','سيدي عيش','القصر','قفصة الجنوبية','أم العرائس','سيدي بوبكر','الرديف','المتلوي','المظيلة','القطار','بلخير','السند','صانوش'],
                'fr' => ['Gafsa Nord','Sidi Aïch','El Ksar','Gafsa Sud','Oum El Araies','Sidi Boubaker','Redeyef','Metlaoui','Mdhilla','El Guettar','Belkhir','Sened','Zannouch'],
                'en' => ['Gafsa Nord','Sidi Aïch','El Ksar','Gafsa Sud','Oum El Araies','Sidi Boubaker','Redeyef','Metlaoui','Mdhilla','El Guettar','Belkhir','Sened','Zannouch'],
            ],
            23 => [
                'ar' => ['توزر','دقاش','تمغزة','نفطة','حزوة','حامة الجريد'],
                'fr' => ['Tozeur','Degache','Tameghza','Nefta','Hazoua','Hammet Jerid'],
                'en' => ['Tozeur','Degache','Tameghza','Nefta','Hazoua','Hammet Jerid'],
            ],
            24 => [
                'ar' => ['قبلي الجنوبية','قبلي الشمالية','سوق الأحد','دوز الشمالية','دوز الجنوبية','الفوار','رجيم معتوق'],
                'fr' => ['Kébili Sud','Kébili Nord','Souk Lahad','Douz Nord','Douz Sud','El Faouar','Rejim Maatoug'],
                'en' => ['Kébili Sud','Kébili Nord','Souk Lahad','Douz Nord','Douz Sud','El Faouar','Rejim Maatoug'],
            ],
        ];
    }
}

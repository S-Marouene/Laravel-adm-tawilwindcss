<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    private const SERVICES = [
        ['key' => Service::SERVICE_PREVENTION, 'name_ar' => 'الوقاية', 'name_fr' => 'Prévention', 'name_en' => 'Prevention', 'sort_order' => 1],
        ['key' => Service::SERVICE_PREVENTIVE_PRESENCE, 'name_ar' => 'الحضور الوقائي', 'name_fr' => 'Présence préventive', 'name_en' => 'Preventive presence', 'sort_order' => 2],
        ['key' => Service::SERVICE_RESCUE_CERTIFICATE, 'name_ar' => 'شهادة نجدة وإنقاذ', 'name_fr' => 'Certificat de secours', 'name_en' => 'Rescue certificate', 'sort_order' => 3],
        ['key' => Service::SERVICE_WORKS_NOTIFICATION, 'name_ar' => 'الإعلام عن إنجاز أشغال', 'name_fr' => 'Notification de travaux', 'name_en' => 'Works notification', 'sort_order' => 4],
        ['key' => Service::SERVICE_SAFETY_FILE_HABITATION, 'name_ar' => 'المصادقة على ملف السلامة - البنايات العمومية', 'name_fr' => 'Validation du dossier sécurité - Bâtiments publics', 'name_en' => 'Safety File Approval - Public Buildings', 'sort_order' => 5],
        ['key' => Service::SERVICE_SAFETY_FILE_HAZARDOUS, 'name_ar' => 'المصادقة على ملف السلامة - المؤسسات الخطرة', 'name_fr' => 'Validation du dossier sécurité - Établissements dangereux', 'name_en' => 'Safety File Approval - Hazardous Establishments', 'sort_order' => 6],
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $service) {
            Service::updateOrCreate(
                ['key' => $service['key']],
                [
                    'name_ar'    => $service['name_ar'],
                    'name_fr'    => $service['name_fr'],
                    'name_en'    => $service['name_en'],
                    'sort_order' => $service['sort_order'],
                    'is_active'  => true,
                ],
            );
        }
    }
}

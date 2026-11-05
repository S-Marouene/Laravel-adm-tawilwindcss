<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds all notification message templates with French and Arabic text.
 *
 * Placeholders: :reference (dossier ref), :nom_prenom (applicant name)
 *
 * Run: php artisan db:seed --class=NotificationTemplateSeeder
 * Safe to run multiple times — uses updateOrCreate.
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'event_key' => 'submitted',
                'level'     => 'info',
                'title_fr'  => 'Nouveau dossier soumis',
                'body_fr'   => 'Le dossier :reference (:nom_prenom) vient d\'être soumis et est en attente d\'examen.',
                'title_ar'  => 'ملف جديد مُقدَّم',
                'body_ar'   => 'تم تقديم الملف :reference (:nom_prenom) وهو في انتظار المراجعة.',
            ],
            [
                'event_key' => 'resubmitted',
                'level'     => 'warning',
                'title_fr'  => 'Dossier re-soumis après correction',
                'body_fr'   => 'Le dossier :reference (:nom_prenom) a été corrigé et re-soumis par le demandeur.',
                'title_ar'  => 'إعادة تقديم الملف بعد التصحيح',
                'body_ar'   => 'أعاد المتقدم :nom_prenom تقديم الملف :reference بعد إجراء التصحيحات المطلوبة.',
            ],
            [
                'event_key' => 'under_consultation',
                'level'     => 'info',
                'title_fr'  => 'Dossier en cours de consultation',
                'body_fr'   => 'Votre dossier :reference a été examiné. Une visite d\'inspection sera programmée prochainement.',
                'title_ar'  => 'الملف قيد الدراسة',
                'body_ar'   => 'تم فحص ملفكم :reference وستتم جدولة زيارة تفتيشية قريبًا.',
            ],
            [
                'event_key' => 'needs_correction',
                'level'     => 'warning',
                'title_fr'  => 'Correction requise',
                'body_fr'   => 'Votre dossier :reference nécessite des corrections. Veuillez consulter la note de l\'administration.',
                'title_ar'  => 'مطلوب تصحيح',
                'body_ar'   => 'يستلزم ملفكم :reference إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.',
            ],
            [
                'event_key' => 'approved',
                'level'     => 'success',
                'title_fr'  => 'Dossier approuvé',
                'body_fr'   => 'Votre dossier :reference a été approuvé par l\'administration.',
                'title_ar'  => 'تمت الموافقة على الملف',
                'body_ar'   => 'تمت الموافقة على ملفكم :reference من قِبل الإدارة.',
            ],
            [
                'event_key' => 'cancelled',
                'level'     => 'danger',
                'title_fr'  => 'Dossier annulé',
                'body_fr'   => 'Le dossier :reference a été annulé. Veuillez contacter l\'administration pour plus d\'informations.',
                'title_ar'  => 'تم إلغاء الملف',
                'body_ar'   => 'تم إلغاء الملف :reference. يرجى التواصل مع الإدارة للمزيد من المعلومات.',
            ],
            [
                'event_key' => 'appointment_scheduled',
                'level'     => 'info',
                'title_fr'  => 'Visite d\'inspection planifiée',
                'body_fr'   => 'Une visite d\'inspection a été planifiée pour votre dossier :reference. Vous recevrez les détails par e-mail.',
                'title_ar'  => 'تمت جدولة زيارة التفتيش',
                'body_ar'   => 'تمت جدولة زيارة تفتيشية لملفكم :reference. ستصلكم التفاصيل عبر البريد الإلكتروني.',
            ],
            [
                'event_key' => 'appointment_cancelled',
                'level'     => 'warning',
                'title_fr'  => 'Visite annulée',
                'body_fr'   => 'La visite d\'inspection planifiée pour le dossier :reference a été annulée.',
                'title_ar'  => 'تم إلغاء الزيارة',
                'body_ar'   => 'تم إلغاء الزيارة التفتيشية المجدولة للملف :reference.',
            ],
            [
                'event_key' => 'waiting_delivery',
                'level'     => 'success',
                'title_fr'  => 'Visite passée — certificat en cours de préparation',
                'body_fr'   => 'La visite d\'inspection pour le dossier :reference s\'est déroulée avec succès. Votre certificat sera délivré prochainement.',
                'title_ar'  => 'نجحت الزيارة — جارٍ إعداد الشهادة',
                'body_ar'   => 'أُجريت زيارة التفتيش للملف :reference بنجاح. ستصدر شهادتكم قريبًا.',
            ],
            [
                'event_key' => 'appointment_failed',
                'level'     => 'warning',
                'title_fr'  => 'Visite d\'inspection non concluante',
                'body_fr'   => 'La visite d\'inspection pour le dossier :reference n\'a pas abouti. Veuillez consulter la note de l\'inspecteur.',
                'title_ar'  => 'لم تنجح زيارة التفتيش',
                'body_ar'   => 'لم تُسفر زيارة التفتيش للملف :reference عن نتيجة إيجابية. يرجى الاطلاع على ملاحظة المفتش.',
            ],
            [
                'event_key' => 'certificate_issued',
                'level'     => 'success',
                'title_fr'  => 'Certificat de prévention émis',
                'body_fr'   => 'Le certificat de prévention pour le dossier :reference a été émis. Vous pouvez le retirer ou choisir un mode de livraison.',
                'title_ar'  => 'صدرت شهادة الوقاية',
                'body_ar'   => 'صدرت شهادة الوقاية الخاصة بالملف :reference. يمكنكم استلامها أو اختيار طريقة التسليم.',
            ],
            [
                'event_key' => 'certificate_collected',
                'level'     => 'success',
                'title_fr'  => 'Certificat retiré au guichet',
                'body_fr'   => 'Votre certificat de prévention pour le dossier :reference a bien été retiré au guichet.',
                'title_ar'  => 'تم استلام الشهادة من الشباك',
                'body_ar'   => 'تم استلام شهادة الوقاية الخاصة بالملف :reference من الشباك.',
            ],
            [
                'event_key' => 'certificate_post_sent',
                'level'     => 'info',
                'title_fr'  => 'Certificat envoyé par courrier',
                'body_fr'   => 'Votre certificat de prévention pour le dossier :reference a été expédié par Poste Tunisienne.',
                'title_ar'  => 'أُرسلت الشهادة بالبريد',
                'body_ar'   => 'تم إرسال شهادة الوقاية الخاصة بالملف :reference عبر البريد التونسي.',
            ],
            [
                'event_key' => 'certificate_delivered',
                'level'     => 'success',
                'title_fr'  => 'Certificat remis',
                'body_fr'   => 'Votre certificat de prévention pour le dossier :reference vous a été remis.',
                'title_ar'  => 'تم تسليم الشهادة',
                'body_ar'   => 'تم تسليم شهادة الوقاية الخاصة بالملف :reference إليكم.',
            ],
            [
                'event_key' => 'certificate_revoked',
                'level'     => 'danger',
                'title_fr'  => 'Certificat révoqué',
                'body_fr'   => 'Le certificat de prévention pour le dossier :reference a été révoqué. Veuillez contacter l\'administration.',
                'title_ar'  => 'تم سحب الشهادة',
                'body_ar'   => 'تم سحب شهادة الوقاية الخاصة بالملف :reference. يرجى التواصل مع الإدارة.',
            ],
            [
                'event_key' => 'delivery_chosen',
                'level'     => 'info',
                'title_fr'  => 'Mode de retrait choisi',
                'body_fr'   => 'Le demandeur a choisi un mode de retrait pour le dossier :reference.',
                'title_ar'  => 'تم اختيار طريقة الاستلام',
                'body_ar'   => 'اختار المتقدم طريقة استلام شهادة الملف :reference.',
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(
                ['event_key' => $data['event_key']],
                $data
            );
        }
    }
}

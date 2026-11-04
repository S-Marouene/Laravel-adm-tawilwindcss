<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, text, boolean, image, email, url, password, select, integer
            $table->string('group')->default('general'); // general, contact, appearance, seo, mail
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        $settings = [
            // General
            ['key' => 'app_name', 'value' => 'Office national de la protection civile', 'type' => 'string', 'group' => 'general', 'label' => 'Application Name', 'description' => 'The name of the application displayed throughout the site.'],
            ['key' => 'app_title', 'value' => 'Vos démarches administratives en ligne', 'type' => 'string', 'group' => 'general', 'label' => 'Site Title', 'description' => 'The site title / tagline shown in the header and meta tags.'],
            ['key' => 'app_description', 'value' => "Plateforme officielle de L'office national de la protection civile", 'type' => 'text', 'group' => 'general', 'label' => 'Meta Description', 'description' => 'Default meta description for SEO.'],

            // Contact
            ['key' => 'contact_email', 'value' => 'contact@onpc.tn', 'type' => 'email', 'group' => 'contact', 'label' => 'Contact Email', 'description' => 'Public contact email address.'],
            ['key' => 'contact_phone', 'value' => '+216 71 000 000', 'type' => 'string', 'group' => 'contact', 'label' => 'Contact Phone', 'description' => 'Public contact phone number.'],
            ['key' => 'contact_address', 'value' => 'Tunis, Tunisie', 'type' => 'text', 'group' => 'contact', 'label' => 'Address', 'description' => 'Office address.'],

            // Appearance
            ['key' => 'app_icon', 'value' => '', 'type' => 'image', 'group' => 'appearance', 'label' => 'Site Icon (192x192)', 'description' => 'Upload a PNG icon (192×192 px) for the browser tab and PWA.'],
            ['key' => 'app_favicon', 'value' => '', 'type' => 'image', 'group' => 'appearance', 'label' => 'Favicon (32x32)', 'description' => 'Upload a PNG/ICO favicon (32×32 px).'],

            // SEO
            ['key' => 'seo_keywords', 'value' => 'administration, Tunisie, services publics, démarches en ligne', 'type' => 'text', 'group' => 'seo', 'label' => 'Meta Keywords', 'description' => 'Comma-separated keywords for SEO.'],
            ['key' => 'seo_og_image', 'value' => '', 'type' => 'image', 'group' => 'seo', 'label' => 'Open Graph Image (1200x630)', 'description' => 'Default OG image shared on social media.'],

            // Mail Configuration
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'select', 'group' => 'mail', 'label' => 'Mail Driver', 'description' => 'Email transport driver (smtp, sendmail, mailgun, ses, postmark, log).'],
            ['key' => 'mail_host', 'value' => '127.0.0.1', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Host', 'description' => 'SMTP server hostname or IP address.'],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'integer', 'group' => 'mail', 'label' => 'SMTP Port', 'description' => 'SMTP server port (587 for TLS, 465 for SSL, 25 for plain).'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Username', 'description' => 'SMTP authentication username.'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'password', 'group' => 'mail', 'label' => 'SMTP Password', 'description' => 'SMTP authentication password. Leave empty to keep current.'],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'select', 'group' => 'mail', 'label' => 'SMTP Encryption', 'description' => 'Encryption protocol (tls, ssl, or empty for none).'],
            ['key' => 'mail_from_address', 'value' => 'noreply@onpc.tn', 'type' => 'email', 'group' => 'mail', 'label' => 'From Address', 'description' => 'Default "from" email address for all outgoing emails.'],
            ['key' => 'mail_from_name', 'value' => 'Office national de la protection civile', 'type' => 'string', 'group' => 'mail', 'label' => 'From Name', 'description' => 'Default "from" name for all outgoing emails.'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert($setting);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

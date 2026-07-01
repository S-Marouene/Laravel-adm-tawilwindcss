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
            $table->string('type')->default('string'); // string, text, boolean, image, email, url
            $table->string('group')->default('general'); // general, contact, appearance, seo
            $table->string('label')->nullable(); // Human-readable label
            $table->text('description')->nullable(); // Help text
            $table->boolean('is_public')->default(false); // Whether it can be exposed publicly
            $table->timestamps();
        });

        // Insert default settings
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('colors');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        $settings = [
            ['key' => 'primary_color', 'value' => '#6B3FAF', 'type' => 'color', 'group' => 'colors', 'label' => 'Primary color', 'description' => 'Main action, active navigation, and focus color.', 'sort_order' => 10, 'is_public' => true],
            ['key' => 'accent_color', 'value' => '#0891B2', 'type' => 'color', 'group' => 'colors', 'label' => 'Accent color', 'description' => 'Secondary highlights, gradients, and decorative accents.', 'sort_order' => 20, 'is_public' => true],
            ['key' => 'sidebar_color', 'value' => '#111C43', 'type' => 'color', 'group' => 'colors', 'label' => 'Admin sidebar color', 'description' => 'Base background color for the admin sidebar.', 'sort_order' => 30, 'is_public' => true],
            ['key' => 'page_background', 'value' => '#F5F6FA', 'type' => 'color', 'group' => 'colors', 'label' => 'Page background', 'description' => 'Default light-mode page background.', 'sort_order' => 40, 'is_public' => true],
            ['key' => 'font_family', 'value' => 'Inter', 'type' => 'select', 'group' => 'typography', 'label' => 'Font family', 'description' => 'Primary interface font for non-Arabic pages.', 'options' => json_encode(['Inter', 'Arial', 'Georgia', 'Tahoma', 'Verdana']), 'sort_order' => 10, 'is_public' => true],
            ['key' => 'base_font_size', 'value' => '16', 'type' => 'number', 'group' => 'typography', 'label' => 'Base font size', 'description' => 'Root font size in pixels.', 'sort_order' => 20, 'is_public' => true],
            ['key' => 'heading_style', 'value' => 'balanced', 'type' => 'select', 'group' => 'typography', 'label' => 'Heading style', 'description' => 'Controls heading weight and spacing.', 'options' => json_encode(['balanced', 'strong', 'light']), 'sort_order' => 30, 'is_public' => true],
            ['key' => 'interface_density', 'value' => 'comfortable', 'type' => 'select', 'group' => 'layout', 'label' => 'Interface density', 'description' => 'Adjusts common form and table spacing.', 'options' => json_encode(['compact', 'comfortable', 'spacious']), 'sort_order' => 10, 'is_public' => true],
            ['key' => 'border_radius', 'value' => '12', 'type' => 'number', 'group' => 'layout', 'label' => 'Border radius', 'description' => 'Default component corner radius in pixels.', 'sort_order' => 20, 'is_public' => true],
            ['key' => 'button_style', 'value' => 'gradient', 'type' => 'select', 'group' => 'components', 'label' => 'Button style', 'description' => 'Visual style for primary action buttons.', 'options' => json_encode(['gradient', 'solid', 'outline']), 'sort_order' => 10, 'is_public' => true],
            ['key' => 'card_shadow', 'value' => 'soft', 'type' => 'select', 'group' => 'components', 'label' => 'Card shadow', 'description' => 'Elevation used on cards and form surfaces.', 'options' => json_encode(['none', 'soft', 'deep']), 'sort_order' => 20, 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            DB::table('theme_settings')->insert($setting);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
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
        DB::table('settings')->where('group', 'mail')->delete();
    }
};

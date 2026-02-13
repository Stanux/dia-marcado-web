<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_configs')->updateOrInsert(
            ['key' => 'guests.whatsapp_webhook_url'],
            [
                'value' => json_encode(''),
                'description' => 'Webhook para envio de convites via WhatsApp (módulo convidados).',
            ]
        );

        DB::table('system_configs')->updateOrInsert(
            ['key' => 'guests.sms_webhook_url'],
            [
                'value' => json_encode(''),
                'description' => 'Webhook para envio de convites via SMS (módulo convidados).',
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_configs')->whereIn('key', [
            'guests.whatsapp_webhook_url',
            'guests.sms_webhook_url',
        ])->delete();
    }
};

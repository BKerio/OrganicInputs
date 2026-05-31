<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('addon_settings')
            ->where('key_name', 'mpesa')
            ->where('settings_type', 'payment_config')
            ->exists();

        if ($exists) {
            return;
        }

        $values = json_encode([
            'gateway' => 'mpesa',
            'mode' => 'test',
            'status' => '0',
        ]);

        DB::table('addon_settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'mpesa',
            'live_values' => $values,
            'test_values' => $values,
            'settings_type' => 'payment_config',
            'mode' => 'test',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'additional_data' => json_encode([
                'gateway_title' => 'M-Pesa',
                'gateway_image' => null,
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'mpesa')
            ->where('settings_type', 'payment_config')
            ->delete();
    }
};

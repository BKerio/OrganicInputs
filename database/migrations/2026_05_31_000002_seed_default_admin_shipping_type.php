<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('shipping_types')->where('seller_id', 0)->exists()) {
            DB::table('shipping_types')->insert([
                'seller_id' => 0,
                'shipping_type' => 'order_wise',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep shipping type data on rollback.
    }
};

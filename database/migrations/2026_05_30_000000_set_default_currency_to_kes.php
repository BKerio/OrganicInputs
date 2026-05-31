<?php

use App\Models\BusinessSetting;
use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Currency::where('id', 1)->update([
            'name' => 'Kenyan Shilling',
            'symbol' => 'Ksh',
            'code' => 'KES',
            'exchange_rate' => '1',
            'status' => 1,
        ]);

        Currency::where('id', '!=', 1)->update(['status' => 0]);

        BusinessSetting::updateOrCreate(
            ['type' => 'system_default_currency'],
            ['value' => '1']
        );

        BusinessSetting::updateOrCreate(
            ['type' => 'currency_model'],
            ['value' => 'single_currency']
        );

        if (DB::getSchemaBuilder()->hasColumn('orders', 'currency_code')) {
            DB::table('orders')->where('currency_code', 'USD')->update(['currency_code' => 'KES']);
        }
    }

    public function down(): void
    {
        Currency::where('id', 1)->update([
            'name' => 'USD',
            'symbol' => '$',
            'code' => 'USD',
            'exchange_rate' => '1',
            'status' => 1,
        ]);

        Currency::whereIn('id', [2, 3, 4, 5, 6, 7])->update(['status' => 1]);

        if (DB::getSchemaBuilder()->hasColumn('orders', 'currency_code')) {
            DB::table('orders')->where('currency_code', 'KES')->update(['currency_code' => 'USD']);
        }
    }
};

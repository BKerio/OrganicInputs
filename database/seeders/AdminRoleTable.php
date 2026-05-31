<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleTable extends Seeder
{
    /**


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminRoleTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Master Admin']
        );

        DB::table('admin_roles')->updateOrInsert(
            ['id' => 2],
            ['name' => 'Employee']
        );
    }
}

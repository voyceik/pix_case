<?php
declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;
use Ramsey\Uuid\Uuid;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Db::table('account')->insert([
            [
                'id' => '3cf61147-436e-4219-b92b-cbe8d7d58fd7',
                'name' => 'Ricardo Voyceik',
                'balance' => 100.00,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}

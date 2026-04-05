<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class WidgetSeeder extends Seeder
{
    public function run()
    {
        (new \App\Services\WidgetService())->sync();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $named = [
            ['name' => 'Acme Corporation', 'email' => 'hello@acme.test'],
            ['name' => 'Globex Industries', 'email' => 'contact@globex.test'],
            ['name' => 'Initech', 'email' => 'billing@initech.test'],
            ['name' => 'Umbrella Group', 'email' => 'projects@umbrella.test'],
            ['name' => 'Stark Industries', 'email' => null],
            ['name' => 'Wayne Enterprises', 'email' => null],
        ];

        foreach ($named as $attrs) {
            Client::factory()->create($attrs);
        }

        Client::factory(8)->create();
        Client::factory(2)->create(['email' => null]);
    }
}

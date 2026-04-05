<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Client::all()->each(function (Client $client) {
            Project::factory(rand(1, 3))->create(['client_id' => $client->id]);
        });
    }
}

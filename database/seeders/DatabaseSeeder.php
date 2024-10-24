<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(EstadoSeeder::class);
        $this->call(RutaSeeder::class);
        $this->call(TipoDirectivoSeeder::class);
        $this->call(TipoOperadorSeeder::class);
        $this->call(TipoUltimaCorridaSeeder::class);
        $this->call(TipoUsuarioSeeder::class);
        $this->call(EmpresaSeeder::class);
        $this->call(ConvenioPagoSeeder::class);
        $this->call(NivelEscolaridadSeeder::class);
        $this->call(TipoMovimientoSeeder::class);
        /*User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);*/
    }
}

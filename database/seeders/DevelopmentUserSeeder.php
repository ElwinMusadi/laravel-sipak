<?php

namespace Database\Seeders;

use App\Models\Loket;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    /**
     * Seed only the documented local/testing accounts and their Loket master data.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $lokets = $this->seedLokets();

        foreach ($this->users($lokets) as $attributes) {
            User::query()->firstOrCreate(
                ['username' => $attributes['username']],
                $attributes,
            );
        }
    }

    /**
     * @return array<string, Loket>
     */
    private function seedLokets(): array
    {
        $definitions = [
            ['code' => 'SAMSAT-KANTOR', 'name' => 'SAMSAT Kantor', 'description' => 'Loket layanan SAMSAT di kantor.'],
            ['code' => 'SAMSAT-KELILING', 'name' => 'SAMSAT Keliling', 'description' => 'Loket layanan SAMSAT keliling.'],
            ['code' => 'SAMSAT-CORNER', 'name' => 'SAMSAT Corner', 'description' => 'Loket layanan SAMSAT Corner.'],
            ['code' => 'MPP', 'name' => 'Mall Pelayanan Publik', 'description' => 'Loket layanan di Mall Pelayanan Publik.'],
        ];

        $lokets = [];

        foreach ($definitions as $definition) {
            $loket = Loket::query()->where('code', $definition['code'])->first()
                ?? Loket::query()->where('name', $definition['name'])->first();

            if ($loket === null) {
                $loket = Loket::create([
                    ...$definition,
                    'is_active' => true,
                ]);
            }

            $lokets[$definition['code']] = $loket;
        }

        return $lokets;
    }

    /**
     * @param  array<string, Loket>  $lokets
     * @return list<array{name: string, username: string, nip: string, password: string, role: UserRole, loket_id: int|null, is_active: bool}>
     */
    private function users(array $lokets): array
    {
        return [
            [
                'name' => 'Elwin Bessiesura',
                'username' => 'elwinmusadi16',
                'nip' => '199707162025061002',
                'password' => Hash::make('password'),
                'role' => UserRole::Superadmin,
                'loket_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Yununs Asamani',
                'username' => 'yunus.asamani',
                'nip' => '197907302009011003',
                'password' => Hash::make('password'),
                'role' => UserRole::BendaharaBarang,
                'loket_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Simson Sae',
                'username' => 'simson.sae',
                'nip' => '197709032007011010',
                'password' => Hash::make('password'),
                'role' => UserRole::PetugasLoket,
                'loket_id' => $lokets['SAMSAT-KANTOR']->id,
                'is_active' => true,
            ],
            [
                'name' => 'Lily Toelle',
                'username' => 'lily.toelle',
                'nip' => '197012281993092001',
                'password' => Hash::make('password'),
                'role' => UserRole::PetugasPenetapan,
                'loket_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Jevon Wila Huky',
                'username' => 'jevon.wilahuky',
                'nip' => '200408152025211001',
                'password' => Hash::make('password'),
                'role' => UserRole::PetugasVerifikasi,
                'loket_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Skolastika G. Maing',
                'username' => 'nena.maing',
                'nip' => '198804212011012006',
                'password' => Hash::make('password'),
                'role' => UserRole::KasiePenetapan,
                'loket_id' => null,
                'is_active' => true,
            ],
            [
                'name' => "Jonny Alfreth Do'o",
                'username' => 'jonny.alfreth',
                'nip' => '197106152007011039',
                'password' => Hash::make('password'),
                'role' => UserRole::KasieVerifikasi,
                'loket_id' => null,
                'is_active' => true,
            ],
        ];
    }
}

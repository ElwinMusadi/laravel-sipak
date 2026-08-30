<?php

namespace App;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case PetugasLoket = 'petugas_loket';
    case PetugasPenetapan = 'petugas_penetapan';
    case KasiePenetapan = 'kasie_penetapan';
    case PetugasVerifikasi = 'petugas_verifikasi';
    case KasieVerifikasi = 'kasie_verifikasi';
    case BendaharaBarang = 'bendahara_barang';
    case KepalaUptd = 'kepala_uptd';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::PetugasLoket => 'Petugas Loket',
            self::PetugasPenetapan => 'Petugas Penetapan',
            self::KasiePenetapan => 'Kasie Penetapan',
            self::PetugasVerifikasi => 'Petugas Verifikasi',
            self::KasieVerifikasi => 'Kasie Verifikasi',
            self::BendaharaBarang => 'Bendahara Barang',
            self::KepalaUptd => 'Kepala UPTD',
        };
    }
}

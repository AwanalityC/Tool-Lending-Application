<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlatTest extends TestCase
{
    use RefreshDatabase;

    private function sebagaiPetugas(): static
    {
        return $this->actingAs(User::factory()->petugas()->create(), 'sanctum');
    }

    private function payload(Kategori $kategori, array $ubah = []): array
    {
        return array_merge([
            'kode' => 'lpt-001',
            'nama' => 'Laptop Asus',
            'kategori_id' => $kategori->id,
            'deskripsi' => 'Laptop praktik',
            'kondisi' => 'baik',
            'stok_total' => 10,
        ], $ubah);
    }

    private function payloadUbah(Alat $alat, array $ubah = []): array
    {
        return array_merge([
            'kode' => $alat->kode,
            'nama' => $alat->nama,
            'kategori_id' => $alat->kategori_id,
            'kondisi' => $alat->kondisi,
            'stok_total' => $alat->stok_total,
        ], $ubah);
    }

    private function buatTransaksi(Alat $alat, string $status): void
    {
        $id = DB::table('peminjaman')->insertGetId([
            'no_transaksi' => 'PJM-TEST-' . uniqid(),
            'user_id' => User::factory()->create()->id,
            'tanggal_pinjam' => '2026-10-10',
            'tanggal_jatuh_tempo' => '2026-10-12',
            'alasan' => 'Praktik',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peminjaman_detail')->insert([
            'peminjaman_id' => $id,
            'alat_id' => $alat->id,
            'jumlah' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ---------- Tambah ----------

    public function test_petugas_bisa_menambah_alat_dan_stok_dihitung_backend(): void
    {
        $kategori = Kategori::factory()->create();

        $this->sebagaiPetugas()->postJson('/api/alat', $this->payload($kategori, ['stok_rusak' => 2]))
            ->assertStatus(201)
            ->assertJsonPath('data.kode', 'LPT-001')   // otomatis huruf besar
            ->assertJsonPath('data.stok_total', 10)
            ->assertJsonPath('data.stok_rusak', 2)
            ->assertJsonPath('data.stok_tersedia', 8)
            ->assertJsonPath('data.stok_dipinjam', 0);
    }

    public function test_klien_tidak_bisa_memanipulasi_stok_tersedia(): void
    {
        $kategori = Kategori::factory()->create();

        $this->sebagaiPetugas()
            ->postJson('/api/alat', $this->payload($kategori, ['stok_tersedia' => 999, 'stok_dipinjam' => 5]))
            ->assertStatus(201)
            ->assertJsonPath('data.stok_tersedia', 10)
            ->assertJsonPath('data.stok_dipinjam', 0);
    }

    public function test_peminjam_tidak_bisa_menambah_ubah_hapus_alat(): void
    {
        $kategori = Kategori::factory()->create();
        $alat = Alat::factory()->create(['kategori_id' => $kategori->id]);
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam, 'sanctum')->postJson('/api/alat', $this->payload($kategori))->assertStatus(403);
        $this->actingAs($peminjam, 'sanctum')->putJson("/api/alat/{$alat->id}", $this->payloadUbah($alat))->assertStatus(403);
        $this->actingAs($peminjam, 'sanctum')->deleteJson("/api/alat/{$alat->id}")->assertStatus(403);
    }

    public function test_validasi_stok_dan_data_wajib(): void
    {
        $kategori = Kategori::factory()->create();
        $this->sebagaiPetugas();

        $this->postJson('/api/alat', $this->payload($kategori, ['stok_total' => 0]))
            ->assertStatus(422)->assertJsonValidationErrors('stok_total');
        $this->postJson('/api/alat', $this->payload($kategori, ['stok_total' => -5]))
            ->assertStatus(422)->assertJsonValidationErrors('stok_total');
        $this->postJson('/api/alat', $this->payload($kategori, ['stok_total' => 'abc']))
            ->assertStatus(422)->assertJsonValidationErrors('stok_total');
        $this->postJson('/api/alat', $this->payload($kategori, ['stok_rusak' => 11]))
            ->assertStatus(422)->assertJsonValidationErrors('stok_rusak');
        $this->postJson('/api/alat', $this->payload($kategori, ['kategori_id' => 99999]))
            ->assertStatus(422)->assertJsonValidationErrors('kategori_id');
        $this->postJson('/api/alat', $this->payload($kategori, ['kondisi' => 'hancur']))
            ->assertStatus(422)->assertJsonValidationErrors('kondisi');
        $this->postJson('/api/alat', $this->payload($kategori, ['nama' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_kode_alat_duplikat_ditolak(): void
    {
        $kategori = Kategori::factory()->create();
        Alat::factory()->create(['kode' => 'LPT-001', 'kategori_id' => $kategori->id]);

        $this->sebagaiPetugas()->postJson('/api/alat', $this->payload($kategori, ['kode' => 'lpt-001']))
            ->assertStatus(422)->assertJsonValidationErrors('kode');
    }

    // ---------- Lihat, cari, filter ----------

    public function test_peminjam_hanya_melihat_alat_aktif_dan_tanpa_rincian_internal(): void
    {
        Alat::factory()->create(['nama' => 'Alat Aktif']);
        $nonaktif = Alat::factory()->create(['nama' => 'Alat Nonaktif', 'aktif' => false]);
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam, 'sanctum')->getJson('/api/alat')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Alat Aktif')
            ->assertJsonMissingPath('data.0.stok_rusak')
            ->assertJsonMissingPath('data.0.stok_dipinjam');

        $this->actingAs($peminjam, 'sanctum')->getJson("/api/alat/{$nonaktif->id}")->assertStatus(404);
    }

    public function test_staf_melihat_semua_alat_termasuk_nonaktif(): void
    {
        Alat::factory()->create();
        Alat::factory()->create(['aktif' => false]);

        $this->sebagaiPetugas()->getJson('/api/alat')->assertOk()->assertJsonCount(2, 'data');
        $this->sebagaiPetugas()->getJson('/api/alat?aktif=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pencarian_berdasarkan_nama_atau_kode(): void
    {
        Alat::factory()->create(['nama' => 'Laptop Asus', 'kode' => 'LPT-001']);
        Alat::factory()->create(['nama' => 'Proyektor Epson', 'kode' => 'PRJ-001']);

        $this->sebagaiPetugas()->getJson('/api/alat?q=laptop')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.kode', 'LPT-001');
        $this->sebagaiPetugas()->getJson('/api/alat?q=prj')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Proyektor Epson');
        $this->sebagaiPetugas()->getJson('/api/alat?q=tidak-ada')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_filter_kategori_kondisi_dan_ketersediaan(): void
    {
        $a = Kategori::factory()->create();
        $b = Kategori::factory()->create();
        Alat::factory()->create(['kategori_id' => $a->id]);
        Alat::factory()->create(['kategori_id' => $b->id, 'kondisi' => 'rusak_ringan']);
        Alat::factory()->create([
            'kategori_id' => $b->id,
            'stok_total' => 5,
            'stok_tersedia' => 0,
            'stok_dipinjam' => 5,
            'stok_rusak' => 0,
        ]);

        $this->sebagaiPetugas();
        $this->getJson("/api/alat?kategori_id={$a->id}")->assertJsonCount(1, 'data');
        $this->getJson("/api/alat?kategori_id={$b->id}")->assertJsonCount(2, 'data');
        $this->getJson('/api/alat?kondisi=rusak_ringan')->assertJsonCount(1, 'data');
        $this->getJson('/api/alat?tersedia=1')->assertJsonCount(2, 'data');
    }

    public function test_status_ketersediaan_habis(): void
    {
        $alat = Alat::factory()->create([
            'stok_total' => 3,
            'stok_tersedia' => 0,
            'stok_dipinjam' => 3,
            'stok_rusak' => 0,
        ]);

        $this->sebagaiPetugas()->getJson("/api/alat/{$alat->id}")
            ->assertOk()->assertJsonPath('data.status_ketersediaan', 'Habis');
    }

    // ---------- Ubah ----------

    public function test_menaikkan_stok_total_menambah_stok_tersedia(): void
    {
        $alat = Alat::factory()->create([
            'stok_total' => 10,
            'stok_tersedia' => 4,
            'stok_dipinjam' => 6,
            'stok_rusak' => 0,
        ]);

        $this->sebagaiPetugas()->putJson("/api/alat/{$alat->id}", $this->payloadUbah($alat, ['stok_total' => 12]))
            ->assertOk()
            ->assertJsonPath('data.stok_total', 12)
            ->assertJsonPath('data.stok_tersedia', 6)
            ->assertJsonPath('data.stok_dipinjam', 6);
    }

    public function test_stok_total_tidak_boleh_lebih_kecil_dari_yang_sedang_dipinjam(): void
    {
        $alat = Alat::factory()->create([
            'stok_total' => 10,
            'stok_tersedia' => 4,
            'stok_dipinjam' => 6,
            'stok_rusak' => 0,
        ]);

        $this->sebagaiPetugas()->putJson("/api/alat/{$alat->id}", $this->payloadUbah($alat, ['stok_total' => 5]))
            ->assertStatus(409);

        // Data tidak berubah sebagian
        $this->assertDatabaseHas('alat', [
            'id' => $alat->id,
            'stok_total' => 10,
            'stok_tersedia' => 4,
            'stok_dipinjam' => 6,
        ]);
    }

    public function test_alat_selesai_diperbaiki_kembali_ke_stok_tersedia(): void
    {
        $alat = Alat::factory()->create([
            'stok_total' => 10,
            'stok_tersedia' => 7,
            'stok_dipinjam' => 0,
            'stok_rusak' => 3,
        ]);

        $this->sebagaiPetugas()
            ->putJson("/api/alat/{$alat->id}", $this->payloadUbah($alat, ['stok_rusak' => 1]))
            ->assertOk()
            ->assertJsonPath('data.stok_rusak', 1)
            ->assertJsonPath('data.stok_tersedia', 9);
    }

    public function test_edit_alat_boleh_memakai_kode_sendiri(): void
    {
        $alat = Alat::factory()->create(['kode' => 'ABC-1']);

        $this->sebagaiPetugas()
            ->putJson("/api/alat/{$alat->id}", $this->payloadUbah($alat, ['nama' => 'Nama Baru']))
            ->assertOk()->assertJsonPath('data.nama', 'Nama Baru');
    }

    // ---------- Hapus ----------

    public function test_hapus_alat_tanpa_transaksi_aktif_adalah_soft_delete(): void
    {
        $alat = Alat::factory()->create();

        $this->sebagaiPetugas()->deleteJson("/api/alat/{$alat->id}")->assertOk();

        $this->assertSoftDeleted('alat', ['id' => $alat->id]);
        $this->sebagaiPetugas()->getJson("/api/alat/{$alat->id}")->assertStatus(404);
    }

    public function test_hapus_alat_dengan_pengajuan_menunggu_atau_disetujui_ditolak(): void
    {
        $menunggu = Alat::factory()->create();
        $disetujui = Alat::factory()->create();
        $this->buatTransaksi($menunggu, 'menunggu');
        $this->buatTransaksi($disetujui, 'disetujui');

        $this->sebagaiPetugas()->deleteJson("/api/alat/{$menunggu->id}")->assertStatus(409);
        $this->sebagaiPetugas()->deleteJson("/api/alat/{$disetujui->id}")->assertStatus(409);

        $this->assertDatabaseHas('alat', ['id' => $menunggu->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('alat', ['id' => $disetujui->id, 'deleted_at' => null]);
    }

    public function test_alat_dengan_transaksi_selesai_boleh_dihapus_dan_riwayat_tetap_ada(): void
    {
        $alat = Alat::factory()->create();
        $this->buatTransaksi($alat, 'selesai');

        $this->sebagaiPetugas()->deleteJson("/api/alat/{$alat->id}")->assertOk();

        $this->assertSoftDeleted('alat', ['id' => $alat->id]);
        $this->assertDatabaseHas('peminjaman_detail', ['alat_id' => $alat->id]);
    }
}
<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    private function sebagaiPetugas(): static
    {
        return $this->actingAs(User::factory()->petugas()->create(), 'sanctum');
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/kategori')->assertStatus(401);
    }

    public function test_petugas_bisa_menambah_kategori(): void
    {
        $this->sebagaiPetugas()
            ->postJson('/api/kategori', ['nama' => 'Elektronik', 'deskripsi' => 'Perangkat elektronik'])
            ->assertStatus(201)
            ->assertJsonPath('data.nama', 'Elektronik');

        $this->assertDatabaseHas('kategori', ['nama' => 'Elektronik']);
    }

    public function test_peminjam_bisa_melihat_tetapi_tidak_bisa_mengubah_kategori(): void
    {
        $kategori = Kategori::factory()->create();
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam, 'sanctum')->getJson('/api/kategori')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($peminjam, 'sanctum')->postJson('/api/kategori', ['nama' => 'Baru'])->assertStatus(403);
        $this->actingAs($peminjam, 'sanctum')->putJson("/api/kategori/{$kategori->id}", ['nama' => 'Ubah'])->assertStatus(403);
        $this->actingAs($peminjam, 'sanctum')->deleteJson("/api/kategori/{$kategori->id}")->assertStatus(403);
    }

    public function test_nama_kategori_duplikat_ditolak(): void
    {
        Kategori::factory()->create(['nama' => 'Elektronik']);

        $this->sebagaiPetugas()
            ->postJson('/api/kategori', ['nama' => 'Elektronik'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama');
    }

    public function test_nama_kosong_ditolak(): void
    {
        $this->sebagaiPetugas()
            ->postJson('/api/kategori', ['nama' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nama');
    }

    public function test_edit_kategori_boleh_memakai_nama_sendiri_tetapi_tidak_nama_lain(): void
    {
        $a = Kategori::factory()->create(['nama' => 'Alpha']);
        Kategori::factory()->create(['nama' => 'Beta']);

        $this->sebagaiPetugas()->putJson("/api/kategori/{$a->id}", ['nama' => 'Alpha', 'deskripsi' => 'baru'])->assertOk();
        $this->sebagaiPetugas()->putJson("/api/kategori/{$a->id}", ['nama' => 'Beta'])
            ->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_detail_kategori_menampilkan_jumlah_alat(): void
    {
        $kategori = Kategori::factory()->create();
        Alat::factory()->count(2)->create(['kategori_id' => $kategori->id]);

        $this->sebagaiPetugas()->getJson("/api/kategori/{$kategori->id}")
            ->assertOk()->assertJsonPath('data.jumlah_alat', 2);
    }

    public function test_kategori_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->sebagaiPetugas()->getJson('/api/kategori/99999')->assertStatus(404);
    }

    public function test_hapus_kategori_kosong_berhasil(): void
    {
        $kategori = Kategori::factory()->create();

        $this->sebagaiPetugas()->deleteJson("/api/kategori/{$kategori->id}")->assertOk();

        $this->assertDatabaseMissing('kategori', ['id' => $kategori->id]);
    }

    public function test_hapus_kategori_yang_dipakai_alat_ditolak(): void
    {
        $kategori = Kategori::factory()->create();
        Alat::factory()->create(['kategori_id' => $kategori->id]);

        $this->sebagaiPetugas()->deleteJson("/api/kategori/{$kategori->id}")
            ->assertStatus(409)->assertJsonPath('sukses', false);

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
    }

    public function test_hapus_kategori_yang_dipakai_alat_terhapus_lunak_tetap_ditolak(): void
    {
        $kategori = Kategori::factory()->create();
        Alat::factory()->create(['kategori_id' => $kategori->id])->delete();

        $this->sebagaiPetugas()->deleteJson("/api/kategori/{$kategori->id}")->assertStatus(409);

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
    }
}
<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanTest extends TestCase
{
    use RefreshDatabase;

    /* ---------- Pembantu ---------- */

    private function peminjam(): User
    {
        return User::factory()->create();
    }

    private function petugas(): User
    {
        return User::factory()->petugas()->create();
    }

    /** Alat dengan seluruh stok tersedia (tidak ada yang dipinjam atau rusak). */
    private function alat(int $total = 10, array $ubah = []): Alat
    {
        return Alat::factory()->create(array_merge([
            'stok_total' => $total,
            'stok_tersedia' => $total,
            'stok_dipinjam' => 0,
            'stok_rusak' => 0,
        ], $ubah));
    }

    private function payload(array $items, array $ubah = []): array
    {
        return array_merge([
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(3)->toDateString(),
            'alasan' => 'Praktikum jaringan kelas XI',
            'items' => $items,
        ], $ubah);
    }

    /** Membuat pengajuan langsung di database. $itemAlat berisi pasangan [Alat, jumlah]. */
    private function buatPengajuan(User $peminjam, array $itemAlat, array $atribut = []): Peminjaman
    {
        $p = Peminjaman::factory()->create(array_merge(['user_id' => $peminjam->id], $atribut));

        foreach ($itemAlat as [$alat, $jumlah]) {
            $p->detail()->create(['alat_id' => $alat->id, 'jumlah' => $jumlah]);
        }

        return $p;
    }

    /* ---------- Pengajuan ---------- */

    public function test_peminjam_bisa_mengajukan_peminjaman(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10);
        $b = $this->alat(5);

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/peminjaman', $this->payload([
            ['alat_id' => $a->id, 'jumlah' => 2],
            ['alat_id' => $b->id, 'jumlah' => 1],
        ]));

        $res->assertStatus(201)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.total_item', 3)
            ->assertJsonCount(2, 'data.items');

        $this->assertMatchesRegularExpression('/^PJM-\d{8}-0001$/', $res->json('data.no_transaksi'));
        $this->assertDatabaseHas('peminjaman', ['user_id' => $user->id, 'status' => 'menunggu']);
        $this->assertDatabaseCount('peminjaman_detail', 2);

        // Pengajuan tidak boleh mengubah stok.
        $this->assertSame(10, $a->fresh()->stok_tersedia);
        $this->assertSame(0, $a->fresh()->stok_dipinjam);
    }

    public function test_nomor_transaksi_bertambah_berurutan(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10);
        $data = $this->payload([['alat_id' => $a->id, 'jumlah' => 1]]);

        $this->actingAs($user, 'sanctum');
        $pertama = $this->postJson('/api/peminjaman', $data)->assertStatus(201);
        $kedua = $this->postJson('/api/peminjaman', $data)->assertStatus(201);

        $this->assertMatchesRegularExpression('/-0001$/', $pertama->json('data.no_transaksi'));
        $this->assertMatchesRegularExpression('/-0002$/', $kedua->json('data.no_transaksi'));
    }

    public function test_peminjam_tidak_bisa_mengajukan_atas_nama_orang_lain(): void
    {
        $user = $this->peminjam();
        $lain = $this->peminjam();
        $a = $this->alat(10);

        $this->actingAs($user, 'sanctum')->postJson('/api/peminjaman', $this->payload(
            [['alat_id' => $a->id, 'jumlah' => 1]],
            ['user_id' => $lain->id]
        ))->assertStatus(201);

        $this->assertDatabaseHas('peminjaman', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('peminjaman', ['user_id' => $lain->id]);
    }

    public function test_sisa_hari_dihitung_untuk_peminjaman_berjalan(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10, ['stok_tersedia' => 9, 'stok_dipinjam' => 1]);
        $p = Peminjaman::factory()->disetujui()->create([
            'user_id' => $user->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(2)->toDateString(),
        ]);
        $p->detail()->create(['alat_id' => $a->id, 'jumlah' => 1]);

        $this->actingAs($user, 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertOk()
            ->assertJsonPath('data.sisa_hari', 2);
    }

    public function test_tanggal_jatuh_tempo_sebelum_tanggal_pinjam_ditolak(): void
    {
        $a = $this->alat(10);

        $this->actingAs($this->peminjam(), 'sanctum')->postJson('/api/peminjaman', $this->payload(
            [['alat_id' => $a->id, 'jumlah' => 1]],
            ['tanggal_pinjam' => now()->addDays(5)->toDateString(), 'tanggal_jatuh_tempo' => now()->addDays(2)->toDateString()]
        ))->assertStatus(422)->assertJsonValidationErrors('tanggal_jatuh_tempo');
    }

    public function test_tanggal_pinjam_di_masa_lalu_ditolak(): void
    {
        $a = $this->alat(10);

        $this->actingAs($this->peminjam(), 'sanctum')->postJson('/api/peminjaman', $this->payload(
            [['alat_id' => $a->id, 'jumlah' => 1]],
            ['tanggal_pinjam' => now()->subDay()->toDateString()]
        ))->assertStatus(422)->assertJsonValidationErrors('tanggal_pinjam');
    }

    public function test_jumlah_nol_atau_negatif_ditolak(): void
    {
        $a = $this->alat(10);
        $this->actingAs($this->peminjam(), 'sanctum');

        $this->postJson('/api/peminjaman', $this->payload([['alat_id' => $a->id, 'jumlah' => 0]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.jumlah');

        $this->postJson('/api/peminjaman', $this->payload([['alat_id' => $a->id, 'jumlah' => -2]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.jumlah');
    }

    public function test_alasan_kosong_ditolak(): void
    {
        $a = $this->alat(10);

        $this->actingAs($this->peminjam(), 'sanctum')->postJson('/api/peminjaman', $this->payload(
            [['alat_id' => $a->id, 'jumlah' => 1]],
            ['alasan' => '']
        ))->assertStatus(422)->assertJsonValidationErrors('alasan');
    }

    public function test_daftar_alat_kosong_ditolak(): void
    {
        $this->actingAs($this->peminjam(), 'sanctum')
            ->postJson('/api/peminjaman', $this->payload([]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_alat_yang_sama_dua_kali_dalam_satu_pengajuan_ditolak(): void
    {
        $a = $this->alat(10);

        $this->actingAs($this->peminjam(), 'sanctum')->postJson('/api/peminjaman', $this->payload([
            ['alat_id' => $a->id, 'jumlah' => 1],
            ['alat_id' => $a->id, 'jumlah' => 2],
        ]))->assertStatus(422);

        $this->assertDatabaseCount('peminjaman', 0);
    }

    public function test_alat_nonaktif_tidak_bisa_diajukan(): void
    {
        $a = $this->alat(5, ['aktif' => false]);

        $this->actingAs($this->peminjam(), 'sanctum')
            ->postJson('/api/peminjaman', $this->payload([['alat_id' => $a->id, 'jumlah' => 1]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.alat_id');
    }

    public function test_jumlah_melebihi_stok_ditolak_dan_tidak_ada_data_tersimpan(): void
    {
        $a = $this->alat(3);

        $this->actingAs($this->peminjam(), 'sanctum')
            ->postJson('/api/peminjaman', $this->payload([['alat_id' => $a->id, 'jumlah' => 5]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.jumlah');

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertDatabaseCount('peminjaman_detail', 0);
    }

    public function test_petugas_tidak_bisa_mengajukan_peminjaman(): void
    {
        $a = $this->alat(10);

        $this->actingAs($this->petugas(), 'sanctum')
            ->postJson('/api/peminjaman', $this->payload([['alat_id' => $a->id, 'jumlah' => 1]]))
            ->assertStatus(403);
    }

    public function test_pengajuan_tanpa_login_ditolak(): void
    {
        $this->postJson('/api/peminjaman', [])->assertStatus(401);
    }

    /* ---------- Hak akses melihat data ---------- */

    public function test_peminjam_bisa_melihat_detail_miliknya(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($user, [[$a, 2]]);

        $this->actingAs($user, 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertOk()
            ->assertJsonPath('data.no_transaksi', $p->no_transaksi)
            ->assertJsonPath('data.items.0.jumlah', 2);
    }

    public function test_peminjam_tidak_bisa_melihat_peminjaman_milik_orang_lain(): void
    {
        $pemilik = $this->peminjam();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($pemilik, [[$a, 1]]);

        // 404 (bukan 403) supaya keberadaan data orang lain tidak bocor.
        $this->actingAs($this->peminjam(), 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertStatus(404);
    }

    public function test_staf_bisa_melihat_detail_peminjaman_siapa_pun(): void
    {
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 1]]);

        $this->actingAs($this->petugas(), 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $p->id);
    }

    public function test_peminjam_tidak_bisa_melihat_daftar_semua_peminjaman(): void
    {
        $this->actingAs($this->peminjam(), 'sanctum')->getJson('/api/peminjaman')->assertStatus(403);
    }

    public function test_staf_bisa_melihat_semua_pengajuan(): void
    {
        $a = $this->alat(10);
        $this->buatPengajuan($this->peminjam(), [[$a, 1]]);
        $this->buatPengajuan($this->peminjam(), [[$a, 1]]);

        $this->actingAs($this->petugas(), 'sanctum')->getJson('/api/peminjaman')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_staf_bisa_memfilter_pengajuan_berdasarkan_status(): void
    {
        $a = $this->alat(10);
        $this->buatPengajuan($this->peminjam(), [[$a, 1]]);
        $this->buatPengajuan($this->peminjam(), [[$a, 1]]);
        $this->buatPengajuan($this->peminjam(), [[$a, 1]], ['status' => 'ditolak']);

        $this->actingAs($this->petugas(), 'sanctum')->getJson('/api/peminjaman?status=ditolak')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_riwayat_hanya_menampilkan_milik_sendiri(): void
    {
        $user = $this->peminjam();
        $lain = $this->peminjam();
        $a = $this->alat(10);

        $this->buatPengajuan($user, [[$a, 1]]);
        $this->buatPengajuan($user, [[$a, 1]]);
        $this->buatPengajuan($lain, [[$a, 1]]);

        $this->actingAs($user, 'sanctum')->getJson('/api/riwayat-peminjaman')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_riwayat_tidak_bisa_dipakai_melihat_milik_orang_lain_lewat_filter(): void
    {
        $user = $this->peminjam();
        $lain = $this->peminjam();
        $a = $this->alat(10);
        $this->buatPengajuan($lain, [[$a, 1]]);

        $this->actingAs($user, 'sanctum')->getJson("/api/riwayat-peminjaman?user_id={$lain->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_staf_tidak_bisa_membuka_endpoint_riwayat_peminjam(): void
    {
        $this->actingAs($this->petugas(), 'sanctum')->getJson('/api/riwayat-peminjaman')->assertStatus(403);
    }

    /* ---------- Status "terlambat" (dihitung, tidak disimpan) ---------- */

    public function test_peminjaman_yang_lewat_jatuh_tempo_ditandai_terlambat(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10, ['stok_tersedia' => 8, 'stok_dipinjam' => 2]);
        $p = Peminjaman::factory()->terlambat()->create(['user_id' => $user->id]);
        $p->detail()->create(['alat_id' => $a->id, 'jumlah' => 2]);

        $this->actingAs($user, 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui')
            ->assertJsonPath('data.terlambat', true)
            ->assertJsonPath('data.hari_terlambat', 2)
            ->assertJsonPath('data.status_tampilan', 'Terlambat');
    }

    public function test_pada_hari_jatuh_tempo_belum_dianggap_terlambat(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10, ['stok_tersedia' => 9, 'stok_dipinjam' => 1]);
        $p = Peminjaman::factory()->disetujui()->create([
            'user_id' => $user->id,
            'tanggal_pinjam' => now()->subDays(3)->toDateString(),
            'tanggal_jatuh_tempo' => now()->toDateString(),
        ]);
        $p->detail()->create(['alat_id' => $a->id, 'jumlah' => 1]);

        $this->actingAs($user, 'sanctum')->getJson("/api/peminjaman/{$p->id}")
            ->assertOk()
            ->assertJsonPath('data.terlambat', false)
            ->assertJsonPath('data.hari_terlambat', 0)
            ->assertJsonPath('data.status_tampilan', 'Disetujui');
    }

    /* ---------- Persetujuan ---------- */

    public function test_menyetujui_pengajuan_mengurangi_stok(): void
    {
        $petugas = $this->petugas();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 3]]);

        $this->actingAs($petugas, 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui')
            ->assertJsonPath('data.diproses_oleh.id', $petugas->id);

        $a->refresh();
        $this->assertSame(7, $a->stok_tersedia);
        $this->assertSame(3, $a->stok_dipinjam);
        $this->assertSame(10, $a->stok_total);

        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'disetujui', 'diproses_oleh' => $petugas->id]);
    }

    public function test_menyetujui_pengajuan_dengan_banyak_alat_mengurangi_semua_stok(): void
    {
        $a = $this->alat(10);
        $b = $this->alat(4);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 2], [$b, 4]]);

        $this->actingAs($this->petugas(), 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")->assertOk();

        $this->assertSame(8, $a->fresh()->stok_tersedia);
        $this->assertSame(0, $b->fresh()->stok_tersedia);
        $this->assertSame(4, $b->fresh()->stok_dipinjam);
    }

    public function test_peminjam_tidak_bisa_menyetujui_pengajuan(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($user, [[$a, 1]]);

        $this->actingAs($user, 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")->assertStatus(403);

        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'menunggu']);
        $this->assertSame(10, $a->fresh()->stok_tersedia);
    }

    public function test_menyetujui_pengajuan_yang_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->petugas(), 'sanctum')->postJson('/api/peminjaman/99999/setujui')->assertStatus(404);
    }

    public function test_persetujuan_gagal_bila_stok_tidak_cukup_dan_tidak_ada_perubahan_sebagian(): void
    {
        $a = $this->alat(10);
        $b = $this->alat(5);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 2], [$b, 3]]);

        // Setelah pengajuan dibuat, stok alat B terpakai orang lain: tersedia tinggal 1.
        Alat::whereKey($b->id)->update(['stok_tersedia' => 1, 'stok_dipinjam' => 4]);

        $this->actingAs($this->petugas(), 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")
            ->assertStatus(409)
            ->assertJsonPath('sukses', false);

        // Alat A (yang sebenarnya cukup) TIDAK boleh ikut berkurang.
        $this->assertSame(10, $a->fresh()->stok_tersedia);
        $this->assertSame(0, $a->fresh()->stok_dipinjam);
        $this->assertSame(1, $b->fresh()->stok_tersedia);
        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'menunggu', 'diproses_oleh' => null]);
    }

    public function test_persetujuan_gagal_bila_alat_dinonaktifkan_setelah_pengajuan(): void
    {
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 1]]);
        Alat::whereKey($a->id)->update(['aktif' => false]);

        $this->actingAs($this->petugas(), 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")
            ->assertStatus(409);

        $this->assertSame(10, $a->fresh()->stok_tersedia);
        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'menunggu']);
    }

    public function test_menyetujui_dua_kali_ditolak_dan_stok_hanya_berkurang_sekali(): void
    {
        $petugas = $this->petugas();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 3]]);

        $this->actingAs($petugas, 'sanctum');
        $this->postJson("/api/peminjaman/{$p->id}/setujui")->assertOk();
        $this->postJson("/api/peminjaman/{$p->id}/setujui")->assertStatus(409);

        $this->assertSame(7, $a->fresh()->stok_tersedia);
        $this->assertSame(3, $a->fresh()->stok_dipinjam);
    }

    public function test_dua_pengajuan_bersaing_untuk_stok_terbatas_hanya_satu_yang_berhasil(): void
    {
        $a = $this->alat(5);
        $pertama = $this->buatPengajuan($this->peminjam(), [[$a, 3]]);
        $kedua = $this->buatPengajuan($this->peminjam(), [[$a, 3]]);

        $this->actingAs($this->petugas(), 'sanctum');
        $this->postJson("/api/peminjaman/{$pertama->id}/setujui")->assertOk();
        $this->postJson("/api/peminjaman/{$kedua->id}/setujui")->assertStatus(409);

        $a->refresh();
        $this->assertSame(2, $a->stok_tersedia);
        $this->assertSame(3, $a->stok_dipinjam);
        $this->assertDatabaseHas('peminjaman', ['id' => $kedua->id, 'status' => 'menunggu']);
    }

    /* ---------- Penolakan ---------- */

    public function test_menolak_pengajuan_dengan_alasan_tidak_mengubah_stok(): void
    {
        $petugas = $this->petugas();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 3]]);

        $this->actingAs($petugas, 'sanctum')
            ->postJson("/api/peminjaman/{$p->id}/tolak", ['alasan_penolakan' => 'Alat sedang dipakai ujian.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ditolak')
            ->assertJsonPath('data.alasan_penolakan', 'Alat sedang dipakai ujian.');

        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'ditolak', 'diproses_oleh' => $petugas->id]);
        $this->assertSame(10, $a->fresh()->stok_tersedia);
        $this->assertSame(0, $a->fresh()->stok_dipinjam);
    }

    public function test_menolak_tanpa_alasan_ditolak(): void
    {
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 1]]);

        $this->actingAs($this->petugas(), 'sanctum')
            ->postJson("/api/peminjaman/{$p->id}/tolak", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('alasan_penolakan');

        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'menunggu']);
    }

    public function test_peminjam_tidak_bisa_menolak_pengajuan(): void
    {
        $user = $this->peminjam();
        $a = $this->alat(10);
        $p = $this->buatPengajuan($user, [[$a, 1]]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/peminjaman/{$p->id}/tolak", ['alasan_penolakan' => 'Coba-coba menolak'])
            ->assertStatus(403);
    }

    public function test_pengajuan_yang_sudah_ditolak_tidak_bisa_disetujui(): void
    {
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 2]], ['status' => 'ditolak']);

        $this->actingAs($this->petugas(), 'sanctum')->postJson("/api/peminjaman/{$p->id}/setujui")
            ->assertStatus(409);

        $this->assertSame(10, $a->fresh()->stok_tersedia);
    }

    public function test_pengajuan_yang_sudah_disetujui_tidak_bisa_ditolak(): void
    {
        $a = $this->alat(10);
        $p = $this->buatPengajuan($this->peminjam(), [[$a, 2]]);

        $this->actingAs($this->petugas(), 'sanctum');
        $this->postJson("/api/peminjaman/{$p->id}/setujui")->assertOk();
        $this->postJson("/api/peminjaman/{$p->id}/tolak", ['alasan_penolakan' => 'Berubah pikiran'])
            ->assertStatus(409);

        $this->assertDatabaseHas('peminjaman', ['id' => $p->id, 'status' => 'disetujui']);
        // Stok tetap berkurang karena persetujuan tidak dibatalkan.
        $this->assertSame(8, $a->fresh()->stok_tersedia);
    }
}

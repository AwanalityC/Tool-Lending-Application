<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function dataRegistrasi(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'jenis_peminjam' => 'siswa',
        ], $ubah);
    }

    // ---------- Registrasi ----------

    public function test_registrasi_email_baru_berhasil(): void
    {
        $this->postJson('/api/register', $this->dataRegistrasi())
            ->assertStatus(201)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.role', 'peminjam');

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'peminjam']);
        // Password harus tersimpan sebagai hash, bukan teks biasa.
        $this->assertNotSame('Rahasia123', User::first()->password);
    }

    public function test_registrasi_email_duplikat_ditolak(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/register', $this->dataRegistrasi())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registrasi_tidak_bisa_memilih_peran_admin(): void
    {
        $this->postJson('/api/register', $this->dataRegistrasi(['role' => 'admin']))
            ->assertStatus(201)
            ->assertJsonPath('data.role', 'peminjam');

        $this->assertDatabaseMissing('users', ['role' => 'admin']);
    }

    // ---------- Login ----------

    public function test_login_berhasil_mengembalikan_token(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'Rahasia123']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'Rahasia123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'nama', 'role']]]);
    }

    public function test_login_password_salah_ditolak(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'Rahasia123']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'salah-total'])
            ->assertStatus(401)
            ->assertJsonPath('sukses', false);
    }

    public function test_login_akun_nonaktif_ditolak(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'Rahasia123', 'aktif' => false]);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'Rahasia123'])
            ->assertStatus(403);
    }

    // ---------- Token & sesi ----------

    public function test_me_tanpa_token_ditolak(): void
    {
        $this->getJson('/api/me')->assertStatus(401)->assertJsonPath('sukses', false);
    }

    public function test_me_dengan_token_tidak_valid_ditolak(): void
    {
        $this->withToken('token-ngawur')->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_dengan_token_kedaluwarsa_ditolak(): void
    {
        $user = User::factory()->create();
        // Token yang masa berlakunya sudah lewat satu menit lalu.
        $token = $user->createToken('mobile', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_dengan_token_valid_berhasil(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_mencabut_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ---------- Hak akses berdasarkan peran ----------

    public function test_peminjam_tidak_bisa_mengakses_endpoint_admin(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/pengguna')
            ->assertStatus(403);
    }

    public function test_petugas_tidak_bisa_mengakses_kelola_pengguna(): void
    {
        $this->actingAs(User::factory()->petugas()->create(), 'sanctum')
            ->getJson('/api/pengguna')
            ->assertStatus(403);
    }

    public function test_admin_bisa_melihat_daftar_pengguna(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->getJson('/api/pengguna')
            ->assertOk()
            ->assertJsonPath('sukses', true);
    }

    public function test_admin_bisa_membuat_akun_petugas(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->postJson('/api/pengguna', [
                'nama' => 'Petugas Baru',
                'email' => 'petugas.baru@example.com',
                'password' => 'Rahasia123',
                'role' => 'petugas',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.role', 'petugas');
    }

    public function test_admin_tidak_bisa_membuat_akun_dengan_peran_peminjam_lewat_endpoint_ini(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'sanctum')
            ->postJson('/api/pengguna', [
                'nama' => 'X',
                'email' => 'x@example.com',
                'password' => 'Rahasia123',
                'role' => 'peminjam',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }
}
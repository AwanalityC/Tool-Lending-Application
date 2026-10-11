import 'package:flutter_test/flutter_test.dart';
import 'package:pinjamalat_mobile/models/models.dart';

void main() {
  test('AppUser membaca peran dan jenis peminjam dari respons API', () {
    final user = AppUser.fromJson({
      'id': 7,
      'nama': 'Awan',
      'email': 'awan@example.test',
      'role': 'peminjam',
      'jenis_peminjam': 'siswa',
      'nomor_induk': '12345',
    });

    expect(user.id, 7);
    expect(user.nama, 'Awan');
    expect(user.isPeminjam, isTrue);
    expect(user.isStaff, isFalse);
    expect(user.jenisPeminjam, 'siswa');
  });

  test('Peminjaman membaca rincian item dan status terlambat', () {
    final loan = Peminjaman.fromJson({
      'id': 3,
      'no_transaksi': 'PJM-20261011-0003',
      'tanggal_pinjam': '2026-10-11',
      'tanggal_jatuh_tempo': '2026-10-12',
      'status': 'disetujui',
      'status_tampilan': 'Terlambat',
      'terlambat': true,
      'hari_terlambat': 2,
      'items': [
        {'id': 9, 'alat_id': 4, 'nama': 'Kabel LAN', 'kode': 'AL-01', 'jumlah': 2},
      ],
    });

    expect(loan.nomor, 'PJM-20261011-0003');
    expect(loan.totalItem, 2);
    expect(loan.items.single.nama, 'Kabel LAN');
    expect(loan.terlambat, isTrue);
  });
}

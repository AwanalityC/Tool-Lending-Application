class AppUser {
  const AppUser({
    required this.id,
    required this.nama,
    required this.email,
    required this.role,
    this.jenisPeminjam,
    this.nomorInduk,
  });

  final int id;
  final String nama;
  final String email;
  final String role;
  final String? jenisPeminjam;
  final String? nomorInduk;

  bool get isAdmin => role.toLowerCase() == 'admin';
  bool get isPetugas => role.toLowerCase() == 'petugas';
  bool get isPeminjam => role.toLowerCase() == 'peminjam';
  bool get isStaff => isAdmin || isPetugas;

  factory AppUser.fromJson(Map<String, dynamic> json) {
    final roleValue = json['role'] ?? json['peran'] ?? json['nama_peran'] ?? '';
    final role = roleValue is Map
        ? (roleValue['nama'] ?? roleValue['slug'] ?? '').toString()
        : roleValue.toString();
    return AppUser(
      id: _intValue(json['id']),
      nama: (json['nama'] ?? json['name'] ?? 'Pengguna').toString(),
      email: (json['email'] ?? '').toString(),
      role: role.isEmpty ? 'peminjam' : role,
      jenisPeminjam: json['jenis_peminjam']?.toString(),
      nomorInduk: json['nomor_induk']?.toString(),
    );
  }
}

class Kategori {
  const Kategori({
    required this.id,
    required this.nama,
    this.deskripsi = '',
    this.jumlahAlat = 0,
  });

  final int id;
  final String nama;
  final String deskripsi;
  final int jumlahAlat;

  factory Kategori.fromJson(Map<String, dynamic> json) => Kategori(
        id: _intValue(json['id']),
        nama: (json['nama'] ?? json['name'] ?? 'Kategori').toString(),
        deskripsi: (json['deskripsi'] ?? json['description'] ?? '').toString(),
        jumlahAlat: _intValue(json['jumlah_alat'] ?? json['alat_count']),
      );
}

class Alat {
  const Alat({
    required this.id,
    required this.nama,
    required this.kode,
    required this.stokTersedia,
    required this.stokTotal,
    required this.stokDipinjam,
    required this.stokRusak,
    this.kondisi = 'baik',
    this.kategoriId,
    this.kategoriNama = '',
    this.deskripsi = '',
    this.aktif = true,
  });

  final int id;
  final String nama;
  final String kode;
  final int stokTersedia;
  final int stokTotal;
  final int stokDipinjam;
  final int stokRusak;
  final String kondisi;
  final int? kategoriId;
  final String kategoriNama;
  final String deskripsi;
  final bool aktif;

  factory Alat.fromJson(Map<String, dynamic> json) {
    final category = json['kategori'] is Map
        ? Map<String, dynamic>.from(json['kategori'] as Map)
        : <String, dynamic>{};
    return Alat(
      id: _intValue(json['id']),
      nama: (json['nama'] ?? json['name'] ?? 'Alat').toString(),
      kode: (json['kode'] ?? json['kode_alat'] ?? '-').toString(),
      stokTersedia: _intValue(json['stok_tersedia'] ?? json['stok'] ?? json['tersedia']),
      stokTotal: _intValue(json['stok_total'] ?? json['stok_awal'] ?? json['stok_tersedia']),
      stokDipinjam: _intValue(json['stok_dipinjam']),
      stokRusak: _intValue(json['stok_rusak']),
      kondisi: (json['kondisi'] ?? 'baik').toString(),
      kategoriId: _nullableInt(json['kategori_id'] ?? category['id']),
      kategoriNama: (category['nama'] ?? json['kategori_nama'] ?? '').toString(),
      deskripsi: (json['deskripsi'] ?? json['description'] ?? '').toString(),
      aktif: _boolValue(json['aktif'], fallback: true),
    );
  }
}

class PeminjamanItem {
  const PeminjamanItem({
    required this.id,
    required this.alatId,
    required this.nama,
    required this.kode,
    required this.jumlah,
  });

  final int id;
  final int alatId;
  final String nama;
  final String kode;
  final int jumlah;

  factory PeminjamanItem.fromJson(Map<String, dynamic> json) => PeminjamanItem(
        id: _intValue(json['id']),
        alatId: _intValue(json['alat_id']),
        nama: (json['nama'] ?? 'Alat dihapus').toString(),
        kode: (json['kode'] ?? '-').toString(),
        jumlah: _intValue(json['jumlah']),
      );
}

class Peminjaman {
  const Peminjaman({
    required this.id,
    required this.nomor,
    required this.tanggalPinjam,
    required this.tanggalJatuhTempo,
    required this.alasan,
    required this.status,
    required this.statusTampilan,
    required this.totalItem,
    required this.items,
    this.namaPeminjam = '',
    this.emailPeminjam = '',
    this.alasanPenolakan,
    this.terlambat = false,
    this.hariTerlambat = 0,
    this.totalDenda = 0,
    this.tanggalKembali,
  });

  final int id;
  final String nomor;
  final String tanggalPinjam;
  final String tanggalJatuhTempo;
  final String alasan;
  final String status;
  final String statusTampilan;
  final int totalItem;
  final List<PeminjamanItem> items;
  final String namaPeminjam;
  final String emailPeminjam;
  final String? alasanPenolakan;
  final bool terlambat;
  final int hariTerlambat;
  final int totalDenda;
  final String? tanggalKembali;

  factory Peminjaman.fromJson(Map<String, dynamic> json) {
    final peminjam = json['peminjam'] is Map
        ? Map<String, dynamic>.from(json['peminjam'] as Map)
        : <String, dynamic>{};
    final rawItems = json['items'];
    final items = rawItems is List
        ? rawItems
            .whereType<Map>()
            .map((e) => PeminjamanItem.fromJson(Map<String, dynamic>.from(e)))
            .toList()
        : <PeminjamanItem>[];
    final status = (json['status'] ?? 'menunggu').toString();
    final pengembalian = json['pengembalian'] is Map
        ? Map<String, dynamic>.from(json['pengembalian'] as Map)
        : <String, dynamic>{};
    final dendaValue = json['total_denda'] ?? json['denda_total'] ?? pengembalian['total_denda'] ?? pengembalian['denda_total'] ?? 0;
    return Peminjaman(
      id: _intValue(json['id']),
      nomor: (json['no_transaksi'] ?? 'PJM-${json['id'] ?? '-'}').toString(),
      tanggalPinjam: (json['tanggal_pinjam'] ?? '-').toString(),
      tanggalJatuhTempo: (json['tanggal_jatuh_tempo'] ?? '-').toString(),
      alasan: (json['alasan'] ?? '').toString(),
      status: status,
      statusTampilan: (json['status_tampilan'] ?? json['status_label'] ?? status).toString(),
      totalItem: _intValue(json['total_item'], fallback: items.fold<int>(0, (sum, item) => sum + item.jumlah)),
      items: items,
      namaPeminjam: (peminjam['nama'] ?? '').toString(),
      emailPeminjam: (peminjam['email'] ?? '').toString(),
      alasanPenolakan: json['alasan_penolakan']?.toString(),
      terlambat: _boolValue(json['terlambat']),
      hariTerlambat: _intValue(json['hari_terlambat']),
      totalDenda: _intValue(dendaValue),
      tanggalKembali: (json['tanggal_kembali'] ?? pengembalian['tanggal_kembali'] ?? pengembalian['created_at'])?.toString(),
    );
  }
}

int _intValue(dynamic value, {int fallback = 0}) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse(value?.toString() ?? '') ?? fallback;
}

int? _nullableInt(dynamic value) {
  if (value == null) return null;
  return _intValue(value);
}

bool _boolValue(dynamic value, {bool fallback = false}) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  if (value is String) return ['1', 'true', 'yes', 'aktif'].contains(value.toLowerCase());
  return fallback;
}

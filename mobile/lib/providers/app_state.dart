import 'package:flutter/foundation.dart';

import '../config/app_config.dart';
import '../models/models.dart';
import '../services/api_service.dart';

class AppState extends ChangeNotifier {
  final ApiService api = ApiService();

  AppUser? user;
  bool initialized = false;
  bool busy = false;
  String? lastMessage;
  List<Alat> alat = [];
  List<Kategori> kategori = [];
  List<Peminjaman> peminjaman = [];
  List<Map<String, dynamic>> pengguna = [];

  bool get isLoggedIn => user != null;
  bool get isReturnEndpointConfigured => AppConfig.returnEndpointTemplate.trim().isNotEmpty;

  Future<void> initialize() async {
    final token = await api.readToken();
    if (token != null && token.isNotEmpty) {
      try {
        user = await _fetchCurrentUser();
      } on ApiException catch (error) {
        if (error.statusCode == 401) await api.clearToken();
        lastMessage = error.message;
      } catch (_) {
        lastMessage = 'Tidak dapat memulihkan sesi. Periksa koneksi ke server.';
      }
    }
    initialized = true;
    notifyListeners();
    if (user != null) await refreshAll();
  }

  Future<AppUser> _fetchCurrentUser() async {
    final response = await api.get('/me');
    var data = ApiService.unwrapMap(response);
    if (data['user'] is Map) data = Map<String, dynamic>.from(data['user'] as Map);
    if (data['pengguna'] is Map) data = Map<String, dynamic>.from(data['pengguna'] as Map);
    return AppUser.fromJson(data);
  }

  Future<void> login(String email, String password) async {
    await _run(() async {
      final response = await api.post('/login', data: {'email': email.trim(), 'password': password});
      final data = ApiService.unwrapMap(response);
      final token = (data['token'] ?? data['access_token'] ?? data['plain_text_token'])?.toString();
      if (token == null || token.isEmpty) {
        throw const ApiException('Login berhasil direspons server, tetapi token tidak ditemukan pada data.token. Periksa format respons AuthController.');
      }
      await api.saveToken(token);
      try {
        user = await _fetchCurrentUser();
      } catch (_) {
        final userData = data['user'] is Map
            ? Map<String, dynamic>.from(data['user'] as Map)
            : data['pengguna'] is Map
                ? Map<String, dynamic>.from(data['pengguna'] as Map)
                : <String, dynamic>{};
        if (userData.isEmpty) rethrow;
        user = AppUser.fromJson(userData);
      }
      lastMessage = ApiService.messageFrom(response, fallback: 'Login berhasil.');
    });
    if (user != null) await refreshAll();
  }

  Future<void> register({
    required String nama,
    required String email,
    required String password,
    required String jenisPeminjam,
    String nomorInduk = '',
  }) async {
    await _run(() async {
      final body = <String, dynamic>{
        'nama': nama.trim(),
        'email': email.trim(),
        'password': password,
        'password_confirmation': password,
        'jenis_peminjam': jenisPeminjam,
      };
      if (nomorInduk.trim().isNotEmpty) body['nomor_induk'] = nomorInduk.trim();
      final response = await api.post('/register', data: body);
      lastMessage = ApiService.messageFrom(response, fallback: 'Pendaftaran berhasil. Silakan login.');
    });
  }

  Future<void> logout() async {
    try {
      await api.post('/logout');
    } catch (_) {
      // Clear local token even when server is offline.
    }
    await api.clearToken();
    user = null;
    alat = [];
    kategori = [];
    peminjaman = [];
    pengguna = [];
    lastMessage = null;
    notifyListeners();
  }

  Future<void> refreshAll() async {
    await Future.wait([
      loadAlat(silent: true),
      loadKategori(silent: true),
      loadPeminjaman(silent: true),
      if (user?.isAdmin ?? false) loadPengguna(silent: true),
    ]);
    notifyListeners();
  }

  Future<void> loadAlat({bool silent = false}) async {
    if (!silent) busy = true;
    try {
      final response = await api.get('/alat', query: {'per_page': 100});
      alat = ApiService.unwrapList(response).map(Alat.fromJson).toList();
    } catch (error) {
      if (!silent) lastMessage = _friendly(error);
    } finally {
      if (!silent) busy = false;
      notifyListeners();
    }
  }

  Future<void> loadKategori({bool silent = false}) async {
    try {
      final response = await api.get('/kategori', query: {'per_page': 100});
      kategori = ApiService.unwrapList(response).map(Kategori.fromJson).toList();
    } catch (error) {
      if (!silent) lastMessage = _friendly(error);
    } finally {
      notifyListeners();
    }
  }

  Future<void> loadPeminjaman({bool silent = false, String? status}) async {
    try {
      final endpoint = user?.isStaff == true ? '/peminjaman' : '/riwayat-peminjaman';
      final query = <String, dynamic>{'per_page': 50};
      if (status != null && status != 'semua') query['status'] = status;
      final response = await api.get(endpoint, query: query);
      peminjaman = ApiService.unwrapList(response).map(Peminjaman.fromJson).toList();
    } catch (error) {
      if (!silent) lastMessage = _friendly(error);
    } finally {
      notifyListeners();
    }
  }

  Future<void> loadPengguna({bool silent = false}) async {
    try {
      final response = await api.get('/pengguna', query: {'per_page': 100});
      pengguna = ApiService.unwrapList(response);
    } catch (error) {
      if (!silent) lastMessage = _friendly(error);
    } finally {
      notifyListeners();
    }
  }

  Future<void> ajukanPeminjaman({
    required String tanggalPinjam,
    required String tanggalJatuhTempo,
    required String alasan,
    required Map<int, int> items,
  }) async {
    await _run(() async {
      final response = await api.post('/peminjaman', data: {
        'tanggal_pinjam': tanggalPinjam,
        'tanggal_jatuh_tempo': tanggalJatuhTempo,
        'alasan': alasan.trim(),
        'items': items.entries
            .where((entry) => entry.value > 0)
            .map((entry) => {'alat_id': entry.key, 'jumlah': entry.value})
            .toList(),
      });
      lastMessage = ApiService.messageFrom(response, fallback: 'Pengajuan peminjaman berhasil.');
    });
    await loadPeminjaman(silent: true);
  }

  Future<void> setujuiPeminjaman(Peminjaman loan) async {
    await _run(() async {
      final response = await api.post('/peminjaman/${loan.id}/setujui');
      lastMessage = ApiService.messageFrom(response, fallback: 'Peminjaman disetujui.');
    });
    await refreshAll();
  }

  Future<void> tolakPeminjaman(Peminjaman loan, String alasan) async {
    await _run(() async {
      final response = await api.post('/peminjaman/${loan.id}/tolak', data: {'alasan_penolakan': alasan.trim()});
      lastMessage = ApiService.messageFrom(response, fallback: 'Peminjaman ditolak.');
    });
    await loadPeminjaman(silent: true);
  }

  Future<void> simpanKategori({int? id, required String nama, String deskripsi = ''}) async {
    await _run(() async {
      final body = {'nama': nama.trim(), 'deskripsi': deskripsi.trim()};
      final response = id == null
          ? await api.post('/kategori', data: body)
          : await api.put('/kategori/$id', data: body);
      lastMessage = ApiService.messageFrom(response, fallback: 'Kategori berhasil disimpan.');
    });
    await loadKategori(silent: true);
  }

  Future<void> hapusKategori(int id) async {
    await _run(() async {
      final response = await api.delete('/kategori/$id');
      lastMessage = ApiService.messageFrom(response, fallback: 'Kategori berhasil dihapus.');
    });
    await loadKategori(silent: true);
  }

  Future<void> simpanAlat({
    int? id,
    required String kode,
    required String nama,
    required int kategoriId,
    required String deskripsi,
    required int stokTotal,
    required String kondisi,
    required bool aktif,
  }) async {
    await _run(() async {
      final body = <String, dynamic>{
        'kode': kode.trim(),
        'nama': nama.trim(),
        'kategori_id': kategoriId,
        'deskripsi': deskripsi.trim(),
        'stok_total': stokTotal,
        'kondisi': kondisi,
        'aktif': aktif,
      };
      final response = id == null
          ? await api.post('/alat', data: body)
          : await api.put('/alat/$id', data: body);
      lastMessage = ApiService.messageFrom(response, fallback: 'Data alat berhasil disimpan.');
    });
    await loadAlat(silent: true);
  }

  Future<void> hapusAlat(int id) async {
    await _run(() async {
      final response = await api.delete('/alat/$id');
      lastMessage = ApiService.messageFrom(response, fallback: 'Alat berhasil dihapus.');
    });
    await loadAlat(silent: true);
  }

  Future<void> tambahPengguna({
    required String nama,
    required String email,
    required String password,
    required String role,
    String jenisPeminjam = 'siswa',
    String nomorInduk = '',
  }) async {
    await _run(() async {
      final body = <String, dynamic>{
        'nama': nama.trim(),
        'email': email.trim(),
        'password': password,
        'role': role,
        'jenis_peminjam': jenisPeminjam,
      };
      if (nomorInduk.trim().isNotEmpty) body['nomor_induk'] = nomorInduk.trim();
      final response = await api.post('/pengguna', data: body);
      lastMessage = ApiService.messageFrom(response, fallback: 'Pengguna berhasil ditambahkan.');
    });
    await loadPengguna(silent: true);
  }

  Future<void> kembalikanPeminjaman({
    required int peminjamanId,
    required List<Map<String, dynamic>> items,
  }) async {
    final template = AppConfig.returnEndpointTemplate.trim();
    if (template.isEmpty) {
      throw const ApiException('Tampilan pengembalian sudah tersedia, tetapi rute API Tahap 5 belum dikonfigurasi. Isi RETURN_ENDPOINT_TEMPLATE sesuai routes/api.php milikmu.');
    }
    await _run(() async {
      final path = template.replaceAll('{id}', peminjamanId.toString());
      final response = await api.post(path, data: {'items': items});
      lastMessage = ApiService.messageFrom(response, fallback: 'Pengembalian berhasil diproses.');
    });
    await refreshAll();
  }

  Future<void> _run(Future<void> Function() action) async {
    busy = true;
    lastMessage = null;
    notifyListeners();
    try {
      await action();
    } catch (error) {
      lastMessage = _friendly(error);
      rethrow;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  String _friendly(Object error) => error is ApiException ? error.message : error.toString();
}

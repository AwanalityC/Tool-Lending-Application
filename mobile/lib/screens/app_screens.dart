import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../config/app_config.dart';
import '../models/models.dart';
import '../providers/app_state.dart';
import '../widgets/app_widgets.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key});
  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nama = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _nomorInduk = TextEditingController();
  bool _register = false;
  bool _hidePassword = true;
  String _jenis = 'siswa';
  bool _submitting = false;

  @override
  void dispose() {
    _nama.dispose();
    _email.dispose();
    _password.dispose();
    _nomorInduk.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final state = context.read<AppState>();
    setState(() => _submitting = true);
    try {
      if (_register) {
        await state.register(
          nama: _nama.text,
          email: _email.text,
          password: _password.text,
          jenisPeminjam: _jenis,
          nomorInduk: _nomorInduk.text,
        );
        try {
          await state.login(_email.text, _password.text);
          if (mounted) showAppMessage(context, 'Pendaftaran berhasil. Selamat datang di PinjamAlat!');
        } catch (_) {
          if (mounted) {
            setState(() => _register = false);
            showAppMessage(context, 'Pendaftaran berhasil. Silakan login menggunakan akun baru.');
          }
        }
      } else {
        await state.login(_email.text, _password.text);
        if (mounted) showAppMessage(context, 'Login berhasil. Selamat datang!');
      }
    } catch (error) {
      if (mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width > 760;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(22),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 1040),
              child: wide
                  ? Row(crossAxisAlignment: CrossAxisAlignment.center, children: [Expanded(child: _brandPanel(context)), const SizedBox(width: 48), Expanded(child: _formPanel(context))])
                  : _formPanel(context),
            ),
          ),
        ),
      ),
    );
  }

  Widget _brandPanel(BuildContext context) => Container(
        padding: const EdgeInsets.all(34),
        decoration: BoxDecoration(
          gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppColors.primaryDark, AppColors.primary, AppColors.accent]),
          borderRadius: BorderRadius.circular(30),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(width: 62, height: 62, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(20)), child: const Icon(Icons.inventory_2_rounded, color: Colors.white, size: 32)),
          const SizedBox(height: 28),
          const Text('PinjamAlat', style: TextStyle(color: Colors.white, fontSize: 34, fontWeight: FontWeight.w900)),
          const SizedBox(height: 12),
          const Text('Peminjaman alat sekolah jadi lebih tertata, mudah dilacak, dan transparan.', style: TextStyle(color: Colors.white, height: 1.6, fontSize: 16)),
          const SizedBox(height: 28),
          _brandBullet(Icons.search_rounded, 'Cari alat dan lihat ketersediaannya'),
          _brandBullet(Icons.fact_check_rounded, 'Pantau status pengajuan'),
          _brandBullet(Icons.assignment_return_rounded, 'Kelola peminjaman dalam satu tempat'),
        ]),
      );

  Widget _brandBullet(IconData icon, String text) => Padding(
        padding: const EdgeInsets.only(bottom: 15),
        child: Row(children: [Icon(icon, color: Colors.white, size: 21), const SizedBox(width: 12), Expanded(child: Text(text, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600))) ]),
      );

  Widget _formPanel(BuildContext context) => Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 450),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            if (MediaQuery.sizeOf(context).width <= 760) ...[
              Center(child: Container(width: 64, height: 64, decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20)), child: const Icon(Icons.inventory_2_rounded, color: Colors.white, size: 32))),
              const SizedBox(height: 15),
              const Text('PinjamAlat', textAlign: TextAlign.center, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 28, color: AppColors.ink)),
              const SizedBox(height: 6),
              const Text('Sistem peminjaman alat sekolah', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted)),
              const SizedBox(height: 28),
            ],
            Text(_register ? 'Buat akun peminjam' : 'Selamat datang kembali', style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text(_register ? 'Lengkapi data berikut untuk mulai meminjam alat.' : 'Masuk untuk mengakses katalog dan layanan peminjaman.', style: const TextStyle(color: AppColors.muted, height: 1.5)),
            const SizedBox(height: 25),
            Form(
              key: _formKey,
              child: Column(children: [
                if (_register) ...[
                  TextFormField(controller: _nama, decoration: appInput('Nama lengkap', icon: Icons.person_outline), textCapitalization: TextCapitalization.words, validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama wajib diisi.' : null),
                  const SizedBox(height: 13),
                ],
                TextFormField(controller: _email, decoration: appInput('Email', icon: Icons.mail_outline), keyboardType: TextInputType.emailAddress, autofillHints: const [AutofillHints.email], validator: (v) => (v == null || !v.contains('@')) ? 'Masukkan email yang valid.' : null),
                if (_register) ...[
                  const SizedBox(height: 13),
                  DropdownButtonFormField<String>(
                    value: _jenis,
                    decoration: appInput('Jenis peminjam', icon: Icons.school_outlined),
                    items: const [DropdownMenuItem(value: 'siswa', child: Text('Siswa')), DropdownMenuItem(value: 'guru', child: Text('Guru'))],
                    onChanged: (v) => setState(() => _jenis = v ?? 'siswa'),
                  ),
                  const SizedBox(height: 13),
                  TextFormField(controller: _nomorInduk, decoration: appInput('NIS / NIP (opsional)', icon: Icons.badge_outlined)),
                ],
                const SizedBox(height: 13),
                TextFormField(
                  controller: _password,
                  decoration: appInput('Kata sandi', icon: Icons.lock_outline).copyWith(suffixIcon: IconButton(icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined), onPressed: () => setState(() => _hidePassword = !_hidePassword))),
                  obscureText: _hidePassword,
                  autofillHints: _register ? const [AutofillHints.newPassword] : const [AutofillHints.password],
                  validator: (v) => (v == null || v.length < 8) ? 'Kata sandi minimal 8 karakter.' : null,
                ),
                const SizedBox(height: 20),
                SizedBox(width: double.infinity, child: FilledButton.icon(onPressed: _submitting ? null : _submit, icon: _submitting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Icon(_register ? Icons.person_add_alt_1_rounded : Icons.login_rounded), label: Text(_submitting ? 'Memproses...' : (_register ? 'Buat akun' : 'Masuk')))),
              ]),
            ),
            const SizedBox(height: 14),
            TextButton(onPressed: _submitting ? null : () => setState(() => _register = !_register), child: Text(_register ? 'Sudah punya akun? Masuk' : 'Belum punya akun? Daftar sebagai peminjam')),
            const SizedBox(height: 8),
            const Text('Gunakan akun yang terdaftar di server PinjamAlat.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 12)),
          ]),
        ),
      );
}

class AppShell extends StatefulWidget {
  const AppShell({super.key});
  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _index = 0;

  List<_NavItem> _itemsFor(AppUser user) {
    if (user.isAdmin) {
      return const [
        _NavItem('Beranda', Icons.space_dashboard_rounded),
        _NavItem('Pengajuan', Icons.fact_check_rounded),
        _NavItem('Inventaris', Icons.inventory_2_rounded),
        _NavItem('Pengguna', Icons.groups_rounded),
        _NavItem('Profil', Icons.person_rounded),
      ];
    }
    if (user.isStaff) {
      return const [
        _NavItem('Beranda', Icons.space_dashboard_rounded),
        _NavItem('Pengajuan', Icons.fact_check_rounded),
        _NavItem('Inventaris', Icons.inventory_2_rounded),
        _NavItem('Profil', Icons.person_rounded),
      ];
    }
    return const [
      _NavItem('Beranda', Icons.space_dashboard_rounded),
      _NavItem('Katalog', Icons.search_rounded),
      _NavItem('Riwayat', Icons.receipt_long_rounded),
      _NavItem('Profil', Icons.person_rounded),
    ];
  }

  List<Widget> _pagesFor(AppUser user) {
    if (user.isAdmin) return const [DashboardScreen(), StaffLoansScreen(), InventoryHubScreen(), UsersScreen(), ProfileScreen()];
    if (user.isStaff) return const [DashboardScreen(), StaffLoansScreen(), InventoryHubScreen(), ProfileScreen()];
    return const [DashboardScreen(), CatalogScreen(), HistoryScreen(), ProfileScreen()];
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) context.read<AppState>().refreshAll();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final user = state.user;
    if (user == null) return const AuthScreen();
    final nav = _itemsFor(user);
    final pages = _pagesFor(user);
    if (_index >= pages.length) _index = 0;
    final wide = MediaQuery.sizeOf(context).width >= 850;

    return Scaffold(
      appBar: AppBar(
        title: Row(mainAxisSize: MainAxisSize.min, children: [
          Container(width: 36, height: 36, decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(11)), child: const Icon(Icons.inventory_2_rounded, color: Colors.white, size: 21)),
          const SizedBox(width: 11),
          const Text('PinjamAlat', style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: -.4)),
        ]),
        actions: [
          if (state.busy) const Padding(padding: EdgeInsets.symmetric(horizontal: 12), child: Center(child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)))),
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: CircleAvatar(
                radius: 17,
                backgroundColor: AppColors.accent.withValues(alpha: .14),
                child: Text(user.nama.isNotEmpty ? user.nama[0].toUpperCase() : 'P', style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w800)),
              ),
            ),
          ),
        ],
      ),
      body: wide
          ? Row(children: [
              NavigationRail(
                selectedIndex: _index,
                onDestinationSelected: (value) => setState(() => _index = value),
                labelType: NavigationRailLabelType.all,
                backgroundColor: AppColors.background,
                destinations: nav.map((item) => NavigationRailDestination(icon: Icon(item.icon), selectedIcon: Icon(item.icon), label: Text(item.label))).toList(),
              ),
              const VerticalDivider(width: 1, color: AppColors.line),
              Expanded(child: IndexedStack(index: _index, children: pages)),
            ])
          : IndexedStack(index: _index, children: pages),
      bottomNavigationBar: wide
          ? null
          : NavigationBar(
              selectedIndex: _index,
              onDestinationSelected: (value) => setState(() => _index = value),
              destinations: nav.map((item) => NavigationDestination(icon: Icon(item.icon), selectedIcon: Icon(item.icon), label: item.label)).toList(),
            ),
    );
  }
}

class _NavItem {
  const _NavItem(this.label, this.icon);
  final String label;
  final IconData icon;
}

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final user = state.user!;
    final loans = state.peminjaman;
    final waiting = loans.where((loan) => loan.status == 'menunggu').length;
    final approved = loans.where((loan) => loan.status == 'disetujui').length;
    final late = loans.where((loan) => loan.terlambat).length;
    final cards = user.isPeminjam
        ? [
            StatCard(label: 'Total pengajuan', value: '${loans.length}', icon: Icons.receipt_long_rounded),
            StatCard(label: 'Menunggu', value: '$waiting', icon: Icons.hourglass_top_rounded, tint: AppColors.warning),
            StatCard(label: 'Sedang dipinjam', value: '$approved', icon: Icons.assignment_turned_in_rounded, tint: AppColors.success),
            StatCard(label: 'Terlambat', value: '$late', icon: Icons.alarm_rounded, tint: AppColors.danger),
          ]
        : [
            StatCard(label: 'Total alat', value: '${state.alat.length}', icon: Icons.inventory_2_rounded),
            StatCard(label: 'Pengajuan baru', value: '$waiting', icon: Icons.hourglass_top_rounded, tint: AppColors.warning),
            StatCard(label: 'Sedang dipinjam', value: '$approved', icon: Icons.assignment_turned_in_rounded, tint: AppColors.success),
            StatCard(label: 'Terlambat', value: '$late', icon: Icons.alarm_rounded, tint: AppColors.danger),
          ];
    return RefreshIndicator(
      onRefresh: state.refreshAll,
      child: ListView(padding: const EdgeInsets.only(bottom: 24), children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 18),
          child: Container(
            padding: const EdgeInsets.all(23),
            decoration: BoxDecoration(gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppColors.primaryDark, AppColors.primary, Color(0xFF187B86)]), borderRadius: BorderRadius.circular(24)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(_greeting(), style: const TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600)),
              const SizedBox(height: 7),
              Text(user.nama, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text(user.isPeminjam ? 'Cari perlengkapan yang kamu butuhkan untuk kegiatan belajar.' : 'Pantau inventaris dan kelola pengajuan peminjaman dengan mudah.', style: const TextStyle(color: Colors.white, height: 1.45)),
              const SizedBox(height: 17),
              Wrap(spacing: 8, children: [
                Container(padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .15), borderRadius: BorderRadius.circular(20)), child: Text(roleLabel(user.role), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12))),
                if (user.jenisPeminjam != null && user.jenisPeminjam!.isNotEmpty)
                  Container(padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .15), borderRadius: BorderRadius.circular(20)), child: Text(user.jenisPeminjam!.toUpperCase(), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12))),
              ]),
            ]),
          ),
        ),
        const Padding(padding: EdgeInsets.fromLTRB(20, 0, 20, 12), child: Text('Ringkasan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.ink))),
        Padding(padding: const EdgeInsets.symmetric(horizontal: 16), child: GridView.count(crossAxisCount: MediaQuery.sizeOf(context).width > 640 ? 4 : 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), crossAxisSpacing: 10, mainAxisSpacing: 10, childAspectRatio: 1.18, children: cards)),
        Padding(padding: const EdgeInsets.fromLTRB(20, 24, 20, 8), child: Row(children: [const Expanded(child: Text('Peminjaman terbaru', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.ink))), TextButton.icon(onPressed: state.refreshAll, icon: const Icon(Icons.refresh_rounded, size: 18), label: const Text('Muat ulang'))])),
        if (loans.isEmpty)
          const EmptyState(icon: Icons.inbox_outlined, title: 'Belum ada peminjaman', message: 'Data pengajuan yang kamu buat atau kelola akan tampil di sini.')
        else
          ...loans.take(4).map((loan) => PeminjamanTile(loan: loan, showBorrower: user.isStaff)),
      ]),
    );
  }

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 11) return 'Selamat pagi, semoga harimu lancar.';
    if (hour < 15) return 'Selamat siang, semoga aktivitasmu lancar.';
    if (hour < 18) return 'Selamat sore, semoga aktivitasmu lancar.';
    return 'Selamat malam, selamat beristirahat.';
  }
}

class CatalogScreen extends StatefulWidget {
  const CatalogScreen({super.key});
  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  final Map<int, int> _cart = {};
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final items = state.alat.where((a) => a.aktif && a.nama.toLowerCase().contains(_query.toLowerCase()) || a.aktif && a.kode.toLowerCase().contains(_query.toLowerCase())).toList();
    final totalJenis = _cart.values.where((q) => q > 0).length;
    final totalJumlah = _cart.values.fold<int>(0, (sum, q) => sum + q);
    return Column(children: [
      const ScreenHeading(title: 'Katalog alat', subtitle: 'Pilih perlengkapan yang tersedia untuk dipinjam.'),
      Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 12), child: TextField(decoration: appInput('Cari nama atau kode alat', icon: Icons.search_rounded), onChanged: (v) => setState(() => _query = v))),
      Expanded(child: RefreshIndicator(onRefresh: () => state.loadAlat(), child: items.isEmpty
          ? ListView(children: const [SizedBox(height: 70), EmptyState(icon: Icons.inventory_2_outlined, title: 'Alat tidak ditemukan', message: 'Coba kata kunci lain atau muat ulang katalog.')])
          : ListView.builder(itemCount: items.length, padding: const EdgeInsets.only(bottom: 12), itemBuilder: (context, index) {
              final item = items[index];
              final quantity = _cart[item.id] ?? 0;
              return SurfaceCard(child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(width: 52, height: 52, decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: .08), borderRadius: BorderRadius.circular(16)), child: const Icon(Icons.handyman_rounded, color: AppColors.primary, size: 27)),
                const SizedBox(width: 13),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(item.nama, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: AppColors.ink)),
                  const SizedBox(height: 4),
                  Text('${item.kode}  •  ${item.kategoriNama.isEmpty ? 'Tanpa kategori' : item.kategoriNama}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                  if (item.deskripsi.isNotEmpty) ...[const SizedBox(height: 7), Text(item.deskripsi, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, height: 1.4))],
                  const SizedBox(height: 10),
                  Row(children: [StatusPill(status: item.stokTersedia > 0 ? 'tersedia' : 'habis', label: item.stokTersedia > 0 ? 'Tersedia: ${item.stokTersedia}' : 'Stok habis'), const Spacer(), if (item.stokTersedia > 0) _QuantityPicker(value: quantity, max: item.stokTersedia, onChanged: (v) => setState(() { if (v == 0) { _cart.remove(item.id); } else { _cart[item.id] = v; } }))]),
                ])),
              ]));
            }))),
      if (totalJenis > 0)
        SafeArea(top: false, child: Container(padding: const EdgeInsets.fromLTRB(16, 12, 16, 10), decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: AppColors.line))), child: Row(children: [
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('$totalJenis jenis alat dipilih', style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.ink)), Text('$totalJumlah barang', style: const TextStyle(color: AppColors.muted, fontSize: 12))])),
          FilledButton.icon(onPressed: () async {
            final result = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => BorrowFormScreen(initialCart: Map<int, int>.from(_cart))));
            if (result == true && mounted) setState(() => _cart.clear());
          }, icon: const Icon(Icons.send_rounded, size: 18), label: const Text('Ajukan')),
        ]))),
    ]);
  }
}

class _QuantityPicker extends StatelessWidget {
  const _QuantityPicker({required this.value, required this.max, required this.onChanged});
  final int value;
  final int max;
  final ValueChanged<int> onChanged;
  @override
  Widget build(BuildContext context) => Row(mainAxisSize: MainAxisSize.min, children: [
        IconButton(onPressed: value > 0 ? () => onChanged(value - 1) : null, visualDensity: VisualDensity.compact, icon: const Icon(Icons.remove_circle_outline_rounded)),
        SizedBox(width: 23, child: Text('$value', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w800))),
        IconButton(onPressed: value < max ? () => onChanged(value + 1) : null, visualDensity: VisualDensity.compact, icon: const Icon(Icons.add_circle_rounded, color: AppColors.primary)),
      ]);
}

class BorrowFormScreen extends StatefulWidget {
  const BorrowFormScreen({super.key, required this.initialCart});
  final Map<int, int> initialCart;
  @override
  State<BorrowFormScreen> createState() => _BorrowFormScreenState();
}

class _BorrowFormScreenState extends State<BorrowFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _alasan = TextEditingController();
  late DateTime _tanggalPinjam;
  late DateTime _tanggalTempo;
  late final Map<int, int> _cart;

  @override
  void initState() {
    super.initState();
    _tanggalPinjam = DateTime.now();
    _tanggalTempo = DateTime.now().add(const Duration(days: 3));
    _cart = Map<int, int>.from(widget.initialCart);
  }

  @override
  void dispose() {
    _alasan.dispose();
    super.dispose();
  }

  String _iso(DateTime value) => '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';

  Future<void> _pickDate(bool isDue) async {
    final initial = isDue ? _tanggalTempo : _tanggalPinjam;
    final picked = await showDatePicker(context: context, initialDate: initial, firstDate: isDue ? _tanggalPinjam : DateTime(DateTime.now().year, DateTime.now().month, DateTime.now().day), lastDate: DateTime.now().add(const Duration(days: 365)));
    if (picked != null && mounted) setState(() { if (isDue) { _tanggalTempo = picked; } else { _tanggalPinjam = picked; if (_tanggalTempo.isBefore(picked)) _tanggalTempo = picked; } });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_cart.isEmpty) { showAppMessage(context, 'Pilih minimal satu alat.', error: true); return; }
    final state = context.read<AppState>();
    try {
      await state.ajukanPeminjaman(tanggalPinjam: _iso(_tanggalPinjam), tanggalJatuhTempo: _iso(_tanggalTempo), alasan: _alasan.text, items: _cart);
      if (mounted) { showAppMessage(context, state.lastMessage ?? 'Pengajuan berhasil dikirim.'); Navigator.of(context).pop(true); }
    } catch (error) {
      if (mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final selected = state.alat.where((item) => _cart.containsKey(item.id)).toList();
    return Scaffold(
      appBar: AppBar(title: const Text('Formulir peminjaman')),
      body: SafeArea(child: Form(key: _formKey, child: ListView(padding: const EdgeInsets.all(18), children: [
        const Text('Periksa alat pilihanmu', style: TextStyle(fontSize: 19, fontWeight: FontWeight.w900, color: AppColors.ink)),
        const SizedBox(height: 6),
        const Text('Stok baru dikurangi setelah pengajuan disetujui petugas.', style: TextStyle(color: AppColors.muted, height: 1.45)),
        const SizedBox(height: 12),
        ...selected.map((item) => SurfaceCard(margin: const EdgeInsets.symmetric(vertical: 4), padding: const EdgeInsets.all(13), child: Row(children: [const Icon(Icons.inventory_2_outlined, color: AppColors.primary), const SizedBox(width: 12), Expanded(child: Text(item.nama, style: const TextStyle(fontWeight: FontWeight.w700))), Text('× ${_cart[item.id]}', style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.primary))]))),
        const SizedBox(height: 17),
        const Text('Jadwal peminjaman', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.ink)),
        const SizedBox(height: 12),
        Row(children: [Expanded(child: _DateBox(label: 'Tanggal pinjam', value: formatTanggal(_iso(_tanggalPinjam)), onTap: () => _pickDate(false))), const SizedBox(width: 10), Expanded(child: _DateBox(label: 'Jatuh tempo', value: formatTanggal(_iso(_tanggalTempo)), onTap: () => _pickDate(true)))]),
        const SizedBox(height: 17),
        TextFormField(controller: _alasan, minLines: 3, maxLines: 5, maxLength: 1000, decoration: appInput('Alasan peminjaman', icon: Icons.notes_rounded, hint: 'Contoh: praktikum atau kegiatan sekolah'), validator: (v) => (v == null || v.trim().isEmpty) ? 'Alasan peminjaman wajib diisi.' : null),
        const SizedBox(height: 18),
        FilledButton.icon(onPressed: state.busy ? null : _submit, icon: state.busy ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send_rounded), label: Text(state.busy ? 'Mengirim...' : 'Kirim pengajuan')),
        const SizedBox(height: 8),
        const Text('Pastikan tanggal dan jumlah alat sudah benar sebelum mengirim.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: AppColors.muted)),
      ]))),
    );
  }
}

class _DateBox extends StatelessWidget {
  const _DateBox({required this.label, required this.value, required this.onTap});
  final String label;
  final String value;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => InkWell(onTap: onTap, borderRadius: BorderRadius.circular(14), child: Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.white, border: Border.all(color: AppColors.line), borderRadius: BorderRadius.circular(14)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label, style: const TextStyle(fontSize: 12, color: AppColors.muted)), const SizedBox(height: 9), Row(children: [const Icon(Icons.calendar_month_rounded, color: AppColors.primary, size: 18), const SizedBox(width: 7), Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)))])])));
}

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});
  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  String _filter = 'semua';
  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final loans = _filter == 'semua' ? state.peminjaman : state.peminjaman.where((p) => p.status == _filter || (_filter == 'terlambat' && p.terlambat)).toList();
    return Column(children: [
      const ScreenHeading(title: 'Riwayat peminjaman', subtitle: 'Lacak semua pengajuan dan status alatmu.'),
      Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 12), child: DropdownButtonFormField<String>(value: _filter, decoration: appInput('Filter status'), items: const [DropdownMenuItem(value: 'semua', child: Text('Semua status')), DropdownMenuItem(value: 'menunggu', child: Text('Menunggu')), DropdownMenuItem(value: 'disetujui', child: Text('Disetujui')), DropdownMenuItem(value: 'ditolak', child: Text('Ditolak')), DropdownMenuItem(value: 'selesai', child: Text('Selesai')), DropdownMenuItem(value: 'terlambat', child: Text('Terlambat'))], onChanged: (v) async { setState(() => _filter = v ?? 'semua'); await state.loadPeminjaman(status: _filter == 'terlambat' ? 'disetujui' : _filter); })),
      Expanded(child: RefreshIndicator(onRefresh: () => state.loadPeminjaman(status: _filter == 'terlambat' ? 'disetujui' : _filter), child: loans.isEmpty ? ListView(children: const [SizedBox(height: 60), EmptyState(icon: Icons.receipt_long_outlined, title: 'Riwayat masih kosong', message: 'Pengajuan peminjamanmu akan muncul di halaman ini.')]) : ListView(padding: const EdgeInsets.only(bottom: 20), children: loans.map((loan) => PeminjamanTile(loan: loan)).toList()))),
    ]);
  }
}

class PeminjamanTile extends StatelessWidget {
  const PeminjamanTile({super.key, required this.loan, this.showBorrower = false, this.actions});
  final Peminjaman loan;
  final bool showBorrower;
  final Widget? actions;

  @override
  Widget build(BuildContext context) => SurfaceCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(width: 43, height: 43, decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.assignment_rounded, color: AppColors.primary)),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(loan.nomor, style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.ink)), const SizedBox(height: 4), if (showBorrower && loan.namaPeminjam.isNotEmpty) Text(loan.namaPeminjam, style: const TextStyle(fontSize: 12, color: AppColors.muted)), Text('${formatTanggal(loan.tanggalPinjam)}  •  ${loan.totalItem} barang', style: const TextStyle(fontSize: 12, color: AppColors.muted))])),
          const SizedBox(width: 8),
          StatusPill(status: loan.terlambat ? 'terlambat' : loan.status, label: loan.statusTampilan),
        ]),
        const SizedBox(height: 14),
        Row(children: [const Icon(Icons.event_available_outlined, size: 16, color: AppColors.muted), const SizedBox(width: 7), const Text('Jatuh tempo', style: TextStyle(color: AppColors.muted, fontSize: 12)), const Spacer(), Text(formatTanggal(loan.tanggalJatuhTempo), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12))]),
        if (loan.alasan.isNotEmpty) ...[const SizedBox(height: 10), Text(loan.alasan, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, height: 1.4))],
        if (loan.items.isNotEmpty) ...[const SizedBox(height: 11), Wrap(spacing: 6, runSpacing: 6, children: loan.items.take(3).map((item) => Container(padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6), decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(8)), child: Text('${item.nama} ×${item.jumlah}', style: const TextStyle(fontSize: 11, color: AppColors.ink)))).toList())],
        if (loan.terlambat) ...[const SizedBox(height: 10), Row(children: [const Icon(Icons.warning_amber_rounded, size: 16, color: AppColors.danger), const SizedBox(width: 6), Text('Terlambat ${loan.hariTerlambat} hari', style: const TextStyle(color: AppColors.danger, fontWeight: FontWeight.w700, fontSize: 12))])],
        if (loan.totalDenda > 0) ...[const SizedBox(height: 8), Row(children: [const Icon(Icons.payments_outlined, size: 16, color: AppColors.warning), const SizedBox(width: 6), Text('Total denda: ${_rupiah(loan.totalDenda)}', style: const TextStyle(color: AppColors.warning, fontWeight: FontWeight.w800, fontSize: 12))])],
        if (loan.tanggalKembali != null && loan.tanggalKembali!.isNotEmpty) ...[const SizedBox(height: 7), Text('Tanggal pengembalian: ${formatTanggal(loan.tanggalKembali!)}', style: const TextStyle(fontSize: 12, color: AppColors.muted))],
        if (loan.alasanPenolakan != null && loan.alasanPenolakan!.isNotEmpty) ...[const SizedBox(height: 10), Text('Alasan penolakan: ${loan.alasanPenolakan}', style: const TextStyle(color: AppColors.danger, fontSize: 12))],
        if (actions != null) ...[const SizedBox(height: 14), const Divider(height: 1, color: AppColors.line), const SizedBox(height: 10), actions!],
      ]));
}

String _formatRoleDetail(AppUser user) {
  final type = user.jenisPeminjam;
  if (type == null || type.isEmpty) return roleLabel(user.role);
  return '${roleLabel(user.role)} • ${type.toUpperCase()}';
}

class StaffLoansScreen extends StatefulWidget {
  const StaffLoansScreen({super.key});
  @override
  State<StaffLoansScreen> createState() => _StaffLoansScreenState();
}

class _StaffLoansScreenState extends State<StaffLoansScreen> {
  String _filter = 'menunggu';

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final all = state.peminjaman;
    final loans = _filter == 'semua'
        ? all
        : all.where((loan) => _filter == 'terlambat' ? loan.terlambat : loan.status == _filter).toList();
    return Column(children: [
      const ScreenHeading(title: 'Pengajuan pinjaman', subtitle: 'Periksa pengajuan dan proses sesuai kondisi stok.'),
      Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 12), child: SingleChildScrollView(scrollDirection: Axis.horizontal, child: Row(children: [
        for (final pair in const [
          ('menunggu', 'Menunggu'), ('disetujui', 'Disetujui'), ('ditolak', 'Ditolak'), ('selesai', 'Selesai'), ('terlambat', 'Terlambat'), ('semua', 'Semua'),
        ])
          Padding(padding: const EdgeInsets.only(right: 7), child: ChoiceChip(label: Text(pair.$2), selected: _filter == pair.$1, onSelected: (_) async { setState(() => _filter = pair.$1); await state.loadPeminjaman(status: pair.$1 == 'terlambat' ? 'disetujui' : pair.$1); })),
      ]))),
      Expanded(child: RefreshIndicator(onRefresh: () => state.loadPeminjaman(status: _filter == 'semua' || _filter == 'terlambat' ? null : _filter), child: loans.isEmpty
          ? ListView(children: const [SizedBox(height: 60), EmptyState(icon: Icons.task_alt_rounded, title: 'Tidak ada pengajuan', message: 'Pengajuan dengan filter ini akan muncul di sini.')])
          : ListView(padding: const EdgeInsets.only(bottom: 18), children: loans.map((loan) {
              final canProcess = loan.status == 'menunggu';
              final canReturn = loan.status == 'disetujui';
              return PeminjamanTile(
                loan: loan,
                showBorrower: true,
                actions: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  if (canProcess) Row(children: [
                    Expanded(child: OutlinedButton.icon(onPressed: state.busy ? null : () => _reject(context, loan), icon: const Icon(Icons.close_rounded, size: 18), label: const Text('Tolak'), style: OutlinedButton.styleFrom(foregroundColor: AppColors.danger))),
                    const SizedBox(width: 9),
                    Expanded(child: FilledButton.icon(onPressed: state.busy ? null : () => _approve(context, loan), icon: const Icon(Icons.check_rounded, size: 18), label: const Text('Setujui'))),
                  ]),
                  if (canReturn) ...[
                    SizedBox(width: double.infinity, child: OutlinedButton.icon(onPressed: () => _openReturn(context, loan), icon: const Icon(Icons.assignment_return_rounded, size: 18), label: const Text('Proses pengembalian'))),
                    if (!state.isReturnEndpointConfigured) const Padding(padding: EdgeInsets.only(top: 5), child: Text('UI tersedia. Rute API pengembalian Tahap 5 perlu diatur di app_config.dart atau --dart-define.', style: TextStyle(fontSize: 11, color: AppColors.muted))),
                  ],
                ]),
              );
            }).toList()))),
    ]);
  }

  Future<void> _approve(BuildContext context, Peminjaman loan) async {
    final state = context.read<AppState>();
    final confirmed = await showDialog<bool>(context: context, builder: (context) => AlertDialog(
      title: const Text('Setujui pengajuan?'),
      content: Text('Pengajuan ${loan.nomor} akan disetujui dan stok alat dikurangi sesuai jumlah yang diajukan.'),
      actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Setujui'))],
    ));
    if (confirmed != true) return;
    try { await state.setujuiPeminjaman(loan); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Pengajuan disetujui.'); }
    catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
  }

  Future<void> _reject(BuildContext context, Peminjaman loan) async {
    final controller = TextEditingController();
    final formKey = GlobalKey<FormState>();
    final reason = await showDialog<String>(context: context, builder: (context) => AlertDialog(
      title: const Text('Tolak pengajuan'),
      content: Form(key: formKey, child: TextFormField(controller: controller, minLines: 2, maxLines: 4, maxLength: 500, decoration: appInput('Alasan penolakan'), validator: (v) => (v == null || v.trim().length < 5) ? 'Alasan minimal 5 karakter.' : null)),
      actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')), FilledButton(onPressed: () { if (formKey.currentState!.validate()) Navigator.pop(context, controller.text); }, style: FilledButton.styleFrom(backgroundColor: AppColors.danger), child: const Text('Tolak pengajuan'))],
    ));
    controller.dispose();
    if (reason == null) return;
    final state = context.read<AppState>();
    try { await state.tolakPeminjaman(loan, reason); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Pengajuan ditolak.'); }
    catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
  }

  Future<void> _openReturn(BuildContext context, Peminjaman loan) async {
    final state = context.read<AppState>();
    if (!state.isReturnEndpointConfigured) {
      await showDialog<void>(context: context, builder: (context) => AlertDialog(
        title: const Text('Konfigurasi rute pengembalian'),
        content: const Text('Layar pengembalian sudah disiapkan. Agar penyimpanan benar-benar tersambung, isi AppConfig.returnEndpointTemplate sesuai rute Tahap 5 di backend. ZIP yang dibagikan sebelumnya hanya memuat routes/api.php Tahap 4, jadi rute Tahap 5 tidak bisa dipastikan di sini.'),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Mengerti'))],
      ));
      return;
    }
    final result = await showDialog<List<Map<String, dynamic>>>(context: context, builder: (context) => _ReturnDialog(loan: loan));
    if (result == null) return;
    try {
      await state.kembalikanPeminjaman(peminjamanId: loan.id, items: result);
      if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Pengembalian berhasil diproses.');
    } catch (error) {
      if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true);
    }
  }
}

class _ReturnDialog extends StatefulWidget {
  const _ReturnDialog({required this.loan});
  final Peminjaman loan;
  @override
  State<_ReturnDialog> createState() => _ReturnDialogState();
}

class _ReturnDialogState extends State<_ReturnDialog> {
  late final List<_ReturnControllers> _controllers;
  String? _validationError;

  @override
  void initState() {
    super.initState();
    _controllers = widget.loan.items.map((item) => _ReturnControllers(item.jumlah)).toList();
  }

  @override
  void dispose() {
    for (final controllers in _controllers) { controllers.good.dispose(); controllers.light.dispose(); controllers.heavy.dispose(); }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
        title: const Text('Pengembalian alat'),
        content: SizedBox(width: 520, child: SingleChildScrollView(child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Transaksi ${widget.loan.nomor}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          const SizedBox(height: 12),
          const Text('Jumlah baik + rusak ringan + rusak berat harus sama dengan jumlah dipinjam.', style: TextStyle(fontSize: 12, color: AppColors.muted, height: 1.4)),
          const SizedBox(height: 12),
          ...List.generate(widget.loan.items.length, (index) {
            final item = widget.loan.items[index];
            final c = _controllers[index];
            return Padding(padding: const EdgeInsets.only(bottom: 14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('${item.nama} (${item.jumlah} unit)', style: const TextStyle(fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Row(children: [Expanded(child: _numberField(c.good, 'Baik')), const SizedBox(width: 6), Expanded(child: _numberField(c.light, 'Rusak ringan')), const SizedBox(width: 6), Expanded(child: _numberField(c.heavy, 'Rusak berat'))]),
            ]));
          }),
          if (_validationError != null) Text(_validationError!, style: const TextStyle(color: AppColors.danger, fontSize: 12)),
        ]))),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')), FilledButton(onPressed: _submit, child: const Text('Simpan pengembalian'))],
      );

  Widget _numberField(TextEditingController controller, String label) => TextField(controller: controller, keyboardType: TextInputType.number, decoration: appInput(label).copyWith(contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10)), onChanged: (_) => setState(() => _validationError = null));

  void _submit() {
    final output = <Map<String, dynamic>>[];
    for (var i = 0; i < widget.loan.items.length; i++) {
      final item = widget.loan.items[i];
      final c = _controllers[i];
      final good = int.tryParse(c.good.text) ?? 0;
      final light = int.tryParse(c.light.text) ?? 0;
      final heavy = int.tryParse(c.heavy.text) ?? 0;
      if (good < 0 || light < 0 || heavy < 0 || good + light + heavy != item.jumlah) {
        setState(() => _validationError = 'Pembagian jumlah untuk ${item.nama} harus tepat ${item.jumlah} unit.');
        return;
      }
      output.add({'detail_id': item.id, 'alat_id': item.alatId, 'jumlah_baik': good, 'jumlah_rusak_ringan': light, 'jumlah_rusak_berat': heavy});
    }
    Navigator.pop(context, output);
  }
}

class _ReturnControllers {
  _ReturnControllers(int jumlah)
      : good = TextEditingController(text: '$jumlah'),
        light = TextEditingController(text: '0'),
        heavy = TextEditingController(text: '0');
  final TextEditingController good;
  final TextEditingController light;
  final TextEditingController heavy;
}

class InventoryHubScreen extends StatelessWidget {
  const InventoryHubScreen({super.key});
  @override
  Widget build(BuildContext context) => DefaultTabController(length: 2, child: Column(children: [
        const ScreenHeading(title: 'Inventaris', subtitle: 'Kelola alat dan kategori perlengkapan.'),
        Padding(padding: const EdgeInsets.symmetric(horizontal: 16), child: Container(decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.line)), child: const TabBar(tabs: [Tab(icon: Icon(Icons.inventory_2_outlined), text: 'Data alat'), Tab(icon: Icon(Icons.category_outlined), text: 'Kategori')]))),
        const Expanded(child: TabBarView(children: [AlatManagementScreen(), CategoryManagementScreen()])),
      ]));
}

class AlatManagementScreen extends StatelessWidget {
  const AlatManagementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    return Column(children: [
      Padding(padding: const EdgeInsets.fromLTRB(16, 14, 16, 10), child: Row(children: [Expanded(child: Text('${state.alat.length} data alat', style: const TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600))), FilledButton.icon(onPressed: () => _editAlat(context), icon: const Icon(Icons.add_rounded, size: 18), label: const Text('Tambah'))])),
      Expanded(
        child: RefreshIndicator(
          onRefresh: () => state.loadAlat(),
          child: state.alat.isEmpty
              ? ListView(children: const [
                  SizedBox(height: 40),
                  EmptyState(icon: Icons.inventory_2_outlined, title: 'Belum ada data alat', message: 'Tambahkan data alat untuk mengisi katalog.'),
                ])
              : ListView(
                  padding: const EdgeInsets.only(bottom: 18),
                  children: state.alat.map((item) => SurfaceCard(
                    child: Row(children: [
                      Container(width: 44, height: 44, decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.handyman_outlined, color: AppColors.primary)),
                      const SizedBox(width: 12),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(item.nama, style: const TextStyle(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 4),
                        Text('${item.kode} • ${item.kategoriNama.isEmpty ? 'Tanpa kategori' : item.kategoriNama}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                        const SizedBox(height: 5),
                        Text('Tersedia ${item.stokTersedia} / Total ${item.stokTotal}', style: const TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.w700)),
                      ])),
                      PopupMenuButton<String>(
                        onSelected: (value) { if (value == 'edit') _editAlat(context, item: item); if (value == 'delete') _deleteAlat(context, item); },
                        itemBuilder: (_) => const [PopupMenuItem(value: 'edit', child: Text('Edit')), PopupMenuItem(value: 'delete', child: Text('Hapus'))],
                      ),
                    ]),
                  )).toList(),
                ),
        ),
      ),
    ]);
  }

  Future<void> _editAlat(BuildContext context, {Alat? item}) async {
    final state = context.read<AppState>();
    if (state.kategori.isEmpty) {
      showAppMessage(context, 'Buat kategori terlebih dahulu sebelum menambahkan alat.', error: true);
      return;
    }
    final formKey = GlobalKey<FormState>();
    final kode = TextEditingController(text: item?.kode == '-' ? '' : item?.kode ?? '');
    final nama = TextEditingController(text: item?.nama ?? '');
    final deskripsi = TextEditingController(text: item?.deskripsi ?? '');
    final stok = TextEditingController(text: '${item?.stokTotal ?? 1}');
    int kategoriId = item?.kategoriId != null && state.kategori.any((k) => k.id == item!.kategoriId) ? item!.kategoriId! : state.kategori.first.id;
    const kondisiValid = ['baik', 'rusak_ringan', 'rusak_berat'];
    String kondisi = kondisiValid.contains(item?.kondisi) ? item!.kondisi : 'baik';
    bool aktif = item?.aktif ?? true;
    final saved = await showDialog<bool>(context: context, builder: (dialogContext) => StatefulBuilder(builder: (context, setDialogState) => AlertDialog(
      title: Text(item == null ? 'Tambah alat' : 'Edit alat'),
      content: SizedBox(width: 500, child: SingleChildScrollView(child: Form(key: formKey, child: Column(mainAxisSize: MainAxisSize.min, children: [
        TextFormField(controller: kode, decoration: appInput('Kode alat'), validator: (v) => v == null || v.trim().isEmpty ? 'Kode wajib diisi.' : null),
        const SizedBox(height: 12),
        TextFormField(controller: nama, decoration: appInput('Nama alat'), validator: (v) => v == null || v.trim().isEmpty ? 'Nama wajib diisi.' : null),
        const SizedBox(height: 12),
        DropdownButtonFormField<int>(value: kategoriId, decoration: appInput('Kategori'), items: state.kategori.map((k) => DropdownMenuItem(value: k.id, child: Text(k.nama))).toList(), onChanged: (v) => setDialogState(() => kategoriId = v ?? kategoriId)),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          value: kondisi,
          decoration: appInput('Kondisi alat'),
          items: const [
            DropdownMenuItem(value: 'baik', child: Text('Baik')),
            DropdownMenuItem(value: 'rusak_ringan', child: Text('Rusak ringan')),
            DropdownMenuItem(value: 'rusak_berat', child: Text('Rusak berat')),
          ],
          onChanged: (value) => setDialogState(() => kondisi = value ?? kondisi),
        ),
        const SizedBox(height: 12),
        TextFormField(controller: deskripsi, decoration: appInput('Deskripsi (opsional)'), maxLines: 2),
        const SizedBox(height: 12),
        TextFormField(controller: stok, decoration: appInput('Stok total'), keyboardType: TextInputType.number, validator: (v) { final n = int.tryParse(v ?? ''); return n == null || n < 1 ? 'Stok harus bilangan bulat minimal 1.' : null; }),
        const SizedBox(height: 6),
        SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Alat aktif'), value: aktif, onChanged: (v) => setDialogState(() => aktif = v)),
        if (item != null) const Text('Stok tersedia, dipinjam, dan rusak dikelola backend. Mengubah stok total harus mengikuti validasi server.', style: TextStyle(fontSize: 11, color: AppColors.muted)),
      ])))),
      actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () { if (formKey.currentState!.validate()) Navigator.pop(context, true); }, child: const Text('Simpan'))],
    )));
    if (saved == true) {
      try {
        await state.simpanAlat(id: item?.id, kode: kode.text, nama: nama.text, kategoriId: kategoriId, deskripsi: deskripsi.text, stokTotal: int.parse(stok.text), kondisi: kondisi, aktif: aktif);
        if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Data alat disimpan.');
      } catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
    }
    kode.dispose(); nama.dispose(); deskripsi.dispose(); stok.dispose();
  }

  Future<void> _deleteAlat(BuildContext context, Alat item) async {
    final yes = await showDialog<bool>(context: context, builder: (context) => AlertDialog(title: const Text('Hapus alat?'), content: Text('Alat "${item.nama}" akan dihapus dari katalog. Backend akan menolak jika masih ada transaksi aktif.'), actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () => Navigator.pop(context, true), style: FilledButton.styleFrom(backgroundColor: AppColors.danger), child: const Text('Hapus'))]));
    if (yes != true) return;
    final state = context.read<AppState>();
    try { await state.hapusAlat(item.id); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Alat dihapus.'); }
    catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
  }
}

class CategoryManagementScreen extends StatelessWidget {
  const CategoryManagementScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    return Column(children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
        child: Row(children: [
          Expanded(child: Text('${state.kategori.length} kategori', style: const TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600))),
          FilledButton.icon(onPressed: () => _editCategory(context), icon: const Icon(Icons.add_rounded, size: 18), label: const Text('Tambah')),
        ]),
      ),
      Expanded(
        child: RefreshIndicator(
          onRefresh: () => state.loadKategori(),
          child: state.kategori.isEmpty
              ? ListView(children: const [
                  SizedBox(height: 40),
                  EmptyState(icon: Icons.category_outlined, title: 'Belum ada kategori', message: 'Buat kategori agar alat lebih mudah dikelompokkan.'),
                ])
              : ListView(
                  padding: const EdgeInsets.only(bottom: 18),
                  children: state.kategori.map((category) => SurfaceCard(
                    child: Row(children: [
                      Container(width: 44, height: 44, decoration: BoxDecoration(color: AppColors.accent.withValues(alpha: .10), borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.category_rounded, color: AppColors.accent)),
                      const SizedBox(width: 12),
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(category.nama, style: const TextStyle(fontWeight: FontWeight.w800)),
                        if (category.deskripsi.isNotEmpty) Text(category.deskripsi, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                      ])),
                      IconButton(tooltip: 'Edit', onPressed: () => _editCategory(context, category: category), icon: const Icon(Icons.edit_outlined)),
                      IconButton(tooltip: 'Hapus', onPressed: () => _deleteCategory(context, category), icon: const Icon(Icons.delete_outline_rounded, color: AppColors.danger)),
                    ]),
                  )).toList(),
                ),
        ),
      ),
    ]);
  }

  Future<void> _editCategory(BuildContext context, {Kategori? category}) async {
    final state = context.read<AppState>();
    final key = GlobalKey<FormState>();
    final name = TextEditingController(text: category?.nama ?? '');
    final description = TextEditingController(text: category?.deskripsi ?? '');
    final saved = await showDialog<bool>(context: context, builder: (context) => AlertDialog(title: Text(category == null ? 'Tambah kategori' : 'Edit kategori'), content: SizedBox(width: 450, child: Form(key: key, child: Column(mainAxisSize: MainAxisSize.min, children: [TextFormField(controller: name, decoration: appInput('Nama kategori'), validator: (v) => v == null || v.trim().isEmpty ? 'Nama kategori wajib diisi.' : null), const SizedBox(height: 12), TextFormField(controller: description, decoration: appInput('Deskripsi (opsional)'), minLines: 2, maxLines: 3)]))), actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () { if (key.currentState!.validate()) Navigator.pop(context, true); }, child: const Text('Simpan'))]));
    if (saved == true) {
      try { await state.simpanKategori(id: category?.id, nama: name.text, deskripsi: description.text); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Kategori disimpan.'); }
      catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
    }
    name.dispose(); description.dispose();
  }

  Future<void> _deleteCategory(BuildContext context, Kategori category) async {
    final yes = await showDialog<bool>(context: context, builder: (context) => AlertDialog(title: const Text('Hapus kategori?'), content: Text('Kategori "${category.nama}" hanya dapat dihapus jika tidak sedang dipakai oleh data alat.'), actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () => Navigator.pop(context, true), style: FilledButton.styleFrom(backgroundColor: AppColors.danger), child: const Text('Hapus'))]));
    if (yes != true) return;
    final state = context.read<AppState>();
    try { await state.hapusKategori(category.id); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Kategori dihapus.'); }
    catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
  }
}

class UsersScreen extends StatelessWidget {
  const UsersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    return Column(children: [
      ScreenHeading(title: 'Pengguna', subtitle: 'Kelola akun yang terdaftar.', action: IconButton(onPressed: state.loadPengguna, tooltip: 'Muat ulang', icon: const Icon(Icons.refresh_rounded))),
      Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 12), child: Row(children: [Expanded(child: Text('${state.pengguna.length} akun', style: const TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600))), FilledButton.icon(onPressed: () => _addUser(context), icon: const Icon(Icons.person_add_alt_1_rounded, size: 18), label: const Text('Tambah'))])),
      Expanded(child: RefreshIndicator(onRefresh: () => state.loadPengguna(), child: state.pengguna.isEmpty ? ListView(children: const [SizedBox(height: 50), EmptyState(icon: Icons.groups_outlined, title: 'Belum ada data pengguna', message: 'Jika daftar akun kosong, tarik layar ke bawah untuk memuat ulang.')]) : ListView(padding: const EdgeInsets.only(bottom: 18), children: state.pengguna.map((json) {
        final user = AppUser.fromJson(json);
        return SurfaceCard(child: Row(children: [CircleAvatar(radius: 23, backgroundColor: AppColors.primary.withValues(alpha: .10), child: Text(user.nama.isNotEmpty ? user.nama[0].toUpperCase() : 'P', style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w900))), const SizedBox(width: 12), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(user.nama, style: const TextStyle(fontWeight: FontWeight.w800)), const SizedBox(height: 4), Text(user.email, style: const TextStyle(color: AppColors.muted, fontSize: 12)), const SizedBox(height: 6), Wrap(spacing: 7, children: [StatusPill(status: user.role, label: roleLabel(user.role)), if (user.jenisPeminjam != null && user.jenisPeminjam!.isNotEmpty) Text(user.jenisPeminjam!.toUpperCase(), style: const TextStyle(fontSize: 10, color: AppColors.muted, fontWeight: FontWeight.w700))])]))]));
      }).toList()))),
    ]);
  }

  Future<void> _addUser(BuildContext context) async {
    final state = context.read<AppState>();
    final key = GlobalKey<FormState>();
    final nama = TextEditingController();
    final email = TextEditingController();
    final password = TextEditingController();
    final nomor = TextEditingController();
    String role = 'peminjam';
    String jenis = 'siswa';
    final saved = await showDialog<bool>(context: context, builder: (dialogContext) => StatefulBuilder(builder: (context, setDialogState) => AlertDialog(
      title: const Text('Tambah pengguna'),
      content: SizedBox(width: 480, child: SingleChildScrollView(child: Form(key: key, child: Column(mainAxisSize: MainAxisSize.min, children: [
        TextFormField(controller: nama, decoration: appInput('Nama lengkap'), validator: (v) => v == null || v.trim().isEmpty ? 'Nama wajib diisi.' : null),
        const SizedBox(height: 11),
        TextFormField(controller: email, decoration: appInput('Email'), keyboardType: TextInputType.emailAddress, validator: (v) => v == null || !v.contains('@') ? 'Email tidak valid.' : null),
        const SizedBox(height: 11),
        TextFormField(controller: password, decoration: appInput('Kata sandi awal'), obscureText: true, validator: (v) => v == null || v.length < 8 ? 'Minimal 8 karakter.' : null),
        const SizedBox(height: 11),
        DropdownButtonFormField<String>(value: role, decoration: appInput('Peran'), items: const [DropdownMenuItem(value: 'peminjam', child: Text('Peminjam')), DropdownMenuItem(value: 'petugas', child: Text('Petugas')), DropdownMenuItem(value: 'admin', child: Text('Admin'))], onChanged: (v) => setDialogState(() => role = v ?? 'peminjam')),
        if (role == 'peminjam') ...[
          const SizedBox(height: 11),
          DropdownButtonFormField<String>(value: jenis, decoration: appInput('Jenis peminjam'), items: const [DropdownMenuItem(value: 'siswa', child: Text('Siswa')), DropdownMenuItem(value: 'guru', child: Text('Guru'))], onChanged: (v) => setDialogState(() => jenis = v ?? 'siswa')),
          const SizedBox(height: 11),
          TextFormField(controller: nomor, decoration: appInput('NIS / NIP (opsional)')),
        ],
      ])))),
      actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () { if (key.currentState!.validate()) Navigator.pop(context, true); }, child: const Text('Buat akun'))],
    )));
    if (saved == true) {
      try { await state.tambahPengguna(nama: nama.text, email: email.text, password: password.text, role: role, jenisPeminjam: jenis, nomorInduk: nomor.text); if (context.mounted) showAppMessage(context, state.lastMessage ?? 'Pengguna ditambahkan.'); }
      catch (error) { if (context.mounted) showAppMessage(context, state.lastMessage ?? error.toString(), error: true); }
    }
    nama.dispose(); email.dispose(); password.dispose(); nomor.dispose();
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final user = state.user!;
    return ListView(padding: const EdgeInsets.only(bottom: 28), children: [
      const ScreenHeading(title: 'Profil akun', subtitle: 'Informasi akun dan koneksi aplikasi.'),
      SurfaceCard(padding: const EdgeInsets.all(21), child: Column(children: [
        CircleAvatar(radius: 38, backgroundColor: AppColors.primary.withValues(alpha: .10), child: Text(user.nama.isNotEmpty ? user.nama[0].toUpperCase() : 'P', style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w900, fontSize: 29))),
        const SizedBox(height: 13),
        Text(user.nama, textAlign: TextAlign.center, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppColors.ink)),
        const SizedBox(height: 4),
        Text(user.email, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted)),
        const SizedBox(height: 13),
        StatusPill(status: user.role, label: _formatRoleDetail(user)),
      ])),
      SurfaceCard(child: Column(children: [
        _ProfileRow(icon: Icons.badge_outlined, label: 'ID pengguna', value: '${user.id}'),
        const Divider(height: 22, color: AppColors.line),
        _ProfileRow(icon: Icons.person_outline, label: 'Jenis peminjam', value: (user.jenisPeminjam ?? '-').toUpperCase()),
        const Divider(height: 22, color: AppColors.line),
        _ProfileRow(icon: Icons.numbers_rounded, label: 'Nomor induk', value: user.nomorInduk?.isNotEmpty == true ? user.nomorInduk! : '-'),
      ])),
      SurfaceCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('Koneksi server', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        const SizedBox(height: 11),
        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [const Icon(Icons.link_rounded, color: AppColors.primary), const SizedBox(width: 10), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('Alamat API', style: TextStyle(fontWeight: FontWeight.w700)), const SizedBox(height: 4), SelectableText(AppConfig.apiBaseUrl, style: const TextStyle(fontSize: 12, color: AppColors.muted))]))]),
        const SizedBox(height: 12),
        SizedBox(width: double.infinity, child: OutlinedButton.icon(onPressed: state.refreshAll, icon: const Icon(Icons.sync_rounded), label: const Text('Sinkronkan data'))),
      ])),
      Padding(padding: const EdgeInsets.fromLTRB(16, 12, 16, 0), child: FilledButton.tonalIcon(onPressed: () async {
        final yes = await showDialog<bool>(context: context, builder: (context) => AlertDialog(title: const Text('Keluar dari akun?'), content: const Text('Kamu perlu login lagi untuk membuka data PinjamAlat.'), actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')), FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Keluar'))]));
        if (yes == true && context.mounted) await context.read<AppState>().logout();
      }, icon: const Icon(Icons.logout_rounded), label: const Text('Keluar dari akun'), style: FilledButton.styleFrom(foregroundColor: AppColors.danger, backgroundColor: AppColors.danger.withValues(alpha: .08)))),
      const SizedBox(height: 18),
      const Center(child: Text('PinjamAlat • UKK Pemrograman Mobile', style: TextStyle(fontSize: 11, color: AppColors.muted))),
    ]);
  }
}

class _ProfileRow extends StatelessWidget {
  const _ProfileRow({required this.icon, required this.label, required this.value});
  final IconData icon;
  final String label;
  final String value;
  @override
  Widget build(BuildContext context) => Row(children: [Icon(icon, color: AppColors.primary), const SizedBox(width: 12), Expanded(child: Text(label, style: const TextStyle(color: AppColors.muted))), Text(value, style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.ink))]);
}

String _rupiah(int value) {
  final digits = value.toString();
  final out = StringBuffer();
  for (var i = 0; i < digits.length; i++) {
    out.write(digits[i]);
    final remaining = digits.length - i - 1;
    if (remaining > 0 && remaining % 3 == 0) out.write('.');
  }
  return 'Rp$out';
}

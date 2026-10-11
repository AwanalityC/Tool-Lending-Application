import 'package:flutter/material.dart';

class AppColors {
  AppColors._();
  static const primary = Color(0xFF164E83);
  static const primaryDark = Color(0xFF0D3154);
  static const accent = Color(0xFF16A6A1);
  static const background = Color(0xFFF4F7FB);
  static const ink = Color(0xFF172B4D);
  static const muted = Color(0xFF66758A);
  static const line = Color(0xFFE2E8F0);
  static const success = Color(0xFF16835D);
  static const danger = Color(0xFFBC3A3A);
  static const warning = Color(0xFF9A6700);
}

InputDecoration appInput(String label, {IconData? icon, String? hint}) {
  return InputDecoration(
    labelText: label,
    hintText: hint,
    prefixIcon: icon == null ? null : Icon(icon, size: 20),
    filled: true,
    fillColor: Colors.white,
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.line)),
    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.line)),
    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.primary, width: 1.6)),
  );
}

class ScreenHeading extends StatelessWidget {
  const ScreenHeading({super.key, required this.title, this.subtitle, this.action});
  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800, color: AppColors.ink)),
              if (subtitle != null) ...[
                const SizedBox(height: 5),
                Text(subtitle!, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.muted)),
              ],
            ])),
            if (action != null) action!,
          ],
        ),
      );
}

class SurfaceCard extends StatelessWidget {
  const SurfaceCard({super.key, required this.child, this.padding = const EdgeInsets.all(16), this.margin, this.onTap});
  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry? margin;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: margin ?? const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18), side: const BorderSide(color: AppColors.line)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(onTap: onTap, child: Padding(padding: padding, child: child)),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.title, required this.message, this.action});
  final IconData icon;
  final String title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Container(width: 76, height: 76, decoration: BoxDecoration(color: AppColors.background, borderRadius: BorderRadius.circular(24)), child: Icon(icon, size: 36, color: AppColors.primary)),
            const SizedBox(height: 18),
            Text(title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.ink)),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(height: 1.5, color: AppColors.muted)),
            if (action != null) ...[const SizedBox(height: 16), action!],
          ]),
        ),
      );
}

class StatCard extends StatelessWidget {
  const StatCard({super.key, required this.label, required this.value, required this.icon, this.tint = AppColors.primary});
  final String label;
  final String value;
  final IconData icon;
  final Color tint;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: AppColors.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(width: 38, height: 38, decoration: BoxDecoration(color: tint.withValues(alpha: .10), borderRadius: BorderRadius.circular(12)), child: Icon(icon, color: tint)),
          const SizedBox(height: 15),
          Text(value, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.ink)),
          const SizedBox(height: 3),
          Text(label, maxLines: 2, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
        ]),
      );
}

class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.status, this.label});
  final String status;
  final String? label;

  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final Color color;
    if (normalized == 'disetujui' || normalized == 'selesai') {
      color = AppColors.success;
    } else if (normalized == 'ditolak' || normalized == 'gagal') {
      color = AppColors.danger;
    } else if (normalized == 'terlambat') {
      color = AppColors.danger;
    } else {
      color = AppColors.warning;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(color: color.withValues(alpha: .10), borderRadius: BorderRadius.circular(20)),
      child: Text(label ?? status, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: color)),
    );
  }
}

class LoadingView extends StatelessWidget {
  const LoadingView({super.key, this.message = 'Memuat data...'});
  final String message;
  @override
  Widget build(BuildContext context) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [const CircularProgressIndicator(), const SizedBox(height: 14), Text(message, style: const TextStyle(color: AppColors.muted))]));
}

void showAppMessage(BuildContext context, String message, {bool error = false}) {
  if (!context.mounted || message.trim().isEmpty) return;
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Text(message),
      behavior: SnackBarBehavior.floating,
      backgroundColor: error ? AppColors.danger : AppColors.primaryDark,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ));
}

String roleLabel(String role) {
  switch (role.toLowerCase()) {
    case 'admin': return 'Administrator';
    case 'petugas': return 'Petugas';
    case 'peminjam': return 'Peminjam';
    default: return role.isEmpty ? 'Pengguna' : role;
  }
}

String formatTanggal(String value) {
  if (value == '-' || value.isEmpty) return '-';
  final date = DateTime.tryParse(value);
  if (date == null) return value;
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  return '${date.day} ${months[date.month - 1]} ${date.year}';
}

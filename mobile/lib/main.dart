import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'providers/app_state.dart';
import 'screens/app_screens.dart';
import 'widgets/app_widgets.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(ChangeNotifierProvider(
    create: (_) => AppState()..initialize(),
    child: const PinjamAlatApp(),
  ));
}

class PinjamAlatApp extends StatelessWidget {
  const PinjamAlatApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'PinjamAlat',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          scaffoldBackgroundColor: AppColors.background,
          colorScheme: ColorScheme.fromSeed(seedColor: AppColors.primary, primary: AppColors.primary, secondary: AppColors.accent, surface: Colors.white),
          appBarTheme: const AppBarTheme(backgroundColor: AppColors.background, foregroundColor: AppColors.ink, elevation: 0, centerTitle: false),
          textTheme: const TextTheme(
            headlineSmall: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.ink),
            titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.ink),
            titleMedium: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.ink),
            bodyMedium: TextStyle(fontSize: 14, color: AppColors.ink),
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          ),
          navigationBarTheme: NavigationBarThemeData(
            backgroundColor: Colors.white,
            indicatorColor: AppColors.accent.withValues(alpha: .14),
            labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(fontSize: 11, fontWeight: states.contains(WidgetState.selected) ? FontWeight.w800 : FontWeight.w500)),
          ),
          filledButtonTheme: FilledButtonThemeData(style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)), textStyle: const TextStyle(fontWeight: FontWeight.w700))),
          outlinedButtonTheme: OutlinedButtonThemeData(style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),),
        ),
        home: const AppGate(),
      );
}

class AppGate extends StatelessWidget {
  const AppGate({super.key});

  @override
  Widget build(BuildContext context) => Consumer<AppState>(builder: (context, state, _) {
        if (!state.initialized) {
          return const Scaffold(body: LoadingView(message: 'Menyiapkan PinjamAlat...'));
        }
        if (!state.isLoggedIn) return const AuthScreen();
        return const AppShell();
      });
}

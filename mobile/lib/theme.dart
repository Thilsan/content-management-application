import 'package:flutter/material.dart';

/// The same palette the React app uses, so the two clients look like one
/// product rather than two takes on it.
class AppColors {
  static const ink = Color(0xFF101720);
  static const inkSoft = Color(0xFF55606D);
  static const muted = Color(0xFF8B95A2);

  static const line = Color(0xFFE7EAEF);
  static const surface = Color(0xFFFFFFFF);
  static const canvas = Color(0xFFF7F8FA);

  static const accent = Color(0xFF2F6FED);
  static const accentStrong = Color(0xFF1E4FBD);
  static const accentWash = Color(0xFFEFF4FE);

  static const ok = Color(0xFF17703F);
  static const okWash = Color(0xFFEFF8F2);
  static const warn = Color(0xFF8A5A00);
  static const warnWash = Color(0xFFFDF7E9);
}

ThemeData buildAppTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: AppColors.accent,
  ).copyWith(surface: AppColors.canvas, onSurface: AppColors.ink);

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: AppColors.canvas,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
      foregroundColor: AppColors.ink,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(
        color: AppColors.ink,
        fontSize: 19,
        fontWeight: FontWeight.w600,
        letterSpacing: -0.3,
      ),
    ),
    dividerTheme: const DividerThemeData(color: AppColors.line, thickness: 1, space: 1),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.line),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFD9DEE6)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.accent, width: 1.6),
      ),
      labelStyle: const TextStyle(color: AppColors.muted),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: AppColors.accent,
        foregroundColor: Colors.white,
        minimumSize: const Size.fromHeight(50),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
      ),
    ),
    textTheme: const TextTheme(
      headlineSmall: TextStyle(
        color: AppColors.ink,
        fontWeight: FontWeight.w600,
        letterSpacing: -0.6,
      ),
      bodyMedium: TextStyle(color: AppColors.inkSoft),
      bodySmall: TextStyle(color: AppColors.muted),
    ),
  );
}

/// A small tracked label, the mobile twin of the web app's eyebrow.
class Eyebrow extends StatelessWidget {
  const Eyebrow(this.text, {super.key, this.padding = EdgeInsets.zero});

  final String text;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding,
      child: Text(
        text.toUpperCase(),
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 1.1,
          color: AppColors.muted,
        ),
      ),
    );
  }
}

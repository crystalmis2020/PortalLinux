import 'package:flutter/material.dart';

class PortalColors {
  static const brandDark = Color(0xff00491e);
  static const primary = Color(0xff02681e);
  static const accent = Color(0xff919f02);
  static const blue = Color(0xff2563eb);
  static const green = Color(0xff2f7d32);
  static const canvas = Color(0xfff1f5ef);
  static const border = Color(0xffd8e3d9);
  static const muted = Color(0xff4f6356);
}

class PortalTheme {
  static ThemeData light() {
    const scheme = ColorScheme.light(
      primary: PortalColors.primary,
      onPrimary: Colors.white,
      secondary: PortalColors.green,
      onSecondary: Colors.white,
      surface: Colors.white,
      onSurface: Color(0xff172033),
      error: Color(0xffb42318),
      onError: Colors.white,
      outline: PortalColors.border,
      surfaceContainerHighest: Color(0xffeef2f7),
    );

    final base = ThemeData(
      colorScheme: scheme,
      useMaterial3: true,
      scaffoldBackgroundColor: PortalColors.canvas,
      fontFamily: 'Roboto',
    );

    return base.copyWith(
      textTheme: base.textTheme.copyWith(
        headlineSmall: base.textTheme.headlineSmall?.copyWith(
          color: PortalColors.brandDark,
          fontWeight: FontWeight.w800,
        ),
        titleLarge: base.textTheme.titleLarge?.copyWith(
          color: PortalColors.brandDark,
          fontWeight: FontWeight.w800,
        ),
        titleMedium: base.textTheme.titleMedium?.copyWith(
          color: PortalColors.brandDark,
          fontWeight: FontWeight.w700,
        ),
        bodyMedium: base.textTheme.bodyMedium?.copyWith(
          color: const Color(0xff334155),
        ),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: PortalColors.brandDark,
        elevation: 0,
        scrolledUnderElevation: 1,
        surfaceTintColor: Colors.white,
      ),
      cardTheme: const CardThemeData(
        color: Colors.white,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(8)),
          side: BorderSide(color: PortalColors.border),
        ),
      ),
      inputDecorationTheme: const InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(8)),
          borderSide: BorderSide(color: PortalColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(8)),
          borderSide: BorderSide(color: PortalColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(8)),
          borderSide: BorderSide(color: PortalColors.primary, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(48, 48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          side: const BorderSide(color: PortalColors.border),
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 72,
        backgroundColor: Colors.white,
        elevation: 4,
        indicatorColor: const Color(0xffdff2e4),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            color: states.contains(WidgetState.selected)
                ? PortalColors.primary
                : PortalColors.muted,
            fontSize: 12,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      dividerTheme: const DividerThemeData(
        color: PortalColors.border,
        thickness: 1,
      ),
    );
  }
}

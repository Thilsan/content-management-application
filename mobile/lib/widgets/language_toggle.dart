import 'package:flutter/material.dart';

import '../locale_store.dart';
import '../theme.dart';

/// Two languages, so a segmented control rather than a dropdown. Matches the
/// switch in the web app.
class LanguageToggle extends StatelessWidget {
  const LanguageToggle({super.key, required this.locales});

  final LocaleStore locales;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.line),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _Option(locales: locales, code: 'en', label: 'EN'),
          _Option(locales: locales, code: 'ar', label: 'عربي'),
        ],
      ),
    );
  }
}

class _Option extends StatelessWidget {
  const _Option({
    required this.locales,
    required this.code,
    required this.label,
  });

  final LocaleStore locales;
  final String code;
  final String label;

  @override
  Widget build(BuildContext context) {
    final selected = locales.locale == code;

    return GestureDetector(
      onTap: () => locales.use(code),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: selected ? AppColors.accent : Colors.transparent,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? Colors.white : AppColors.inkSoft,
          ),
        ),
      ),
    );
  }
}

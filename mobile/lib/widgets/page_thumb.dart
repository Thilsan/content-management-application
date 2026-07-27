import 'package:flutter/material.dart';

import '../api/models.dart';
import '../theme.dart';

/// The page's cover if it has one, otherwise a colour derived from its slug.
/// Same formula as the web app, so a page keeps one identity on both.
class PageThumb extends StatelessWidget {
  const PageThumb({super.key, required this.page, this.size = 46, this.radius = 12});

  final CmsPage page;
  final double size;
  final double radius;

  static int _hueFrom(String seed) {
    var hash = 0;

    for (final unit in seed.codeUnits) {
      hash = (hash * 31 + unit) % 360;
    }

    return hash;
  }

  @override
  Widget build(BuildContext context) {
    if (page.coverImageUrl != null) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: Image.network(
          page.coverImageUrl!,
          width: size,
          height: size,
          fit: BoxFit.cover,
          errorBuilder: (_, _, _) => _generated(),
        ),
      );
    }

    return _generated();
  }

  Widget _generated() {
    final hue = _hueFrom(page.slug).toDouble();

    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(radius),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            HSLColor.fromAHSL(1, hue, 0.58, 0.60).toColor(),
            HSLColor.fromAHSL(1, (hue + 42) % 360, 0.62, 0.47).toColor(),
          ],
        ),
      ),
      child: Text(
        page.title.trim().isEmpty ? '·' : page.title.trim()[0].toUpperCase(),
        style: TextStyle(
          color: Colors.white.withValues(alpha: 0.92),
          fontSize: size * 0.4,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class StateChip extends StatelessWidget {
  const StateChip({super.key, required this.state});

  final String state;

  @override
  Widget build(BuildContext context) {
    final (background, foreground) = switch (state) {
      'Live' => (AppColors.okWash, AppColors.ok),
      'Draft' => (AppColors.warnWash, AppColors.warn),
      _ => (AppColors.accentWash, AppColors.accentStrong),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 5,
            height: 5,
            decoration: BoxDecoration(color: foreground, shape: BoxShape.circle),
          ),
          const SizedBox(width: 5),
          Text(
            state,
            style: TextStyle(
              fontSize: 11,
              color: foreground,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

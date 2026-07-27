const _months = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

/// "27 May 2026" rather than a locale guess like 5/27/2026.
String formatDate(DateTime? value) {
  if (value == null) return '';

  final local = value.toLocal();

  return '${local.day} ${_months[local.month - 1]} ${local.year}';
}

/// Roughly how long the body takes to read, at 200 words a minute.
int readingMinutes(String? html) {
  final words = (html ?? '')
      .replaceAll(RegExp(r'<[^>]+>'), ' ')
      .trim()
      .split(RegExp(r'\s+'))
      .where((word) => word.isNotEmpty)
      .length;

  return words == 0 ? 1 : (words / 200).round().clamp(1, 999);
}

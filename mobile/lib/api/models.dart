// Plain models over the JSON the CMS API returns. Only the fields this client
// actually shows are parsed; the API sends more.

class ApiUser {
  const ApiUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    required this.privileges,
  });

  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final List<String> privileges;

  factory ApiUser.fromJson(Map<String, dynamic> json) {
    return ApiUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      roles: ((json['roles'] as List?) ?? const [])
          .map((role) => (role as Map<String, dynamic>)['name'] as String)
          .toList(),
      privileges: ((json['privileges'] as List?) ?? const []).cast<String>(),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'roles': roles.map((name) => {'name': name}).toList(),
    'privileges': privileges,
  };

  bool can(String privilege) => privileges.contains(privilege);
}

/// A node of the menu tree. Children nest to any depth.
class MenuNode {
  const MenuNode({
    required this.id,
    required this.title,
    required this.titleAr,
    required this.slug,
    required this.children,
  });

  final int id;
  final String title;
  final String? titleAr;
  final String slug;
  final List<MenuNode> children;

  String titleIn(String locale) =>
      locale == 'ar' && (titleAr?.isNotEmpty ?? false) ? titleAr! : title;

  factory MenuNode.fromJson(Map<String, dynamic> json) {
    return MenuNode(
      id: json['id'] as int,
      title: json['title'] as String,
      titleAr: json['title_ar'] as String?,
      slug: json['slug'] as String,
      children: ((json['children'] as List?) ?? const [])
          .map((child) => MenuNode.fromJson(child as Map<String, dynamic>))
          .toList(),
    );
  }

  /// This node and everything under it, paired with how deep each one sits.
  List<({MenuNode node, int depth})> flatten([int depth = 0]) => [
    (node: this, depth: depth),
    ...children.expand((child) => child.flatten(depth + 1)),
  ];
}

class CmsPage {
  const CmsPage({
    required this.id,
    required this.menuId,
    required this.title,
    required this.slug,
    required this.excerpt,
    required this.status,
    required this.isVisible,
    this.titleAr,
    this.bodyAr,
    this.isTranslated = false,
    this.body,
    this.coverImageUrl,
    this.publishedAt,
    this.menuTitle,
    this.menuTitleAr,
  });

  final int id;
  final int menuId;
  final String title;
  final String slug;
  final String excerpt;
  final String status;
  final bool isVisible;
  final String? titleAr;
  final String? bodyAr;

  /// Whether the page can be read end to end in Arabic. The API decides this,
  /// so the phone and the website agree on what counts as translated.
  final bool isTranslated;
  final String? body;
  final String? coverImageUrl;
  final DateTime? publishedAt;
  final String? menuTitle;
  final String? menuTitleAr;

  factory CmsPage.fromJson(Map<String, dynamic> json) {
    final menu = json['menu'] as Map<String, dynamic>?;
    final publishedAt = json['published_at'] as String?;

    return CmsPage(
      id: json['id'] as int,
      menuId: json['menu_id'] as int,
      title: json['title'] as String,
      titleAr: json['title_ar'] as String?,
      bodyAr: json['body_ar'] as String?,
      isTranslated: (json['is_translated'] as bool?) ?? false,
      slug: json['slug'] as String,
      excerpt: (json['excerpt'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'draft',
      isVisible: (json['is_visible'] as bool?) ?? false,
      body: json['body'] as String?,
      coverImageUrl: json['cover_image_url'] as String?,
      publishedAt: publishedAt == null ? null : DateTime.tryParse(publishedAt),
      menuTitle: menu?['title'] as String?,
      menuTitleAr: menu?['title_ar'] as String?,
    );
  }

  /// Arabic only when the whole page exists in Arabic. A title in one language
  /// above a body in another is worse than either on its own.
  String titleIn(String locale) =>
      locale == 'ar' && isTranslated ? titleAr! : title;

  String? bodyIn(String locale) =>
      locale == 'ar' && isTranslated ? bodyAr : body;

  /// The section name follows the language independently of the page: a
  /// translated menu can label an untranslated page.
  String? menuTitleIn(String locale) =>
      locale == 'ar' && (menuTitleAr?.isNotEmpty ?? false)
      ? menuTitleAr
      : menuTitle;

  /// The API's excerpt is cut from the English body, so derive our own when
  /// showing Arabic. Mirrors the server helper: headings dropped, whitespace
  /// collapsed, trimmed to the same length.
  String excerptIn(String locale) {
    final source = bodyIn(locale);

    if (locale != 'ar' || !isTranslated || source == null || source.isEmpty) {
      return excerpt;
    }

    final withoutHeadings = source.replaceAll(
      RegExp(r'<(h[1-6])[^>]*>.*?</\1>', dotAll: true, caseSensitive: false),
      ' ',
    );

    final text = withoutHeadings
        .replaceAll(RegExp(r'<[^>]+>'), ' ')
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&mdash;', '—')
        .replaceAll(RegExp(r'[\s\u00A0]+'), ' ')
        .trim();

    if (text.length <= 160) {
      return text;
    }

    return '${text.substring(0, 160).trimRight()}...';
  }

  /// The direction this page's own content reads in, which is not always the
  /// direction the rest of the app is using.
  bool readsRtlIn(String locale) => locale == 'ar' && isTranslated;

  /// Draft, scheduled or live: a page can be published and still be waiting on
  /// its publish date, which the back end reports through is_visible.
  String get state {
    if (status == 'draft') return 'Draft';
    return isVisible ? 'Live' : 'Scheduled';
  }
}

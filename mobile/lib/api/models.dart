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
    required this.slug,
    required this.children,
  });

  final int id;
  final String title;
  final String slug;
  final List<MenuNode> children;

  factory MenuNode.fromJson(Map<String, dynamic> json) {
    return MenuNode(
      id: json['id'] as int,
      title: json['title'] as String,
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
    this.body,
    this.coverImageUrl,
    this.publishedAt,
    this.menuTitle,
  });

  final int id;
  final int menuId;
  final String title;
  final String slug;
  final String excerpt;
  final String status;
  final bool isVisible;
  final String? body;
  final String? coverImageUrl;
  final DateTime? publishedAt;
  final String? menuTitle;

  factory CmsPage.fromJson(Map<String, dynamic> json) {
    final menu = json['menu'] as Map<String, dynamic>?;
    final publishedAt = json['published_at'] as String?;

    return CmsPage(
      id: json['id'] as int,
      menuId: json['menu_id'] as int,
      title: json['title'] as String,
      slug: json['slug'] as String,
      excerpt: (json['excerpt'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'draft',
      isVisible: (json['is_visible'] as bool?) ?? false,
      body: json['body'] as String?,
      coverImageUrl: json['cover_image_url'] as String?,
      publishedAt: publishedAt == null ? null : DateTime.tryParse(publishedAt),
      menuTitle: menu?['title'] as String?,
    );
  }

  /// Draft, scheduled or live: a page can be published and still be waiting on
  /// its publish date, which the back end reports through is_visible.
  String get state {
    if (status == 'draft') return 'Draft';
    return isVisible ? 'Live' : 'Scheduled';
  }
}

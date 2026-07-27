import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../api/models.dart';
import '../api/session.dart';

/// One page, with the CKEditor body rendered as real markup rather than as a
/// wall of escaped text.
class PageScreen extends StatefulWidget {
  const PageScreen({super.key, required this.session, required this.pageId});

  final Session session;
  final int pageId;

  @override
  State<PageScreen> createState() => _PageScreenState();
}

class _PageScreenState extends State<PageScreen> {
  late Future<CmsPage> _page;

  @override
  void initState() {
    super.initState();
    _page = widget.session.api.page(widget.pageId);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(),
      body: FutureBuilder<CmsPage>(
        future: _page,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Padding(
              padding: const EdgeInsets.all(32),
              child: Center(child: Text('${snapshot.error}')),
            );
          }

          final page = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              if (page.menuTitle != null)
                Text(
                  page.menuTitle!.toUpperCase(),
                  style: theme.textTheme.labelSmall?.copyWith(
                    letterSpacing: 1.1,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              const SizedBox(height: 6),
              Text(page.title, style: theme.textTheme.headlineSmall),
              const SizedBox(height: 8),
              Text(
                page.publishedAt == null
                    ? page.state
                    : '${page.state} · ${_formatted(page.publishedAt!)}',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),

              if (page.coverImageUrl != null) ...[
                const SizedBox(height: 16),
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Image.network(
                    page.coverImageUrl!,
                    fit: BoxFit.cover,
                    // A missing upload should not take the whole screen down.
                    errorBuilder: (_, _, _) => const SizedBox.shrink(),
                  ),
                ),
              ],

              const SizedBox(height: 8),
              Html(
                data: page.body ?? '',
                style: {
                  'body': Style(margin: Margins.zero, padding: HtmlPaddings.zero),
                  'h2': Style(fontSize: FontSize(19), margin: Margins.only(top: 22, bottom: 6)),
                  'h3': Style(fontSize: FontSize(16), margin: Margins.only(top: 16, bottom: 4)),
                  'p': Style(fontSize: FontSize(15), lineHeight: LineHeight.number(1.55)),
                  'li': Style(fontSize: FontSize(15), lineHeight: LineHeight.number(1.5)),
                  'blockquote': Style(
                    margin: Margins.symmetric(vertical: 12),
                    padding: HtmlPaddings.only(left: 12),
                    border: const Border(
                      left: BorderSide(color: Color(0xFF2F6FED), width: 3),
                    ),
                  ),
                },
              ),
            ],
          );
        },
      ),
    );
  }

  String _formatted(DateTime value) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];

    final local = value.toLocal();

    return '${local.day} ${months[local.month - 1]} ${local.year}';
  }
}

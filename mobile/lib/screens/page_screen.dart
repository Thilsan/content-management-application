import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../api/models.dart';
import '../api/session.dart';
import '../format.dart';
import '../theme.dart';
import '../widgets/page_thumb.dart';

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
    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(backgroundColor: AppColors.surface),
      body: FutureBuilder<CmsPage>(
        future: _page,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Padding(
              padding: const EdgeInsets.all(32),
              child: Center(
                child: Text(
                  '${snapshot.error}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
              ),
            );
          }

          final page = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 48),
            children: [
              if (page.menuTitle != null) Eyebrow(page.menuTitle!),
              const SizedBox(height: 10),

              Text(
                page.title,
                style: const TextStyle(
                  fontSize: 27,
                  height: 1.2,
                  fontWeight: FontWeight.w600,
                  letterSpacing: -0.7,
                  color: AppColors.ink,
                ),
              ),

              const SizedBox(height: 14),

              // Identity strip: the same colour this page carries in the list.
              Row(
                children: [
                  PageThumb(page: page, size: 34, radius: 9),
                  const SizedBox(width: 10),
                  StateChip(state: page.state),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      [
                        if (page.publishedAt != null) formatDate(page.publishedAt),
                        '${readingMinutes(page.body)} min read',
                      ].join(' · '),
                      style: const TextStyle(fontSize: 12.5, color: AppColors.muted),
                    ),
                  ),
                ],
              ),

              if (page.coverImageUrl != null) ...[
                const SizedBox(height: 18),
                ClipRRect(
                  borderRadius: BorderRadius.circular(14),
                  child: Image.network(
                    page.coverImageUrl!,
                    fit: BoxFit.cover,
                    // A missing upload should not take the whole screen down.
                    errorBuilder: (_, _, _) => const SizedBox.shrink(),
                  ),
                ),
              ],

              const Padding(
                padding: EdgeInsets.symmetric(vertical: 18),
                child: Divider(),
              ),

              Html(
                data: page.body ?? '',
                style: {
                  'body': Style(margin: Margins.zero, padding: HtmlPaddings.zero),
                  'h2': Style(
                    fontSize: FontSize(19),
                    fontWeight: FontWeight.w600,
                    color: AppColors.ink,
                    margin: Margins.only(top: 24, bottom: 8),
                  ),
                  'h3': Style(
                    fontSize: FontSize(16),
                    fontWeight: FontWeight.w600,
                    color: AppColors.ink,
                    margin: Margins.only(top: 18, bottom: 6),
                  ),
                  'p': Style(
                    fontSize: FontSize(15.5),
                    lineHeight: LineHeight.number(1.6),
                    color: const Color(0xFF1D2732),
                    margin: Margins.only(bottom: 14),
                  ),
                  'li': Style(
                    fontSize: FontSize(15.5),
                    lineHeight: LineHeight.number(1.55),
                    color: const Color(0xFF1D2732),
                    margin: Margins.only(bottom: 6),
                  ),
                  'strong': Style(fontWeight: FontWeight.w600, color: AppColors.ink),
                  'blockquote': Style(
                    margin: Margins.symmetric(vertical: 16),
                    padding: HtmlPaddings.only(left: 14),
                    color: AppColors.inkSoft,
                    fontStyle: FontStyle.normal,
                    border: const Border(
                      left: BorderSide(color: AppColors.accent, width: 3),
                    ),
                  ),
                  'a': Style(color: AppColors.accent, textDecoration: TextDecoration.none),
                },
              ),
            ],
          );
        },
      ),
    );
  }
}

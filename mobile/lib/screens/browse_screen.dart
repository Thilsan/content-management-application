import 'package:flutter/material.dart';

import '../api/models.dart';
import '../api/session.dart';
import '../locale_store.dart';
import '../strings.dart';
import '../theme.dart';
import '../widgets/language_toggle.dart';
import '../widgets/page_thumb.dart';
import 'page_screen.dart';

/// Everything the browse screen needs, fetched together.
class _Content {
  const _Content({required this.menus, required this.pages});

  final List<MenuNode> menus;
  final List<CmsPage> pages;
}

class BrowseScreen extends StatefulWidget {
  const BrowseScreen({super.key, required this.session, required this.locales});

  final Session session;
  final LocaleStore locales;

  @override
  State<BrowseScreen> createState() => _BrowseScreenState();
}

class _BrowseScreenState extends State<BrowseScreen> {
  late Future<_Content> _content;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _content = _load();
  }

  Future<_Content> _load() async {
    final api = widget.session.api;
    final menus = await api.menus();
    final pages = await api.pages();

    return _Content(menus: menus, pages: pages);
  }

  Future<void> _refresh() async {
    final loaded = _load();
    setState(() => _content = loaded);
    await loaded;
  }

  @override
  Widget build(BuildContext context) {
    final user = widget.session.user;
    final locale = widget.locales.locale;
    final t = AppStrings.of(locale);

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 20,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(t.pages),
            if (user != null)
              Text(
                user.roles.isEmpty
                    ? user.name
                    : '${user.name} · ${user.roles.join(', ')}',
                style: const TextStyle(fontSize: 12, color: AppColors.muted),
              ),
          ],
        ),
        actions: [
          LanguageToggle(locales: widget.locales),
          IconButton(
            icon: const Icon(Icons.logout, size: 20),
            tooltip: t.signOut,
            onPressed: widget.session.signOut,
          ),
          const SizedBox(width: 4),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(62),
          child: Container(
            color: AppColors.surface,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextField(
              onChanged: (value) => setState(() => _search = value),
              decoration: InputDecoration(
                hintText: t.searchPages,
                prefixIcon: const Icon(
                  Icons.search,
                  size: 20,
                  color: AppColors.muted,
                ),
                isDense: true,
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ),
      ),
      body: FutureBuilder<_Content>(
        future: _content,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return _Message(
              title: t.couldNotLoad,
              detail: '${snapshot.error}',
              onRetry: _refresh,
              retryLabel: t.tryAgain,
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: _list(snapshot.data!, locale, t),
          );
        },
      ),
    );
  }

  Widget _list(_Content content, String locale, AppStrings t) {
    final term = _search.trim().toLowerCase();

    final matching = content.pages.where((page) {
      if (term.isEmpty) return true;

      return page.titleIn(locale).toLowerCase().contains(term) ||
          page.excerptIn(locale).toLowerCase().contains(term);
    }).toList();

    // Menu order decides the order pages appear in, exactly as on the website.
    final ordered = content.menus.expand((node) => node.flatten()).toList();

    final children = <Widget>[];

    for (final entry in ordered) {
      final pages = matching
          .where((page) => page.menuId == entry.node.id)
          .toList();

      if (pages.isEmpty) continue;

      children.add(
        Padding(
          padding: EdgeInsets.fromLTRB(20 + entry.depth * 12, 22, 20, 8),
          child: Row(
            children: [
              Eyebrow(entry.node.titleIn(locale)),
              const SizedBox(width: 10),
              const Expanded(child: Divider()),
            ],
          ),
        ),
      );

      children.addAll(
        pages.map(
          (page) => Padding(
            padding: EdgeInsets.fromLTRB(16 + entry.depth * 8, 0, 16, 8),
            child: _PageCard(
              page: page,
              session: widget.session,
              locales: widget.locales,
            ),
          ),
        ),
      );
    }

    if (children.isEmpty) {
      return ListView(
        children: [
          _Message(
            title: term.isEmpty ? t.noPages : t.noMatches,
            detail: term.isEmpty ? t.noPagesDetail : t.tryShorter,
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.only(bottom: 28),
      children: children,
    );
  }
}

class _PageCard extends StatelessWidget {
  const _PageCard({
    required this.page,
    required this.session,
    required this.locales,
  });

  final CmsPage page;
  final Session session;
  final LocaleStore locales;

  @override
  Widget build(BuildContext context) {
    final locale = locales.locale;
    final t = AppStrings.of(locale);

    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) =>
                PageScreen(session: session, locales: locales, pageId: page.id),
          ),
        ),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.line),
          ),
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageThumb(page: page),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            page.titleIn(locale),
                            style: const TextStyle(
                              fontSize: 15.5,
                              fontWeight: FontWeight.w600,
                              color: AppColors.ink,
                              height: 1.25,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        StateChip(
                          state: page.state,
                          label: t.state(page.state),
                        ),
                      ],
                    ),
                    const SizedBox(height: 5),
                    Text(
                      page.excerptIn(locale),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 13.5,
                        color: AppColors.inkSoft,
                        height: 1.4,
                      ),
                    ),
                    if (page.publishedAt != null) ...[
                      const SizedBox(height: 7),
                      Text(
                        t.date(page.publishedAt, locale),
                        style: const TextStyle(
                          fontSize: 11.5,
                          color: AppColors.muted,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Message extends StatelessWidget {
  const _Message({
    required this.title,
    required this.detail,
    this.onRetry,
    this.retryLabel = 'Try again',
  });

  final String title;
  final String detail;
  final Future<void> Function()? onRetry;
  final String retryLabel;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(32, 72, 32, 32),
      child: Column(
        children: [
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            detail,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 13, color: AppColors.muted),
          ),
          if (onRetry != null) ...[
            const SizedBox(height: 18),
            OutlinedButton(onPressed: onRetry, child: Text(retryLabel)),
          ],
        ],
      ),
    );
  }
}

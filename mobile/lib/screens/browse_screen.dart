import 'package:flutter/material.dart';

import '../api/models.dart';
import '../api/session.dart';
import 'page_screen.dart';

/// Everything the browse screen needs, fetched together.
class _Content {
  const _Content({required this.menus, required this.pages});

  final List<MenuNode> menus;
  final List<CmsPage> pages;
}

class BrowseScreen extends StatefulWidget {
  const BrowseScreen({super.key, required this.session});

  final Session session;

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

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pages'),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.account_circle_outlined),
            onSelected: (value) {
              if (value == 'sign-out') widget.session.signOut();
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                enabled: false,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(user?.name ?? ''),
                    Text(
                      user?.roles.join(', ') ?? '',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem(value: 'sign-out', child: Text('Sign out')),
            ],
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(56),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
            child: SearchBar(
              hintText: 'Search pages',
              leading: const Icon(Icons.search),
              onChanged: (value) => setState(() => _search = value),
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
              title: 'Could not load the pages',
              detail: '${snapshot.error}',
              onRetry: _refresh,
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: _sections(snapshot.data!),
          );
        },
      ),
    );
  }

  Widget _sections(_Content content) {
    final term = _search.trim().toLowerCase();

    final matching = content.pages.where((page) {
      if (term.isEmpty) return true;

      return page.title.toLowerCase().contains(term) ||
          page.excerpt.toLowerCase().contains(term);
    }).toList();

    // Menu order decides the order pages appear in, exactly as on the website.
    final ordered = content.menus.expand((node) => node.flatten()).toList();

    final tiles = <Widget>[];

    for (final entry in ordered) {
      final pages = matching.where((page) => page.menuId == entry.node.id).toList();

      if (pages.isEmpty) continue;

      tiles.add(
        Padding(
          padding: EdgeInsets.fromLTRB(16 + entry.depth * 12, 20, 16, 6),
          child: Text(
            entry.node.title.toUpperCase(),
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
              letterSpacing: 1.1,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ),
      );

      tiles.addAll(pages.map((page) => _PageTile(page: page, session: widget.session)));
    }

    if (tiles.isEmpty) {
      return ListView(
        children: [
          _Message(
            title: term.isEmpty ? 'No pages yet' : 'Nothing matches “$_search”',
            detail: term.isEmpty
                ? 'Pages added in the back office will show up here.'
                : 'Try a shorter word.',
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.only(bottom: 24),
      children: tiles,
    );
  }
}

class _PageTile extends StatelessWidget {
  const _PageTile({required this.page, required this.session});

  final CmsPage page;
  final Session session;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      title: Text(page.title),
      subtitle: Text(page.excerpt, maxLines: 2, overflow: TextOverflow.ellipsis),
      trailing: _StateChip(state: page.state),
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => PageScreen(session: session, pageId: page.id),
        ),
      ),
    );
  }
}

class _StateChip extends StatelessWidget {
  const _StateChip({required this.state});

  final String state;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    final (background, foreground) = switch (state) {
      'Live' => (const Color(0xFFEFF8F2), const Color(0xFF17703F)),
      'Draft' => (const Color(0xFFFDF7E9), const Color(0xFF8A5A00)),
      _ => (const Color(0xFFEFF4FE), const Color(0xFF1E4FBD)),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: scheme.outlineVariant),
      ),
      child: Text(
        state,
        style: TextStyle(fontSize: 11, color: foreground, fontWeight: FontWeight.w600),
      ),
    );
  }
}

class _Message extends StatelessWidget {
  const _Message({required this.title, required this.detail, this.onRetry});

  final String title;
  final String detail;
  final Future<void> Function()? onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 6),
          Text(
            detail,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall,
          ),
          if (onRetry != null) ...[
            const SizedBox(height: 16),
            OutlinedButton(onPressed: onRetry, child: const Text('Try again')),
          ],
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';

import 'api/api_client.dart';
import 'api/session.dart';
import 'screens/browse_screen.dart';
import 'screens/login_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  final session = Session(api: ApiClient(baseUrl: ApiClient.defaultBaseUrl));

  // Restoring reaches the network, so the app opens on a spinner and swaps to
  // the right screen once the stored token has been checked.
  session.restore();

  runApp(CmsApp(session: session));
}

class CmsApp extends StatelessWidget {
  const CmsApp({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CMS',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorSchemeSeed: const Color(0xFF2F6FED),
        useMaterial3: true,
      ),
      home: ListenableBuilder(
        listenable: session,
        builder: (context, _) {
          if (session.restoring) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }

          return session.signedIn
              ? BrowseScreen(session: session)
              : LoginScreen(session: session);
        },
      ),
    );
  }
}

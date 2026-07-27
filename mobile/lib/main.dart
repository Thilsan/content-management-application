import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'api/api_client.dart';
import 'api/session.dart';
import 'locale_store.dart';
import 'screens/browse_screen.dart';
import 'screens/login_screen.dart';
import 'theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Without this, formatting a date in Arabic throws rather than falling back.
  await initializeDateFormatting();

  final session = Session(api: ApiClient(baseUrl: ApiClient.defaultBaseUrl));
  final locales = LocaleStore();

  // Both reach storage, so the app opens on a spinner and swaps once they land.
  session.restore();
  locales.restore();

  runApp(CmsApp(session: session, locales: locales));
}

class CmsApp extends StatelessWidget {
  const CmsApp({super.key, required this.session, required this.locales});

  final Session session;
  final LocaleStore locales;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: locales,
      builder: (context, _) {
        return MaterialApp(
          title: 'CMS',
          debugShowCheckedModeBanner: false,
          theme: buildAppTheme(),

          // Setting the locale is what flips the whole tree to right to left;
          // Flutter does the mirroring itself once it knows the language.
          locale: Locale(locales.locale),
          supportedLocales: const [Locale('en'), Locale('ar')],
          localizationsDelegates: const [
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],

          home: ListenableBuilder(
            listenable: session,
            builder: (context, _) {
              if (session.restoring) {
                return const Scaffold(
                  body: Center(child: CircularProgressIndicator()),
                );
              }

              return session.signedIn
                  ? BrowseScreen(session: session, locales: locales)
                  : LoginScreen(session: session, locales: locales);
            },
          ),
        );
      },
    );
  }
}

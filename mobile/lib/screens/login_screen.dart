import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../api/session.dart';
import '../locale_store.dart';
import '../strings.dart';
import '../theme.dart';
import '../widgets/language_toggle.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session, required this.locales});

  final Session session;
  final LocaleStore locales;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();

  String? _message;
  String? _emailError;
  bool _busy = false;
  bool _obscured = true;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _message = null;
      _emailError = null;
    });

    try {
      await widget.session.signIn(_email.text.trim(), _password.text);
    } on ApiException catch (error) {
      setState(() {
        _message = error.message;
        _emailError = error.errors['email']?.first;
      });
    } catch (_) {
      setState(
        () => _message = AppStrings.of(widget.locales.locale).couldNotReach,
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  /// Tapping a demo account fills the form rather than making the reviewer type
  /// an email on a phone keyboard.
  void _use(String email) {
    setState(() {
      _email.text = email;
      _password.text = '12345';
      _emailError = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final t = AppStrings.of(widget.locales.locale);

    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 26, vertical: 32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: LanguageToggle(locales: widget.locales),
                  ),

                  const SizedBox(height: 20),

                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: Container(
                      width: 52,
                      height: 52,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.accent, Color(0xFF6D4BF0)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(15),
                      ),
                      child: const Text(
                        'CM',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 26),
                  Text(
                    t.signIn,
                    style: const TextStyle(
                      fontSize: 30,
                      fontWeight: FontWeight.w600,
                      letterSpacing: -0.8,
                      color: AppColors.ink,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    t.signInBlurb,
                    style: const TextStyle(
                      fontSize: 14.5,
                      color: AppColors.inkSoft,
                      height: 1.45,
                    ),
                  ),

                  if (_message != null) ...[
                    const SizedBox(height: 20),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 11,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFDF2F1),
                        borderRadius: BorderRadius.circular(10),
                        border: const Border(
                          left: BorderSide(color: Color(0xFFC0392F), width: 3),
                        ),
                      ),
                      child: Text(
                        _message!,
                        style: const TextStyle(
                          fontSize: 13.5,
                          color: Color(0xFFC0392F),
                        ),
                      ),
                    ),
                  ],

                  const SizedBox(height: 26),
                  TextField(
                    controller: _email,
                    textDirection: TextDirection.ltr,
                    keyboardType: TextInputType.emailAddress,
                    autocorrect: false,
                    enabled: !_busy,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(
                      labelText: t.email,
                      hintText: 'you@example.com',
                      errorText: _emailError,
                    ),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _password,
                    textDirection: TextDirection.ltr,
                    obscureText: _obscured,
                    enabled: !_busy,
                    textInputAction: TextInputAction.go,
                    onSubmitted: (_) => _busy ? null : _submit(),
                    decoration: InputDecoration(
                      labelText: t.password,
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscured
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined,
                          size: 20,
                          color: AppColors.muted,
                        ),
                        onPressed: () => setState(() => _obscured = !_obscured),
                      ),
                    ),
                  ),

                  const SizedBox(height: 22),
                  FilledButton(
                    onPressed: _busy ? null : _submit,
                    child: _busy
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(t.signIn),
                  ),

                  const SizedBox(height: 30),
                  Eyebrow(t.seededAccounts),
                  const SizedBox(height: 10),
                  _DemoAccount(
                    email: 'admin@cms.com',
                    detail: t.adminDetail,
                    onTap: () => _use('admin@cms.com'),
                  ),
                  const SizedBox(height: 8),
                  _DemoAccount(
                    email: 'moderator@cms.com',
                    detail: t.moderatorDetail,
                    onTap: () => _use('moderator@cms.com'),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    t.tapToFill,
                    style: const TextStyle(
                      fontSize: 12.5,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _DemoAccount extends StatelessWidget {
  const _DemoAccount({
    required this.email,
    required this.detail,
    required this.onTap,
  });

  final String email;
  final String detail;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.canvas,
      borderRadius: BorderRadius.circular(11),
      child: InkWell(
        borderRadius: BorderRadius.circular(11),
        onTap: onTap,
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(11),
            border: Border.all(color: AppColors.line),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      email,
                      textDirection: TextDirection.ltr,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppColors.ink,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      detail,
                      style: const TextStyle(
                        fontSize: 12.5,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.north_west, size: 16, color: AppColors.muted),
            ],
          ),
        ),
      ),
    );
  }
}

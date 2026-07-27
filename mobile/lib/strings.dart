import 'package:intl/intl.dart';

/// The interface text this app owns. Page content itself is translated in the
/// CMS, not here.
class AppStrings {
  const AppStrings({
    required this.pages,
    required this.searchPages,
    required this.signIn,
    required this.signInBlurb,
    required this.email,
    required this.password,
    required this.signOut,
    required this.seededAccounts,
    required this.tapToFill,
    required this.adminDetail,
    required this.moderatorDetail,
    required this.couldNotReach,
    required this.couldNotLoad,
    required this.tryAgain,
    required this.noPages,
    required this.noPagesDetail,
    required this.noMatches,
    required this.tryShorter,
    required this.minRead,
    required this.live,
    required this.draft,
    required this.scheduled,
    required this.onlyInEnglish,
  });

  final String pages;
  final String searchPages;
  final String signIn;
  final String signInBlurb;
  final String email;
  final String password;
  final String signOut;
  final String seededAccounts;
  final String tapToFill;
  final String adminDetail;
  final String moderatorDetail;
  final String couldNotReach;
  final String couldNotLoad;
  final String tryAgain;
  final String noPages;
  final String noPagesDetail;
  final String noMatches;
  final String tryShorter;
  final String minRead;
  final String live;
  final String draft;
  final String scheduled;
  final String onlyInEnglish;

  static const en = AppStrings(
    pages: 'Pages',
    searchPages: 'Search pages',
    signIn: 'Sign in',
    signInBlurb: 'Browse the pages your role gives you access to.',
    email: 'Email',
    password: 'Password',
    signOut: 'Sign out',
    seededAccounts: 'Seeded accounts',
    tapToFill: 'Tap one to fill the form. Password is “password”.',
    adminDetail: 'Administrator · every privilege',
    moderatorDetail: 'Moderator · pages only, no deletes',
    couldNotReach: 'Could not reach the API. Is the backend running?',
    couldNotLoad: 'Could not load the pages',
    tryAgain: 'Try again',
    noPages: 'No pages yet',
    noPagesDetail: 'Pages added in the back office show up here.',
    noMatches: 'Nothing matches that search',
    tryShorter: 'Try a shorter word.',
    minRead: 'min read',
    live: 'Live',
    draft: 'Draft',
    scheduled: 'Scheduled',
    onlyInEnglish: 'Not translated yet, so this page is shown in English.',
  );

  static const ar = AppStrings(
    pages: 'الصفحات',
    searchPages: 'ابحث في الصفحات',
    signIn: 'تسجيل الدخول',
    signInBlurb: 'تصفّح الصفحات التي يتيحها لك دورك.',
    email: 'البريد الإلكتروني',
    password: 'كلمة المرور',
    signOut: 'تسجيل الخروج',
    seededAccounts: 'حسابات تجريبية',
    tapToFill: 'اضغط على أحدها لتعبئة النموذج. كلمة المرور هي «password».',
    adminDetail: 'مدير · جميع الصلاحيات',
    moderatorDetail: 'مشرف · الصفحات فقط، دون حذف',
    couldNotReach: 'تعذّر الوصول إلى الخادم. هل هو قيد التشغيل؟',
    couldNotLoad: 'تعذّر تحميل الصفحات',
    tryAgain: 'حاول مرة أخرى',
    noPages: 'لا توجد صفحات بعد',
    noPagesDetail: 'تظهر هنا الصفحات المضافة من لوحة التحكم.',
    noMatches: 'لا توجد نتائج مطابقة',
    tryShorter: 'جرّب كلمة أقصر.',
    minRead: 'دقيقة قراءة',
    live: 'منشورة',
    draft: 'مسودة',
    scheduled: 'مجدولة',
    onlyInEnglish: 'لم تُترجم هذه الصفحة بعد، لذا تُعرض بالإنجليزية.',
  );

  static AppStrings of(String locale) => locale == 'ar' ? ar : en;

  /// Dates follow the language: Arabic digits and month names when Arabic.
  String date(DateTime? value, String locale) {
    if (value == null) return '';

    return DateFormat(
      'd MMM yyyy',
      locale == 'ar' ? 'ar' : 'en_GB',
    ).format(value.toLocal());
  }

  String state(String state) => switch (state) {
    'Live' => live,
    'Draft' => draft,
    _ => scheduled,
  };
}

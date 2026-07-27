import 'package:cms_mobile/api/models.dart';
import 'package:cms_mobile/strings.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';

CmsPage page({String? titleAr, String? bodyAr, bool translated = false}) {
  return CmsPage.fromJson({
    'id': 1,
    'menu_id': 1,
    'title': 'Who We Are',
    'title_ar': titleAr,
    'slug': 'who-we-are',
    'excerpt': 'We started in 2014.',
    'body': '<p>We started in 2014.</p>',
    'body_ar': bodyAr,
    'is_translated': translated,
    'status': 'published',
    'is_visible': true,
  });
}

void main() {
  setUpAll(() async => initializeDateFormatting());

  test('English is served unless Arabic is asked for', () {
    final subject = page(
      titleAr: 'من نحن',
      bodyAr: '<p>بدأنا</p>',
      translated: true,
    );

    expect(subject.titleIn('en'), 'Who We Are');
    expect(subject.bodyIn('en'), '<p>We started in 2014.</p>');
    expect(subject.readsRtlIn('en'), isFalse);
  });

  test('Arabic is served when the page is translated', () {
    final subject = page(
      titleAr: 'من نحن',
      bodyAr: '<p>بدأنا</p>',
      translated: true,
    );

    expect(subject.titleIn('ar'), 'من نحن');
    expect(subject.bodyIn('ar'), '<p>بدأنا</p>');
    expect(subject.readsRtlIn('ar'), isTrue);
  });

  test(
    'an untranslated page falls back to English and reads left to right',
    () {
      final subject = page();

      expect(subject.titleIn('ar'), 'Who We Are');
      expect(subject.bodyIn('ar'), '<p>We started in 2014.</p>');

      // The app is in Arabic but this page is not, so its body must not flip.
      expect(subject.readsRtlIn('ar'), isFalse);
    },
  );

  test('a half translated page falls back wholly, matching the website', () {
    // The API reports is_translated false when only one field is filled in.
    final subject = page(titleAr: 'من نحن', translated: false);

    expect(subject.titleIn('ar'), 'Who We Are');
    expect(subject.readsRtlIn('ar'), isFalse);
  });

  test('menu titles fall back independently of the pages under them', () {
    final translated = MenuNode.fromJson({
      'id': 1,
      'title': 'About',
      'title_ar': 'من نحن',
      'slug': 'about',
      'children': [],
    });

    final plain = MenuNode.fromJson({
      'id': 2,
      'title': 'News',
      'title_ar': null,
      'slug': 'news',
      'children': [],
    });

    expect(translated.titleIn('ar'), 'من نحن');
    expect(translated.titleIn('en'), 'About');
    expect(plain.titleIn('ar'), 'News');
  });

  test('strings and dates follow the language', () {
    expect(AppStrings.of('en').pages, 'Pages');
    expect(AppStrings.of('ar').pages, 'الصفحات');
    expect(
      AppStrings.of('fr').pages,
      'Pages',
      reason: 'unknown locales fall back',
    );

    final may = DateTime.utc(2026, 5, 27, 12);

    expect(AppStrings.of('en').date(may, 'en'), contains('2026'));
    expect(AppStrings.of('ar').date(may, 'ar'), isNot(contains('May')));
  });

  test('the excerpt is cut from the body in the language on screen', () {
    final subject = page(
      titleAr: 'من نحن',
      bodyAr: '<h2>لمحة عن تاريخنا</h2><p>بدأنا في عام 2014 بثلاثة أشخاص.</p>',
      translated: true,
    );

    // The heading is dropped, exactly as the server helper does it.
    expect(subject.excerptIn('ar'), 'بدأنا في عام 2014 بثلاثة أشخاص.');

    // English keeps whatever the API already computed.
    expect(subject.excerptIn('en'), 'We started in 2014.');
  });

  test('an untranslated page keeps the English excerpt', () {
    expect(page().excerptIn('ar'), 'We started in 2014.');
  });

  test('state labels translate but the colour key does not', () {
    expect(AppStrings.of('en').state('Draft'), 'Draft');
    expect(AppStrings.of('ar').state('Draft'), 'مسودة');
    expect(AppStrings.of('ar').state('Scheduled'), 'مجدولة');
  });
}

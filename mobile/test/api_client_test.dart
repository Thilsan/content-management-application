import 'dart:convert';

import 'package:cms_mobile/api/api_client.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

/// Responses copied from what the Laravel API actually returns, so a change to
/// the resource shape shows up here rather than as an empty screen.
void main() {
  ApiClient clientReturning(
    Object body, {
    int status = 200,
    void Function(http.Request request)? inspect,
  }) {
    return ApiClient(
      baseUrl: 'http://api.test',
      client: MockClient((request) async {
        inspect?.call(request);

        return http.Response(
          jsonEncode(body),
          status,
          headers: {'content-type': 'application/json'},
        );
      }),
    );
  }

  test('login returns the token and the privileges the user holds', () async {
    late http.Request seen;

    final api = clientReturning({
      'data': {
        'id': 2,
        'name': 'Content Moderator',
        'email': 'moderator@cms.test',
        'roles': [
          {'id': 2, 'name': 'Moderator', 'slug': 'moderator'},
        ],
        'privileges': [
          'menus.view',
          'pages.create',
          'pages.update',
          'pages.view',
        ],
      },
      'token': '4|abcdef',
    }, inspect: (request) => seen = request);

    final result = await api.login('moderator@cms.test', 'password');

    expect(result.token, '4|abcdef');
    expect(result.user.name, 'Content Moderator');
    expect(result.user.roles, ['Moderator']);
    expect(result.user.can('pages.update'), isTrue);
    expect(result.user.can('pages.delete'), isFalse);
    expect(seen.url.path, '/api/auth/login');
  });

  test('the token is sent as a bearer header once set', () async {
    late http.Request seen;

    final api = clientReturning({
      'data': [],
    }, inspect: (request) => seen = request);
    api.token = 'secret-token';

    await api.menus();

    expect(seen.headers['Authorization'], 'Bearer secret-token');
    expect(seen.headers['Accept'], 'application/json');
  });

  test('a 422 surfaces the field errors', () async {
    final api = clientReturning({
      'message': 'These credentials do not match our records.',
      'errors': {
        'email': ['These credentials do not match our records.'],
      },
    }, status: 422);

    expect(
      () => api.login('nobody@cms.test', 'wrong'),
      throwsA(
        isA<ApiException>()
            .having((error) => error.status, 'status', 422)
            .having(
              (error) => error.errors['email']?.first,
              'email error',
              isNotNull,
            ),
      ),
    );
  });

  test('a 403 from a privilege check is reported, not swallowed', () async {
    final api = clientReturning({
      'message': 'This action is unauthorized.',
    }, status: 403);

    expect(
      () => api.pages(),
      throwsA(
        isA<ApiException>().having((error) => error.status, 'status', 403),
      ),
    );
  });

  test('pages carry the state the back end reports', () async {
    final api = clientReturning({
      'data': [
        {
          'id': 1,
          'menu_id': 3,
          'title': 'Autumn Product Launch',
          'slug': 'autumn-product-launch',
          'excerpt': 'Launch announcement.',
          'status': 'published',
          'is_visible': false,
          'published_at': '2026-08-03T00:00:00.000000Z',
        },
        {
          'id': 2,
          'menu_id': 3,
          'title': 'Company Update',
          'slug': 'company-update',
          'excerpt': 'Where things stand.',
          'status': 'published',
          'is_visible': true,
          'published_at': '2026-07-22T00:00:00.000000Z',
        },
        {
          'id': 3,
          'menu_id': 2,
          'title': 'Internship Programme',
          'slug': 'internship-programme',
          'excerpt': 'Still being written.',
          'status': 'draft',
          'is_visible': false,
          'published_at': null,
        },
      ],
    });

    final pages = await api.pages();

    // Published but not yet due reads as scheduled, which is the distinction
    // the whole publishing feature turns on.
    expect(pages[0].state, 'Scheduled');
    expect(pages[1].state, 'Live');
    expect(pages[2].state, 'Draft');
    expect(pages[1].publishedAt?.year, 2026);
  });

  test('the menu tree flattens in the order the site presents it', () async {
    final api = clientReturning({
      'data': [
        {
          'id': 1,
          'title': 'About',
          'slug': 'about',
          'children': [
            {'id': 2, 'title': 'Our Team', 'slug': 'our-team', 'children': []},
            {
              'id': 3,
              'title': 'Careers',
              'slug': 'careers',
              'children': [
                {
                  'id': 4,
                  'title': 'Interns',
                  'slug': 'interns',
                  'children': [],
                },
              ],
            },
          ],
        },
      ],
    });

    final flattened = (await api.menus())
        .expand((node) => node.flatten())
        .toList();

    expect(flattened.map((entry) => entry.node.title), [
      'About',
      'Our Team',
      'Careers',
      'Interns',
    ]);
    expect(flattened.map((entry) => entry.depth), [0, 1, 1, 2]);
  });

  test('a search term reaches the query string', () async {
    late http.Request seen;

    final api = clientReturning({
      'data': [],
    }, inspect: (request) => seen = request);

    await api.pages(search: 'report');

    expect(seen.url.queryParameters['search'], 'report');
    expect(seen.url.queryParameters['per_page'], '100');
  });
}

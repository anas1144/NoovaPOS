import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api_client.dart';

/// App phases that drive which screen the root shows.
enum Phase { loading, setup, login, ready }

@immutable
class SessionState {
  final Phase phase;
  final String? baseUrl;
  final String? token;
  final Map<String, dynamic>? user;
  final Map<String, dynamic> features;
  final List<String> roles;
  final String? error;
  final bool busy;

  const SessionState({
    this.phase = Phase.loading,
    this.baseUrl,
    this.token,
    this.user,
    this.features = const {},
    this.roles = const [],
    this.error,
    this.busy = false,
  });

  SessionState copyWith({
    Phase? phase,
    String? baseUrl,
    String? token,
    Map<String, dynamic>? user,
    Map<String, dynamic>? features,
    List<String>? roles,
    String? error,
    bool? busy,
  }) {
    return SessionState(
      phase: phase ?? this.phase,
      baseUrl: baseUrl ?? this.baseUrl,
      token: token ?? this.token,
      user: user ?? this.user,
      features: features ?? this.features,
      roles: roles ?? this.roles,
      error: error,
      busy: busy ?? this.busy,
    );
  }

  bool hasFeature(String key) => features[key] == true;
  bool hasRole(String r) => roles.contains(r);
  ApiClient get api => ApiClient(baseUrl: baseUrl ?? '', token: token);
}

const _storage = FlutterSecureStorage();
const _kBaseUrl = 'base_url';
const _kToken = 'token';

final sessionProvider =
    StateNotifierProvider<SessionController, SessionState>((ref) {
  return SessionController()..init();
});

class SessionController extends StateNotifier<SessionState> {
  SessionController() : super(const SessionState());

  Future<void> init() async {
    final baseUrl = await _storage.read(key: _kBaseUrl);
    final token = await _storage.read(key: _kToken);
    if (baseUrl == null || baseUrl.isEmpty) {
      state = state.copyWith(phase: Phase.setup);
      return;
    }
    if (token == null || token.isEmpty) {
      state = state.copyWith(phase: Phase.login, baseUrl: baseUrl);
      return;
    }
    state = state.copyWith(phase: Phase.loading, baseUrl: baseUrl, token: token);
    await _loadMe();
  }

  Future<void> saveBaseUrl(String url) async {
    final clean = url.trim().replaceAll(RegExp(r'/$'), '');
    if (!RegExp(r'^https?://').hasMatch(clean)) {
      state = state.copyWith(error: 'Enter a full http(s) URL.');
      return;
    }
    await _storage.write(key: _kBaseUrl, value: clean);
    state = state.copyWith(phase: Phase.login, baseUrl: clean, error: null);
  }

  Future<void> login(String email, String password) async {
    state = state.copyWith(busy: true, error: null);
    try {
      final api = ApiClient(baseUrl: state.baseUrl ?? '');
      final res = await api.dio.post('/login', data: {
        'email': email,
        'password': password,
      });
      final data = ApiClient.data(res);
      final token = (data is Map ? data['token'] : null)?.toString();
      if (token == null || token.isEmpty) {
        throw Exception('No token returned.');
      }
      await _storage.write(key: _kToken, value: token);
      state = state.copyWith(token: token, busy: false);
      await _loadMe();
    } catch (e) {
      state = state.copyWith(busy: false, error: ApiClient.errorMessage(e));
    }
  }

  Future<void> _loadMe() async {
    try {
      final res = await state.api.dio.get('/v1/me');
      final data = ApiClient.data(res) as Map<String, dynamic>;
      final user = (data['user'] as Map?)?.cast<String, dynamic>();
      final features = (data['features'] as Map?)?.cast<String, dynamic>() ?? {};
      final roles = ((user?['roles'] as List?) ?? const [])
          .map((e) => e.toString())
          .toList();
      state = state.copyWith(
        phase: Phase.ready,
        user: user,
        features: features,
        roles: roles,
      );
    } catch (e) {
      // Token invalid/expired → back to login.
      await _storage.delete(key: _kToken);
      state = state.copyWith(phase: Phase.login, token: null, error: ApiClient.errorMessage(e));
    }
  }

  Future<void> logout() async {
    try {
      await state.api.dio.post('/logout');
    } catch (_) {}
    await _storage.delete(key: _kToken);
    state = state.copyWith(phase: Phase.login, token: null, user: null, features: {}, roles: []);
  }

  Future<void> changeServer() async {
    await _storage.delete(key: _kToken);
    await _storage.delete(key: _kBaseUrl);
    state = const SessionState(phase: Phase.setup);
  }
}

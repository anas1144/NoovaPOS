import 'dart:io' show Platform;

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api_client.dart';

/// FCM push registration. Registers the device token with the backend
/// (`/api/v1/devices`). All calls are guarded so the app runs fine even before
/// Firebase config files are added (it simply skips push then).
class PushService {
  static String get _platform => Platform.isIOS ? 'ios' : 'android';

  static Future<void> register(ApiClient api) async {
    try {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission();

      final token = await messaging.getToken();
      if (token != null && token.isNotEmpty) {
        await _send(api, token);
      }

      messaging.onTokenRefresh.listen((t) {
        _send(api, t).catchError((_) {});
      });

      // Foreground messages — surface lightly (a snackbar/notification UI can be
      // added later). For now just log in debug.
      FirebaseMessaging.onMessage.listen((m) {
        if (kDebugMode) {
          debugPrint('Push: ${m.notification?.title} — ${m.notification?.body}');
        }
      });
    } catch (_) {
      // Firebase not configured / unavailable — push is optional.
    }
  }

  static Future<void> _send(ApiClient api, String token) async {
    await api.dio.post('/v1/devices', data: {
      'token': token,
      'platform': _platform,
      'device_name': 'mobile',
    });
  }

  static Future<void> unregister(ApiClient api) async {
    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null) {
        await api.dio.delete('/v1/devices', data: {'token': token});
      }
    } catch (_) {}
  }
}

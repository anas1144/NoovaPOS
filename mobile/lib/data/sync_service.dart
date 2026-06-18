import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import '../core/api_client.dart';
import '../core/session.dart';
import 'local_db.dart';

final localDbProvider = Provider<LocalDb>((ref) => LocalDb());

final syncServiceProvider = Provider<SyncService>((ref) {
  return SyncService(ref.read(localDbProvider));
});

/// Pending-sale counter the UI can watch (refreshed after sale/sync).
final pendingCountProvider = StateProvider<int>((ref) => 0);

class SyncService {
  SyncService(this.db);

  final LocalDb db;
  final _storage = const FlutterSecureStorage();
  final _uuid = const Uuid();

  /// Download the offline first-load bundle into the local cache.
  Future<void> bootstrap(ApiClient api) async {
    final res = await api.dio.get('/v1/sync/bootstrap');
    final data = ApiClient.data(res) as Map;
    await db.replaceProducts((data['products'] as List?) ?? const []);
    await db.replaceCustomers((data['customers'] as List?) ?? const []);
  }

  /// Queue a sale locally (offline-safe) and return its local_uuid.
  Future<String> queueSale(Map<String, dynamic> sale) async {
    final localUuid = _uuid.v4();
    sale['local_uuid'] = localUuid;
    sale['created_at'] = DateTime.now().toIso8601String();
    await db.queueSale(localUuid, sale);
    return localUuid;
  }

  /// Upload all pending sales as one batch. Safe to call repeatedly
  /// (idempotent on the server via local_uuid).
  Future<Map<String, dynamic>> pushPending(ApiClient api) async {
    final pending = await db.pendingSales();
    if (pending.isEmpty) return {'ok': true, 'count': 0};

    final deviceUuid = await _ensureDevice(api);

    final items = pending
        .map((s) => {
              'local_uuid': s['local_uuid'],
              'entity_type': 'sale',
              'operation': 'create',
              'payload': s['payload'],
            })
        .toList();

    try {
      await api.dio.post('/offline-sync/batches', data: {
        'device_uuid': deviceUuid,
        'batch_uuid': _uuid.v4(),
        'items': items,
      });
      await db.markSynced(pending.map((s) => s['local_uuid'] as String).toList());
      return {'ok': true, 'count': items.length};
    } catch (e) {
      return {'ok': false, 'error': ApiClient.errorMessage(e)};
    }
  }

  /// Register this device once and remember its uuid.
  Future<String> _ensureDevice(ApiClient api) async {
    var deviceUuid = await _storage.read(key: 'device_uuid');
    if (deviceUuid != null && deviceUuid.isNotEmpty) return deviceUuid;

    deviceUuid = _uuid.v4();
    await api.dio.post('/offline-devices/register', data: {
      'device_uuid': deviceUuid,
      'name': 'Mobile device',
      'platform': 'mobile',
      'app_version': '1.0.0',
    });
    await _storage.write(key: 'device_uuid', value: deviceUuid);
    return deviceUuid;
  }
}

/// One-shot helper: refresh the pending counter.
Future<void> refreshPending(WidgetRef ref) async {
  final n = await ref.read(localDbProvider).pendingCount();
  ref.read(pendingCountProvider.notifier).state = n;
}

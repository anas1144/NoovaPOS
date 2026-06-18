import 'dart:convert';

import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// Local SQLite cache + offline sale queue (no codegen — plain sqflite).
class LocalDb {
  Database? _db;

  Future<Database> get db async => _db ??= await _open();

  Future<Database> _open() async {
    final dir = await getDatabasesPath();
    return openDatabase(
      p.join(dir, 'noovapos.db'),
      version: 1,
      onCreate: (d, _) async {
        await d.execute('''
          CREATE TABLE products(
            id INTEGER PRIMARY KEY, name TEXT, code TEXT, price REAL, cost REAL
          )''');
        await d.execute('''
          CREATE TABLE customers(
            id INTEGER PRIMARY KEY, name TEXT, phone TEXT, price_tier TEXT
          )''');
        await d.execute('''
          CREATE TABLE pending_sales(
            local_uuid TEXT PRIMARY KEY, payload TEXT, created_at TEXT, synced INTEGER DEFAULT 0
          )''');
      },
    );
  }

  // ── cache ──────────────────────────────────────────────────────────────────

  Future<void> replaceProducts(List products) async {
    final d = await db;
    final batch = d.batch();
    batch.delete('products');
    for (final r in products) {
      batch.insert('products', {
        'id': r['id'],
        'name': r['name'],
        'code': r['code'],
        'price': (r['price'] as num?)?.toDouble() ?? 0,
        'cost': (r['cost'] as num?)?.toDouble() ?? 0,
      });
    }
    await batch.commit(noResult: true);
  }

  Future<void> replaceCustomers(List customers) async {
    final d = await db;
    final batch = d.batch();
    batch.delete('customers');
    for (final r in customers) {
      batch.insert('customers', {
        'id': r['id'],
        'name': r['name'],
        'phone': r['phone'],
        'price_tier': r['price_tier'],
      });
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, Object?>>> searchProducts(String q, {int limit = 50}) async {
    final d = await db;
    if (q.trim().isEmpty) {
      return d.query('products', limit: limit, orderBy: 'name');
    }
    final like = '%${q.trim()}%';
    return d.query('products',
        where: 'name LIKE ? OR code LIKE ?', whereArgs: [like, like], limit: limit, orderBy: 'name');
  }

  Future<Map<String, Object?>?> productByCode(String code) async {
    final d = await db;
    final rows = await d.query('products', where: 'code = ?', whereArgs: [code], limit: 1);
    return rows.isEmpty ? null : rows.first;
  }

  Future<List<Map<String, Object?>>> customers() async {
    final d = await db;
    return d.query('customers', orderBy: 'name', limit: 1000);
  }

  Future<int> productCount() async {
    final d = await db;
    final r = await d.rawQuery('SELECT COUNT(*) c FROM products');
    return (r.first['c'] as int?) ?? 0;
  }

  // ── offline sale queue ──────────────────────────────────────────────────────

  Future<void> queueSale(String localUuid, Map<String, dynamic> payload) async {
    final d = await db;
    await d.insert('pending_sales', {
      'local_uuid': localUuid,
      'payload': jsonEncode(payload),
      'created_at': DateTime.now().toIso8601String(),
      'synced': 0,
    });
  }

  Future<List<Map<String, dynamic>>> pendingSales() async {
    final d = await db;
    final rows = await d.query('pending_sales', where: 'synced = 0', orderBy: 'created_at');
    return rows
        .map((r) => {
              'local_uuid': r['local_uuid'],
              'payload': jsonDecode(r['payload'] as String),
            })
        .toList();
  }

  Future<int> pendingCount() async {
    final d = await db;
    final r = await d.rawQuery('SELECT COUNT(*) c FROM pending_sales WHERE synced = 0');
    return (r.first['c'] as int?) ?? 0;
  }

  Future<void> markSynced(List<String> localUuids) async {
    if (localUuids.isEmpty) return;
    final d = await db;
    final placeholders = List.filled(localUuids.length, '?').join(',');
    await d.rawUpdate(
        'UPDATE pending_sales SET synced = 1 WHERE local_uuid IN ($placeholders)', localUuids);
  }
}

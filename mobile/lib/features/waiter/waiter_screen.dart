import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../data/sync_service.dart';
import '../pos/cart.dart';

/// Waiter: build an order from cached products and send it to the kitchen (KOT).
class WaiterScreen extends ConsumerStatefulWidget {
  const WaiterScreen({super.key});

  @override
  ConsumerState<WaiterScreen> createState() => _WaiterScreenState();
}

class _WaiterScreenState extends ConsumerState<WaiterScreen> {
  final _search = TextEditingController();
  List<Map<String, Object?>> _results = [];
  List _tables = [];
  String? _tableId;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final api = ref.read(sessionProvider).api;
    try {
      final res = await api.dio.get('/restaurant/tables');
      _tables = (ApiClient.data(res) as List?) ?? [];
    } catch (_) {}
    _results = await ref.read(localDbProvider).searchProducts('');
    if (mounted) setState(() {});
  }

  Future<void> _send() async {
    final lines = ref.read(cartProvider);
    if (lines.isEmpty) return;
    setState(() => _sending = true);
    try {
      await ref.read(sessionProvider).api.dio.post('/restaurant/kots', data: {
        'table_id': _tableId,
        'order_type': 'dine_in',
        'items': lines
            .map((l) => {'product_id': l.productId, 'product_name': l.name, 'quantity': l.qty})
            .toList(),
      });
      ref.read(cartProvider.notifier).clear();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Sent to kitchen.')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(ApiClient.errorMessage(e))));
      }
    }
    if (mounted) setState(() => _sending = false);
  }

  @override
  Widget build(BuildContext context) {
    final lines = ref.watch(cartProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Waiter')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _tableId,
                    decoration: const InputDecoration(labelText: 'Table', border: OutlineInputBorder(), isDense: true),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('— Takeaway —')),
                      ..._tables.map((t) => DropdownMenuItem(
                          value: '${t['id']}', child: Text('${t['name'] ?? t['id']}'))),
                    ],
                    onChanged: (v) => setState(() => _tableId = v),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: TextField(
              controller: _search,
              onChanged: (q) async {
                _results = await ref.read(localDbProvider).searchProducts(q);
                if (mounted) setState(() {});
              },
              decoration: const InputDecoration(
                  hintText: 'Search items…', prefixIcon: Icon(Icons.search), border: OutlineInputBorder(), isDense: true),
            ),
          ),
          Expanded(
            child: ListView.separated(
              itemCount: _results.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (_, i) {
                final p = _results[i];
                return ListTile(
                  title: Text((p['name'] ?? '') as String),
                  trailing: const Icon(Icons.add),
                  onTap: () => ref.read(cartProvider.notifier).add(p),
                );
              },
            ),
          ),
          if (lines.isNotEmpty)
            Material(
              elevation: 8,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    ...lines.asMap().entries.map((e) => Row(
                          children: [
                            Expanded(child: Text(e.value.name, overflow: TextOverflow.ellipsis)),
                            IconButton(icon: const Icon(Icons.remove), onPressed: () => ref.read(cartProvider.notifier).dec(e.key)),
                            Text('${e.value.qty}'),
                            IconButton(icon: const Icon(Icons.add), onPressed: () => ref.read(cartProvider.notifier).inc(e.key)),
                          ],
                        )),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _sending ? null : _send,
                        icon: const Icon(Icons.restaurant),
                        label: Text(_sending ? 'Sending…' : 'Send to Kitchen'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

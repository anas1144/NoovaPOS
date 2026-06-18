import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';

/// Delivery rider board: assigned deliveries with status updates.
class DeliveryScreen extends ConsumerStatefulWidget {
  const DeliveryScreen({super.key});

  @override
  ConsumerState<DeliveryScreen> createState() => _DeliveryScreenState();
}

class _DeliveryScreenState extends ConsumerState<DeliveryScreen> {
  List _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ref.read(sessionProvider).api.dio.get('/deliveries');
      final data = ApiClient.data(res);
      _items = data is Map && data['data'] is List ? data['data'] : (data is List ? data : []);
    } catch (_) {
      _items = [];
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _setStatus(dynamic id, String status) async {
    try {
      await ref.read(sessionProvider).api.dio.post('/deliveries/$id/status', data: {'status': status});
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(ApiClient.errorMessage(e))));
      }
    }
  }

  String _f(Map d, List<String> keys, [String fallback = '—']) {
    for (final k in keys) {
      final v = d[k];
      if (v != null && '$v'.isNotEmpty) return '$v';
    }
    return fallback;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Deliveries'),
        actions: [IconButton(icon: const Icon(Icons.refresh), onPressed: _load)],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _items.isEmpty
              ? const Center(child: Text('No deliveries assigned.'))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.separated(
                    itemCount: _items.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final d = (_items[i] as Map);
                      final id = d['id'];
                      final status = _f(d, ['delivery_status', 'status'], 'pending');
                      return ListTile(
                        title: Text(_f(d, ['reference_code', 'invoice_no', 'reference'], 'Sale #$id')),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(_f(d, ['customer_name', 'customer'], 'Customer')),
                            Text(_f(d, ['address', 'delivery_address'], '')),
                            Chip(label: Text(status), visualDensity: VisualDensity.compact),
                          ],
                        ),
                        isThreeLine: true,
                        trailing: PopupMenuButton<String>(
                          onSelected: (s) => _setStatus(id, s),
                          itemBuilder: (_) => const [
                            PopupMenuItem(value: 'out_for_delivery', child: Text('Out for delivery')),
                            PopupMenuItem(value: 'delivered', child: Text('Delivered')),
                            PopupMenuItem(value: 'failed', child: Text('Failed')),
                          ],
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}

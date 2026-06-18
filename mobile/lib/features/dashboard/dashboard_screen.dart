import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';

/// Manager dashboard — today's + overall sale/purchase counts.
class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Map<String, dynamic> _today = {};
  Map<String, dynamic> _all = {};
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final api = ref.read(sessionProvider).api;
    Future<Map<String, dynamic>> get(String path) async {
      try {
        final res = await api.dio.get(path);
        final d = ApiClient.data(res);
        return d is Map ? d.cast<String, dynamic>() : {};
      } catch (_) {
        return {};
      }
    }

    _today = await get('/today-sales-purchases-count');
    _all = await get('/all-sales-purchases-count');
    if (mounted) setState(() => _loading = false);
  }

  String _label(String k) => k
      .replaceAll('_', ' ')
      .replaceAll(RegExp(r'([a-z])([A-Z])'), r'$1 $2')
      .trim();

  List<Widget> _cards(Map<String, dynamic> m) {
    final entries = m.entries.where((e) => e.value is num || num.tryParse('${e.value}') != null);
    return entries
        .map((e) => Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(_label(e.key), style: const TextStyle(color: Colors.black54, fontSize: 12)),
                    const SizedBox(height: 4),
                    Text('${e.value}', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [IconButton(icon: const Icon(Icons.refresh), onPressed: _load)],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const Text('Today', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                GridView.count(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisCount: 2,
                  childAspectRatio: 1.8,
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  children: _cards(_today),
                ),
                const SizedBox(height: 20),
                const Text('Overall', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                GridView.count(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisCount: 2,
                  childAspectRatio: 1.8,
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  children: _cards(_all),
                ),
              ],
            ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/printing.dart';
import '../../core/session.dart';
import '../../data/sync_service.dart';
import 'cart.dart';
import 'scan_screen.dart';

class PosScreen extends ConsumerStatefulWidget {
  const PosScreen({super.key});

  @override
  ConsumerState<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends ConsumerState<PosScreen> {
  final _search = TextEditingController();
  List<Map<String, Object?>> _results = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _init() async {
    final db = ref.read(localDbProvider);
    final sync = ref.read(syncServiceProvider);
    final api = ref.read(sessionProvider).api;

    // First run / empty cache → download bootstrap when online.
    try {
      if (await db.productCount() == 0) await sync.bootstrap(api);
    } catch (_) {/* offline — use whatever is cached */}

    await _query('');
    await refreshPending(ref);
    // Best-effort flush of anything pending from a previous offline session.
    sync.pushPending(api).then((_) => refreshPending(ref));
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _query(String q) async {
    final rows = await ref.read(localDbProvider).searchProducts(q);
    if (mounted) setState(() => _results = rows);
  }

  Future<void> _scan() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const ScanScreen()),
    );
    if (code == null) return;
    final product = await ref.read(localDbProvider).productByCode(code);
    if (!mounted) return;
    if (product != null) {
      ref.read(cartProvider.notifier).add(product);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('No product for code $code')));
    }
  }

  Future<void> _pickPrinter() async {
    final printers = await ReceiptPrinter.pairedPrinters();
    if (!mounted) return;
    if (printers.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No paired Bluetooth printers found. Pair one in system settings.')),
      );
      return;
    }
    await showDialog(
      context: context,
      builder: (_) => SimpleDialog(
        title: const Text('Select receipt printer'),
        children: printers
            .map((p) => SimpleDialogOption(
                  child: Text('${p.name}  (${p.macAdress})'),
                  onPressed: () async {
                    await ReceiptPrinter.saveMac(p.macAdress);
                    if (mounted) Navigator.of(context).pop();
                  },
                ))
            .toList(),
      ),
    );
  }

  Future<void> _charge() async {
    final cart = ref.read(cartProvider.notifier);
    final lines = ref.read(cartProvider);
    if (lines.isEmpty) return;

    final sale = {
      'customer_id': null,
      'payment_type': 'cash',
      'items': lines.map((l) => l.toJson()).toList(),
      'subtotal': cart.total,
      'total': cart.total,
    };

    final sync = ref.read(syncServiceProvider);
    final api = ref.read(sessionProvider).api;

    // Keep a copy for the receipt before clearing the cart.
    final receiptLines = List<SaleLine>.from(lines);
    final receiptTotal = cart.total;

    await sync.queueSale(sale);   // saved locally first (offline-safe)
    cart.clear();
    final res = await sync.pushPending(api); // try to upload now
    await refreshPending(ref);

    // Best-effort Bluetooth receipt (no-op if no printer is selected).
    ReceiptPrinter.printSale(
      storeName: 'NoovaPOS',
      lines: receiptLines,
      total: receiptTotal,
    );

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(res['ok'] == true && res['count'] != 0
          ? 'Sale completed & synced.'
          : 'Sale saved offline — will sync automatically.'),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final lines = ref.watch(cartProvider);
    final total = ref.watch(cartProvider.notifier).total;
    final pending = ref.watch(pendingCountProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('POS'),
        actions: [
          if (pending > 0)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: Center(child: Chip(label: Text('$pending to sync'))),
            ),
          IconButton(icon: const Icon(Icons.qr_code_scanner), tooltip: 'Scan', onPressed: _scan),
          IconButton(icon: const Icon(Icons.print), tooltip: 'Receipt printer', onPressed: _pickPrinter),
          IconButton(
            icon: const Icon(Icons.sync),
            onPressed: () async {
              final res = await ref.read(syncServiceProvider).pushPending(ref.read(sessionProvider).api);
              await refreshPending(ref);
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                  content: Text(res['ok'] == true ? 'Synced ${res['count']} sale(s).' : 'Sync failed: ${res['error']}'),
                ));
              }
            },
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(12),
                  child: TextField(
                    controller: _search,
                    onChanged: _query,
                    decoration: const InputDecoration(
                      hintText: 'Search products…',
                      prefixIcon: Icon(Icons.search),
                      border: OutlineInputBorder(),
                      isDense: true,
                    ),
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
                        subtitle: Text((p['code'] ?? '') as String? ?? ''),
                        trailing: Text(((p['price'] as num?) ?? 0).toStringAsFixed(2)),
                        onTap: () => ref.read(cartProvider.notifier).add(p),
                      );
                    },
                  ),
                ),
                if (lines.isNotEmpty) _CartPanel(onCharge: _charge),
              ],
            ),
    );
  }
}

class _CartPanel extends ConsumerWidget {
  final VoidCallback onCharge;
  const _CartPanel({required this.onCharge});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lines = ref.watch(cartProvider);
    final cart = ref.read(cartProvider.notifier);
    final total = cart.total;

    return Material(
      elevation: 8,
      child: Container(
        constraints: const BoxConstraints(maxHeight: 300),
        padding: const EdgeInsets.all(12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Flexible(
              child: ListView.builder(
                shrinkWrap: true,
                itemCount: lines.length,
                itemBuilder: (_, i) {
                  final l = lines[i];
                  return Row(
                    children: [
                      Expanded(child: Text(l.name, overflow: TextOverflow.ellipsis)),
                      IconButton(icon: const Icon(Icons.remove_circle_outline), onPressed: () => cart.dec(i)),
                      Text('${l.qty}'),
                      IconButton(icon: const Icon(Icons.add_circle_outline), onPressed: () => cart.inc(i)),
                      SizedBox(width: 70, child: Text(l.amount.toStringAsFixed(2), textAlign: TextAlign.right)),
                    ],
                  );
                },
              ),
            ),
            const Divider(),
            Row(
              children: [
                const Text('Total', style: TextStyle(fontWeight: FontWeight.bold)),
                const Spacer(),
                Text(total.toStringAsFixed(2), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
              ],
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: onCharge,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Text('Charge ${total.toStringAsFixed(2)}'),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

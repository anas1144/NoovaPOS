import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../pos/scan_screen.dart';

/// Self attendance: check in/out, breaks, and barcode/QR scan check-in.
class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  Map<String, dynamic>? _self;
  bool _loading = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final res = await ref.read(sessionProvider).api.dio.get('/attendance/status');
      final data = ApiClient.data(res) as Map;
      _self = (data['self'] as Map?)?.cast<String, dynamic>();
    } catch (_) {
      _self = null;
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _post(String path, Map<String, dynamic> body) async {
    setState(() => _busy = true);
    try {
      await ref.read(sessionProvider).api.dio.post(path, data: body);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(ApiClient.errorMessage(e))));
      }
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _scanCheckIn() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const ScanScreen()),
    );
    if (code == null) return;
    await _post('/attendance/check-in', {'method': 'barcode', 'code': code});
  }

  String _hms(num? s) {
    final t = (s ?? 0).toInt();
    final h = (t ~/ 3600).toString().padLeft(2, '0');
    final m = ((t % 3600) ~/ 60).toString().padLeft(2, '0');
    final ss = (t % 60).toString().padLeft(2, '0');
    return '$h:$m:$ss';
  }

  @override
  Widget build(BuildContext context) {
    final s = _self;
    final checkedIn = s?['checked_in'] == true;
    final checkedOut = s?['checked_out'] == true;
    final onBreak = s?['on_break'] == true;

    return Scaffold(
      appBar: AppBar(title: const Text('Attendance')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        children: [
                          Text(
                            checkedOut
                                ? 'Checked out for today'
                                : checkedIn
                                    ? (onBreak ? 'On break' : 'Working')
                                    : 'Not checked in',
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          if (checkedIn) Text('Worked: ${_hms(s?['working_seconds'] as num?)}'),
                          if (s?['active_task'] != null)
                            Text('Task: ${(s?['active_task'] as Map?)?['name'] ?? ''}'),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (!checkedIn && !checkedOut) ...[
                    FilledButton.icon(
                      onPressed: _busy ? null : () => _post('/attendance/check-in', {'method': 'manual'}),
                      icon: const Icon(Icons.login),
                      label: const Text('Check In'),
                    ),
                    const SizedBox(height: 10),
                    OutlinedButton.icon(
                      onPressed: _busy ? null : _scanCheckIn,
                      icon: const Icon(Icons.qr_code_scanner),
                      label: const Text('Scan to check in'),
                    ),
                  ],
                  if (checkedIn) ...[
                    if (!onBreak)
                      FilledButton.tonalIcon(
                        onPressed: _busy ? null : () => _post('/attendance/break/start', {}),
                        icon: const Icon(Icons.free_breakfast),
                        label: const Text('Start Break'),
                      ),
                    if (onBreak)
                      FilledButton.tonalIcon(
                        onPressed: _busy ? null : () => _post('/attendance/break/end', {}),
                        icon: const Icon(Icons.play_arrow),
                        label: const Text('End Break'),
                      ),
                    const SizedBox(height: 10),
                    FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: Colors.red),
                      onPressed: _busy ? null : () => _post('/attendance/check-out', {'method': 'manual'}),
                      icon: const Icon(Icons.logout),
                      label: const Text('Check Out'),
                    ),
                  ],
                ],
              ),
            ),
    );
  }
}

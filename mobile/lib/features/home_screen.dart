import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/push.dart';
import '../core/session.dart';
import 'pos/pos_screen.dart';
import 'waiter/waiter_screen.dart';
import 'delivery/delivery_screen.dart';
import 'attendance/attendance_screen.dart';
import 'dashboard/dashboard_screen.dart';

/// Role-aware home — lists the modules available to the signed-in role and
/// registers the device for push on first display.
class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  void initState() {
    super.initState();
    // Register for push once we're signed in (no-op if Firebase isn't set up).
    WidgetsBinding.instance.addPostFrameCallback((_) {
      PushService.register(ref.read(sessionProvider).api);
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = ref.watch(sessionProvider);
    final name = (s.user?['name'] as String?)?.trim();

    final tiles = <_Module>[
      if (_any(s, ['tenant_owner', 'admin', 'branch_manager', 'shop_manager', 'cashier']))
        const _Module('POS', Icons.point_of_sale, Color(0xFF6366F1)),
      if (s.hasRole('waiter') || s.hasFeature('kitchen'))
        const _Module('Waiter / KOT', Icons.restaurant, Color(0xFFF59E0B)),
      if (_any(s, ['delivery_staff', 'delivery_boy']))
        const _Module('Deliveries', Icons.local_shipping, Color(0xFF0EA5E9)),
      const _Module('Attendance', Icons.fingerprint, Color(0xFF22C55E)),
      if (_any(s, ['tenant_owner', 'admin', 'branch_manager', 'shop_manager']))
        const _Module('Dashboard', Icons.insights, Color(0xFF8B5CF6)),
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('NoovaPOS'),
        actions: [
          PopupMenuButton<String>(
            onSelected: (v) {
              if (v == 'logout') ref.read(sessionProvider.notifier).logout();
              if (v == 'server') ref.read(sessionProvider.notifier).changeServer();
            },
            itemBuilder: (_) => const [
              PopupMenuItem(value: 'server', child: Text('Change server')),
              PopupMenuItem(value: 'logout', child: Text('Sign out')),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text('Welcome${name != null && name.isNotEmpty ? ', $name' : ''}',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
            ),
          ),
          Expanded(
            child: GridView.count(
              padding: const EdgeInsets.all(16),
              crossAxisCount: 2,
              mainAxisSpacing: 14,
              crossAxisSpacing: 14,
              childAspectRatio: 1.1,
              children: tiles.map((m) => _Card(module: m)).toList(),
            ),
          ),
        ],
      ),
    );
  }

  static bool _any(SessionState s, List<String> roles) => roles.any(s.hasRole);
}

class _Module {
  final String label;
  final IconData icon;
  final Color color;
  const _Module(this.label, this.icon, this.color);
}

class _Card extends StatelessWidget {
  final _Module module;
  const _Card({required this.module});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () {
        final Widget? screen = switch (module.label) {
          'POS' => const PosScreen(),
          'Waiter / KOT' => const WaiterScreen(),
          'Deliveries' => const DeliveryScreen(),
          'Attendance' => const AttendanceScreen(),
          'Dashboard' => const DashboardScreen(),
          _ => null,
        };
        if (screen != null) {
          Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));
        }
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [BoxShadow(color: Color(0x14000000), blurRadius: 10, offset: Offset(0, 4))],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircleAvatar(radius: 28, backgroundColor: module.color.withOpacity(0.12), child: Icon(module.icon, color: module.color, size: 28)),
            const SizedBox(height: 12),
            Text(module.label, style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/session.dart';
import 'features/setup_screen.dart';
import 'features/login_screen.dart';
import 'features/home_screen.dart';

class NoovaPosApp extends StatelessWidget {
  const NoovaPosApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'NoovaPOS',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: const Color(0xFF6366F1),
        scaffoldBackgroundColor: const Color(0xFFF1F5F9),
      ),
      home: const _Root(),
    );
  }
}

class _Root extends ConsumerWidget {
  const _Root();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final phase = ref.watch(sessionProvider).phase;
    switch (phase) {
      case Phase.loading:
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      case Phase.setup:
        return const SetupScreen();
      case Phase.login:
        return const LoginScreen();
      case Phase.ready:
        return const HomeScreen();
    }
  }
}

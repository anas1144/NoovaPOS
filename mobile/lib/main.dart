import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase is optional — if config files aren't present yet, push is skipped.
  try {
    await Firebase.initializeApp();
  } catch (_) {}
  runApp(const ProviderScope(child: NoovaPosApp()));
}

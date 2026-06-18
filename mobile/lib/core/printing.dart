import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';

import '../features/pos/cart.dart';

/// Bluetooth thermal (ESC/POS) receipt printing.
///
/// Lists paired printers, remembers the chosen one (MAC), and prints a sale.
class ReceiptPrinter {
  static const _storage = FlutterSecureStorage();
  static const _kMac = 'bt_printer_mac';

  static Future<List<BluetoothInfo>> pairedPrinters() async {
    try {
      return await PrintBluetoothThermal.pairedBluetooths;
    } catch (_) {
      return [];
    }
  }

  static Future<String?> savedMac() => _storage.read(key: _kMac);
  static Future<void> saveMac(String mac) => _storage.write(key: _kMac, value: mac);

  /// Print a sale receipt. Returns null on success, else an error string.
  static Future<String?> printSale({
    required String storeName,
    String? address,
    required List<SaleLine> lines,
    required double total,
    String? footer,
  }) async {
    final mac = await savedMac();
    if (mac == null || mac.isEmpty) return 'No printer selected.';

    try {
      final connected = await PrintBluetoothThermal.connectionStatus;
      if (!connected) {
        final ok = await PrintBluetoothThermal.connect(macPrinterAddress: mac);
        if (!ok) return 'Could not connect to the printer.';
      }

      final profile = await CapabilityProfile.load();
      final g = Generator(PaperSize.mm58, profile);
      List<int> bytes = [];

      bytes += g.text(storeName,
          styles: const PosStyles(align: PosAlign.center, bold: true, height: PosTextSize.size2));
      if (address != null && address.isNotEmpty) {
        bytes += g.text(address, styles: const PosStyles(align: PosAlign.center));
      }
      bytes += g.text(DateTime.now().toString().substring(0, 19),
          styles: const PosStyles(align: PosAlign.center));
      bytes += g.hr();

      for (final l in lines) {
        bytes += g.row([
          PosColumn(text: l.name, width: 7),
          PosColumn(text: '${l.qty}x', width: 2, styles: const PosStyles(align: PosAlign.center)),
          PosColumn(text: l.amount.toStringAsFixed(2), width: 3, styles: const PosStyles(align: PosAlign.right)),
        ]);
      }
      bytes += g.hr();
      bytes += g.row([
        PosColumn(text: 'TOTAL', width: 8, styles: const PosStyles(bold: true)),
        PosColumn(text: total.toStringAsFixed(2), width: 4, styles: const PosStyles(bold: true, align: PosAlign.right)),
      ]);
      bytes += g.feed(1);
      bytes += g.text(footer ?? 'Thank you!', styles: const PosStyles(align: PosAlign.center));
      bytes += g.cut();

      final ok = await PrintBluetoothThermal.writeBytes(bytes);
      return ok ? null : 'Print failed.';
    } catch (e) {
      return e.toString();
    }
  }
}

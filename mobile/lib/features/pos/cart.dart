import 'package:flutter_riverpod/flutter_riverpod.dart';

class SaleLine {
  final int productId;
  final String name;
  final double price;
  final int qty;

  const SaleLine({required this.productId, required this.name, required this.price, this.qty = 1});

  double get amount => price * qty;

  SaleLine copyWith({int? qty}) =>
      SaleLine(productId: productId, name: name, price: price, qty: qty ?? this.qty);

  Map<String, dynamic> toJson() => {
        'product_id': productId,
        'name': name,
        'price': price,
        'quantity': qty,
        'amount': amount,
      };
}

final cartProvider = StateNotifierProvider<CartController, List<SaleLine>>((ref) {
  return CartController();
});

class CartController extends StateNotifier<List<SaleLine>> {
  CartController() : super(const []);

  void add(Map<String, Object?> product) {
    final id = product['id'] as int;
    final idx = state.indexWhere((l) => l.productId == id);
    if (idx >= 0) {
      inc(idx);
      return;
    }
    state = [
      ...state,
      SaleLine(
        productId: id,
        name: (product['name'] ?? '') as String,
        price: ((product['price'] as num?) ?? 0).toDouble(),
      ),
    ];
  }

  void inc(int i) => _update(i, state[i].qty + 1);
  void dec(int i) => _update(i, state[i].qty - 1);

  void _update(int i, int qty) {
    if (qty <= 0) {
      state = [...state]..removeAt(i);
      return;
    }
    final copy = [...state];
    copy[i] = copy[i].copyWith(qty: qty);
    state = copy;
  }

  void removeAt(int i) => state = [...state]..removeAt(i);
  void clear() => state = const [];

  double get total => state.fold(0.0, (s, l) => s + l.amount);
}

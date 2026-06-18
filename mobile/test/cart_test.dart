import 'package:flutter_test/flutter_test.dart';
import 'package:noovapos_mobile/features/pos/cart.dart';

void main() {
  group('CartController', () {
    test('adding the same product twice increments quantity', () {
      final c = CartController();
      c.add({'id': 1, 'name': 'A', 'price': 100.0});
      c.add({'id': 1, 'name': 'A', 'price': 100.0});
      expect(c.state, hasLength(1));
      expect(c.state.first.qty, 2);
      expect(c.total, 200.0);
    });

    test('decrement to zero removes the line', () {
      final c = CartController();
      c.add({'id': 1, 'name': 'A', 'price': 50.0});
      c.dec(0); // qty 1 -> 0 removes
      expect(c.state, isEmpty);
      expect(c.total, 0.0);
    });

    test('total sums multiple lines', () {
      final c = CartController();
      c.add({'id': 1, 'name': 'A', 'price': 100.0});
      c.add({'id': 2, 'name': 'B', 'price': 25.0});
      c.inc(1); // B -> qty 2
      expect(c.total, 150.0);
    });
  });
}

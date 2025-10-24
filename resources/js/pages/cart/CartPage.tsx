import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Trash2, Plus, Minus, ShoppingBag } from 'lucide-react';
import { useCart } from '../../contexts/CartContext';
import { formatCurrency, generateCartWhatsAppMessage, generateWhatsAppLink } from '../../utils/helpers';
import Layout from '../../components/layout/Layout';

const CartPage = () => {
  const { cart, removeFromCart, updateQuantity, clearCart } = useCart();

  if (cart.items.length === 0) {
    return (
      <div className="container-custom py-16">
        <div className="max-w-2xl mx-auto text-center">
          <ShoppingBag className="w-24 h-24 mx-auto text-gray-300 mb-4" />
          <h2 className="text-3xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
          <p className="text-gray-600 mb-8">Add some textbooks to get started!</p>
          <Link href="/shop" className="btn-primary inline-block">
            Browse Books
          </Link>
        </div>
      </div>
    );
  }

  const whatsappMessage = generateCartWhatsAppMessage(
    cart.items,
    cart.subtotal_zwl,
    cart.subtotal_usd
  );
  const whatsappLink = generateWhatsAppLink(whatsappMessage);

  return (
    <div className="container-custom py-8">
      <h1 className="text-3xl font-bold mb-8">Shopping Cart</h1>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <div className="space-y-4">
            {cart.items.map((item) => (
              <div key={item.product.id} className="card p-4">
                <div className="flex gap-4">
                  <img
                    src={item.product.cover_image}
                    alt={item.product.title}
                    className="w-24 h-32 object-cover rounded"
                  />

                  <div className="flex-grow">
                    <h3 className="font-semibold text-lg mb-1">{item.product.title}</h3>
                    <p className="text-sm text-gray-600 mb-2">
                      {item.product.syllabus} | {item.product.level}
                    </p>
                    <div className="flex items-center justify-between">
                      <div className="text-primary-700 font-semibold">
                        {formatCurrency(item.product.price_zwl, 'ZWL')} /{' '}
                        {formatCurrency(item.product.price_usd, 'USD')}
                      </div>

                      <div className="flex items-center gap-3">
                        <div className="flex items-center border border-gray-300 rounded-lg">
                          <button
                            onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
                            className="p-2 hover:bg-gray-100 transition-colors"
                          >
                            <Minus className="w-4 h-4" />
                          </button>
                          <span className="px-4 py-2 font-medium">{item.quantity}</span>
                          <button
                            onClick={() => updateQuantity(item.product.id, item.quantity + 1)}
                            className="p-2 hover:bg-gray-100 transition-colors"
                          >
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>

                        <button
                          onClick={() => removeFromCart(item.product.id)}
                          className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <button
            onClick={clearCart}
            className="mt-4 text-red-600 hover:text-red-700 font-medium"
          >
            Clear Cart
          </button>
        </div>

        <div className="lg:col-span-1">
          <div className="card p-6 sticky top-24">
            <h2 className="text-xl font-bold mb-4">Order Summary</h2>

            <div className="space-y-3 mb-6">
              <div className="flex justify-between">
                <span className="text-gray-600">Subtotal (ZWL)</span>
                <span className="font-semibold">{formatCurrency(cart.subtotal_zwl, 'ZWL')}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Subtotal (USD)</span>
                <span className="font-semibold">{formatCurrency(cart.subtotal_usd, 'USD')}</span>
              </div>
              <div className="border-t pt-3">
                <p className="text-sm text-gray-600">
                  Delivery charges will be confirmed after placing your order
                </p>
              </div>
            </div>

            <div className="space-y-3">
              <Link href="/checkout" className="btn-primary w-full text-center block">
                Submit Inquiry
              </Link>

              <a
                href={whatsappLink}
                target="_blank"
                rel="noopener noreferrer"
                className="btn-whatsapp w-full text-center block"
              >
                Order via WhatsApp
              </a>

              <Link href="/shop" className="btn-secondary w-full text-center block">
                Continue Shopping
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

CartPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default CartPage;

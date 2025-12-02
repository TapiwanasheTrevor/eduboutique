import { useState, type ReactNode } from 'react';
import { Link, router } from '@inertiajs/react';
import { ShoppingBag, MapPin, Truck, Store, Send } from 'lucide-react';
import { useCart } from '../../contexts/CartContext';
import { formatCurrency } from '../../utils/helpers';
import Layout from '../../components/layout/Layout';

const CheckoutPage = () => {
  const { cart, clearCart } = useCart();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitStatus, setSubmitStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState('');

  const [formData, setFormData] = useState({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    delivery_method: 'store_pickup',
    delivery_address: '',
    delivery_city: '',
    message: '',
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitStatus('idle');
    setErrorMessage('');

    try {
      const inquiryData = {
        ...formData,
        cart_items: cart.items.map(item => ({
          product_id: item.product.id,
          odoo_product_id: item.product.odoo_product_id || null,
          title: item.product.title,
          isbn: item.product.isbn || '',
          sku: item.product.sku || '',
          author: item.product.author || '',
          cover_image: item.product.cover_image || '',
          quantity: item.quantity,
          price_zwl: item.product.price_zwl,
          price_usd: item.product.price_usd,
        })),
        total_zwl: cart.subtotal_zwl,
        total_usd: cart.subtotal_usd,
      };

      const response = await fetch('/api/v1/inquiries', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(inquiryData),
      });

      const data = await response.json();

      if (response.ok) {
        setSubmitStatus('success');
        clearCart();
      } else {
        setSubmitStatus('error');
        setErrorMessage(data.message || 'Failed to submit inquiry');
      }
    } catch (error) {
      console.error('Checkout submission failed:', error);
      setSubmitStatus('error');
      setErrorMessage('Network error. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (cart.items.length === 0 && submitStatus !== 'success') {
    return (
      <div className="container-custom py-16">
        <div className="max-w-2xl mx-auto text-center">
          <ShoppingBag className="w-24 h-24 mx-auto text-gray-300 mb-4" />
          <h2 className="text-3xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
          <p className="text-gray-600 mb-8">Add some textbooks before checking out!</p>
          <Link href="/shop" className="btn-primary inline-block">
            Browse Books
          </Link>
        </div>
      </div>
    );
  }

  if (submitStatus === 'success') {
    return (
      <div className="container-custom py-16">
        <div className="max-w-2xl mx-auto text-center">
          <div className="bg-green-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
            <Send className="w-12 h-12 text-green-600" />
          </div>
          <h2 className="text-3xl font-bold text-gray-900 mb-4">Inquiry Submitted!</h2>
          <p className="text-gray-600 mb-8">
            Thank you for your inquiry. We'll contact you shortly via WhatsApp or email
            to confirm your order and arrange delivery.
          </p>
          <div className="space-x-4">
            <Link href="/shop" className="btn-primary inline-block">
              Continue Shopping
            </Link>
            <Link href="/" className="btn-secondary inline-block">
              Back to Home
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container-custom py-8">
      <h1 className="text-3xl font-bold mb-8">Submit Inquiry</h1>

      {submitStatus === 'error' && (
        <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
          {errorMessage || 'Something went wrong. Please try again.'}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Customer Details */}
          <div className="lg:col-span-2 space-y-6">
            <div className="card p-6">
              <h2 className="text-xl font-bold mb-4">Your Details</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label htmlFor="customer_name" className="block text-sm font-medium text-gray-700 mb-2">
                    Full Name *
                  </label>
                  <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    required
                    value={formData.customer_name}
                    onChange={handleChange}
                    className="input-field"
                    placeholder="Your full name"
                  />
                </div>

                <div>
                  <label htmlFor="customer_phone" className="block text-sm font-medium text-gray-700 mb-2">
                    Phone / WhatsApp *
                  </label>
                  <input
                    type="tel"
                    id="customer_phone"
                    name="customer_phone"
                    required
                    value={formData.customer_phone}
                    onChange={handleChange}
                    className="input-field"
                    placeholder="+263..."
                  />
                </div>

                <div className="md:col-span-2">
                  <label htmlFor="customer_email" className="block text-sm font-medium text-gray-700 mb-2">
                    Email Address *
                  </label>
                  <input
                    type="email"
                    id="customer_email"
                    name="customer_email"
                    required
                    value={formData.customer_email}
                    onChange={handleChange}
                    className="input-field"
                    placeholder="your@email.com"
                  />
                </div>
              </div>
            </div>

            <div className="card p-6">
              <h2 className="text-xl font-bold mb-4">Delivery Method</h2>
              <div className="space-y-4">
                <label className="flex items-start gap-4 p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                  <input
                    type="radio"
                    name="delivery_method"
                    value="store_pickup"
                    checked={formData.delivery_method === 'store_pickup'}
                    onChange={handleChange}
                    className="mt-1"
                  />
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <Store className="w-5 h-5 text-primary-600" />
                      <span className="font-medium">Store Pickup</span>
                    </div>
                    <p className="text-sm text-gray-600 mt-1">
                      Pick up your order from our store location
                    </p>
                  </div>
                </label>

                <label className="flex items-start gap-4 p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                  <input
                    type="radio"
                    name="delivery_method"
                    value="agent_delivery"
                    checked={formData.delivery_method === 'agent_delivery'}
                    onChange={handleChange}
                    className="mt-1"
                  />
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <Truck className="w-5 h-5 text-primary-600" />
                      <span className="font-medium">Home Delivery</span>
                    </div>
                    <p className="text-sm text-gray-600 mt-1">
                      Delivery via our agent network (charges apply)
                    </p>
                  </div>
                </label>
              </div>

              {formData.delivery_method === 'agent_delivery' && (
                <div className="mt-4 space-y-4">
                  <div>
                    <label htmlFor="delivery_city" className="block text-sm font-medium text-gray-700 mb-2">
                      City / Town *
                    </label>
                    <input
                      type="text"
                      id="delivery_city"
                      name="delivery_city"
                      required={formData.delivery_method === 'agent_delivery'}
                      value={formData.delivery_city}
                      onChange={handleChange}
                      className="input-field"
                      placeholder="e.g., Harare, Bulawayo, Mutare"
                    />
                  </div>

                  <div>
                    <label htmlFor="delivery_address" className="block text-sm font-medium text-gray-700 mb-2">
                      Delivery Address *
                    </label>
                    <textarea
                      id="delivery_address"
                      name="delivery_address"
                      required={formData.delivery_method === 'agent_delivery'}
                      value={formData.delivery_address}
                      onChange={handleChange}
                      rows={3}
                      className="input-field"
                      placeholder="Street address, suburb, landmarks..."
                    />
                  </div>
                </div>
              )}
            </div>

            <div className="card p-6">
              <h2 className="text-xl font-bold mb-4">Additional Notes</h2>
              <textarea
                id="message"
                name="message"
                value={formData.message}
                onChange={handleChange}
                rows={3}
                className="input-field"
                placeholder="Any special requests or questions..."
              />
            </div>
          </div>

          {/* Order Summary */}
          <div className="lg:col-span-1">
            <div className="card p-6 sticky top-24">
              <h2 className="text-xl font-bold mb-4">Order Summary</h2>

              <div className="space-y-3 mb-4">
                {cart.items.map((item) => (
                  <div key={item.product.id} className="flex gap-3">
                    <img
                      src={item.product.cover_image}
                      alt={item.product.title}
                      className="w-12 h-16 object-cover rounded"
                    />
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium truncate">{item.product.title}</p>
                      <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                      <p className="text-sm text-primary-600">
                        {formatCurrency(item.product.price_usd * item.quantity, 'USD')}
                      </p>
                    </div>
                  </div>
                ))}
              </div>

              <div className="border-t pt-4 space-y-2">
                <div className="flex justify-between">
                  <span className="text-gray-600">Subtotal (ZWL)</span>
                  <span className="font-semibold">{formatCurrency(cart.subtotal_zwl, 'ZWL')}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Subtotal (USD)</span>
                  <span className="font-semibold">{formatCurrency(cart.subtotal_usd, 'USD')}</span>
                </div>
                <p className="text-xs text-gray-500 mt-2">
                  * Delivery charges will be confirmed after review
                </p>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="btn-primary w-full mt-6 flex items-center justify-center gap-2"
              >
                {isSubmitting ? (
                  'Submitting...'
                ) : (
                  <>
                    <Send className="w-5 h-5" />
                    Submit Inquiry
                  </>
                )}
              </button>

              <Link href="/cart" className="btn-secondary w-full mt-3 text-center block">
                Back to Cart
              </Link>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
};

CheckoutPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default CheckoutPage;

import { useParams, Link, useNavigate } from 'react-router-dom';
import { ShoppingCart, MessageCircle, ArrowLeft, Check, X } from 'lucide-react';
import { useProduct, useProducts } from '../../hooks/useProducts';
import { useCart } from '../../contexts/CartContext';
import ProductCard from '../../components/product/ProductCard';
import { formatCurrency, getSyllabusBadgeColor, generateProductWhatsAppMessage, generateWhatsAppLink } from '../../utils/helpers';

const ProductDetailPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { product, loading, error } = useProduct(slug || '');
  const { addToCart } = useCart();

  const { products: relatedProducts } = useProducts({
    filters: product ? { syllabus: [product.syllabus], level: [product.level] } : {},
    per_page: 4,
  });

  if (loading) {
    return (
      <div className="container-custom py-12">
        <div className="animate-pulse">
          <div className="bg-gray-200 h-8 w-32 mb-8" />
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div className="bg-gray-200 h-96 rounded-lg" />
            <div className="space-y-4">
              <div className="bg-gray-200 h-8 rounded" />
              <div className="bg-gray-200 h-4 rounded w-2/3" />
              <div className="bg-gray-200 h-32 rounded" />
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="container-custom py-12">
        <div className="text-center">
          <h2 className="text-2xl font-bold mb-4">Product Not Found</h2>
          <p className="text-gray-600 mb-8">The product you're looking for doesn't exist.</p>
          <Link to="/shop" className="btn-primary">
            Browse All Books
          </Link>
        </div>
      </div>
    );
  }

  const whatsappMessage = generateProductWhatsAppMessage(product.title);
  const whatsappLink = generateWhatsAppLink(whatsappMessage);

  const handleAddToCart = () => {
    addToCart(product);
  };

  return (
    <div className="bg-gray-50">
      <div className="container-custom py-8">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center gap-2 text-gray-600 hover:text-primary-600 mb-6 transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
          Back to Shop
        </button>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
          <div>
            <img
              src={product.cover_image}
              alt={product.title}
              className="w-full h-auto rounded-lg shadow-lg"
            />
          </div>

          <div>
            <div className="flex gap-2 mb-4">
              <span className={`px-3 py-1 rounded text-sm font-semibold ${getSyllabusBadgeColor(product.syllabus)}`}>
                {product.syllabus}
              </span>
              <span className="px-3 py-1 rounded text-sm font-semibold bg-gray-200 text-gray-800">
                {product.level}
              </span>
            </div>

            <h1 className="text-4xl font-bold mb-4">{product.title}</h1>

            {product.author && (
              <p className="text-gray-600 mb-2">
                <span className="font-medium">Author:</span> {product.author}
              </p>
            )}

            <p className="text-gray-600 mb-4">
              <span className="font-medium">Publisher:</span> {product.publisher}
            </p>

            {product.isbn && (
              <p className="text-gray-600 mb-4">
                <span className="font-medium">ISBN:</span> {product.isbn}
              </p>
            )}

            <div className="border-t border-b py-4 mb-6">
              <div className="flex items-baseline gap-4 mb-2">
                <span className="text-3xl font-bold text-primary-700">
                  {formatCurrency(product.price_zwl, 'ZWL')}
                </span>
                <span className="text-xl text-gray-600">
                  {formatCurrency(product.price_usd, 'USD')}
                </span>
              </div>

              <div className="flex items-center gap-2 mt-4">
                {product.stock_status === 'in_stock' ? (
                  <>
                    <Check className="w-5 h-5 text-green-600" />
                    <span className="text-green-600 font-medium">In Stock</span>
                  </>
                ) : product.stock_status === 'low_stock' ? (
                  <>
                    <Check className="w-5 h-5 text-yellow-600" />
                    <span className="text-yellow-600 font-medium">Low Stock - Order Soon</span>
                  </>
                ) : (
                  <>
                    <X className="w-5 h-5 text-red-600" />
                    <span className="text-red-600 font-medium">Out of Stock</span>
                  </>
                )}
              </div>
            </div>

            <div className="space-y-3 mb-8">
              <button
                onClick={handleAddToCart}
                disabled={product.stock_status === 'out_of_stock'}
                className="btn-primary w-full flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                <ShoppingCart className="w-5 h-5" />
                Add to Cart
              </button>

              <a
                href={whatsappLink}
                target="_blank"
                rel="noopener noreferrer"
                className="btn-whatsapp w-full flex items-center justify-center gap-2"
              >
                <MessageCircle className="w-5 h-5" />
                Order via WhatsApp
              </a>

              <Link to="/contact" className="btn-secondary w-full text-center block">
                Send Inquiry
              </Link>
            </div>

            {product.description && (
              <div className="bg-white rounded-lg p-6">
                <h2 className="text-xl font-bold mb-4">Description</h2>
                <p className="text-gray-700 leading-relaxed">{product.description}</p>
              </div>
            )}
          </div>
        </div>

        <div className="bg-white rounded-lg p-6 mb-12">
          <h2 className="text-xl font-bold mb-4">Product Details</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex justify-between py-2 border-b">
              <span className="font-medium text-gray-700">Syllabus:</span>
              <span className="text-gray-900">{product.syllabus}</span>
            </div>
            <div className="flex justify-between py-2 border-b">
              <span className="font-medium text-gray-700">Level:</span>
              <span className="text-gray-900">{product.level}</span>
            </div>
            <div className="flex justify-between py-2 border-b">
              <span className="font-medium text-gray-700">Subject:</span>
              <span className="text-gray-900">{product.subject}</span>
            </div>
            <div className="flex justify-between py-2 border-b">
              <span className="font-medium text-gray-700">Publisher:</span>
              <span className="text-gray-900">{product.publisher}</span>
            </div>
            {product.isbn && (
              <div className="flex justify-between py-2 border-b">
                <span className="font-medium text-gray-700">ISBN:</span>
                <span className="text-gray-900">{product.isbn}</span>
              </div>
            )}
            {product.author && (
              <div className="flex justify-between py-2 border-b">
                <span className="font-medium text-gray-700">Author:</span>
                <span className="text-gray-900">{product.author}</span>
              </div>
            )}
          </div>
        </div>

        {relatedProducts.length > 0 && (
          <div>
            <h2 className="text-2xl font-bold mb-6">Related Books</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {relatedProducts
                .filter((p) => p.id !== product.id)
                .slice(0, 4)
                .map((relatedProduct) => (
                  <ProductCard key={relatedProduct.id} product={relatedProduct} />
                ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductDetailPage;

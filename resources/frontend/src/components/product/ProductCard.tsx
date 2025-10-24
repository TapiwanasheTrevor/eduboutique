import { Link } from 'react-router-dom';
import { ShoppingCart, MessageCircle } from 'lucide-react';
import { Product } from '../../types';
import { formatCurrency, getSyllabusBadgeColor, getStockBadgeColor, getStockLabel, generateProductWhatsAppMessage, generateWhatsAppLink } from '../../utils/helpers';
import { useCart } from '../../contexts/CartContext';

interface ProductCardProps {
  product: Product;
}

const ProductCard = ({ product }: ProductCardProps) => {
  const { addToCart } = useCart();

  const handleAddToCart = (e: React.MouseEvent) => {
    e.preventDefault();
    addToCart(product);
  };

  const whatsappMessage = generateProductWhatsAppMessage(product.title);
  const whatsappLink = generateWhatsAppLink(whatsappMessage);

  return (
    <Link to={`/product/${product.slug}`} className="card group hover:shadow-hover transition-shadow">
      <div className="relative overflow-hidden">
        <img
          src={product.cover_image}
          alt={product.title}
          className="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
        />
        <div className="absolute top-2 left-2 flex flex-col gap-2">
          <span className={`px-2 py-1 rounded text-xs font-semibold ${getSyllabusBadgeColor(product.syllabus)}`}>
            {product.syllabus}
          </span>
          <span className={`px-2 py-1 rounded text-xs font-semibold ${getStockBadgeColor(product.stock_status)}`}>
            {getStockLabel(product.stock_status)}
          </span>
        </div>
      </div>

      <div className="p-4">
        <h3 className="font-semibold text-lg mb-1 line-clamp-2 group-hover:text-primary-600 transition-colors">
          {product.title}
        </h3>
        <p className="text-sm text-gray-600 mb-2">
          {product.level} | {product.subject}
        </p>

        <div className="flex items-center justify-between mb-3">
          <div>
            <p className="text-primary-700 font-bold">{formatCurrency(product.price_zwl, 'ZWL')}</p>
            <p className="text-sm text-gray-600">{formatCurrency(product.price_usd, 'USD')}</p>
          </div>
        </div>

        <div className="flex gap-2">
          <button
            onClick={handleAddToCart}
            disabled={product.stock_status === 'out_of_stock'}
            className="flex-1 flex items-center justify-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed text-sm"
          >
            <ShoppingCart className="w-4 h-4" />
            Add to Cart
          </button>

          <a
            href={whatsappLink}
            target="_blank"
            rel="noopener noreferrer"
            onClick={(e) => e.stopPropagation()}
            className="bg-green-500 text-white p-2 rounded-lg hover:bg-green-600 transition-colors"
            aria-label="Order via WhatsApp"
          >
            <MessageCircle className="w-4 h-4" />
          </a>
        </div>
      </div>
    </Link>
  );
};

export default ProductCard;

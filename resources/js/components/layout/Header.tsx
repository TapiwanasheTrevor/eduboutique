import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Menu, X, Search, ShoppingCart, MessageCircle, ChevronDown } from 'lucide-react';
import { useCart } from '../../contexts/CartContext';
import { generateWhatsAppLink } from '../../utils/helpers';
import { WHATSAPP_NUMBER } from '../../utils/constants';

const Header = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isShopOpen, setIsShopOpen] = useState(false);
  const { getItemCount } = useCart();

  const toggleMenu = () => setIsMenuOpen(!isMenuOpen);

  const whatsappLink = generateWhatsAppLink('Hello! I would like to inquire about your books.');

  return (
    <header className="bg-white shadow-md sticky top-0 z-50">
      <div className="bg-primary-600 text-white py-2">
        <div className="container-custom flex justify-between items-center text-sm">
          <span>Nationwide Delivery via Agents</span>
          <a href={whatsappLink} target="_blank" rel="noopener noreferrer" className="hover:underline">
            WhatsApp: {WHATSAPP_NUMBER}
          </a>
        </div>
      </div>

      <nav className="container-custom py-4">
        <div className="flex items-center justify-between">
          <Link href="/" className="flex items-center">
            <h1 className="text-2xl md:text-3xl font-serif font-bold text-primary-700">
              Edu Boutique Bookstore
            </h1>
          </Link>

          <div className="hidden lg:flex items-center space-x-8">
            <Link href="/" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
              Home
            </Link>

            <div
              className="relative group"
              onMouseEnter={() => setIsShopOpen(true)}
              onMouseLeave={() => setIsShopOpen(false)}
            >
              <button
                className="flex items-center text-gray-700 hover:text-primary-600 font-medium transition-colors"
              >
                Shop <ChevronDown className="ml-1 w-4 h-4" />
              </button>

              {isShopOpen && (
                <div className="absolute left-0 pt-2 w-64 z-50">
                  <div className="bg-white rounded-lg shadow-lg py-2">
                    <Link href="/shop?category=zimsec" className="block px-4 py-2 hover:bg-gray-100">
                      ZIMSEC Textbooks
                    </Link>
                    <Link href="/shop?category=cambridge" className="block px-4 py-2 hover:bg-gray-100">
                      Cambridge Textbooks
                    </Link>
                    <Link href="/shop?category=stationery" className="block px-4 py-2 hover:bg-gray-100">
                      Stationery
                    </Link>
                    <Link href="/shop?category=supplementary" className="block px-4 py-2 hover:bg-gray-100">
                      Supplementary Books
                    </Link>
                  </div>
                </div>
              )}
            </div>

            <Link href="/about" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
              About Us
            </Link>

            <Link href="/videos" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
              Videos
            </Link>

            <Link href="/contact" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
              Contact
            </Link>
          </div>

          <div className="hidden lg:flex items-center space-x-4">
            <button className="text-gray-700 hover:text-primary-600">
              <Search className="w-5 h-5" />
            </button>

            <a href={whatsappLink} target="_blank" rel="noopener noreferrer" className="text-gray-700 hover:text-green-600">
              <MessageCircle className="w-5 h-5" />
            </a>

            <Link href="/cart" className="relative text-gray-700 hover:text-primary-600">
              <ShoppingCart className="w-5 h-5" />
              {getItemCount() > 0 && (
                <span className="absolute -top-2 -right-2 bg-primary-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {getItemCount()}
                </span>
              )}
            </Link>
          </div>

          <button onClick={toggleMenu} className="lg:hidden text-gray-700">
            {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>

        {isMenuOpen && (
          <div className="lg:hidden mt-4 pb-4 border-t pt-4">
            <div className="flex flex-col space-y-4">
              <Link href="/" className="text-gray-700 hover:text-primary-600 font-medium" onClick={toggleMenu}>
                Home
              </Link>
              <Link href="/shop" className="text-gray-700 hover:text-primary-600 font-medium" onClick={toggleMenu}>
                Shop
              </Link>
              <Link href="/about" className="text-gray-700 hover:text-primary-600 font-medium" onClick={toggleMenu}>
                About Us
              </Link>
              <Link href="/videos" className="text-gray-700 hover:text-primary-600 font-medium" onClick={toggleMenu}>
                Videos
              </Link>
              <Link href="/contact" className="text-gray-700 hover:text-primary-600 font-medium" onClick={toggleMenu}>
                Contact
              </Link>
              <Link href="/cart" className="text-gray-700 hover:text-primary-600 font-medium flex items-center" onClick={toggleMenu}>
                Cart {getItemCount() > 0 && `(${getItemCount()})`}
              </Link>
            </div>
          </div>
        )}
      </nav>
    </header>
  );
};

export default Header;

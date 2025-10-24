import { Link } from '@inertiajs/react';
import { MessageCircle, Facebook } from 'lucide-react';
import { STORE_INFO, SOCIAL_MEDIA } from '../../utils/constants';
import { generateWhatsAppLink } from '../../utils/helpers';

const Footer = () => {
  const whatsappLink = generateWhatsAppLink('Hello! I would like more information.');

  return (
    <footer className="bg-gray-900 text-gray-300">
      <div className="container-custom py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div>
            <h3 className="text-white text-lg font-bold mb-4 font-serif">
              Edu Boutique Bookstore
            </h3>
            <p className="text-sm mb-4">{STORE_INFO.tagline}</p>
            <div className="flex space-x-4">
              <a
                href={whatsappLink}
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-green-400 transition-colors"
              >
                <MessageCircle className="w-5 h-5" />
              </a>
              <a
                href={SOCIAL_MEDIA.facebook}
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-blue-400 transition-colors"
              >
                <Facebook className="w-5 h-5" />
              </a>
            </div>
          </div>

          <div>
            <h4 className="text-white text-lg font-semibold mb-4">Shop</h4>
            <ul className="space-y-2 text-sm">
              <li>
                <Link href="/shop?category=zimsec" className="hover:text-white transition-colors">
                  ZIMSEC Textbooks
                </Link>
              </li>
              <li>
                <Link href="/shop?category=cambridge" className="hover:text-white transition-colors">
                  Cambridge Textbooks
                </Link>
              </li>
              <li>
                <Link href="/shop?category=stationery" className="hover:text-white transition-colors">
                  Stationery
                </Link>
              </li>
              <li>
                <Link href="/shop?category=supplementary" className="hover:text-white transition-colors">
                  Supplementary Books
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-white text-lg font-semibold mb-4">Information</h4>
            <ul className="space-y-2 text-sm">
              <li>
                <Link href="/about" className="hover:text-white transition-colors">
                  About Us
                </Link>
              </li>
              <li>
                <Link href="/contact" className="hover:text-white transition-colors">
                  Contact
                </Link>
              </li>
              <li>
                <Link href="/videos" className="hover:text-white transition-colors">
                  Videos
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-white text-lg font-semibold mb-4">Contact Us</h4>
            <ul className="space-y-2 text-sm">
              <li>WhatsApp: {STORE_INFO.phone}</li>
              <li>Email: {STORE_INFO.email}</li>
              <li>{STORE_INFO.address}</li>
              <li className="mt-4">
                <span className="text-xs text-gray-400">Payment: Cash on Delivery</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="border-t border-gray-800">
        <div className="container-custom py-6">
          <div className="flex flex-col md:flex-row justify-between items-center text-sm">
            <p>&copy; {new Date().getFullYear()} Edu Boutique Bookstore. All rights reserved.</p>
            <div className="flex space-x-4 mt-4 md:mt-0">
              <Link href="/privacy" className="hover:text-white transition-colors">
                Privacy Policy
              </Link>
              <Link href="/terms" className="hover:text-white transition-colors">
                Terms & Conditions
              </Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;

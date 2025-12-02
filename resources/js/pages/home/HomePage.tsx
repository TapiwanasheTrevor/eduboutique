import { Link } from '@inertiajs/react';
import Layout from '../../components/layout/Layout';
import type { ReactNode } from 'react';
import { BookOpen, Truck, Award, MessageCircle, ArrowRight } from 'lucide-react';
import { generateWhatsAppLink } from '../../utils/helpers';
import ProductCard from '../../components/product/ProductCard';
import { Product, Category, Video } from '../../types';

interface HomePageProps {
  featuredProducts: Product[];
  newArrivals: Product[];
  popularCategories: Category[];
  latestVideos: Video[];
}

const HomePage = ({ featuredProducts, newArrivals, popularCategories, latestVideos }: HomePageProps) => {
  const whatsappLink = generateWhatsAppLink('Hello! I would like to browse your book collection.');

  return (
    <div>
      {/* Hero Section with animated background */}
      <section className="relative text-white h-[calc(100vh-108px)] flex items-end pb-16 overflow-hidden">
        {/* Animated background image */}
        <div
          className="absolute inset-0 w-[120%] h-[120%] -top-[10%] -left-[10%] bg-cover bg-center animate-slow-drift"
          style={{ backgroundImage: "url('/cover.jpg')" }}
        />
        {/* Gradient overlay */}
        <div className="absolute inset-0 bg-gradient-to-br from-primary-700/90 via-primary-800/85 to-primary-900/90" />

        {/* Content */}
        <div className="container-custom relative z-10">
          <div>
            <h1 className="text-4xl md:text-6xl font-bold font-serif mb-8">
              Your Source for ZIMSEC & Cambridge Textbooks
            </h1>
            <p className="text-xl md:text-2xl mb-4 text-primary-50">
              Quality educational books for ECD, Primary, O-Level, and A-Level students across Zimbabwe
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/shop" className="bg-white text-primary-700 px-8 py-4 rounded-lg font-semibold hover:bg-primary-50 transition-colors inline-flex items-center justify-center">
                Browse Books <ArrowRight className="ml-2 w-5 h-5" />
              </Link>
              <a
                href={whatsappLink}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-green-500 text-white px-8 py-4 rounded-lg font-semibold hover:bg-green-600 transition-colors inline-flex items-center justify-center"
              >
                <MessageCircle className="mr-2 w-5 h-5" />
                Order via WhatsApp
              </a>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="text-center p-6">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <BookOpen className="w-8 h-8 text-primary-700" />
              </div>
              <h3 className="text-xl font-bold mb-2">Wide Selection</h3>
              <p className="text-gray-600">
                ZIMSEC and Cambridge textbooks for all levels from ECD to A-Level
              </p>
            </div>

            <div className="text-center p-6">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <Truck className="w-8 h-8 text-primary-700" />
              </div>
              <h3 className="text-xl font-bold mb-2">Nationwide Delivery</h3>
              <p className="text-gray-600">
                Reliable delivery through our agent network across Zimbabwe
              </p>
            </div>

            <div className="text-center p-6">
              <div className="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <Award className="w-8 h-8 text-primary-700" />
              </div>
              <h3 className="text-xl font-bold mb-2">Quality Assured</h3>
              <p className="text-gray-600">
                Genuine textbooks aligned with current syllabi and examination requirements
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-gray-50">
        <div className="container-custom">
          <h2 className="text-3xl md:text-4xl font-bold text-center mb-4">Shop by Category</h2>
          <p className="text-center text-gray-600 mb-12 max-w-2xl mx-auto">
            Find the perfect textbooks for your educational level
          </p>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Link href="/shop?category=zimsec" className="group">
              <div className="card p-6 text-center hover:shadow-hover transition-shadow">
                <div className="bg-primary-600 text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <BookOpen className="w-10 h-10" />
                </div>
                <h3 className="font-bold text-xl mb-2">ZIMSEC Textbooks</h3>
                <p className="text-gray-600 text-sm">For Zimbabwean curriculum students</p>
              </div>
            </Link>

            <Link href="/shop?category=cambridge" className="group">
              <div className="card p-6 text-center hover:shadow-hover transition-shadow">
                <div className="bg-accent-sky text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <BookOpen className="w-10 h-10" />
                </div>
                <h3 className="font-bold text-xl mb-2">Cambridge Books</h3>
                <p className="text-gray-600 text-sm">IGCSE and A-Level resources</p>
              </div>
            </Link>

            <Link href="/shop?category=stationery" className="group">
              <div className="card p-6 text-center hover:shadow-hover transition-shadow">
                <div className="bg-accent-gold text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <BookOpen className="w-10 h-10" />
                </div>
                <h3 className="font-bold text-xl mb-2">Stationery</h3>
                <p className="text-gray-600 text-sm">Essential school supplies</p>
              </div>
            </Link>

            <Link href="/shop?category=supplementary" className="group">
              <div className="card p-6 text-center hover:shadow-hover transition-shadow">
                <div className="bg-primary-700 text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                  <BookOpen className="w-10 h-10" />
                </div>
                <h3 className="font-bold text-xl mb-2">Reference Books</h3>
                <p className="text-gray-600 text-sm">Dictionaries and study guides</p>
              </div>
            </Link>
          </div>
        </div>
      </section>

      {featuredProducts && featuredProducts.length > 0 && (
        <section className="py-16 bg-white">
          <div className="container-custom">
            <div className="text-center mb-12">
              <h2 className="text-3xl md:text-4xl font-bold mb-4">Featured Textbooks</h2>
              <p className="text-gray-600 max-w-2xl mx-auto">
                Popular textbooks trusted by schools and students across Zimbabwe
              </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>

            <div className="text-center mt-12">
              <Link href="/shop" className="btn-primary inline-flex items-center gap-2">
                View All Books <ArrowRight className="w-5 h-5" />
              </Link>
            </div>
          </div>
        </section>
      )}

      <section className="py-16 bg-primary-700 text-white">
        <div className="container-custom text-center">
          <h2 className="text-3xl md:text-4xl font-bold mb-6">Trusted by Zimbabwean Schools</h2>
          <p className="text-xl text-primary-50 mb-8 max-w-2xl mx-auto">
            Supporting education with quality textbooks and reliable service
          </p>
          <Link href="/about" className="bg-white text-primary-700 px-8 py-4 rounded-lg font-semibold hover:bg-primary-50 transition-colors inline-block">
            Learn More About Us
          </Link>
        </div>
      </section>
    </div>
  );
};

HomePage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default HomePage;

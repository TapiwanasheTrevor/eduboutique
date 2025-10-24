import { BookOpen, Users, Truck, Award } from 'lucide-react';
import { Link } from 'react-router-dom';

const AboutPage = () => {
  return (
    <div className="bg-gray-50">
      <section className="bg-primary-700 text-white py-16">
        <div className="container-custom">
          <h1 className="text-4xl md:text-5xl font-bold mb-6 text-center">About Edu Boutique Bookstore</h1>
          <p className="text-xl text-primary-50 text-center max-w-3xl mx-auto">
            Supporting Zimbabwean education with quality ZIMSEC and Cambridge textbooks since our establishment
          </p>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl font-bold mb-6">Our Story</h2>
            <div className="space-y-4 text-gray-700 leading-relaxed">
              <p>
                Edu Boutique Bookstore was founded with a clear mission: to make quality educational materials
                accessible to students across Zimbabwe. We understand the challenges that schools, parents, and
                students face in obtaining the right textbooks aligned with current syllabi.
              </p>
              <p>
                From our physical store in Harare to our nationwide agent network, we have built a reputation
                for reliability, quality, and customer service. We specialize in ZIMSEC and Cambridge textbooks,
                covering all educational levels from ECD to A-Level.
              </p>
              <p>
                Our commitment goes beyond just selling books. We aim to be a trusted educational partner,
                providing guidance on the right resources and supporting schools with bulk orders and flexible
                payment options including cash-on-delivery.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-gray-50">
        <div className="container-custom">
          <h2 className="text-3xl font-bold text-center mb-12">What Makes Us Different</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div className="text-center">
              <div className="bg-primary-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <BookOpen className="w-10 h-10 text-primary-700" />
              </div>
              <h3 className="font-bold text-xl mb-2">Wide Selection</h3>
              <p className="text-gray-600">
                Comprehensive collection of ZIMSEC and Cambridge textbooks for all levels and subjects
              </p>
            </div>

            <div className="text-center">
              <div className="bg-primary-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <Award className="w-10 h-10 text-primary-700" />
              </div>
              <h3 className="font-bold text-xl mb-2">Quality Assured</h3>
              <p className="text-gray-600">
                Only genuine textbooks from approved publishers, aligned with current examination requirements
              </p>
            </div>

            <div className="text-center">
              <div className="bg-primary-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <Truck className="w-10 h-10 text-primary-700" />
              </div>
              <h3 className="font-bold text-xl mb-2">Nationwide Delivery</h3>
              <p className="text-gray-600">
                Reliable delivery through our trusted agent network covering all major cities in Zimbabwe
              </p>
            </div>

            <div className="text-center">
              <div className="bg-primary-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <Users className="w-10 h-10 text-primary-700" />
              </div>
              <h3 className="font-bold text-xl mb-2">Trusted Partner</h3>
              <p className="text-gray-600">
                Serving schools, parents, and individual students with personalized service and support
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl font-bold mb-6">Our Agent Network</h2>
            <p className="text-gray-700 leading-relaxed mb-6">
              We understand that accessing quality educational materials can be challenging for students outside
              major cities. That's why we've established a reliable agent-based delivery system across Zimbabwe.
            </p>
            <p className="text-gray-700 leading-relaxed mb-6">
              Our agents are trusted partners who ensure your books reach you safely, with payment collected
              upon delivery. This cash-on-delivery system provides peace of mind for both schools and parents.
            </p>
            <div className="bg-primary-50 border-l-4 border-primary-600 p-6 rounded">
              <p className="text-gray-800 font-medium">
                Delivery Coverage: Harare, Bulawayo, Mutare, Gweru, Masvingo, Chitungwiza, and many other
                cities across Zimbabwe
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-16 bg-primary-700 text-white">
        <div className="container-custom text-center">
          <h2 className="text-3xl font-bold mb-6">Ready to Order?</h2>
          <p className="text-xl text-primary-50 mb-8 max-w-2xl mx-auto">
            Browse our collection or get in touch via WhatsApp for personalized assistance
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              to="/shop"
              className="bg-white text-primary-700 px-8 py-4 rounded-lg font-semibold hover:bg-primary-50 transition-colors inline-block"
            >
              Browse Books
            </Link>
            <Link
              to="/contact"
              className="bg-primary-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-800 transition-colors inline-block border-2 border-white"
            >
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default AboutPage;

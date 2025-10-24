import { useState, type ReactNode } from 'react';
import { Filter, Search, SlidersHorizontal } from 'lucide-react';
import { router } from '@inertiajs/react';
import ProductCard from '../../components/product/ProductCard';
import FilterSidebar from '../../components/common/FilterSidebar';
import Pagination from '../../components/common/Pagination';
import { FilterOptions, Product } from '../../types';
import Layout from '../../components/layout/Layout';

interface ShopPageProps {
  products: Product[];
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
  filterOptions: {
    syllabuses: string[];
    levels: string[];
    subjects: string[];
  };
  filters: {
    q?: string;
    sort?: string;
    syllabus?: string[];
    level?: string[];
    subject?: string[];
  };
}

const ShopPage = ({ products, total, perPage, currentPage, lastPage, filterOptions, filters }: ShopPageProps) => {
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState(filters.q || '');
  const [sortBy, setSortBy] = useState(filters.sort || 'featured');
  const [activeFilters, setActiveFilters] = useState<FilterOptions>({
    syllabus: filters.syllabus || [],
    level: filters.level || [],
    subject: filters.subject || [],
  });

  const handleSearch = (value: string) => {
    setSearchQuery(value);
    const params: any = { ...filters, q: value || undefined };
    delete params.page;
    router.get('/shop', params, { preserveState: true, preserveScroll: true });
  };

  const handleFilterChange = (newFilters: FilterOptions) => {
    setActiveFilters(newFilters);
    const params: any = {
      ...filters,
      syllabus: newFilters.syllabus,
      level: newFilters.level,
      subject: newFilters.subject,
    };
    delete params.page;
    router.get('/shop', params, { preserveState: true, preserveScroll: true });
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    const params: any = { ...filters, sort: value !== 'featured' ? value : undefined };
    delete params.page;
    router.get('/shop', params, { preserveState: true, preserveScroll: true });
  };

  const handlePageChange = (page: number) => {
    router.get('/shop', { ...filters, page }, { preserveState: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const clearAllFilters = () => {
    setSearchQuery('');
    setActiveFilters({});
    router.get('/shop', {}, { preserveState: true });
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      <div className="bg-primary-700 text-white py-12">
        <div className="container-custom">
          <h1 className="text-4xl font-bold mb-4">Shop Textbooks</h1>
          <p className="text-primary-50">
            Browse our collection of ZIMSEC and Cambridge textbooks for all levels
          </p>
        </div>
      </div>

      <div className="container-custom py-8">
        <div className="flex gap-8">
          <div className="hidden lg:block w-64 flex-shrink-0">
            <FilterSidebar
              filters={activeFilters}
              onFilterChange={handleFilterChange}
              isOpen={true}
              onClose={() => {}}
              filterOptions={filterOptions}
            />
          </div>

          <div className="flex-1">
            <div className="mb-6">
              <div className="flex flex-col sm:flex-row gap-4 mb-4">
                <div className="flex-1 relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    placeholder="Search for books, subjects, or topics..."
                    value={searchQuery}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="input-field pl-10"
                  />
                </div>

                <button
                  onClick={() => setIsFilterOpen(true)}
                  className="lg:hidden btn-secondary flex items-center justify-center gap-2"
                >
                  <Filter className="w-5 h-5" />
                  Filters
                </button>
              </div>

              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <p className="text-gray-600">
                  Showing {products.length} of {total} products
                </p>

                <div className="flex items-center gap-2">
                  <SlidersHorizontal className="w-5 h-5 text-gray-600" />
                  <select
                    value={sortBy}
                    onChange={(e) => handleSortChange(e.target.value)}
                    className="input-field py-2"
                  >
                    <option value="featured">Featured</option>
                    <option value="newest">Newest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="alphabetical">A to Z</option>
                  </select>
                </div>
              </div>
            </div>

            {products.length === 0 ? (
              <div className="text-center py-12">
                <div className="bg-gray-200 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Search className="w-12 h-12 text-gray-400" />
                </div>
                <h3 className="text-xl font-bold mb-2">No products found</h3>
                <p className="text-gray-600 mb-6">
                  Try adjusting your filters or search terms
                </p>
                <button
                  onClick={clearAllFilters}
                  className="btn-primary"
                >
                  Clear All Filters
                </button>
              </div>
            ) : (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                  ))}
                </div>

                {lastPage > 1 && (
                  <Pagination
                    currentPage={currentPage}
                    totalPages={lastPage}
                    onPageChange={handlePageChange}
                  />
                )}
              </>
            )}
          </div>
        </div>
      </div>

      <div className="lg:hidden">
        <FilterSidebar
          filters={activeFilters}
          onFilterChange={handleFilterChange}
          isOpen={isFilterOpen}
          onClose={() => setIsFilterOpen(false)}
          filterOptions={filterOptions}
        />
      </div>
    </div>
  );
};

ShopPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default ShopPage;

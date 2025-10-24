import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Filter, Search, SlidersHorizontal } from 'lucide-react';
import { useProducts } from '../../hooks/useProducts';
import ProductCard from '../../components/product/ProductCard';
import FilterSidebar from '../../components/common/FilterSidebar';
import Pagination from '../../components/common/Pagination';
import { FilterOptions, SearchParams } from '../../types';
import { debounce } from '../../utils/helpers';

const ShopPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');
  const [currentPage, setCurrentPage] = useState(1);
  const [sortBy, setSortBy] = useState(searchParams.get('sort') || 'featured');
  const [filters, setFilters] = useState<FilterOptions>({});

  const params: SearchParams = {
    query: searchQuery,
    sort: sortBy as any,
    page: currentPage,
    per_page: 12,
    filters,
  };

  const { products, loading, error, totalCount } = useProducts(params);
  const totalPages = Math.ceil(totalCount / 12);

  useEffect(() => {
    const urlFilters: FilterOptions = {};

    const syllabus = searchParams.getAll('syllabus');
    if (syllabus.length) urlFilters.syllabus = syllabus;

    const level = searchParams.getAll('level');
    if (level.length) urlFilters.level = level;

    const subject = searchParams.getAll('subject');
    if (subject.length) urlFilters.subject = subject;

    setFilters(urlFilters);
  }, []);

  const handleSearch = debounce((value: string) => {
    setSearchQuery(value);
    setCurrentPage(1);
    if (value) {
      searchParams.set('q', value);
    } else {
      searchParams.delete('q');
    }
    setSearchParams(searchParams);
  }, 500);

  const handleFilterChange = (newFilters: FilterOptions) => {
    setFilters(newFilters);
    setCurrentPage(1);

    searchParams.delete('syllabus');
    searchParams.delete('level');
    searchParams.delete('subject');

    newFilters.syllabus?.forEach((s) => searchParams.append('syllabus', s));
    newFilters.level?.forEach((l) => searchParams.append('level', l));
    newFilters.subject?.forEach((s) => searchParams.append('subject', s));

    setSearchParams(searchParams);
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    setCurrentPage(1);
    if (value !== 'featured') {
      searchParams.set('sort', value);
    } else {
      searchParams.delete('sort');
    }
    setSearchParams(searchParams);
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
              filters={filters}
              onFilterChange={handleFilterChange}
              isOpen={true}
              onClose={() => {}}
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
                    defaultValue={searchQuery}
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
                  Showing {products.length} of {totalCount} products
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

            {loading ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {[...Array(6)].map((_, i) => (
                  <div key={i} className="card h-96 animate-pulse">
                    <div className="bg-gray-200 h-64" />
                    <div className="p-4 space-y-3">
                      <div className="bg-gray-200 h-4 rounded" />
                      <div className="bg-gray-200 h-4 rounded w-2/3" />
                    </div>
                  </div>
                ))}
              </div>
            ) : error ? (
              <div className="text-center py-12">
                <p className="text-red-600 mb-4">{error}</p>
                <button onClick={() => window.location.reload()} className="btn-primary">
                  Try Again
                </button>
              </div>
            ) : products.length === 0 ? (
              <div className="text-center py-12">
                <div className="bg-gray-200 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Search className="w-12 h-12 text-gray-400" />
                </div>
                <h3 className="text-xl font-bold mb-2">No products found</h3>
                <p className="text-gray-600 mb-6">
                  Try adjusting your filters or search terms
                </p>
                <button
                  onClick={() => {
                    setFilters({});
                    setSearchQuery('');
                    setSearchParams({});
                  }}
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

                <Pagination
                  currentPage={currentPage}
                  totalPages={totalPages}
                  onPageChange={handlePageChange}
                />
              </>
            )}
          </div>
        </div>
      </div>

      <div className="lg:hidden">
        <FilterSidebar
          filters={filters}
          onFilterChange={handleFilterChange}
          isOpen={isFilterOpen}
          onClose={() => setIsFilterOpen(false)}
        />
      </div>
    </div>
  );
};

export default ShopPage;

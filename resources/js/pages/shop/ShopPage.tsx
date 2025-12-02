import { useState, useEffect, useRef, useCallback, type ReactNode } from 'react';
import { Filter, Search, SlidersHorizontal, Loader2 } from 'lucide-react';
import { router, Head } from '@inertiajs/react';
import ProductCard from '../../components/product/ProductCard';
import FilterSidebar from '../../components/common/FilterSidebar';
import { FilterOptions, Product } from '../../types';
import Layout from '../../components/layout/Layout';

interface ShopPageProps {
  products: Product[];
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
  hasMorePages: boolean;
  filterOptions: {
    syllabuses: string[];
    levels: string[];
    subjects: string[];
    authors: string[];
    publishers: string[];
  };
  filters: {
    q?: string;
    sort?: string;
    syllabus?: string[];
    level?: string[];
    subject?: string[];
    author?: string[];
    publisher?: string[];
  };
}

const ShopPage = ({
  products: initialProducts,
  total,
  currentPage: initialPage,
  lastPage,
  hasMorePages: initialHasMore,
  filterOptions,
  filters,
}: ShopPageProps) => {
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState(filters.q || '');
  const [sortBy, setSortBy] = useState(filters.sort || 'featured');
  const [activeFilters, setActiveFilters] = useState<FilterOptions>({
    syllabus: filters.syllabus || [],
    level: filters.level || [],
    subject: filters.subject || [],
    author: filters.author || [],
    publisher: filters.publisher || [],
  });

  // Infinite scroll state
  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [page, setPage] = useState(initialPage);
  const [hasMore, setHasMore] = useState(initialHasMore);
  const [isLoading, setIsLoading] = useState(false);
  const loadMoreRef = useRef<HTMLDivElement>(null);
  const searchTimeoutRef = useRef<NodeJS.Timeout>();

  // Reset products when filters change (from server)
  useEffect(() => {
    setProducts(initialProducts);
    setPage(initialPage);
    setHasMore(initialHasMore);
  }, [initialProducts, initialPage, initialHasMore]);

  // Build query string for API calls
  const buildQueryString = useCallback(() => {
    const params = new URLSearchParams();
    if (filters.q) params.set('q', filters.q);
    if (filters.sort && filters.sort !== 'featured') params.set('sort', filters.sort);
    if (filters.syllabus?.length) filters.syllabus.forEach((s) => params.append('syllabus[]', s));
    if (filters.level?.length) filters.level.forEach((l) => params.append('level[]', l));
    if (filters.subject?.length) filters.subject.forEach((s) => params.append('subject[]', s));
    if (filters.author?.length) filters.author.forEach((a) => params.append('author[]', a));
    if (filters.publisher?.length) filters.publisher.forEach((p) => params.append('publisher[]', p));
    return params;
  }, [filters]);

  // Load more products
  const loadMore = useCallback(async () => {
    if (isLoading || !hasMore) return;

    setIsLoading(true);
    const nextPage = page + 1;
    const params = buildQueryString();
    params.set('page', nextPage.toString());

    try {
      const response = await fetch(`/api/shop/products?${params.toString()}`);
      const data = await response.json();

      setProducts((prev) => [...prev, ...data.products]);
      setPage(data.currentPage);
      setHasMore(data.hasMorePages);
    } catch (error) {
      console.error('Failed to load more products:', error);
    } finally {
      setIsLoading(false);
    }
  }, [isLoading, hasMore, page, buildQueryString]);

  // Intersection Observer for infinite scroll
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasMore && !isLoading) {
          loadMore();
        }
      },
      { threshold: 0.1, rootMargin: '100px' }
    );

    if (loadMoreRef.current) {
      observer.observe(loadMoreRef.current);
    }

    return () => observer.disconnect();
  }, [hasMore, isLoading, loadMore]);

  // Debounced search
  const handleSearch = (value: string) => {
    setSearchQuery(value);

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    searchTimeoutRef.current = setTimeout(() => {
      const params: Record<string, any> = { ...filters, q: value || undefined };
      delete params.page;
      router.get('/shop', params, { preserveState: true, preserveScroll: false });
    }, 300);
  };

  const handleFilterChange = (newFilters: FilterOptions) => {
    setActiveFilters(newFilters);
    const params: Record<string, any> = {
      ...filters,
      syllabus: newFilters.syllabus,
      level: newFilters.level,
      subject: newFilters.subject,
      author: newFilters.author,
      publisher: newFilters.publisher,
    };
    delete params.page;
    router.get('/shop', params, { preserveState: true, preserveScroll: false });
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    const params: Record<string, any> = { ...filters, sort: value !== 'featured' ? value : undefined };
    delete params.page;
    router.get('/shop', params, { preserveState: true, preserveScroll: false });
  };

  const clearAllFilters = () => {
    setSearchQuery('');
    setActiveFilters({});
    router.get('/shop', {}, { preserveState: true });
  };

  return (
    <>
      <Head title="Shop Textbooks" />
      <div className="bg-gray-50 min-h-screen">
      {/* Header */}
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
          {/* Desktop Sidebar */}
          <div className="hidden lg:block w-64 flex-shrink-0">
            <FilterSidebar
              filters={activeFilters}
              onFilterChange={handleFilterChange}
              isOpen={true}
              onClose={() => {}}
              filterOptions={filterOptions}
            />
          </div>

          {/* Main Content */}
          <div className="flex-1">
            {/* Search & Sort Bar */}
            <div className="mb-6">
              <div className="flex flex-col sm:flex-row gap-4 mb-4">
                <div className="flex-1 relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    placeholder="Search by title, author, subject, publisher..."
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
                  Showing <span className="font-semibold">{products.length}</span> of{' '}
                  <span className="font-semibold">{total}</span> products
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

            {/* Products Grid */}
            {products.length === 0 ? (
              <div className="text-center py-12">
                <div className="bg-gray-200 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Search className="w-12 h-12 text-gray-400" />
                </div>
                <h3 className="text-xl font-bold mb-2">No products found</h3>
                <p className="text-gray-600 mb-6">Try adjusting your filters or search terms</p>
                <button onClick={clearAllFilters} className="btn-primary">
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

                {/* Infinite Scroll Trigger */}
                <div ref={loadMoreRef} className="py-8 flex justify-center">
                  {isLoading && (
                    <div className="flex items-center gap-2 text-gray-600">
                      <Loader2 className="w-5 h-5 animate-spin" />
                      <span>Loading more products...</span>
                    </div>
                  )}
                  {!hasMore && products.length > 0 && (
                    <p className="text-gray-500 text-sm">
                      You've reached the end - {total} products shown
                    </p>
                  )}
                </div>
              </>
            )}
          </div>
        </div>
      </div>

      {/* Mobile Filter Sidebar */}
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
    </>
  );
};

ShopPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default ShopPage;

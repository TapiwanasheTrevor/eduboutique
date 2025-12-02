import { useState } from 'react';
import { X, Filter, Search, ChevronDown } from 'lucide-react';
import { FilterOptions } from '../../types';

interface FilterSidebarProps {
  filters: FilterOptions;
  onFilterChange: (filters: FilterOptions) => void;
  isOpen: boolean;
  onClose: () => void;
  filterOptions?: {
    syllabuses?: string[];
    levels?: string[];
    subjects?: string[];
    authors?: string[];
    publishers?: string[];
  };
}

interface FilterAccordionProps {
  title: string;
  isOpen: boolean;
  onToggle: () => void;
  selectedCount: number;
  children: React.ReactNode;
}

const FilterAccordion = ({ title, isOpen, onToggle, selectedCount, children }: FilterAccordionProps) => (
  <div className="border-b border-gray-200">
    <button
      onClick={onToggle}
      className="w-full flex items-center justify-between py-3 text-left hover:bg-gray-50 transition-colors"
    >
      <span className="font-medium text-gray-900 flex items-center gap-2">
        {title}
        {selectedCount > 0 && (
          <span className="bg-primary-100 text-primary-700 text-xs font-semibold px-2 py-0.5 rounded-full">
            {selectedCount}
          </span>
        )}
      </span>
      <ChevronDown
        className={`w-5 h-5 text-gray-500 transition-transform duration-200 ${
          isOpen ? 'rotate-180' : ''
        }`}
      />
    </button>
    <div
      className={`overflow-hidden transition-all duration-200 ${
        isOpen ? 'max-h-96 pb-4' : 'max-h-0'
      }`}
    >
      {children}
    </div>
  </div>
);

interface CheckboxListProps {
  items: string[];
  selectedItems: string[];
  onItemChange: (item: string) => void;
  searchPlaceholder?: string;
  showSearch?: boolean;
}

const CheckboxList = ({
  items,
  selectedItems,
  onItemChange,
  searchPlaceholder = 'Search...',
  showSearch = false,
}: CheckboxListProps) => {
  const [searchTerm, setSearchTerm] = useState('');

  const filteredItems = searchTerm
    ? items.filter((item) => item.toLowerCase().includes(searchTerm.toLowerCase()))
    : items;

  return (
    <div>
      {showSearch && (
        <div className="relative mb-2">
          <Search className="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <input
            type="text"
            placeholder={searchPlaceholder}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-gray-50"
          />
        </div>
      )}
      <div className="space-y-1 max-h-52 overflow-y-auto pr-1">
        {filteredItems.map((item) => (
          <label
            key={item}
            className="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 cursor-pointer group"
          >
            <input
              type="checkbox"
              checked={selectedItems.includes(item)}
              onChange={() => onItemChange(item)}
              className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 focus:ring-offset-0"
            />
            <span className="text-sm text-gray-700 group-hover:text-gray-900 truncate" title={item}>
              {item}
            </span>
          </label>
        ))}
        {filteredItems.length === 0 && (
          <p className="text-sm text-gray-500 py-2 text-center">No results found</p>
        )}
      </div>
    </div>
  );
};

const FilterSidebar = ({ filters, onFilterChange, isOpen, onClose, filterOptions }: FilterSidebarProps) => {
  const syllabi = filterOptions?.syllabuses || [];
  const levels = filterOptions?.levels || [];
  const subjects = filterOptions?.subjects || [];
  const authors = filterOptions?.authors || [];
  const publishers = filterOptions?.publishers || [];

  // Track which accordions are open - syllabus open by default
  const [openSections, setOpenSections] = useState<Record<string, boolean>>({
    syllabus: true,
    level: false,
    subject: false,
    author: false,
    publisher: false,
    price: false,
  });

  const toggleSection = (section: string) => {
    setOpenSections((prev) => ({ ...prev, [section]: !prev[section] }));
  };

  const handleFilterToggle = (
    filterKey: keyof FilterOptions,
    value: string,
    currentValues: string[] = []
  ) => {
    const newValues = currentValues.includes(value)
      ? currentValues.filter((v) => v !== value)
      : [...currentValues, value];
    onFilterChange({ ...filters, [filterKey]: newValues });
  };

  const handlePriceChange = (type: 'min' | 'max', value: string) => {
    const numValue = value ? parseFloat(value) : undefined;
    onFilterChange({
      ...filters,
      [type === 'min' ? 'price_min' : 'price_max']: numValue,
    });
  };

  const clearFilters = () => {
    onFilterChange({});
  };

  const totalActiveFilters =
    (filters.syllabus?.length || 0) +
    (filters.level?.length || 0) +
    (filters.subject?.length || 0) +
    (filters.author?.length || 0) +
    (filters.publisher?.length || 0) +
    (filters.price_min !== undefined ? 1 : 0) +
    (filters.price_max !== undefined ? 1 : 0);

  const FilterContent = () => (
    <div className="p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
        <div className="flex items-center gap-2">
          <Filter className="w-5 h-5 text-primary-600" />
          <h2 className="text-lg font-bold text-gray-900">Filters</h2>
          {totalActiveFilters > 0 && (
            <span className="bg-primary-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              {totalActiveFilters}
            </span>
          )}
        </div>
        <button onClick={onClose} className="lg:hidden p-1 hover:bg-gray-100 rounded">
          <X className="w-5 h-5 text-gray-500" />
        </button>
      </div>

      {/* Clear All Button */}
      {totalActiveFilters > 0 && (
        <button
          onClick={clearFilters}
          className="w-full text-sm text-primary-600 hover:text-primary-700 hover:bg-primary-50 py-2 px-3 rounded-lg font-medium mb-4 transition-colors border border-primary-200"
        >
          Clear all filters ({totalActiveFilters})
        </button>
      )}

      {/* Filter Sections */}
      <div className="space-y-0">
        {/* Syllabus Filter */}
        {syllabi.length > 0 && (
          <FilterAccordion
            title="Syllabus"
            isOpen={openSections.syllabus}
            onToggle={() => toggleSection('syllabus')}
            selectedCount={filters.syllabus?.length || 0}
          >
            <CheckboxList
              items={syllabi}
              selectedItems={filters.syllabus || []}
              onItemChange={(item) => handleFilterToggle('syllabus', item, filters.syllabus)}
            />
          </FilterAccordion>
        )}

        {/* Level Filter */}
        {levels.length > 0 && (
          <FilterAccordion
            title="Level"
            isOpen={openSections.level}
            onToggle={() => toggleSection('level')}
            selectedCount={filters.level?.length || 0}
          >
            <CheckboxList
              items={levels}
              selectedItems={filters.level || []}
              onItemChange={(item) => handleFilterToggle('level', item, filters.level)}
              showSearch={levels.length > 10}
              searchPlaceholder="Search levels..."
            />
          </FilterAccordion>
        )}

        {/* Subject Filter */}
        {subjects.length > 0 && (
          <FilterAccordion
            title="Subject"
            isOpen={openSections.subject}
            onToggle={() => toggleSection('subject')}
            selectedCount={filters.subject?.length || 0}
          >
            <CheckboxList
              items={subjects}
              selectedItems={filters.subject || []}
              onItemChange={(item) => handleFilterToggle('subject', item, filters.subject)}
              showSearch={subjects.length > 10}
              searchPlaceholder="Search subjects..."
            />
          </FilterAccordion>
        )}

        {/* Author Filter */}
        {authors.length > 0 && (
          <FilterAccordion
            title="Author"
            isOpen={openSections.author}
            onToggle={() => toggleSection('author')}
            selectedCount={filters.author?.length || 0}
          >
            <CheckboxList
              items={authors}
              selectedItems={filters.author || []}
              onItemChange={(item) => handleFilterToggle('author', item, filters.author)}
              showSearch
              searchPlaceholder="Search authors..."
            />
          </FilterAccordion>
        )}

        {/* Publisher Filter */}
        {publishers.length > 0 && (
          <FilterAccordion
            title="Publisher"
            isOpen={openSections.publisher}
            onToggle={() => toggleSection('publisher')}
            selectedCount={filters.publisher?.length || 0}
          >
            <CheckboxList
              items={publishers}
              selectedItems={filters.publisher || []}
              onItemChange={(item) => handleFilterToggle('publisher', item, filters.publisher)}
              showSearch={publishers.length > 10}
              searchPlaceholder="Search publishers..."
            />
          </FilterAccordion>
        )}

        {/* Price Range Filter */}
        <FilterAccordion
          title="Price Range (USD)"
          isOpen={openSections.price}
          onToggle={() => toggleSection('price')}
          selectedCount={
            (filters.price_min !== undefined ? 1 : 0) + (filters.price_max !== undefined ? 1 : 0)
          }
        >
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs text-gray-500 font-medium">Min</label>
              <input
                type="number"
                min="0"
                step="0.5"
                value={filters.price_min ?? ''}
                onChange={(e) => handlePriceChange('min', e.target.value)}
                className="w-full mt-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-gray-50"
                placeholder="0"
              />
            </div>
            <div>
              <label className="text-xs text-gray-500 font-medium">Max</label>
              <input
                type="number"
                min="0"
                step="0.5"
                value={filters.price_max ?? ''}
                onChange={(e) => handlePriceChange('max', e.target.value)}
                className="w-full mt-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-gray-50"
                placeholder="100"
              />
            </div>
          </div>
        </FilterAccordion>
      </div>
    </div>
  );

  return (
    <>
      {/* Overlay for mobile */}
      <div
        className={`fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity duration-300 ${
          isOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'
        }`}
        onClick={onClose}
      />

      {/* Sidebar */}
      <div
        className={`fixed lg:sticky top-0 left-0 h-full lg:h-auto w-80 lg:w-full bg-white z-50 lg:z-0 overflow-y-auto transition-transform duration-300 transform lg:rounded-lg lg:shadow-sm lg:border lg:border-gray-200 ${
          isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <FilterContent />
      </div>
    </>
  );
};

export default FilterSidebar;

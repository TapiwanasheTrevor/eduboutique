import { X, Filter } from 'lucide-react';
import { SYLLABI, LEVELS, SUBJECTS } from '../../utils/constants';
import { FilterOptions } from '../../types';

interface FilterSidebarProps {
  filters: FilterOptions;
  onFilterChange: (filters: FilterOptions) => void;
  isOpen: boolean;
  onClose: () => void;
}

const FilterSidebar = ({ filters, onFilterChange, isOpen, onClose }: FilterSidebarProps) => {
  const handleSyllabusChange = (syllabus: string) => {
    const currentSyllabi = filters.syllabus || [];
    const newSyllabi = currentSyllabi.includes(syllabus)
      ? currentSyllabi.filter((s) => s !== syllabus)
      : [...currentSyllabi, syllabus];
    onFilterChange({ ...filters, syllabus: newSyllabi });
  };

  const handleLevelChange = (level: string) => {
    const currentLevels = filters.level || [];
    const newLevels = currentLevels.includes(level)
      ? currentLevels.filter((l) => l !== level)
      : [...currentLevels, level];
    onFilterChange({ ...filters, level: newLevels });
  };

  const handleSubjectChange = (subject: string) => {
    const currentSubjects = filters.subject || [];
    const newSubjects = currentSubjects.includes(subject)
      ? currentSubjects.filter((s) => s !== subject)
      : [...currentSubjects, subject];
    onFilterChange({ ...filters, subject: newSubjects });
  };

  const handlePriceChange = (type: 'min' | 'max', value: string) => {
    const numValue = value ? parseFloat(value) : undefined;
    if (type === 'min') {
      onFilterChange({ ...filters, price_min: numValue });
    } else {
      onFilterChange({ ...filters, price_max: numValue });
    }
  };

  const clearFilters = () => {
    onFilterChange({});
  };

  const hasActiveFilters =
    (filters.syllabus && filters.syllabus.length > 0) ||
    (filters.level && filters.level.length > 0) ||
    (filters.subject && filters.subject.length > 0) ||
    filters.price_min !== undefined ||
    filters.price_max !== undefined;

  const FilterContent = () => (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6 lg:mb-4">
        <div className="flex items-center gap-2">
          <Filter className="w-5 h-5 text-primary-600" />
          <h2 className="text-xl font-bold">Filters</h2>
        </div>
        <button onClick={onClose} className="lg:hidden">
          <X className="w-6 h-6" />
        </button>
      </div>

      {hasActiveFilters && (
        <button
          onClick={clearFilters}
          className="text-sm text-primary-600 hover:text-primary-700 mb-4 font-medium"
        >
          Clear all filters
        </button>
      )}

      <div className="space-y-6">
        <div>
          <h3 className="font-semibold mb-3">Syllabus</h3>
          <div className="space-y-2">
            {SYLLABI.map((syllabus) => (
              <label key={syllabus} className="flex items-center">
                <input
                  type="checkbox"
                  checked={filters.syllabus?.includes(syllabus) || false}
                  onChange={() => handleSyllabusChange(syllabus)}
                  className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span className="ml-2 text-sm">{syllabus}</span>
              </label>
            ))}
          </div>
        </div>

        <div className="border-t pt-6">
          <h3 className="font-semibold mb-3">Level</h3>
          <div className="space-y-2">
            {LEVELS.map((level) => (
              <label key={level} className="flex items-center">
                <input
                  type="checkbox"
                  checked={filters.level?.includes(level) || false}
                  onChange={() => handleLevelChange(level)}
                  className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span className="ml-2 text-sm">{level}</span>
              </label>
            ))}
          </div>
        </div>

        <div className="border-t pt-6">
          <h3 className="font-semibold mb-3">Subject</h3>
          <div className="space-y-2 max-h-64 overflow-y-auto">
            {SUBJECTS.map((subject) => (
              <label key={subject} className="flex items-center">
                <input
                  type="checkbox"
                  checked={filters.subject?.includes(subject) || false}
                  onChange={() => handleSubjectChange(subject)}
                  className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span className="ml-2 text-sm">{subject}</span>
              </label>
            ))}
          </div>
        </div>

        <div className="border-t pt-6">
          <h3 className="font-semibold mb-3">Price Range (USD)</h3>
          <div className="space-y-3">
            <div>
              <label className="text-sm text-gray-600">Min Price</label>
              <input
                type="number"
                min="0"
                step="0.5"
                value={filters.price_min || ''}
                onChange={(e) => handlePriceChange('min', e.target.value)}
                className="input-field mt-1"
                placeholder="0"
              />
            </div>
            <div>
              <label className="text-sm text-gray-600">Max Price</label>
              <input
                type="number"
                min="0"
                step="0.5"
                value={filters.price_max || ''}
                onChange={(e) => handlePriceChange('max', e.target.value)}
                className="input-field mt-1"
                placeholder="100"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <>
      <div
        className={`fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden transition-opacity ${
          isOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'
        }`}
        onClick={onClose}
      />

      <div
        className={`fixed lg:sticky top-0 left-0 h-full lg:h-auto w-80 lg:w-full bg-white z-50 lg:z-0 overflow-y-auto transition-transform transform ${
          isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <FilterContent />
      </div>
    </>
  );
};

export default FilterSidebar;

import { useState, type ReactNode } from 'react';
import { Search, SlidersHorizontal, MapPin, Clock, Briefcase, Building2, Calendar } from 'lucide-react';
import { router, Link } from '@inertiajs/react';
import Layout from '../../components/layout/Layout';
import { JobPosting } from '../../types';
import Pagination from '../../components/common/Pagination';

interface CareersPageProps {
  jobs: JobPosting[];
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
  filterOptions: {
    departments: string[];
    employment_types: Record<string, string>;
  };
  filters: {
    q?: string;
    sort?: string;
    department?: string[];
    employment_type?: string[];
    location?: string;
  };
}

const employmentTypeLabels: Record<string, string> = {
  full_time: 'Full Time',
  part_time: 'Part Time',
  contract: 'Contract',
  internship: 'Internship',
};

const CareersPage = ({ jobs, total, perPage, currentPage, lastPage, filterOptions, filters }: CareersPageProps) => {
  const [searchQuery, setSearchQuery] = useState(filters.q || '');
  const [sortBy, setSortBy] = useState(filters.sort || 'newest');

  const handleSearch = (value: string) => {
    setSearchQuery(value);
    const params: any = { ...filters, q: value || undefined };
    delete params.page;
    router.get('/careers', params, { preserveState: true, preserveScroll: true });
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    const params: any = { ...filters, sort: value !== 'newest' ? value : undefined };
    delete params.page;
    router.get('/careers', params, { preserveState: true, preserveScroll: true });
  };

  const handlePageChange = (page: number) => {
    router.get('/careers', { ...filters, page }, { preserveState: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const formatDate = (dateString?: string) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  const isDeadlineSoon = (deadline?: string) => {
    if (!deadline) return false;
    const deadlineDate = new Date(deadline);
    const now = new Date();
    const daysUntil = Math.ceil((deadlineDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    return daysUntil <= 7 && daysUntil > 0;
  };

  return (
    <div className="bg-gray-50">
      <div className="bg-primary-700 text-white py-12">
        <div className="container-custom">
          <h1 className="text-4xl font-bold mb-4">Careers at Edu Boutique</h1>
          <p className="text-primary-50">
            Join our team and help shape the future of education in Zimbabwe
          </p>
        </div>
      </div>

      <div className="container-custom py-8">
        <div className="mb-6">
          <div className="flex flex-col sm:flex-row gap-4 mb-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Search job positions..."
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                className="input-field pl-10"
              />
            </div>
          </div>

          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p className="text-gray-600">
              {total === 0 ? 'No open positions' : `${total} open position${total !== 1 ? 's' : ''}`}
            </p>

            <div className="flex items-center gap-2">
              <SlidersHorizontal className="w-5 h-5 text-gray-600" />
              <select
                value={sortBy}
                onChange={(e) => handleSortChange(e.target.value)}
                className="input-field py-2"
              >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="deadline">By Deadline</option>
              </select>
            </div>
          </div>
        </div>

        {jobs.length === 0 ? (
          <div className="text-center py-12">
            <Briefcase className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-bold mb-2">No open positions</h3>
            <p className="text-gray-600">
              We don't have any open positions at the moment. Check back later or follow us for updates.
            </p>
          </div>
        ) : (
          <>
            <div className="space-y-4">
              {jobs.map((job) => (
                <Link
                  key={job.id}
                  href={`/careers/${job.slug}`}
                  className="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
                >
                  <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div className="flex-1">
                      <div className="flex items-start gap-3 mb-3">
                        <h2 className="text-xl font-semibold text-gray-900 hover:text-primary-600">
                          {job.title}
                        </h2>
                        {isDeadlineSoon(job.deadline) && (
                          <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded">
                            Closing Soon
                          </span>
                        )}
                      </div>

                      <div className="flex flex-wrap gap-4 text-sm text-gray-600 mb-3">
                        {job.department && (
                          <div className="flex items-center gap-1">
                            <Building2 className="w-4 h-4" />
                            <span>{job.department}</span>
                          </div>
                        )}
                        {job.location && (
                          <div className="flex items-center gap-1">
                            <MapPin className="w-4 h-4" />
                            <span>{job.location}</span>
                          </div>
                        )}
                        <div className="flex items-center gap-1">
                          <Clock className="w-4 h-4" />
                          <span>{employmentTypeLabels[job.employment_type] || job.employment_type}</span>
                        </div>
                        {job.deadline && (
                          <div className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            <span>Deadline: {formatDate(job.deadline)}</span>
                          </div>
                        )}
                      </div>

                      {job.description && (
                        <p className="text-gray-600 text-sm line-clamp-2" dangerouslySetInnerHTML={{ __html: job.description.substring(0, 200) + '...' }} />
                      )}
                    </div>

                    <div className="flex-shrink-0">
                      <span className="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-primary-50 text-primary-700 rounded-full">
                        View Details
                      </span>
                    </div>
                  </div>
                </Link>
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
  );
};

CareersPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default CareersPage;

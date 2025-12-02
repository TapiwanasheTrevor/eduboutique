import { useState, type ReactNode } from 'react';
import { Play, Search, SlidersHorizontal } from 'lucide-react';
import { router } from '@inertiajs/react';
import Layout from '../../components/layout/Layout';
import { Video } from '../../types';
import Pagination from '../../components/common/Pagination';

interface VideosPageProps {
  videos: Video[];
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
  filterOptions: {
    categories: string[];
  };
  filters: {
    q?: string;
    sort?: string;
    category?: string[];
  };
}

const VideosPage = ({ videos, total, perPage, currentPage, lastPage, filterOptions, filters }: VideosPageProps) => {
  const [searchQuery, setSearchQuery] = useState(filters.q || '');
  const [sortBy, setSortBy] = useState(filters.sort || 'newest');

  const handleSearch = (value: string) => {
    setSearchQuery(value);
    const params: any = { ...filters, q: value || undefined };
    delete params.page;
    router.get('/videos', params, { preserveState: true, preserveScroll: true });
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    const params: any = { ...filters, sort: value !== 'newest' ? value : undefined };
    delete params.page;
    router.get('/videos', params, { preserveState: true, preserveScroll: true });
  };

  const handlePageChange = (page: number) => {
    router.get('/videos', { ...filters, page }, { preserveState: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const getVideoIdFromUrl = (url: string): string => {
    const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
    return match ? match[1] : '';
  };

  return (
    <div className="bg-gray-50">
      <div className="bg-primary-700 text-white py-12">
        <div className="container-custom">
          <h1 className="text-4xl font-bold mb-4">Educational Videos</h1>
          <p className="text-primary-50">
            Study tips, book previews, and syllabus guides to support your learning
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
                placeholder="Search for videos..."
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                className="input-field pl-10"
              />
            </div>
          </div>

          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p className="text-gray-600">
              Showing {videos.length} of {total} videos
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
                <option value="alphabetical">A to Z</option>
              </select>
            </div>
          </div>
        </div>

        {videos.length === 0 ? (
          <div className="text-center py-12">
            <h3 className="text-xl font-bold mb-2">No videos found</h3>
            <p className="text-gray-600">Try adjusting your search or check back later for new content</p>
          </div>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {videos.map((video) => {
                const videoId = getVideoIdFromUrl(video.video_url);
                const thumbnailUrl = video.thumbnail_url || `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;

                return (
                  <div key={video.id} className="card group hover:shadow-hover transition-shadow">
                    <div className="relative">
                      <img
                        src={thumbnailUrl}
                        alt={video.title}
                        className="w-full h-48 object-cover"
                      />
                      <div className="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <a
                          href={video.video_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="bg-primary-600 text-white p-4 rounded-full hover:bg-primary-700 transition-colors"
                        >
                          <Play className="w-8 h-8" />
                        </a>
                      </div>
                      {video.duration && (
                        <span className="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                          {video.duration}
                        </span>
                      )}
                    </div>

                    <div className="p-4">
                      <h3 className="font-semibold text-lg mb-2 line-clamp-2">{video.title}</h3>
                      {video.description && (
                        <p className="text-gray-600 text-sm line-clamp-2 mb-3">{video.description}</p>
                      )}
                      {video.category && (
                        <div className="flex gap-2 mb-3">
                          <span className="text-xs bg-gray-100 px-2 py-1 rounded">{video.category}</span>
                        </div>
                      )}
                      <a
                        href={video.video_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary-600 hover:text-primary-700 font-medium text-sm"
                      >
                        Watch Now
                      </a>
                    </div>
                  </div>
                );
              })}
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

VideosPage.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default VideosPage;

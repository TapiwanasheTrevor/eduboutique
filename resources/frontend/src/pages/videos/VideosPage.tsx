import { useState } from 'react';
import { Play } from 'lucide-react';
import { useVideos } from '../../hooks/useVideos';
import { VIDEO_CATEGORIES } from '../../utils/constants';

const VideosPage = () => {
  const [selectedCategory, setSelectedCategory] = useState('all');
  const { videos, loading } = useVideos(selectedCategory);

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
        <div className="mb-8">
          <h2 className="text-lg font-semibold mb-4">Filter by Category</h2>
          <div className="flex flex-wrap gap-3">
            <button
              onClick={() => setSelectedCategory('all')}
              className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                selectedCategory === 'all'
                  ? 'bg-primary-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100'
              }`}
            >
              All Videos
            </button>
            {VIDEO_CATEGORIES.map((category) => (
              <button
                key={category.value}
                onClick={() => setSelectedCategory(category.value)}
                className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                  selectedCategory === category.value
                    ? 'bg-primary-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100'
                }`}
              >
                {category.label}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="card animate-pulse">
                <div className="bg-gray-200 h-48" />
                <div className="p-4 space-y-3">
                  <div className="bg-gray-200 h-4 rounded" />
                  <div className="bg-gray-200 h-4 rounded w-2/3" />
                </div>
              </div>
            ))}
          </div>
        ) : videos.length === 0 ? (
          <div className="text-center py-12">
            <h3 className="text-xl font-bold mb-2">No videos found</h3>
            <p className="text-gray-600">Check back later for new educational content</p>
          </div>
        ) : (
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
                    <div className="flex items-center justify-between">
                      <span className="text-xs text-gray-500 capitalize">
                        {VIDEO_CATEGORIES.find((c) => c.value === video.category)?.label || video.category}
                      </span>
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
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};

export default VideosPage;
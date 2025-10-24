import { useState, useEffect } from 'react';
import { supabase } from '../services/supabase';
import { Video } from '../types';

export const useVideos = (category?: string) => {
  const [videos, setVideos] = useState<Video[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchVideos();
  }, [category]);

  const fetchVideos = async () => {
    const client = supabase;

    if (!client) {
      setError('Video catalogue is temporarily unavailable.');
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      let query = client
        .from('videos')
        .select('*')
        .order('created_at', { ascending: false });

      if (category && category !== 'all') {
        query = query.eq('category', category);
      }

      const { data, error: fetchError } = await query;

      if (fetchError) throw fetchError;
      setVideos(data || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch videos');
      console.error('Error fetching videos:', err);
    } finally {
      setLoading(false);
    }
  };

  return { videos, loading, error };
};

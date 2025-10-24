import { useState, useEffect } from 'react';
import { supabase } from '../services/supabase';
import { Product, SearchParams } from '../types';

export const useProducts = (params?: SearchParams) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [totalCount, setTotalCount] = useState(0);

  useEffect(() => {
    fetchProducts();
  }, [params?.query, params?.category, params?.filters, params?.sort, params?.page]);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      setError(null);

      let query = supabase
        .from('products')
        .select('*', { count: 'exact' });

      if (params?.query) {
        query = query.or(`title.ilike.%${params.query}%,description.ilike.%${params.query}%,subject.ilike.%${params.query}%`);
      }

      if (params?.filters?.syllabus && params.filters.syllabus.length > 0) {
        query = query.in('syllabus', params.filters.syllabus);
      }

      if (params?.filters?.level && params.filters.level.length > 0) {
        query = query.in('level', params.filters.level);
      }

      if (params?.filters?.subject && params.filters.subject.length > 0) {
        query = query.in('subject', params.filters.subject);
      }

      if (params?.filters?.price_min) {
        query = query.gte('price_usd', params.filters.price_min);
      }

      if (params?.filters?.price_max) {
        query = query.lte('price_usd', params.filters.price_max);
      }

      if (params?.filters?.stock_status && params.filters.stock_status.length > 0) {
        query = query.in('stock_status', params.filters.stock_status);
      }

      switch (params?.sort) {
        case 'price_asc':
          query = query.order('price_usd', { ascending: true });
          break;
        case 'price_desc':
          query = query.order('price_usd', { ascending: false });
          break;
        case 'newest':
          query = query.order('created_at', { ascending: false });
          break;
        case 'alphabetical':
          query = query.order('title', { ascending: true });
          break;
        default:
          query = query.order('featured', { ascending: false }).order('created_at', { ascending: false });
      }

      const page = params?.page || 1;
      const perPage = params?.per_page || 12;
      const start = (page - 1) * perPage;
      const end = start + perPage - 1;

      query = query.range(start, end);

      const { data, error: fetchError, count } = await query;

      if (fetchError) throw fetchError;

      setProducts(data || []);
      setTotalCount(count || 0);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch products');
      console.error('Error fetching products:', err);
    } finally {
      setLoading(false);
    }
  };

  return { products, loading, error, totalCount, refetch: fetchProducts };
};

export const useFeaturedProducts = () => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchFeaturedProducts();
  }, []);

  const fetchFeaturedProducts = async () => {
    try {
      const { data, error } = await supabase
        .from('products')
        .select('*')
        .eq('featured', true)
        .limit(6);

      if (error) throw error;
      setProducts(data || []);
    } catch (err) {
      console.error('Error fetching featured products:', err);
    } finally {
      setLoading(false);
    }
  };

  return { products, loading };
};

export const useProduct = (slug: string) => {
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (slug) {
      fetchProduct();
    }
  }, [slug]);

  const fetchProduct = async () => {
    try {
      setLoading(true);
      const { data, error: fetchError } = await supabase
        .from('products')
        .select('*')
        .eq('slug', slug)
        .maybeSingle();

      if (fetchError) throw fetchError;
      setProduct(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch product');
      console.error('Error fetching product:', err);
    } finally {
      setLoading(false);
    }
  };

  return { product, loading, error };
};

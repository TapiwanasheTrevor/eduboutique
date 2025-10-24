import { Product, Category, Inquiry, ContactForm, Video, SearchParams, PaginatedResponse, ApiResponse } from '../types';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiService {
  private async request<T>(endpoint: string, options?: RequestInit): Promise<T> {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options?.headers,
      },
    });

    if (!response.ok) {
      throw new Error(`API Error: ${response.statusText}`);
    }

    return response.json();
  }

  async getProducts(params?: SearchParams): Promise<PaginatedResponse<Product>> {
    const queryParams = new URLSearchParams();

    if (params?.query) queryParams.append('query', params.query);
    if (params?.category) queryParams.append('category', params.category);
    if (params?.sort) queryParams.append('sort', params.sort);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString());

    if (params?.filters) {
      if (params.filters.syllabus) {
        params.filters.syllabus.forEach(s => queryParams.append('syllabus[]', s));
      }
      if (params.filters.level) {
        params.filters.level.forEach(l => queryParams.append('level[]', l));
      }
      if (params.filters.subject) {
        params.filters.subject.forEach(s => queryParams.append('subject[]', s));
      }
      if (params.filters.price_min) {
        queryParams.append('price_min', params.filters.price_min.toString());
      }
      if (params.filters.price_max) {
        queryParams.append('price_max', params.filters.price_max.toString());
      }
    }

    const queryString = queryParams.toString();
    const endpoint = `/products${queryString ? `?${queryString}` : ''}`;

    return this.request<PaginatedResponse<Product>>(endpoint);
  }

  async getProduct(slug: string): Promise<ApiResponse<Product>> {
    return this.request<ApiResponse<Product>>(`/products/${slug}`);
  }

  async getFeaturedProducts(): Promise<ApiResponse<Product[]>> {
    return this.request<ApiResponse<Product[]>>('/products/featured');
  }

  async getCategories(): Promise<ApiResponse<Category[]>> {
    return this.request<ApiResponse<Category[]>>('/categories');
  }

  async getCategory(slug: string): Promise<ApiResponse<Category>> {
    return this.request<ApiResponse<Category>>(`/categories/${slug}`);
  }

  async submitInquiry(inquiry: Inquiry): Promise<ApiResponse<Inquiry>> {
    return this.request<ApiResponse<Inquiry>>('/inquiries', {
      method: 'POST',
      body: JSON.stringify(inquiry),
    });
  }

  async submitContactForm(form: ContactForm): Promise<ApiResponse<void>> {
    return this.request<ApiResponse<void>>('/contact', {
      method: 'POST',
      body: JSON.stringify(form),
    });
  }

  async getVideos(category?: string): Promise<ApiResponse<Video[]>> {
    const endpoint = category ? `/videos?category=${category}` : '/videos';
    return this.request<ApiResponse<Video[]>>(endpoint);
  }

  async searchProducts(query: string): Promise<ApiResponse<Product[]>> {
    return this.request<ApiResponse<Product[]>>(`/products/search?q=${encodeURIComponent(query)}`);
  }
}

export const apiService = new ApiService();

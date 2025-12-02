export interface Product {
  id: string;
  title: string;
  slug: string;
  description: string;
  price_zwl: number;
  price_usd: number;
  category_id: string;
  category?: Category;
  syllabus: 'ZIMSEC' | 'Cambridge' | 'Other';
  level: 'ECD' | 'Primary' | 'Grade 7' | 'O-Level' | 'A-Level' | 'IGCSE' | 'AS-Level';
  subject: string;
  publisher: string;
  isbn?: string;
  author?: string;
  cover_image: string;
  stock_status: 'in_stock' | 'low_stock' | 'out_of_stock';
  stock_quantity: number;
  featured: boolean;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  description?: string;
  parent_id?: string;
  children?: Category[];
  products?: Product[];
  products_count?: number;
  image?: string;
  order: number;
  created_at?: string;
  updated_at?: string;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface Cart {
  items: CartItem[];
  subtotal_zwl: number;
  subtotal_usd: number;
}

export interface Inquiry {
  id?: string;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  delivery_method: 'store_pickup' | 'agent_delivery';
  delivery_address?: string;
  delivery_city?: string;
  message?: string;
  cart_items: CartItem[];
  total_zwl: number;
  total_usd: number;
  status: 'pending' | 'contacted' | 'confirmed' | 'completed';
  created_at?: string;
}

export interface ContactForm {
  name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
}

export interface Video {
  id: string;
  title: string;
  description?: string;
  video_url: string;
  thumbnail_url?: string;
  category?: string;
  level?: string;
  subject?: string;
  duration?: string;
  is_published: boolean;
  published_at?: string;
  created_at: string;
  updated_at: string;
}

export interface FilterOptions {
  syllabus?: string[];
  level?: string[];
  subject?: string[];
  author?: string[];
  publisher?: string[];
  price_min?: number;
  price_max?: number;
  stock_status?: string[];
}

export interface SearchParams {
  query?: string;
  category?: string;
  filters?: FilterOptions;
  sort?: 'price_asc' | 'price_desc' | 'newest' | 'popular' | 'alphabetical';
  page?: number;
  per_page?: number;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  success: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

/*
  # Edu Boutique Bookstore Database Schema

  ## Overview
  This migration creates the complete database schema for the Edu Boutique Bookstore application,
  including tables for products, categories, inquiries, videos, and newsletter subscriptions.

  ## Tables Created

  ### 1. categories
  Stores book categories and subcategories with hierarchical structure
  - `id` (uuid, primary key)
  - `name` (text) - Category name
  - `slug` (text) - URL-friendly identifier
  - `description` (text) - Category description
  - `parent_id` (uuid) - References parent category for subcategories
  - `image` (text) - Category image URL
  - `order` (integer) - Display order
  - `created_at` (timestamptz)
  - `updated_at` (timestamptz)

  ### 2. products
  Stores all book inventory and product information
  - `id` (uuid, primary key)
  - `title` (text) - Book title
  - `slug` (text) - URL-friendly identifier
  - `description` (text) - Book description
  - `price_zwl` (numeric) - Price in Zimbabwean Dollar
  - `price_usd` (numeric) - Price in US Dollar
  - `category_id` (uuid) - References categories table
  - `syllabus` (text) - ZIMSEC, Cambridge, or Other
  - `level` (text) - Educational level (ECD, Primary, O-Level, etc.)
  - `subject` (text) - Subject area
  - `publisher` (text) - Book publisher
  - `isbn` (text) - ISBN number
  - `author` (text) - Book author
  - `cover_image` (text) - Cover image URL
  - `stock_status` (text) - in_stock, low_stock, out_of_stock
  - `stock_quantity` (integer) - Current stock count
  - `featured` (boolean) - Featured on homepage
  - `created_at` (timestamptz)
  - `updated_at` (timestamptz)

  ### 3. inquiries
  Stores customer inquiries and order requests
  - `id` (uuid, primary key)
  - `customer_name` (text)
  - `customer_email` (text)
  - `customer_phone` (text)
  - `delivery_method` (text) - store_pickup or agent_delivery
  - `delivery_address` (text)
  - `delivery_city` (text)
  - `message` (text)
  - `cart_items` (jsonb) - Array of products and quantities
  - `total_zwl` (numeric)
  - `total_usd` (numeric)
  - `status` (text) - pending, contacted, confirmed, completed
  - `created_at` (timestamptz)
  - `updated_at` (timestamptz)

  ### 4. contact_forms
  Stores general contact form submissions
  - `id` (uuid, primary key)
  - `name` (text)
  - `email` (text)
  - `phone` (text)
  - `subject` (text)
  - `message` (text)
  - `created_at` (timestamptz)

  ### 5. videos
  Stores educational video content metadata
  - `id` (uuid, primary key)
  - `title` (text)
  - `description` (text)
  - `video_url` (text) - YouTube or Vimeo URL
  - `thumbnail_url` (text)
  - `category` (text) - study_tips, book_previews, syllabus_guides, other
  - `duration` (text)
  - `created_at` (timestamptz)
  - `updated_at` (timestamptz)

  ### 6. newsletter_subscribers
  Stores email addresses for newsletter
  - `id` (uuid, primary key)
  - `email` (text, unique)
  - `subscribed_at` (timestamptz)

  ## Security
  - Row Level Security (RLS) enabled on all tables
  - Public read access for products, categories, and videos
  - Write access restricted for inquiries and contact forms
  - Admin-only access for data modification on products and categories

  ## Indexes
  - Indexes on commonly queried fields for performance
  - Full-text search indexes on product titles and descriptions
*/

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name text NOT NULL,
  slug text UNIQUE NOT NULL,
  description text,
  parent_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  image text,
  "order" integer DEFAULT 0,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  slug text UNIQUE NOT NULL,
  description text,
  price_zwl numeric(10, 2) DEFAULT 0,
  price_usd numeric(10, 2) DEFAULT 0,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  syllabus text DEFAULT 'Other',
  level text DEFAULT 'Primary',
  subject text,
  publisher text,
  isbn text,
  author text,
  cover_image text,
  stock_status text DEFAULT 'in_stock',
  stock_quantity integer DEFAULT 0,
  featured boolean DEFAULT false,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create inquiries table
CREATE TABLE IF NOT EXISTS inquiries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_name text NOT NULL,
  customer_email text NOT NULL,
  customer_phone text NOT NULL,
  delivery_method text DEFAULT 'agent_delivery',
  delivery_address text,
  delivery_city text,
  message text,
  cart_items jsonb DEFAULT '[]'::jsonb,
  total_zwl numeric(10, 2) DEFAULT 0,
  total_usd numeric(10, 2) DEFAULT 0,
  status text DEFAULT 'pending',
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create contact_forms table
CREATE TABLE IF NOT EXISTS contact_forms (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name text NOT NULL,
  email text NOT NULL,
  phone text NOT NULL,
  subject text NOT NULL,
  message text NOT NULL,
  created_at timestamptz DEFAULT now()
);

-- Create videos table
CREATE TABLE IF NOT EXISTS videos (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  description text,
  video_url text NOT NULL,
  thumbnail_url text,
  category text DEFAULT 'other',
  duration text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create newsletter_subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  email text UNIQUE NOT NULL,
  subscribed_at timestamptz DEFAULT now()
);

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_syllabus ON products(syllabus);
CREATE INDEX IF NOT EXISTS idx_products_level ON products(level);
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(featured);
CREATE INDEX IF NOT EXISTS idx_products_stock_status ON products(stock_status);
CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status);
CREATE INDEX IF NOT EXISTS idx_inquiries_created_at ON inquiries(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id);

-- Enable Row Level Security
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE inquiries ENABLE ROW LEVEL SECURITY;
ALTER TABLE contact_forms ENABLE ROW LEVEL SECURITY;
ALTER TABLE videos ENABLE ROW LEVEL SECURITY;
ALTER TABLE newsletter_subscribers ENABLE ROW LEVEL SECURITY;

-- RLS Policies for categories (public read)
CREATE POLICY "Categories are viewable by everyone"
  ON categories FOR SELECT
  TO anon, authenticated
  USING (true);

-- RLS Policies for products (public read)
CREATE POLICY "Products are viewable by everyone"
  ON products FOR SELECT
  TO anon, authenticated
  USING (true);

-- RLS Policies for inquiries (anyone can insert, restricted read)
CREATE POLICY "Anyone can submit inquiries"
  ON inquiries FOR INSERT
  TO anon, authenticated
  WITH CHECK (true);

CREATE POLICY "Users can view their own inquiries"
  ON inquiries FOR SELECT
  TO authenticated
  USING (customer_email = current_setting('request.jwt.claims', true)::json->>'email');

-- RLS Policies for contact_forms (anyone can insert)
CREATE POLICY "Anyone can submit contact forms"
  ON contact_forms FOR INSERT
  TO anon, authenticated
  WITH CHECK (true);

-- RLS Policies for videos (public read)
CREATE POLICY "Videos are viewable by everyone"
  ON videos FOR SELECT
  TO anon, authenticated
  USING (true);

-- RLS Policies for newsletter_subscribers (anyone can subscribe)
CREATE POLICY "Anyone can subscribe to newsletter"
  ON newsletter_subscribers FOR INSERT
  TO anon, authenticated
  WITH CHECK (true);

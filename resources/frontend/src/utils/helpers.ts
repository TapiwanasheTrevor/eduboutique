import { WHATSAPP_BASE_URL, WHATSAPP_NUMBER } from './constants';
import { CartItem } from '../types';

export const formatCurrency = (amount: number, currency: 'ZWL' | 'USD'): string => {
  return new Intl.NumberFormat('en-ZW', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
  }).format(amount);
};

export const generateWhatsAppLink = (message: string): string => {
  const encodedMessage = encodeURIComponent(message);
  return `${WHATSAPP_BASE_URL}${WHATSAPP_NUMBER}?text=${encodedMessage}`;
};

export const generateProductWhatsAppMessage = (productTitle: string): string => {
  return `Hello! I'm interested in ordering: ${productTitle}`;
};

export const generateCartWhatsAppMessage = (items: CartItem[], totalZWL: number, totalUSD: number): string => {
  let message = 'Hello! I would like to order the following books:\n\n';

  items.forEach((item, index) => {
    message += `${index + 1}. ${item.product.title} (x${item.quantity})\n`;
  });

  message += `\nTotal: ${formatCurrency(totalZWL, 'ZWL')} / ${formatCurrency(totalUSD, 'USD')}`;

  return message;
};

export const truncateText = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

export const slugify = (text: string): string => {
  return text
    .toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/--+/g, '-')
    .trim();
};

export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout;
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

export const calculateCartTotals = (items: CartItem[]): { zwl: number; usd: number } => {
  return items.reduce(
    (acc, item) => ({
      zwl: acc.zwl + item.product.price_zwl * item.quantity,
      usd: acc.usd + item.product.price_usd * item.quantity,
    }),
    { zwl: 0, usd: 0 }
  );
};

export const getStockBadgeColor = (status: string): string => {
  switch (status) {
    case 'in_stock':
      return 'bg-green-100 text-green-800';
    case 'low_stock':
      return 'bg-yellow-100 text-yellow-800';
    case 'out_of_stock':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

export const getStockLabel = (status: string): string => {
  switch (status) {
    case 'in_stock':
      return 'In Stock';
    case 'low_stock':
      return 'Low Stock';
    case 'out_of_stock':
      return 'Out of Stock';
    default:
      return 'Unknown';
  }
};

export const getSyllabusBadgeColor = (syllabus: string): string => {
  switch (syllabus) {
    case 'ZIMSEC':
      return 'bg-green-600 text-white';
    case 'Cambridge':
      return 'bg-blue-600 text-white';
    default:
      return 'bg-gray-600 text-white';
  }
};

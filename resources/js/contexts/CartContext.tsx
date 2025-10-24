import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { Cart, CartItem, Product } from '../types';
import { calculateCartTotals } from '../utils/helpers';

interface CartContextType {
  cart: Cart;
  addToCart: (product: Product, quantity?: number) => void;
  removeFromCart: (productId: string) => void;
  updateQuantity: (productId: string, quantity: number) => void;
  clearCart: () => void;
  getItemCount: () => number;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

const CART_STORAGE_KEY = 'edu_boutique_cart';
const EMPTY_CART: Cart = { items: [], subtotal_zwl: 0, subtotal_usd: 0 };

const getSavedCart = () => {
  if (typeof window === 'undefined') {
    return EMPTY_CART;
  }

  const savedCart = window.localStorage.getItem(CART_STORAGE_KEY);

  if (!savedCart) {
    return EMPTY_CART;
  }

  try {
    const parsed = JSON.parse(savedCart) as Cart;
    return {
      ...EMPTY_CART,
      ...parsed,
      items: parsed.items ?? [],
    };
  } catch {
    return EMPTY_CART;
  }
};

export const CartProvider = ({ children }: { children: ReactNode }) => {
  const [cart, setCart] = useState<Cart>(() => getSavedCart());

  useEffect(() => {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    }
  }, [cart]);

  const addToCart = (product: Product, quantity: number = 1) => {
    setCart((prevCart) => {
      const existingItemIndex = prevCart.items.findIndex(
        (item) => item.product.id === product.id
      );

      let newItems: CartItem[];
      if (existingItemIndex > -1) {
        newItems = [...prevCart.items];
        newItems[existingItemIndex].quantity += quantity;
      } else {
        newItems = [...prevCart.items, { product, quantity }];
      }

      const totals = calculateCartTotals(newItems);

      return {
        items: newItems,
        subtotal_zwl: totals.zwl,
        subtotal_usd: totals.usd,
      };
    });
  };

  const removeFromCart = (productId: string) => {
    setCart((prevCart) => {
      const newItems = prevCart.items.filter((item) => item.product.id !== productId);
      const totals = calculateCartTotals(newItems);

      return {
        items: newItems,
        subtotal_zwl: totals.zwl,
        subtotal_usd: totals.usd,
      };
    });
  };

  const updateQuantity = (productId: string, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }

    setCart((prevCart) => {
      const newItems = prevCart.items.map((item) =>
        item.product.id === productId ? { ...item, quantity } : item
      );
      const totals = calculateCartTotals(newItems);

      return {
        items: newItems,
        subtotal_zwl: totals.zwl,
        subtotal_usd: totals.usd,
      };
    });
  };

  const clearCart = () => {
    setCart(EMPTY_CART);
  };

  const getItemCount = () => {
    return cart.items.reduce((total, item) => total + item.quantity, 0);
  };

  return (
    <CartContext.Provider
      value={{
        cart,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getItemCount,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

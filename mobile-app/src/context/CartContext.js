import React, { createContext, useContext, useEffect, useState } from 'react';
import { cartAPI } from '../services/api';
import { useAuth } from './AuthContext';

const CartContext = createContext({});

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [cartCount, setCartCount] = useState(0);
  const [cartTotal, setCartTotal] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const { isAuthenticated } = useAuth();

  useEffect(() => {
    if (isAuthenticated) {
      loadCart();
    } else {
      // Clear cart when user logs out
      setCartItems([]);
      setCartCount(0);
      setCartTotal(0);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    // Calculate totals whenever cart items change
    const count = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const total = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    setCartCount(count);
    setCartTotal(total);
  }, [cartItems]);

  const loadCart = async () => {
    if (!isAuthenticated) return;
    
    try {
      setIsLoading(true);
      const response = await cartAPI.getCart();
      
      if (response.success) {
        setCartItems(response.items || []);
      }
    } catch (error) {
      console.error('Error loading cart:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const addToCart = async (productId, quantity = 1) => {
    if (!isAuthenticated) {
      throw new Error('Please log in to add items to cart');
    }

    try {
      const response = await cartAPI.addToCart(productId, quantity);
      
      if (response.success) {
        await loadCart(); // Refresh cart
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
      return { 
        success: false, 
        error: error.response?.data?.error || 'Failed to add item to cart' 
      };
    }
  };

  const updateCartItem = async (productId, quantity) => {
    if (!isAuthenticated) return;

    try {
      const response = await cartAPI.updateCart(productId, quantity);
      
      if (response.success) {
        await loadCart(); // Refresh cart
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      console.error('Error updating cart item:', error);
      return { 
        success: false, 
        error: error.response?.data?.error || 'Failed to update cart item' 
      };
    }
  };

  const removeFromCart = async (productId) => {
    if (!isAuthenticated) return;

    try {
      const response = await cartAPI.removeFromCart(productId);
      
      if (response.success) {
        await loadCart(); // Refresh cart
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      console.error('Error removing from cart:', error);
      return { 
        success: false, 
        error: error.response?.data?.error || 'Failed to remove item from cart' 
      };
    }
  };

  const clearCart = async () => {
    if (!isAuthenticated) return;

    try {
      const response = await cartAPI.clearCart();
      
      if (response.success) {
        setCartItems([]);
        return { success: true };
      } else {
        return { success: false, error: response.error };
      }
    } catch (error) {
      console.error('Error clearing cart:', error);
      return { 
        success: false, 
        error: error.response?.data?.error || 'Failed to clear cart' 
      };
    }
  };

  const value = {
    cartItems,
    cartCount,
    cartTotal,
    isLoading,
    loadCart,
    addToCart,
    updateCartItem,
    removeFromCart,
    clearCart,
  };

  return (
    <CartContext.Provider value={value}>
      {children}
    </CartContext.Provider>
  );
};

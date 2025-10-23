import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

// Configure your backend URL here
const BASE_URL = 'http://192.168.0.226/m-ecommerce-php'; // Updated to match your current IP
// For testing on physical device, use your computer's IP address like: 'http://192.168.1.100/m-ecommerce-php'

const api = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add request interceptor to include auth token
api.interceptors.request.use(
  async (config) => {
    const token = await SecureStore.getItemAsync('userToken');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      await SecureStore.deleteItemAsync('userToken');
      await SecureStore.deleteItemAsync('userData');
    }
    return Promise.reject(error);
  }
);

export const authAPI = {
  login: async (email, password) => {
    const response = await api.post('/api/auth.php', {
      action: 'login',
      email,
      password,
    });
    return response.data;
  },

  register: async (name, email, password) => {
    const response = await api.post('/api/auth.php', {
      action: 'register',
      name,
      email,
      password,
    });
    return response.data;
  },

  logout: async () => {
    const response = await api.post('/api/auth.php', {
      action: 'logout',
    });
    return response.data;
  },

  getCurrentUser: async () => {
    const response = await api.get('/api/auth.php?action=current_user');
    return response.data;
  },
};

export const productsAPI = {
  getProducts: async (page = 1, limit = 20, category = null, search = null) => {
    const params = new URLSearchParams({
      page: page.toString(),
      limit: limit.toString(),
    });
    
    if (category) params.append('category', category);
    if (search) params.append('search', search);
    
    const response = await api.get(`/api/products.php?${params}`);
    return response.data;
  },

  getProduct: async (id) => {
    const response = await api.get(`/api/products.php?id=${id}`);
    return response.data;
  },

  getCategories: async () => {
    const response = await api.get('/api/products.php?action=categories');
    return response.data;
  },
};

export const cartAPI = {
  getCart: async () => {
    const response = await api.get('/api/cart.php');
    return response.data;
  },

  addToCart: async (productId, quantity = 1) => {
    const response = await api.post('/api/cart.php', {
      action: 'add',
      product_id: productId,
      quantity,
    });
    return response.data;
  },

  updateCart: async (productId, quantity) => {
    const response = await api.post('/api/cart.php', {
      action: 'update',
      product_id: productId,
      quantity,
    });
    return response.data;
  },

  removeFromCart: async (productId) => {
    const response = await api.post('/api/cart.php', {
      action: 'remove',
      product_id: productId,
    });
    return response.data;
  },

  clearCart: async () => {
    const response = await api.post('/api/cart.php', {
      action: 'clear',
    });
    return response.data;
  },
};

export const ordersAPI = {
  getOrders: async () => {
    const response = await api.get('/api/orders.php');
    return response.data;
  },

  createOrder: async (orderData) => {
    const response = await api.post('/api/orders.php', orderData);
    return response.data;
  },

  getOrder: async (id) => {
    const response = await api.get(`/api/orders.php?id=${id}`);
    return response.data;
  },
};

export const supportAPI = {
  getTickets: async () => {
    const response = await api.get('/api/support.php');
    return response.data;
  },

  createTicket: async (ticketData) => {
    const response = await api.post('/api/support.php', ticketData);
    return response.data;
  },

  getTicket: async (id) => {
    const response = await api.get(`/api/support.php?id=${id}`);
    return response.data;
  },
};

// Helper function to format currency in Taka
export const formatCurrency = (amount) => {
  return `৳${parseFloat(amount || 0).toFixed(2)}`;
};

export default api;

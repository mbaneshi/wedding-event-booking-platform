import axios, { AxiosInstance, AxiosError } from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
      },
      withCredentials: true,
    });

    this.client.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  async get<T>(url: string, params?: any): Promise<T> {
    const response = await this.client.get<T>(url, { params });
    return response.data;
  }

  async post<T>(url: string, data?: any): Promise<T> {
    const response = await this.client.post<T>(url, data);
    return response.data;
  }

  async put<T>(url: string, data?: any): Promise<T> {
    const response = await this.client.put<T>(url, data);
    return response.data;
  }

  async delete<T>(url: string): Promise<T> {
    const response = await this.client.delete<T>(url);
    return response.data;
  }
}

export const api = new ApiClient();

// Auth API
export const authApi = {
  login: (email: string, password: string) =>
    api.post('/auth/login', { email, password }),
  register: (data: any) => api.post('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};

// Vendor API
export const vendorApi = {
  search: (filters: any) => api.get('/vendors', filters),
  getById: (id: string) => api.get(`/vendors/${id}`),
  create: (data: any) => api.post('/vendors', data),
  update: (id: string, data: any) => api.put(`/vendors/${id}`, data),
  getServices: (id: string) => api.get(`/vendors/${id}/services`),
  getReviews: (id: string) => api.get(`/vendors/${id}/reviews`),
  getAvailability: (id: string, month: string) =>
    api.get(`/vendors/${id}/availability`, { month }),
};

// Booking API
export const bookingApi = {
  create: (data: any) => api.post('/bookings', data),
  getById: (id: string) => api.get(`/bookings/${id}`),
  getMyBookings: () => api.get('/bookings/my'),
  cancel: (id: string, reason: string) =>
    api.post(`/bookings/${id}/cancel`, { reason }),
};

// Payment API
export const paymentApi = {
  createIntent: (bookingId: string, amount: number) =>
    api.post('/payments/create-intent', { bookingId, amount }),
  confirm: (paymentIntentId: string) =>
    api.post('/payments/confirm', { paymentIntentId }),
};

// Review API
export const reviewApi = {
  create: (data: any) => api.post('/reviews', data),
  respond: (id: string, response: string) =>
    api.post(`/reviews/${id}/respond`, { response }),
};

// Category API
export const categoryApi = {
  getAll: () => api.get('/categories'),
};

// Favorites API
export const favoriteApi = {
  toggle: (vendorId: string) => api.post('/favorites/toggle', { vendorId }),
  getMyFavorites: () => api.get('/favorites'),
};

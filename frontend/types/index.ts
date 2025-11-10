export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  phone?: string;
  role: 'customer' | 'vendor' | 'admin';
  emailVerifiedAt?: Date;
  createdAt: Date;
  updatedAt: Date;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  icon?: string;
  parentId?: string;
  sortOrder: number;
  createdAt: Date;
}

export interface Vendor {
  id: string;
  userId: string;
  businessName: string;
  slug: string;
  description?: string;
  logoUrl?: string;
  coverImageUrl?: string;
  categoryId: string;
  category?: Category;
  phone?: string;
  email?: string;
  website?: string;
  address?: string;
  city?: string;
  region?: string;
  country: string;
  latitude?: number;
  longitude?: number;
  yearsInBusiness?: number;
  licenseNumber?: string;
  status: 'pending' | 'approved' | 'suspended' | 'rejected';
  verified: boolean;
  featured: boolean;
  ratingAverage: number;
  ratingCount: number;
  viewCount: number;
  services?: Service[];
  media?: Media[];
  reviews?: Review[];
  createdAt: Date;
  updatedAt: Date;
}

export interface Service {
  id: string;
  vendorId: string;
  name: string;
  description?: string;
  priceFrom?: number;
  priceTo?: number;
  currency: string;
  pricingType: 'fixed' | 'hourly' | 'per_person' | 'package';
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface Media {
  id: string;
  vendorId: string;
  type: 'photo' | 'video';
  url: string;
  thumbnailUrl?: string;
  caption?: string;
  sortOrder: number;
  createdAt: Date;
}

export interface Booking {
  id: string;
  bookingNumber: string;
  customerId: string;
  customer?: User;
  vendorId: string;
  vendor?: Vendor;
  serviceId: string;
  service?: Service;
  eventDate: Date;
  eventTime?: string;
  eventType?: string;
  guestCount?: number;
  venueName?: string;
  venueAddress?: string;
  servicePrice: number;
  extrasPrice: number;
  totalPrice: number;
  currency: string;
  commissionRate: number;
  commissionAmount: number;
  depositAmount?: number;
  depositPaid: boolean;
  depositPaidAt?: Date;
  balanceAmount?: number;
  balancePaid: boolean;
  balancePaidAt?: Date;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'refunded';
  specialRequests?: string;
  cancellationReason?: string;
  cancelledBy?: string;
  cancelledAt?: Date;
  payments?: Payment[];
  createdAt: Date;
  updatedAt: Date;
}

export interface Payment {
  id: string;
  bookingId: string;
  stripePaymentIntentId?: string;
  amount: number;
  currency: string;
  paymentMethod: 'card' | 'paypal' | 'bank_transfer';
  status: 'pending' | 'succeeded' | 'failed' | 'refunded';
  paymentType: 'deposit' | 'balance' | 'full';
  refunded: boolean;
  refundAmount?: number;
  refundReason?: string;
  refundedAt?: Date;
  createdAt: Date;
  updatedAt: Date;
}

export interface Review {
  id: string;
  bookingId: string;
  customerId: string;
  customer?: User;
  vendorId: string;
  rating: number;
  title?: string;
  comment?: string;
  response?: string;
  responseAt?: Date;
  status: 'published' | 'hidden' | 'flagged';
  createdAt: Date;
  updatedAt: Date;
}

export interface Availability {
  id: string;
  vendorId: string;
  date: Date;
  isAvailable: boolean;
  reason?: string;
  createdAt: Date;
}

export interface Message {
  id: string;
  bookingId?: string;
  senderId: string;
  sender?: User;
  receiverId: string;
  receiver?: User;
  message: string;
  read: boolean;
  readAt?: Date;
  createdAt: Date;
}

export interface Favorite {
  id: string;
  userId: string;
  vendorId: string;
  vendor?: Vendor;
  createdAt: Date;
}

export interface SearchFilters {
  category?: string;
  city?: string;
  priceMin?: number;
  priceMax?: number;
  date?: string;
  minRating?: number;
  sortBy?: 'rating' | 'price' | 'popularity' | 'relevance';
  page?: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
}

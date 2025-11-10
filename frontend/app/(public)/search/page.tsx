'use client';

import { useState, useEffect } from 'react';
import { Search, MapPin, Star, Calendar, Filter } from 'lucide-react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { vendorApi } from '@/lib/api';
import { Vendor, SearchFilters } from '@/types';
import { formatPrice } from '@/lib/utils';

export default function SearchPage() {
  const [vendors, setVendors] = useState<Vendor[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState<SearchFilters>({
    sortBy: 'rating',
  });

  useEffect(() => {
    loadVendors();
  }, [filters]);

  const loadVendors = async () => {
    try {
      setLoading(true);
      const data = await vendorApi.search(filters);
      setVendors(data.data);
    } catch (error) {
      console.error('Failed to load vendors:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Search Header */}
      <div className="bg-white border-b py-6">
        <div className="container mx-auto px-4">
          <div className="flex gap-4">
            <Input
              type="text"
              placeholder="Search vendors..."
              className="flex-1"
              onChange={(e) =>
                setFilters({ ...filters, query: e.target.value })
              }
            />
            <Input
              type="text"
              placeholder="Location"
              className="w-64"
              onChange={(e) => setFilters({ ...filters, city: e.target.value })}
            />
            <Button>
              <Search className="mr-2 h-4 w-4" />
              Search
            </Button>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid lg:grid-cols-4 gap-8">
          {/* Filters Sidebar */}
          <div className="lg:col-span-1">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <Filter className="mr-2 h-5 w-5" />
                  Filters
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-2">
                    Category
                  </label>
                  <select
                    className="w-full border rounded-md p-2"
                    onChange={(e) =>
                      setFilters({ ...filters, category: e.target.value })
                    }
                  >
                    <option value="">All Categories</option>
                    <option value="venues">Venues</option>
                    <option value="photographers">Photographers</option>
                    <option value="catering">Catering</option>
                    <option value="florists">Florists</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    Price Range
                  </label>
                  <div className="flex gap-2">
                    <Input
                      type="number"
                      placeholder="Min"
                      onChange={(e) =>
                        setFilters({
                          ...filters,
                          priceMin: Number(e.target.value),
                        })
                      }
                    />
                    <Input
                      type="number"
                      placeholder="Max"
                      onChange={(e) =>
                        setFilters({
                          ...filters,
                          priceMax: Number(e.target.value),
                        })
                      }
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    Minimum Rating
                  </label>
                  <select
                    className="w-full border rounded-md p-2"
                    onChange={(e) =>
                      setFilters({
                        ...filters,
                        minRating: Number(e.target.value),
                      })
                    }
                  >
                    <option value="">Any Rating</option>
                    <option value="4">4+ Stars</option>
                    <option value="4.5">4.5+ Stars</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    <Calendar className="inline mr-2 h-4 w-4" />
                    Event Date
                  </label>
                  <Input
                    type="date"
                    onChange={(e) =>
                      setFilters({ ...filters, date: e.target.value })
                    }
                  />
                </div>

                <Button className="w-full" onClick={loadVendors}>
                  Apply Filters
                </Button>
              </CardContent>
            </Card>
          </div>

          {/* Results */}
          <div className="lg:col-span-3">
            <div className="flex justify-between items-center mb-6">
              <h1 className="text-2xl font-bold">
                {vendors.length} Vendors Found
              </h1>
              <select
                className="border rounded-md p-2"
                value={filters.sortBy}
                onChange={(e) =>
                  setFilters({ ...filters, sortBy: e.target.value as any })
                }
              >
                <option value="rating">Highest Rated</option>
                <option value="price">Price: Low to High</option>
                <option value="popularity">Most Popular</option>
              </select>
            </div>

            {loading ? (
              <div className="text-center py-12">Loading...</div>
            ) : vendors.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                No vendors found. Try adjusting your filters.
              </div>
            ) : (
              <div className="grid gap-6">
                {vendors.map((vendor) => (
                  <VendorCard key={vendor.id} vendor={vendor} />
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function VendorCard({ vendor }: { vendor: Vendor }) {
  const minPrice = vendor.services?.[0]?.priceFrom || 0;

  return (
    <Link href={`/vendors/${vendor.id}`}>
      <Card className="hover:shadow-lg transition-shadow cursor-pointer">
        <div className="grid md:grid-cols-4 gap-4">
          <div className="md:col-span-1">
            <img
              src={vendor.coverImageUrl || '/placeholder.jpg'}
              alt={vendor.businessName}
              className="w-full h-48 object-cover rounded-l-lg"
            />
          </div>
          <div className="md:col-span-3 p-6">
            <div className="flex justify-between items-start mb-2">
              <div>
                <h3 className="text-xl font-bold">{vendor.businessName}</h3>
                {vendor.verified && (
                  <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                    Verified
                  </span>
                )}
              </div>
              <div className="text-right">
                <div className="flex items-center text-yellow-500">
                  <Star className="h-5 w-5 fill-current" />
                  <span className="ml-1 font-semibold">
                    {vendor.ratingAverage.toFixed(1)}
                  </span>
                </div>
                <div className="text-xs text-gray-500">
                  {vendor.ratingCount} reviews
                </div>
              </div>
            </div>

            <div className="flex items-center text-gray-600 mb-2">
              <MapPin className="h-4 w-4 mr-1" />
              <span className="text-sm">
                {vendor.city}, {vendor.country}
              </span>
            </div>

            <p className="text-gray-600 mb-4 line-clamp-2">
              {vendor.description}
            </p>

            <div className="flex justify-between items-center">
              <div>
                <span className="text-sm text-gray-500">Starting from</span>
                <div className="text-2xl font-bold text-primary">
                  {formatPrice(minPrice, vendor.services?.[0]?.currency)}
                </div>
              </div>
              <Button>View Details</Button>
            </div>
          </div>
        </div>
      </Card>
    </Link>
  );
}

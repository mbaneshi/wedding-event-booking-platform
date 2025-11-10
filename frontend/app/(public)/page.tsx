'use client';

import Link from 'next/link';
import { Search, Star, Users, Calendar, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';

const categories = [
  { name: 'Wedding Venues', icon: '🏛️', slug: 'venues' },
  { name: 'Photographers', icon: '📷', slug: 'photographers' },
  { name: 'Catering', icon: '🍽️', slug: 'catering' },
  { name: 'Florists', icon: '💐', slug: 'florists' },
  { name: 'Music & DJ', icon: '🎵', slug: 'music' },
  { name: 'Decorators', icon: '🎨', slug: 'decorators' },
];

const features = [
  {
    icon: Search,
    title: 'Easy Search',
    description: 'Find the perfect vendors with advanced filters',
  },
  {
    icon: Star,
    title: 'Verified Reviews',
    description: 'Read authentic reviews from real customers',
  },
  {
    icon: Calendar,
    title: 'Instant Booking',
    description: 'Book and pay securely in just a few clicks',
  },
  {
    icon: Shield,
    title: 'Secure Payments',
    description: 'Your payments are protected with Stripe',
  },
];

export default function HomePage() {
  return (
    <div className="min-h-screen">
      {/* Header */}
      <header className="border-b">
        <div className="container mx-auto px-4 py-4">
          <nav className="flex items-center justify-between">
            <div className="text-2xl font-bold text-primary">
              WeddingBook
            </div>
            <div className="flex items-center gap-4">
              <Link href="/search">
                <Button variant="ghost">Find Vendors</Button>
              </Link>
              <Link href="/vendor/register">
                <Button variant="ghost">List Your Business</Button>
              </Link>
              <Link href="/login">
                <Button variant="outline">Login</Button>
              </Link>
              <Link href="/register">
                <Button>Sign Up</Button>
              </Link>
            </div>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="bg-gradient-to-r from-purple-600 to-pink-600 text-white py-20">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center">
            <h1 className="text-5xl font-bold mb-6">
              Find Perfect Vendors for Your Dream Wedding
            </h1>
            <p className="text-xl mb-8 opacity-90">
              Connect with trusted wedding professionals in Montenegro
            </p>
            <div className="flex gap-4 max-w-2xl mx-auto">
              <Input
                type="text"
                placeholder="Search venues, photographers, caterers..."
                className="flex-1 h-12 text-black"
              />
              <Button size="lg" variant="secondary">
                <Search className="mr-2 h-5 w-5" />
                Search
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="py-16">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">
            Browse by Category
          </h2>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {categories.map((category) => (
              <Link
                key={category.slug}
                href={`/search?category=${category.slug}`}
              >
                <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                  <CardContent className="p-6 text-center">
                    <div className="text-4xl mb-3">{category.icon}</div>
                    <h3 className="font-semibold">{category.name}</h3>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="bg-gray-50 py-16">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">
            Why Choose WeddingBook?
          </h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <div key={index} className="text-center">
                <div className="inline-flex items-center justify-center w-16 h-16 bg-primary text-white rounded-full mb-4">
                  <feature.icon className="h-8 w-8" />
                </div>
                <h3 className="text-xl font-semibold mb-2">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-16">
        <div className="container mx-auto px-4">
          <Card className="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
            <CardContent className="p-12 text-center">
              <h2 className="text-3xl font-bold mb-4">
                Are You a Wedding Vendor?
              </h2>
              <p className="text-xl mb-8 opacity-90">
                Join our platform and connect with couples planning their dream wedding
              </p>
              <Link href="/vendor/register">
                <Button size="lg" variant="secondary">
                  <Users className="mr-2 h-5 w-5" />
                  Register as Vendor
                </Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container mx-auto px-4">
          <div className="grid md:grid-cols-4 gap-8">
            <div>
              <h3 className="text-xl font-bold mb-4">WeddingBook</h3>
              <p className="text-gray-400">
                The ultimate wedding planning marketplace
              </p>
            </div>
            <div>
              <h4 className="font-semibold mb-4">For Couples</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/search">Find Vendors</Link></li>
                <li><Link href="/register">Sign Up</Link></li>
                <li><Link href="/how-it-works">How It Works</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">For Vendors</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/vendor/register">Register</Link></li>
                <li><Link href="/vendor/pricing">Pricing</Link></li>
                <li><Link href="/vendor/login">Vendor Login</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Support</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/contact">Contact Us</Link></li>
                <li><Link href="/faq">FAQ</Link></li>
                <li><Link href="/terms">Terms of Service</Link></li>
                <li><Link href="/privacy">Privacy Policy</Link></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2025 WeddingBook. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}

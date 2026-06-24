'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { 
  Loader2, 
  Search, 
  ArrowLeft, 
  Apple, 
  TrendingUp, 
  Flame, 
  Beef, 
  Cookie, 
  Droplet, 
  AlertCircle 
} from 'lucide-react';
import api from '@/src/utils/api';
import Toast from '@/src/components/Toast';

interface Nutrient {
  calories_kcal: number;
  protein_g: number;
  fat_g: number;
  carbs_g: number;
  fiber_g: number;
}

interface FoodItem {
  id: string;
  label: string;
  category: string;
  image: string | null;
  nutrients: Nutrient;
}

export default function NutritionPage() {
  const router = useRouter();
  const [query, setQuery] = useState('');
  const [items, setItems] = useState<FoodItem[]>([]);
  const [searchedQuery, setSearchedQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);

  useEffect(() => {
    const token = localStorage.getItem('medicare_token');
    if (!token) {
      router.push('/auth');
    }
  }, [router]);

  const showToast = (message: string, type: 'success' | 'error' | 'info') => {
    setToast({ message, type });
  };

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!query.trim()) {
      showToast('Kueri pencarian tidak boleh kosong', 'error');
      return;
    }

    setLoading(true);
    try {
      const response = await api.get('/v1/nutrition/search', {
        params: { query: query.trim() }
      });

      if (response.data.status === 'success') {
        setItems(response.data.data.items || []);
        setSearchedQuery(response.data.data.query);
        if (response.data.data.items.length === 0) {
          showToast('Makanan tidak ditemukan di database.', 'info');
        }
      }
    } catch (error: any) {
      const errMsg = error.response?.data?.message || 'Gagal mengambil data gizi makanan.';
      showToast(errMsg, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col font-sans">
      {/* Toast Notification */}
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}

      {/* Header Bar */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <button
              onClick={() => router.push('/dashboard')}
              className="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer"
              title="Kembali ke Dashboard"
            >
              <ArrowLeft className="h-5 w-5" />
            </button>
            <div className="flex items-center gap-2">
              <div className="bg-emerald-600 p-2 rounded-lg text-white">
                <Apple className="h-5 w-5" />
              </div>
              <span className="font-bold text-lg text-slate-900 dark:text-white">Kalkulator Gizi</span>
            </div>
          </div>
          <span className="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider hidden sm:inline">
            MediCare Wellness
          </span>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        {/* Intro */}
        <div className="text-center max-w-2xl mx-auto space-y-3">
          <h1 className="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Kalkulator Kandungan Gizi Makanan
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
            Ketahui rincian nutrisi harian Anda secara mendalam menggunakan Edamam API. Masukkan nama bahan atau jenis hidangan di bawah ini.
          </p>
        </div>

        {/* Search Input Box */}
        <div className="max-w-xl mx-auto">
          <form onSubmit={handleSearch} className="relative flex gap-2">
            <div className="relative flex-1">
              <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                <Search className="h-5 w-5" />
              </span>
              <input
                type="text"
                placeholder="Cari makanan (misal: avocado, fried chicken)..."
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                className="w-full pl-11 pr-4 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-base shadow-sm"
                disabled={loading}
                required
              />
            </div>
            <button
              type="submit"
              disabled={loading}
              className="px-6 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white font-semibold rounded-xl transition-all shadow-md shadow-emerald-600/10 cursor-pointer flex items-center gap-2 select-none"
            >
              {loading ? (
                <Loader2 className="h-5 w-5 animate-spin" />
              ) : (
                <span>Cari</span>
              )}
            </button>
          </form>
        </div>

        {/* Loading Spinner */}
        {loading && (
          <div className="py-16 text-center space-y-4">
            <Loader2 className="h-8 w-8 animate-spin text-emerald-600 mx-auto" />
            <p className="text-slate-500 dark:text-slate-400 text-sm">Menghubungi Edamam Database API...</p>
          </div>
        )}

        {/* Results display */}
        {!loading && items.length > 0 && (
          <div className="space-y-8 animate-in fade-in duration-300">
            <div className="border-b border-slate-200 dark:border-slate-800 pb-4">
              <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                Hasil Analisis Gizi untuk &quot;<span className="text-emerald-600 dark:text-emerald-400 capitalize">{searchedQuery}</span>&quot;
              </h2>
            </div>

            <div className="space-y-12">
              {items.map((item, idx) => (
                <div 
                  key={item.id || idx}
                  className="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm flex flex-col md:flex-row gap-8 items-start md:items-center"
                >
                  {/* Food Image / Icon */}
                  {item.image ? (
                    <img 
                      src={item.image} 
                      alt={item.label}
                      className="w-24 h-24 rounded-2xl object-cover border border-slate-100 dark:border-slate-800 shrink-0 mx-auto md:mx-0"
                    />
                  ) : (
                    <div className="w-24 h-24 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 mx-auto md:mx-0 border border-emerald-100/50 dark:border-emerald-900/30">
                      <Apple className="h-10 w-10" />
                    </div>
                  )}

                  {/* Food Details & Macro Nutrition Metrics Cards */}
                  <div className="flex-1 space-y-6 w-full">
                    <div>
                      <h3 className="text-xl font-bold text-slate-900 dark:text-white capitalize text-center md:text-left">
                        {item.label}
                      </h3>
                      <p className="text-xs text-slate-500 dark:text-slate-400 font-medium text-center md:text-left mt-0.5">
                        Kategori: {item.category}
                      </p>
                    </div>

                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                      {/* Calories */}
                      <div className="bg-orange-50/50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 rounded-xl p-4 flex items-center gap-3">
                        <div className="bg-orange-100 dark:bg-orange-900/40 p-2.5 rounded-lg text-orange-700 dark:text-orange-300">
                          <Flame className="h-5 w-5" />
                        </div>
                        <div>
                          <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">Kalori</div>
                          <div className="text-base font-extrabold text-slate-900 dark:text-white">
                            {Math.round(item.nutrients.calories_kcal)} <span className="text-xs font-normal text-slate-500">kcal</span>
                          </div>
                        </div>
                      </div>

                      {/* Protein */}
                      <div className="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 rounded-xl p-4 flex items-center gap-3">
                        <div className="bg-blue-100 dark:bg-blue-900/40 p-2.5 rounded-lg text-blue-700 dark:text-blue-300">
                          <Beef className="h-5 w-5" />
                        </div>
                        <div>
                          <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">Protein</div>
                          <div className="text-base font-extrabold text-slate-900 dark:text-white">
                            {item.nutrients.protein_g.toFixed(1)} <span className="text-xs font-normal text-slate-500">g</span>
                          </div>
                        </div>
                      </div>

                      {/* Carbs */}
                      <div className="bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40 rounded-xl p-4 flex items-center gap-3">
                        <div className="bg-amber-100 dark:bg-amber-900/40 p-2.5 rounded-lg text-amber-700 dark:text-amber-300">
                          <Cookie className="h-5 w-5" />
                        </div>
                        <div>
                          <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">Karbohidrat</div>
                          <div className="text-base font-extrabold text-slate-900 dark:text-white">
                            {item.nutrients.carbs_g.toFixed(1)} <span className="text-xs font-normal text-slate-500">g</span>
                          </div>
                        </div>
                      </div>

                      {/* Fat */}
                      <div className="bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/40 rounded-xl p-4 flex items-center gap-3">
                        <div className="bg-rose-100 dark:bg-rose-900/40 p-2.5 rounded-lg text-rose-700 dark:text-rose-300">
                          <Droplet className="h-5 w-5" />
                        </div>
                        <div>
                          <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">Lemak</div>
                          <div className="text-base font-extrabold text-slate-900 dark:text-white">
                            {item.nutrients.fat_g.toFixed(1)} <span className="text-xs font-normal text-slate-500">g</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Empty state illustration */}
        {!loading && items.length === 0 && (
          <div className="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-12 text-center max-w-md mx-auto space-y-4 shadow-sm">
            <div className="w-16 h-16 bg-slate-50 dark:bg-slate-950 text-slate-400 rounded-full flex items-center justify-center mx-auto border border-slate-100 dark:border-slate-850">
              <Search className="h-6 w-6" />
            </div>
            <div className="space-y-1">
              <h3 className="font-bold text-slate-900 dark:text-white">Mulai Analisis Makanan</h3>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                Gunakan kotak pencarian di atas untuk mendapatkan informasi makronutrisi dari makanan favorit Anda.
              </p>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}

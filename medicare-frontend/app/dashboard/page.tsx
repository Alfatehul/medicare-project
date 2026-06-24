'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { 
  Loader2, 
  Calendar, 
  LogOut, 
  CloudSun, 
  Stethoscope, 
  User, 
  Clock, 
  HeartPulse, 
  ChevronRight, 
  ExternalLink,
  Utensils
} from 'lucide-react';
import api from '@/src/utils/api';
import Toast from '@/src/components/Toast';

interface Doctor {
  id: number;
  name: string;
  specialization: string;
  fee_idr: number;
}

interface Weather {
  city: string;
  temperature: number;
  feels_like: number;
  humidity: number;
  description: string;
  icon?: string;
  message?: string;
}

interface UserProfile {
  id: number;
  name: string;
  whatsapp_number: string;
}

export default function DashboardPage() {
  const router = useRouter();
  const [doctors, setDoctors] = useState<Doctor[]>([]);
  const [weather, setWeather] = useState<Weather | null>(null);
  const [user, setUser] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [bookingLoading, setBookingLoading] = useState(false);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);
  
  // Modal states
  const [selectedDoctor, setSelectedDoctor] = useState<Doctor | null>(null);
  const [appointmentDate, setAppointmentDate] = useState('');
  
  // Midtrans Payment state
  const [paymentInfo, setPaymentInfo] = useState<{ token: string; redirectUrl: string } | null>(null);
  const [isIframeOpen, setIsIframeOpen] = useState(false);
  const [bookingStatus, setBookingStatus] = useState<'idle' | 'pending_payment' | 'paid'>('idle');

  useEffect(() => {
    const snapScript = 'https://app.sandbox.midtrans.com/snap/snap.js';
    const clientKey = 'Mid-client-cgDM6uzzGJVCx9zyU0s3bEYt';

    if (!document.querySelector(`script[src="${snapScript}"]`)) {
      const script = document.createElement('script');
      script.src = snapScript;
      script.setAttribute('data-client-key', clientKey);
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);

  useEffect(() => {
    const token = localStorage.getItem('medicare_token');
    const savedUser = localStorage.getItem('medicare_user');
    
    if (!token) {
      router.push('/auth');
      return;
    }

    if (savedUser) {
      setUser(JSON.parse(savedUser));
    }

    fetchDashboardData();
  }, [router]);

  const showToast = (message: string, type: 'success' | 'error' | 'info') => {
    setToast({ message, type });
  };

  const fetchDashboardData = async () => {
    setLoading(true);
    try {
      const response = await api.get('/v1/doctors');
      if (response.data.status === 'success') {
        setDoctors(response.data.data);
        if (response.data.meta?.weather) {
          setWeather(response.data.meta.weather);
        }
      }
    } catch (error: any) {
      const errMsg = error.response?.data?.message || 'Gagal mengambil data dokter dan cuaca.';
      showToast(errMsg, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('medicare_token');
    localStorage.removeItem('medicare_user');
    showToast('Berhasil keluar!', 'success');
    setTimeout(() => {
      router.push('/auth');
    }, 1000);
  };

  const handleOpenBookingModal = (doctor: Doctor) => {
    setSelectedDoctor(doctor);
    
    // Set default appointment date to tomorrow at 09:00
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    
    // Format to YYYY-MM-DDTHH:MM local string for datetime-local input
    const timezoneOffset = tomorrow.getTimezoneOffset() * 60000; // in MS
    const localISOTime = (new Date(tomorrow.getTime() - timezoneOffset)).toISOString().slice(0, 16);
    setAppointmentDate(localISOTime);
  };

  const handleConfirmBooking = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedDoctor || !appointmentDate) return;

    setBookingLoading(true);
    try {
      const response = await api.post('/v1/appointments', {
        doctor_id: selectedDoctor.id,
        appointment_date: appointmentDate,
      });

      if (response.data && response.data.status === 'success') {
        const payment = response.data.data?.payment;
        const snapToken = response.data.snap_token || payment?.token;
        const redirectUrl = response.data.redirect_url || payment?.redirect_url;

        showToast('Appointment berhasil dibuat!', 'success');
        
        if (snapToken) {
          setPaymentInfo({
            token: snapToken,
            redirectUrl: redirectUrl || ''
          });
          setBookingStatus('pending_payment');
          setSelectedDoctor(null); // Close appointment date modal
          
          // Trigger Midtrans Snap payment
          if (window && (window as any).snap) {
            (window as any).snap.pay(snapToken, {
              onSuccess: function (result: any) {
                showToast('Pembayaran berhasil dikonfirmasi!', 'success');
                setBookingStatus('paid');
                fetchDashboardData();
              },
              onPending: function (result: any) {
                showToast('Menunggu pembayaran Anda.', 'info');
                setBookingStatus('pending_payment');
              },
              onError: function (result: any) {
                alert('Pembayaran gagal. Silakan coba kembali.');
                showToast('Pembayaran gagal.', 'error');
                setBookingStatus('idle');
              },
              onClose: function () {
                showToast('Anda menutup pop-up pembayaran.', 'info');
              }
            });
          } else if (redirectUrl) {
            // Fallback to redirect URL in new window
            window.open(redirectUrl, '_blank');
          } else {
            alert('Gagal memproses pembayaran: snap token & redirect url tidak valid.');
          }
        } else {
          setSelectedDoctor(null);
        }
      } else {
        const msg = response.data?.message || 'Gagal membuat reservasi.';
        alert(msg);
        showToast(msg, 'error');
      }
    } catch (error: any) {
      const errMsg = error.response?.data?.message || error.message || 'Terjadi kesalahan saat membuat janji temu.';
      alert(errMsg);
      showToast(errMsg, 'error');
    } finally {
      setBookingLoading(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
        <div className="text-center space-y-4">
          <Loader2 className="h-10 w-10 animate-spin text-indigo-600 mx-auto" />
          <p className="text-slate-600 dark:text-slate-400 font-medium">Memuat Portal MediCare...</p>
        </div>
      </div>
    );
  }

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

      {/* Navigation Bar */}
      <nav className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            {/* Logo */}
            <div className="flex items-center gap-2">
              <div className="bg-indigo-600 p-2 rounded-lg text-white">
                <HeartPulse className="h-5 w-5" />
              </div>
              <span className="font-bold text-lg text-slate-900 dark:text-white">MediCare</span>
            </div>

            {/* Menu Links */}
            <div className="flex items-center gap-6">
              <button 
                onClick={() => router.push('/nutrition')}
                className="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
              >
                <Utensils className="h-4 w-4" />
                <span>Kalkulator Gizi</span>
              </button>

              <div className="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>

              {/* User profile & logout */}
              <div className="flex items-center gap-4">
                <div className="hidden md:flex flex-col text-right">
                  <span className="text-sm font-semibold text-slate-950 dark:text-slate-50">
                    {user?.name || 'Pasien'}
                  </span>
                  <span className="text-xs text-slate-500 dark:text-slate-400">
                    {user?.whatsapp_number}
                  </span>
                </div>
                <button
                  onClick={handleLogout}
                  className="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-lg transition-colors cursor-pointer"
                  title="Logout"
                >
                  <LogOut className="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </nav>

      {/* Main Body */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        {/* Banner Status Midtrans "Menunggu Pembayaran" */}
        {bookingStatus === 'pending_payment' && paymentInfo && (
          <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/60 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-4 duration-300">
            <div className="flex items-start gap-4">
              <div className="bg-amber-100 dark:bg-amber-900/40 p-3 rounded-xl text-amber-800 dark:text-amber-300 shrink-0">
                <Clock className="h-6 w-6 animate-pulse" />
              </div>
              <div className="space-y-1">
                <h3 className="text-base font-bold text-amber-900 dark:text-amber-200">
                  Menunggu Pembayaran Pasien
                </h3>
                <p className="text-sm text-amber-700 dark:text-amber-400">
                  Reservasi Anda telah terdaftar. Silakan selesaikan pembayaran Sandbox Snap Midtrans Anda untuk konfirmasi instan.
                </p>
              </div>
            </div>
            <div className="flex flex-wrap gap-3 w-full md:w-auto justify-end">
              <button
                onClick={() => {
                  if (window && (window as any).snap && paymentInfo.token) {
                    (window as any).snap.pay(paymentInfo.token, {
                      onSuccess: function (result: any) {
                        showToast('Pembayaran berhasil dikonfirmasi!', 'success');
                        setBookingStatus('paid');
                        fetchDashboardData();
                      },
                      onPending: function (result: any) {
                        showToast('Menunggu pembayaran Anda.', 'info');
                      },
                      onError: function (result: any) {
                        alert('Pembayaran gagal. Silakan coba kembali.');
                        showToast('Pembayaran gagal.', 'error');
                        setBookingStatus('idle');
                      },
                      onClose: function () {
                        showToast('Anda menutup pop-up pembayaran.', 'info');
                      }
                    });
                  } else if (paymentInfo.redirectUrl) {
                    window.open(paymentInfo.redirectUrl, '_blank');
                  } else {
                    alert('Token pembayaran tidak ditemukan.');
                  }
                }}
                className="flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-amber-600/10 cursor-pointer"
              >
                <span>Bayar Sekarang</span>
                <ExternalLink className="h-4 w-4" />
              </button>
              <button
                onClick={() => setBookingStatus('idle')}
                className="px-4 py-2.5 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-sm font-medium rounded-xl transition-all"
              >
                Tutup Banner
              </button>
            </div>
          </div>
        )}

        {/* Weather Alert / Banner */}
        {weather && (
          <div className="bg-gradient-to-r from-sky-500/10 via-indigo-500/10 to-indigo-500/5 dark:from-sky-950/20 dark:via-indigo-950/15 dark:to-transparent border border-sky-100 dark:border-sky-900/30 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div className="flex items-center gap-4">
              {weather.icon ? (
                <img 
                  src={weather.icon} 
                  alt={weather.description} 
                  className="h-16 w-16 bg-sky-100/50 dark:bg-sky-950/40 rounded-xl"
                />
              ) : (
                <div className="bg-sky-100 dark:bg-sky-950/40 p-4 rounded-xl text-sky-600 dark:text-sky-400">
                  <CloudSun className="h-8 w-8" />
                </div>
              )}
              <div className="space-y-1">
                <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                  Informasi Klinik & Cuaca ({weather.city})
                </h2>
                <p className="text-sm text-slate-600 dark:text-slate-400">
                  Cuaca saat ini: <span className="font-semibold text-slate-800 dark:text-slate-200 capitalize">{weather.description}</span> dengan suhu <span className="font-semibold text-slate-800 dark:text-slate-200">{weather.temperature}°C</span>. Silakan reservasi jadwal konsultasi Anda hari ini!
                </p>
              </div>
            </div>
            
            <div className="flex flex-col text-right shrink-0">
              <span className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Kelembapan</span>
              <span className="text-lg font-bold text-sky-600 dark:text-sky-400">{weather.humidity}%</span>
            </div>
          </div>
        )}

        {/* Doctors Grid Section */}
        <section className="space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
            <div>
              <h2 className="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Daftar Dokter Spesialis</h2>
              <p className="text-sm text-slate-500 dark:text-slate-400">Pilih spesialisasi yang Anda butuhkan untuk membuat janji temu medis instan</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {doctors.map((doctor) => (
              <div 
                key={doctor.id}
                className="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between group"
              >
                <div className="space-y-4">
                  {/* Doctor Icon */}
                  <div className="flex items-center gap-3">
                    <div className="bg-indigo-50 dark:bg-indigo-950/40 p-3 rounded-xl text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                      <Stethoscope className="h-6 w-6" />
                    </div>
                    <div>
                      <h3 className="font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        {doctor.name}
                      </h3>
                      <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {doctor.specialization}
                      </p>
                    </div>
                  </div>

                  <div className="h-px bg-slate-100 dark:bg-slate-800/80"></div>

                  <div className="flex justify-between items-center text-sm py-1">
                    <span className="text-slate-500 dark:text-slate-400">Tarif Konsultasi</span>
                    <span className="font-bold text-slate-900 dark:text-white">
                      {formatCurrency(doctor.fee_idr)}
                    </span>
                  </div>
                </div>

                <div className="mt-6">
                  <button
                    onClick={() => handleOpenBookingModal(doctor)}
                    className="w-full flex items-center justify-center gap-1.5 py-3 bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/30 dark:hover:bg-indigo-600 text-indigo-600 hover:text-white dark:text-indigo-400 dark:hover:text-white font-semibold rounded-xl transition-all cursor-pointer"
                  >
                    <span>Buat Janji Temu</span>
                    <ChevronRight className="h-4 w-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </section>
      </main>

      {/* Booking Appointment Modal */}
      {selectedDoctor && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full overflow-hidden shadow-2xl animate-in zoom-in-95 duration-200">
            {/* Modal Header */}
            <div className="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
              <div className="bg-indigo-50 dark:bg-indigo-950/40 p-2 rounded-xl text-indigo-600 dark:text-indigo-400">
                <Calendar className="h-5 w-5" />
              </div>
              <div>
                <h3 className="font-bold text-slate-900 dark:text-white">Buat Jadwal Janji Temu</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">Silakan tentukan waktu konsultasi Anda</p>
              </div>
            </div>

            {/* Modal Body */}
            <form onSubmit={handleConfirmBooking}>
              <div className="p-6 space-y-6">
                {/* Selected Doctor Summary Card */}
                <div className="bg-slate-50 dark:bg-slate-950 p-4 rounded-xl space-y-2 border border-slate-100 dark:border-slate-800">
                  <span className="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 block">Dokter Pilihan</span>
                  <div className="font-semibold text-slate-900 dark:text-white">{selectedDoctor.name}</div>
                  <div className="text-xs text-slate-500 dark:text-slate-400">{selectedDoctor.specialization}</div>
                  <div className="text-xs text-slate-500 dark:text-slate-400">Tarif: {formatCurrency(selectedDoctor.fee_idr)}</div>
                </div>

                {/* Appointment Date input */}
                <div className="space-y-2">
                  <label className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">
                    Tanggal & Waktu Kunjungan
                  </label>
                  <input
                    type="datetime-local"
                    value={appointmentDate}
                    onChange={(e) => setAppointmentDate(e.target.value)}
                    className="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-base"
                    required
                  />
                </div>
              </div>

              {/* Modal Footer */}
              <div className="px-6 py-4 bg-slate-50 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setSelectedDoctor(null)}
                  disabled={bookingLoading}
                  className="px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={bookingLoading}
                  className="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 cursor-pointer"
                >
                  {bookingLoading ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      <span>Membuat...</span>
                    </>
                  ) : (
                    <span>Konfirmasi Reservasi</span>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

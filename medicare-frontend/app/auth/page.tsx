'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { ShieldCheck, MessageSquare, KeyRound, Loader2, ArrowRight } from 'lucide-react';
import api from '@/src/utils/api';
import Toast from '@/src/components/Toast';

export default function AuthPage() {
  const router = useRouter();
  const [whatsappNumber, setWhatsappNumber] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [step, setStep] = useState<1 | 2>(1);
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);

  // Check if already authenticated, then redirect
  useEffect(() => {
    const token = localStorage.getItem('medicare_token');
    if (token) {
      router.push('/dashboard');
    }
  }, [router]);

  const showToast = (message: string, type: 'success' | 'error' | 'info') => {
    setToast({ message, type });
  };

  const handleRequestOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!whatsappNumber) {
      showToast('Nomor WhatsApp wajib diisi', 'error');
      return;
    }

    setLoading(true);
    try {
      const response = await api.post('/v1/auth/request-otp', {
        whatsapp_number: whatsappNumber,
      });

      if (response.data.status === 'success') {
        showToast(response.data.message || 'OTP berhasil dikirim!', 'success');
        setStep(2);
      } else {
        showToast(response.data.message || 'Gagal mengirim OTP', 'error');
      }
    } catch (error: any) {
      console.error('[MediCare] Request OTP Error:', {
        status: error.response?.status,
        data: error.response?.data,
        message: error.message,
      });
      const errMsg = error.response?.data?.message || 'Terjadi kesalahan saat menghubungi server.';
      showToast(errMsg, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!otpCode || otpCode.length !== 6) {
      showToast('Masukkan 6 digit kode OTP yang valid', 'error');
      return;
    }

    setLoading(true);
    try {
      const response = await api.post('/v1/auth/verify-otp', {
        whatsapp_number: whatsappNumber,
        otp_code: otpCode,
      });

      if (response.data.status === 'success') {
        const { access_token, user } = response.data.data;
        localStorage.setItem('medicare_token', access_token);
        localStorage.setItem('medicare_user', JSON.stringify(user));
        showToast('Autentikasi berhasil! Mengalihkan...', 'success');
        
        setTimeout(() => {
          router.push('/dashboard');
        }, 1000);
      } else {
        showToast(response.data.message || 'Verifikasi OTP gagal', 'error');
      }
    } catch (error: any) {
      console.error('[MediCare] Verify OTP Error:', {
        status: error.response?.status,
        data: error.response?.data,
        message: error.message,
      });
      const errMsg = error.response?.data?.message || 'OTP salah atau kedaluwarsa.';
      showToast(errMsg, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen bg-slate-50 dark:bg-slate-950 font-sans">
      {/* Toast Notification */}
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}

      {/* Left side: Premium Medical Clinic Hero illustration (Split Screen) */}
      <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-tr from-sky-600 via-indigo-600 to-indigo-800 text-white flex-col justify-between p-16 overflow-hidden">
        {/* Subtle decorative background circles */}
        <div className="absolute top-0 right-0 w-[500px] h-[500px] rounded-full bg-white/5 -mr-40 -mt-40 blur-3xl"></div>
        <div className="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full bg-sky-400/10 -ml-20 -mb-20 blur-2xl"></div>

        {/* Top brand */}
        <div className="flex items-center gap-2 relative z-10">
          <div className="bg-white/10 backdrop-blur-md p-2 rounded-xl border border-white/20">
            <ShieldCheck className="h-6 w-6 text-sky-300 animate-pulse" />
          </div>
          <span className="font-bold text-xl tracking-tight bg-gradient-to-r from-white to-sky-100 bg-clip-text text-transparent">
            MediCare Portal
          </span>
        </div>

        {/* Middle text */}
        <div className="space-y-6 relative z-10 max-w-md">
          <span className="px-3 py-1 text-xs font-semibold tracking-wider uppercase bg-sky-400/20 text-sky-200 border border-sky-400/30 rounded-full">
            Pasien Layanan Cepat
          </span>
          <h1 className="text-4xl font-extrabold tracking-tight leading-tight sm:text-5xl">
            Akses Rekam Medis & Jadwal Dokter Instan
          </h1>
          <p className="text-lg text-indigo-100/90 leading-relaxed font-light">
            Masuk tanpa password rumit. Kami menggunakan verifikasi nomor WhatsApp untuk menjamin keamanan rekam medis Anda.
          </p>
        </div>

        {/* Footer info */}
        <div className="text-xs text-indigo-200/60 relative z-10">
          &copy; {new Date().getFullYear()} MediCare Core Clinic API. All rights reserved.
        </div>
      </div>

      {/* Right side: Login Card / OTP Verification Form */}
      <div className="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 md:p-16">
        <div className="w-full max-w-md space-y-8 bg-white dark:bg-slate-900 p-8 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all duration-300">
          
          {/* Header */}
          <div className="text-center lg:text-left space-y-2">
            <h2 className="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
              {step === 1 ? 'Masuk ke Portal Pasien' : 'Verifikasi OTP'}
            </h2>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              {step === 1 
                ? 'Masukkan nomor WhatsApp Anda untuk meminta kode verifikasi OTP' 
                : `Kode OTP 6-digit dikirim ke nomor WhatsApp ${whatsappNumber}`
              }
            </p>
          </div>

          {/* Form */}
          {step === 1 ? (
            <form onSubmit={handleRequestOtp} className="space-y-6">
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">
                  Nomor WhatsApp
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                    <MessageSquare className="h-5 w-5" />
                  </span>
                  <input
                    type="tel"
                    placeholder="Contoh: 08123456789"
                    value={whatsappNumber}
                    onChange={(e) => setWhatsappNumber(e.target.value)}
                    className="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-base placeholder-slate-400 dark:placeholder-slate-600"
                    disabled={loading}
                    required
                  />
                </div>
                <p className="text-xs text-slate-400 dark:text-slate-500">
                  Gunakan format nomor lokal (misal: 0812xxx) atau format internasional.
                </p>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full flex items-center justify-center gap-2 py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all cursor-pointer select-none"
              >
                {loading ? (
                  <>
                    <Loader2 className="h-5 w-5 animate-spin" />
                    <span>Mengirim OTP...</span>
                  </>
                ) : (
                  <>
                    <span>Kirim OTP</span>
                    <ArrowRight className="h-5 w-5" />
                  </>
                )}
              </button>
            </form>
          ) : (
            <form onSubmit={handleVerifyOtp} className="space-y-6">
              <div className="space-y-2">
                <label className="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">
                  Kode OTP (6 Digit)
                </label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                    <KeyRound className="h-5 w-5" />
                  </span>
                  <input
                    type="text"
                    maxLength={6}
                    placeholder="Masukkan 6-digit OTP"
                    value={otpCode}
                    onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, ''))}
                    className="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-base tracking-widest font-mono text-center placeholder-slate-400 dark:placeholder-slate-600"
                    disabled={loading}
                    required
                  />
                </div>
              </div>

              <div className="flex flex-col gap-3">
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex items-center justify-center gap-2 py-3.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all cursor-pointer select-none"
                >
                  {loading ? (
                    <>
                      <Loader2 className="h-5 w-5 animate-spin" />
                      <span>Memverifikasi...</span>
                    </>
                  ) : (
                    <>
                      <span>Verifikasi & Masuk</span>
                      <ArrowRight className="h-5 w-5" />
                    </>
                  )}
                </button>

                <button
                  type="button"
                  onClick={() => setStep(1)}
                  disabled={loading}
                  className="w-full text-center py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                  Kembali ke Masukkan Nomor
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}

"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";

export default function Home() {
    const router = useRouter();

    useEffect(() => {
        const token = localStorage.getItem("medicare_token");
        if (token) {
            router.replace("/dashboard");
        } else {
            router.replace("/auth");
        }
    }, [router]);

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
            <div className="text-center space-y-4">
                <Loader2 className="h-8 w-8 animate-spin text-indigo-600 mx-auto" />
                <p className="text-slate-500 dark:text-slate-400 font-medium text-sm">
                    Mengalihkan ke Portal MediCare...
                </p>
            </div>
        </div>
    );
}

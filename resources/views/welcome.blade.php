<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HireAI - AI-Powered Hiring Assistant</title>
    <meta name="description" content="Land your dream job with AI-powered CV analysis, cover letters, and interview prep.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 50%, #6366f1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .glow { box-shadow: 0 0 60px rgba(139, 92, 246, 0.3); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    </style>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <nav class="fixed top-0 left-0 right-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold">HireAI</span>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-zinc-400 hover:text-white transition">Features</a>
                <a href="#pricing" class="text-zinc-400 hover:text-white transition">Pricing</a>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-500 rounded-lg font-medium transition">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-zinc-400 hover:text-white transition">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-500 rounded-lg font-medium transition">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-20 px-6">
        <div class="max-w-7xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-sm text-violet-400 mb-8">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/></svg>
                Powered by AI
            </div>
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                Land Your Dream Job<br>
                <span class="gradient-text">With AI on Your Side</span>
            </h1>
            <p class="text-xl text-zinc-400 max-w-2xl mx-auto mb-10">
                AI-powered CV analysis, cover letter generation, and interview prep. 
                Everything you need to stand out and get hired.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-violet-600 hover:bg-violet-500 rounded-xl font-semibold text-lg transition glow">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-violet-600 hover:bg-violet-500 rounded-xl font-semibold text-lg transition glow">
                        Start Free Trial
                    </a>
                    <a href="#features" class="px-8 py-4 glass hover:bg-white/10 rounded-xl font-semibold text-lg transition">
                        See How It Works
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass rounded-2xl p-6 text-center animate-float" style="animation-delay: 0s;">
                    <div class="text-3xl font-bold text-violet-400">50K+</div>
                    <div class="text-sm text-zinc-400">CVs Analyzed</div>
                </div>
                <div class="glass rounded-2xl p-6 text-center animate-float" style="animation-delay: 0.5s;">
                    <div class="text-3xl font-bold text-violet-400">89%</div>
                    <div class="text-sm text-zinc-400">Interview Rate</div>
                </div>
                <div class="glass rounded-2xl p-6 text-center animate-float" style="animation-delay: 1s;">
                    <div class="text-3xl font-bold text-violet-400">12K+</div>
                    <div class="text-sm text-zinc-400">Jobs Landed</div>
                </div>
                <div class="glass rounded-2xl p-6 text-center animate-float" style="animation-delay: 1.5s;">
                    <div class="text-3xl font-bold text-violet-400">4.9★</div>
                    <div class="text-sm text-zinc-400">User Rating</div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Everything You Need to Get Hired</h2>
                <p class="text-zinc-400 text-lg">AI-powered tools to optimize every step of your job search</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6">
                <div class="md:col-span-2 glass rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">CV Analyzer</h3>
                    </div>
                    <p class="text-zinc-400 mb-4">Get instant feedback on how well your CV matches any job. AI identifies missing keywords, calculates ATS compatibility, and suggests improvements.</p>
                    <div class="flex gap-3 flex-wrap">
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full">Match Score</span>
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 text-sm rounded-full">ATS Score</span>
                        <span class="px-3 py-1 bg-violet-500/20 text-violet-400 text-sm rounded-full">Keyword Gap</span>
                    </div>
                </div>

                <div class="glass rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Cover Letters</h3>
                    </div>
                    <p class="text-zinc-400">Generate personalized cover letters in seconds. Choose your tone, edit inline, and export.</p>
                </div>

                <div class="glass rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Job Tracker</h3>
                    </div>
                    <p class="text-zinc-400">Kanban board to track all your applications from wishlist to offer.</p>
                </div>

                <div class="md:col-span-2 glass rounded-2xl p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Interview Prep</h3>
                    </div>
                    <p class="text-zinc-400 mb-4">Practice with AI-generated questions tailored to your target role. Get instant feedback and model answers to improve your responses.</p>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-white/5 rounded-lg p-3">
                            <div class="text-2xl font-bold text-white">10</div>
                            <div class="text-xs text-zinc-500">Questions</div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-3">
                            <div class="text-2xl font-bold text-white">AI</div>
                            <div class="text-xs text-zinc-500">Scoring</div>
                        </div>
                        <div class="bg-white/5 rounded-lg p-3">
                            <div class="text-2xl font-bold text-white">Live</div>
                            <div class="text-xs text-zinc-500">Feedback</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-20 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Simple Pricing</h2>
                <p class="text-zinc-400 text-lg">Start free, upgrade when you need more</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="glass rounded-2xl p-8">
                    <h3 class="text-xl font-semibold mb-2">Free</h3>
                    <div class="text-4xl font-bold mb-4">$0<span class="text-lg text-zinc-400 font-normal">/month</span></div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            5 CV analyses/month
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Basic cover letters
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Job tracker
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            1 interview prep/month
                        </li>
                    </ul>
                    @auth
                        <a href="{{ route('dashboard') }}" class="block w-full py-3 text-center glass rounded-lg hover:bg-white/10 transition font-medium">Current Plan</a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full py-3 text-center glass rounded-lg hover:bg-white/10 transition font-medium">Get Started</a>
                    @endauth
                </div>

                <div class="relative glass rounded-2xl p-8 border-violet-500/50">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-violet-600 rounded-full text-sm font-medium">Popular</div>
                    <h3 class="text-xl font-semibold mb-2">Pro</h3>
                    <div class="text-4xl font-bold mb-4">$9<span class="text-lg text-zinc-400 font-normal">/month</span></div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <strong>Unlimited</strong> CV analyses
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            All tone options
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            PDF export
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Unlimited interview prep
                        </li>
                        <li class="flex items-center gap-2 text-zinc-300">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Priority support
                        </li>
                    </ul>
                    @auth
                        <a href="#" class="block w-full py-3 text-center bg-violet-600 hover:bg-violet-500 rounded-lg transition font-medium">Upgrade Now</a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full py-3 text-center bg-violet-600 hover:bg-violet-500 rounded-lg transition font-medium">Start Free Trial</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl font-bold mb-4">Ready to Land Your Dream Job?</h2>
            <p class="text-zinc-400 text-lg mb-8">Join thousands of job seekers who've transformed their job search with AI.</p>
            @auth
                <a href="{{ route('dashboard') }}" class="inline-block px-8 py-4 bg-violet-600 hover:bg-violet-500 rounded-xl font-semibold text-lg transition glow">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-violet-600 hover:bg-violet-500 rounded-xl font-semibold text-lg transition glow">
                    Get Started Free
                </a>
            @endauth
        </div>
    </section>

    <footer class="py-12 px-6 border-t border-white/10">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="font-bold">HireAI</span>
            </div>
            <p class="text-zinc-500 text-sm">© 2026 HireAI. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

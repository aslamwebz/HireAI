<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-950">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-white/10 bg-slate-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2" wire:navigate>
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                        <flux:icon name="sparkles" class="w-5 h-5 text-white" />
                    </div>
                    <span class="text-lg font-bold text-white">HireAI</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Tools')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('resumes.index')" :current="request()->routeIs('resumes.*')" wire:navigate>
                        {{ __('Resume Writer') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user" :href="route('profile.show')" :current="request()->routeIs('profile.*')" wire:navigate>
                        {{ __('User Profiles') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-open" :href="route('cvs.index')" :current="request()->routeIs('cvs.*')" wire:navigate>
                        {{ __('My CVs') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-magnifying-glass" :href="route('cv-analyzer')" :current="request()->routeIs('cv-analyzer')" wire:navigate>
                        {{ __('CV Analyzer') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="envelope" :href="route('cover-letter')" :current="request()->routeIs('cover-letter')" wire:navigate>
                        {{ __('Cover Letter') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('jobs.index')" :current="request()->routeIs('jobs.*')" wire:navigate>
                        {{ __('Job Tracker') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('interview-prep')" :current="request()->routeIs('interview-prep')" wire:navigate>
                        {{ __('Interview Prep') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Settings')" class="grid">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('ai-settings')" :current="request()->routeIs('ai-settings')" wire:navigate>
                        {{ __('AI Settings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            @if(!auth()->user()->isPro())
                <div class="mx-2 mb-2 p-3 rounded-lg bg-violet-500/10 border border-violet-500/20">
                    <p class="text-xs text-violet-400 font-medium mb-1">Free Plan</p>
                    <p class="text-xs text-zinc-400 mb-2">{{ auth()->user()->getAnalysisUsage() }}/5 analyses used</p>
                    <flux:button href="{{ route('pricing') }}" size="sm" variant="primary" class="w-full">
                        Upgrade to Pro
                    </flux:button>
                </div>
            @endif

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('ai-settings')" icon="cog-6-tooth" wire:navigate>
                            {{ __('AI Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="p-6">
            {{ $slot }}
        </flux:main>

        @fluxScripts

        @stack('scripts')
    </body>
</html>

<style>
    .glass-card {
        @apply bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6;
    }
    
    .glass-card-sm {
        @apply bg-white/5 backdrop-blur-md border border-white/10 rounded-lg p-4;
    }
</style>

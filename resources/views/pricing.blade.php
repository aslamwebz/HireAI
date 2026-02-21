<x-layouts::app.sidebar :title="__('Pricing')">
    <div class="max-w-4xl mx-auto space-y-8">
        @if(session('status'))
            <flux:callout color="violet">
                {{ session('status') }}
            </flux:callout>
        @endif

        <div class="text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Upgrade to Pro</h1>
            <p class="text-zinc-400">Unlock unlimited AI-powered job search tools</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-card">
                <h2 class="text-xl font-semibold text-white mb-2">Free</h2>
                <div class="text-4xl font-bold text-white mb-4">$0<span class="text-lg text-zinc-400 font-normal">/month</span></div>
                <p class="text-zinc-400 text-sm mb-6">Perfect for getting started</p>
                
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-green-400" />
                        5 CV analyses per month
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-green-400" />
                        Basic cover letter generation
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-green-400" />
                        Job application tracker
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-green-400" />
                        1 interview prep session/month
                    </li>
                </ul>
                
                @if(!auth()->user()->isPro())
                    <flux:button variant="subtle" class="w-full" disabled>
                        Current Plan
                    </flux:button>
                @else
                    <flux:button variant="subtle" class="w-full" disabled>
                        Free Plan
                    </flux:button>
                @endif
            </div>

            <div class="glass-card border border-violet-500/50">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-semibold text-white">Pro</h2>
                    <span class="px-2 py-1 bg-violet-500/20 text-violet-400 text-xs rounded-full">Recommended</span>
                </div>
                <div class="text-4xl font-bold text-white mb-4">$9<span class="text-lg text-zinc-400 font-normal">/month</span></div>
                <p class="text-zinc-400 text-sm mb-6">For serious job seekers</p>
                
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-violet-400" />
                        <strong>Unlimited</strong> CV analyses
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-violet-400" />
                        All cover letter tone options
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-violet-400" />
                        PDF export for cover letters
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-violet-400" />
                        Unlimited interview prep
                    </li>
                    <li class="flex items-center gap-2 text-zinc-300">
                        <flux:icon name="check" class="w-5 h-5 text-violet-400" />
                        Priority email support
                    </li>
                </ul>
                
                @if(auth()->user()->isPro())
                    <flux:button variant="primary" class="w-full" disabled>
                        Pro Plan Active
                    </flux:button>
                    <div class="mt-4 text-center">
                        <form method="POST" action="{{ route('subscription.billing') }}">
                            @csrf
                            <flux:button type="submit" variant="subtle" size="sm">
                                Manage Subscription
                            </flux:button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('subscription.checkout') }}">
                        @csrf
                        <flux:button type="submit" variant="primary" class="w-full">
                            Upgrade to Pro
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>

        <div class="glass-card text-center">
            <h3 class="text-lg font-semibold text-white mb-2">Need a team plan?</h3>
            <p class="text-zinc-400 mb-4">Contact us for enterprise pricing and features for teams and organizations.</p>
            <flux:button variant="subtle" icon="envelope">
                Contact Sales
            </flux:button>
        </div>
    </div>
</x-layouts::app.sidebar>

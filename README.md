# HireAI 🚀

[![Tests](https://img.shields.io/badge/tests-79%20passed-brightgreen.svg)](#-testing--code-quality)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4.x-purple.svg)](https://livewire.laravel.com)
[![Pest](https://img.shields.io/badge/Pest-4.x-blue.svg)](https://pestphp.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-4.x-cyan.svg)](https://tailwindcss.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**HireAI** is an AI-native career advancement platform built with modern Laravel 12, Livewire 4, and Flux UI. It provides career engineering tools including ATS-optimized résumé creation and versioning, CV analysis, cover letter generation, interview preparation, multi-provider AI integrations, and subscription billing.

---

## 🏛️ System Architecture

```text
                           ┌────────────────────────────┐
                           │   Browser / Client (Flux)  │
                           └─────────────┬──────────────┘
                                         │
                         HTTPS / Livewire 4 Reactive UI
                                         ▼
┌────────────────────────────────────────────────────────────────────────────────┐
│ Laravel 12 Core Architecture                                                   │
│                                                                                │
│  ┌────────────────────────┐  ┌─────────────────────────┐  ┌─────────────────┐ │
│  │ HTTP & Form Requests   │  │   Livewire 4 Components │  │ Fortify Auth    │ │
│  │ (Auth & Rate-Limited)  │  │   (Real-time Streaming) │  │ & Policies      │ │
│  └───────────┬────────────┘  └────────────┬────────────┘  └────────┬────────┘ │
│              │                            │                        │          │
│              ▼                            ▼                        ▼          │
│  ┌──────────────────────────────────────────────────────────────────────────┐ │
│  │ Domain Service Layer                                                     │ │
│  │  • ResumeBuilder (DomPDF with SSRF protection)                           │ │
│  │  • ResumeParser (Smalot PDF Parser + Heuristic Regex Fallback)           │ │
│  │  • AiService (Prism Multi-Provider Engine & Guardrails)                  │ │
│  └──────────────────────────────────────┬───────────────────────────────────┘ │
│                                         │                                     │
│              ┌──────────────────────────┴────────────────────────┐            │
│              ▼                                                   ▼            │
│  ┌───────────────────────────────┐           ┌─────────────────────────────┐  │
│  │ Queue Worker (Async Jobs)     │           │ Persistence & Billing       │  │
│  │  • ProcessCvAnalysis          │           │  • SQLite / MySQL           │  │
│  │  • ProcessInterviewPrep       │           │  • Atomic UsageRecord       │  │
│  │  • ProcessCoverLetter         │           │  • Laravel Cashier (Stripe) │  │
│  └───────────┬───────────────────┘           └─────────────────────────────┘  │
└──────────────┼─────────────────────────────────────────────────────────────────┘
               │
               ▼
┌────────────────────────────────────────────────────────────────────────────────┐
│ AI Provider Integrations (User Encrypted Keys or Global Fallback)              │
│  • OpenAI (GPT-4o, GPT-4o-mini)          • Anthropic (Claude 3.5 Sonnet)       │
│  • Google Gemini (Gemini 2.5 Flash)      • Groq (Llama 3.3 70B)                │
│  • Mistral AI (Mistral Large)            • Ollama (Local LLMs)                 │
└────────────────────────────────────────────────────────────────────────────────┘
```

---

## ✨ Core Engineering Features

- **📄 Smart Résumé Builder & Versioning**: Real-time drafting with atomic version tracking, restoration, branching duplicates, and SSRF-hardened PDF exports via DomPDF.
- **🤖 Resilient Multi-Provider AI Architecture**: Native support for OpenAI, Anthropic, Gemini, Groq, Mistral, and Ollama. Supports both user-supplied encrypted API keys and team-wide global fallbacks with automatic structured model schema resolution.
- **🛡️ Anti-Hallucination ATS Optimization**: Deep keyword extraction and alignment against job descriptions with strict factual-truthfulness guardrails preventing hallucinated work histories.
- **🔍 Two-Tier CV Parsing**: High-fidelity structured parsing via LLMs with automatic heuristic regex fallback when offline or unconfigured.
- **⚡ Reactive Streaming Generation**: Livewire 4 streaming for cover letters and interview preparation with real-time UI feedback.
- **🎙️ Mock Interview Engine**: Role-tailored behavioral and technical interview questions with automated answer scoring and actionable improvement tips.
- **💼 Kanban Job Application Tracker**: Track application statuses, dates, salaries, interview schedules, and notes.
- **💳 Cashier Subscription System**: Stripe billing portal and checkout workflows backed by atomic monthly quota enforcement.

---

## 🛠️ Technology Stack

| Component | Technology | Description |
| :--- | :--- | :--- |
| **Backend** | [Laravel 12](https://laravel.com) | PHP 8.2+ framework with modern streamlined bootstrap |
| **Frontend** | [Livewire 4](https://livewire.laravel.com) + [Flux UI](https://fluxui.dev) | Server-driven reactive UI with Alpine.js |
| **Styling** | [Tailwind CSS 4](https://tailwindcss.com) | Next-generation engine with zero-config utility classes |
| **AI Driver** | [EchoLabs Prism](https://prismphp.dev) / `laravel/ai` | Multi-provider LLM abstraction with structured schema output |
| **Testing** | [Pest 4](https://pestphp.com) | Expressive testing suite with full mock coverage |
| **Parsing** | [Smalot PDF Parser](https://github.com/smalot/pdfparser) | Native server-side PDF text extraction |
| **Billing** | [Laravel Cashier](https://laravel.com/docs/cashier) | Stripe billing portal, checkout sessions, and webhook handling |

---

## 🚀 Getting Started

### Prerequisites

- **PHP**: `^8.2` (PHP 8.4 recommended) with `pdo`, `mbstring`, `openssl`, `xml`, `curl`
- **Composer**: `v2+`
- **Node.js**: `v20+` & npm
- **Web Server**: [Laravel Herd](https://herd.laravel.com) (Recommended) or `php artisan serve`

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/aslamwebz/HireAI.git
   cd HireAI
   ```

2. **Automated Setup**:
   Run the project setup script which installs dependencies, prepares environment files, generates encryption keys, and executes database migrations:
   ```bash
   composer run setup
   ```

3. **Configure Environment**:
   Update `.env` with your AI credentials (or configure them per-user in the app UI):
   ```env
   AI_PROVIDER=openai
   OPENAI_API_KEY=sk-...

   # Stripe (Optional for subscriptions)
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   STRIPE_PRO_PRICE_ID=price_...
   ```

4. **Start Development Server**:
   ```bash
   composer run dev
   ```
   If using **Laravel Herd**, navigate to `http://hireai.test`. Otherwise open the local server URL displayed in your terminal.

---

## 🧪 Testing & Code Quality

HireAI adheres to test-driven reliability. Every critical flow—from authentication and subscriptions to AI fallback parsing and version collisions—is programmatically tested.

```bash
# Run the complete test suite (79 passing tests)
php artisan test --compact

# Run targeted test suites
php artisan test --filter=ResumeFeatureTest
php artisan test --filter=CvAnalysisTest
php artisan test --filter=SubscriptionTest

# Format code according to Laravel standards (Laravel Pint)
vendor/bin/pint --dirty
```

---

## 📄 License

The HireAI platform is open-source software licensed under the [MIT License](LICENSE).

# HireAI 🚀

**The Ultimate AI-Powered Job Application Suite**

HireAI is a modern, high-performance platform designed to streamline your job search and career growth. Built with the latest Laravel ecosystem, it leverages AI to help you build stunning resumes, optimize them for ATS, analyze your CV, and prepare for interviews.

![HireAI Dashboard](file:///c:/Users/Em/Herd/hireai/public/images/dashboard-preview.png) _(Note: Add actual dashboard screenshot here)_

---

## ✨ Key Features

- **📄 Smart Resume Builder**: Create professional resumes with real-time previews using premium Flux UI components.
- **🤖 AI Content Optimization**: Enhance your resume's impact with AI-powered suggestions for your work experience bullet points.
- **📊 ATS & Keyword Optimization**: Tailor your resume specifically for a job description to improve your ATS ranking.
- **🔍 CV Deep Analysis**: Upload your existing CV and get detailed feedback on readability, impact, and missing skills.
- **✉️ Cover Letter Generator**: Generate personalized, high-converting cover letters for every application.
- **💼 Job Tracker**: Organize your job applications, interviews, and follow-ups in one centralized dashboard.
- **🎙️ Interview Preparation**: Get AI-generated interview questions and practice your answers based on specific job roles.

---

## 🛠️ Technology Stack

| Category           | Technology                                                                                                   |
| :----------------- | :----------------------------------------------------------------------------------------------------------- |
| **Backend**        | [Laravel 12](https://laravel.com)                                                                            |
| **Frontend**       | [Livewire 4](https://livewire.laravel.com), [Flux UI](https://fluxui.dev), [Alpine.js](https://alpinejs.dev) |
| **Styling**        | [Tailwind CSS 4](https://tailwindcss.com)                                                                    |
| **Database**       | SQLite / MySQL                                                                                               |
| **Testing**        | [Pest 4](https://pestphp.com)                                                                                |
| **AI Integration** | OpenAI / Anthropic Integration via `laravel/ai`                                                              |
| **Payment**        | [Stripe](https://stripe.com)                                                                                 |

---

## 🚀 Getting Started

### Prerequisites

- **PHP**: ^8.2 (8.4 recommended)
- **Database**: SQLite (default), MySQL, or PostgreSQL
- **Environment**: [Laravel Herd](https://herd.laravel.com) (Recommended for local development)

### Installation

1. **Clone the repository**:

    ```bash
    git clone https://github.com/your-username/hireai.git
    cd hireai
    ```

2. **Run the setup command**:
   This command installs dependencies, sets up your `.env` file, generates keys, and migrates the database.

    ```bash
    composer run setup
    ```

3. **Configure AI Keys**:
   Edit your `.env` file and add your AI provider credentials:

    ```env
    AI_PROVIDER=openai # or anthropic
    OPENAI_API_KEY=your_key_here
    ```

4. **Start the development server**:
    ```bash
    composer run dev
    ```

Visit `http://hireai.test` (if using Herd) or the output URL from the dev server.

---

## 🧪 Testing & Linting

HireAI maintains high code quality with automated tests and formatting guides.

```bash
# Run all tests
composer test

# Run linting (Laravel Pint)
composer lint
```

---

## 📄 License

The HireAI platform is open-sourced software licensed under the [MIT license](LICENSE).

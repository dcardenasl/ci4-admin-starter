# Documentation Hub: CI4 Admin Starter

Welcome to the official documentation for **CI4 Admin Starter** — a production-ready administrative dashboard template for CodeIgniter 4.

🌐 **Languages:** [English](./INDEX.md) | [Español](./es/INDEX.md)

## 🚀 Getting Started

**New to the project?** Start here:

### 📖 [Quick Start Guide](./QUICK-START.md)
Step-by-step setup instructions for first-time users. Covers installation, configuration, and verification. **5-minute setup guide.**

### ❓ [Frequently Asked Questions (FAQ)](./FAQ.md)
Common questions about architecture, development, testing, deployment, and API integration.

### 🆘 [Troubleshooting Guide](./TROUBLESHOOTING.md)
Solutions for common problems: setup issues, server errors, styling problems, authentication issues, and more.

---

## 📚 Core Documentation

### 🏗️ [Architecture & Core Concepts](./ARCHITECTURE.md)
Deep dive into the system design:
- Server-rendered architecture vs SPA
- ApiClient: HTTP client with auto-token refresh
- Session-based JWT storage
- Security patterns and best practices
- Data flow from form submission to API response

### 🔌 [Services & Validation Layer](./SERVICES.md)
Learn how to communicate with the backend API:
- Service pattern and BaseApiService
- FormRequest validation (rules, payload, error handling)
- Error handling helpers (safeApiCall, failApi, extractData)
- Response normalization

### 🎨 [Frontend & UI Guidelines](./FRONTEND.md)
Build interfaces using our design system:
- Tailwind CSS utility classes
- Alpine.js for client-side interactivity
- Lucide Icons integration
- Reusable components and patterns
- Table management and pagination
- Modal dialogs and confirmations

### 🧪 [Testing & Quality Assurance](./TESTING.md)
Write reliable code with comprehensive tests:
- Unit testing strategy
- Feature testing with mocked ApiClient
- Mocking patterns
- Coverage reports
- Code quality tools (PHPStan, PHP-CS-Fixer)

### 🚀 [Deployment & Production](./DEPLOYMENT.md)
Everything needed for production deployment:
- Environment configuration
- Server setup (Nginx/Apache)
- Security headers and HTTPS
- Performance optimization
- Database and session setup
- Production checklist

---

## 🛠️ How-To Guides

### 📖 [How-To Guide](./HOW-TO.md)
Step-by-step instructions for common tasks:
- Adding a new module/feature
- Creating API endpoints
- Managing sidebar navigation
- Customizing branding and colors
- Adding internationalization (i18n)
- And more...

---

## 🔗 Reference Documentation

| Guide | Purpose |
|-------|---------|
| **[Component Library](./COMPONENTS.md)** | UI components catalog with examples |
| **[API Compatibility](./API-COMPATIBILITY.md)** | Backend/Frontend integration contract |
| **[Validation Layer](./VALIDATION-LAYER.md)** | Detailed FormRequest patterns |
| **[Critical Flows](./CRITICAL-FLOWS.md)** | File upload and JWT refresh workflows |
| **[Google OAuth Setup](./GOOGLE-LOGIN-SETUP.md)** | Google login configuration |

---

## 📊 Documentation Structure

```
docs/
├── INDEX.md                    ← You are here
├── QUICK-START.md             ← Start here first
├── FAQ.md                      ← Common questions
├── TROUBLESHOOTING.md          ← Problem solving
│
├── ARCHITECTURE.md            ← System design
├── SERVICES.md                ← API communication
├── FRONTEND.md                ← UI/UX patterns
├── TESTING.md                 ← Test strategies
├── DEPLOYMENT.md              ← Production checklist
│
├── HOW-TO.md                  ← Feature development guides
├── COMPONENTS.md              ← UI component catalog
├── VALIDATION-LAYER.md        ← FormRequest details
├── API-COMPATIBILITY.md       ← API contract
├── CRITICAL-FLOWS.md          ← Critical workflows
└── GOOGLE-LOGIN-SETUP.md      ← OAuth configuration
```

---

## 🎯 By Role

### 👨‍💻 **Developers**
1. Start with [Quick Start](./QUICK-START.md) to set up
2. Read [Architecture](./ARCHITECTURE.md) to understand the system
3. Follow [Services & Validation](./SERVICES.md) when adding features
4. Refer to [Frontend Guide](./FRONTEND.md) for UI implementation
5. Use [How-To Guide](./HOW-TO.md) for step-by-step instructions

### 🧪 **QA & Testing**
1. Review [Testing Guide](./TESTING.md) for test patterns
2. Check [Troubleshooting](./TROUBLESHOOTING.md) for common issues
3. Understand [Architecture](./ARCHITECTURE.md) for system design
4. Use [Deployment](./DEPLOYMENT.md) for environment setup

### 🚀 **DevOps & Deployment**
1. Read [Deployment Guide](./DEPLOYMENT.md) first
2. Check [Architecture](./ARCHITECTURE.md) for system design
3. Reference [Quick Start](./QUICK-START.md) for configuration
4. Use [FAQ](./FAQ.md) for deployment questions

### 📚 **Project Managers**
1. Review [Architecture](./ARCHITECTURE.md) for system overview
2. Check [How-To Guide](./HOW-TO.md) for feature development process
3. Use [FAQ](./FAQ.md) for common questions
4. See [Deployment](./DEPLOYMENT.md) for release checklist

---

## 🔍 Find Information By Topic

| Topic | Document |
|-------|----------|
| **Setup & Installation** | [Quick Start](./QUICK-START.md) |
| **API Communication** | [Services & Validation](./SERVICES.md) |
| **Building Forms** | [Validation Layer](./VALIDATION-LAYER.md) |
| **Building UI** | [Frontend Guide](./FRONTEND.md) |
| **Adding Features** | [How-To Guide](./HOW-TO.md) |
| **Writing Tests** | [Testing Guide](./TESTING.md) |
| **Deploying** | [Deployment Guide](./DEPLOYMENT.md) |
| **Authentication** | [Architecture](./ARCHITECTURE.md) → Security Patterns |
| **File Uploads** | [Critical Flows](./CRITICAL-FLOWS.md) |
| **JWT Refresh** | [Critical Flows](./CRITICAL-FLOWS.md) |
| **Google OAuth** | [Google OAuth Setup](./GOOGLE-LOGIN-SETUP.md) |
| **Error Handling** | [Services & Validation](./SERVICES.md) → Error Handling |
| **i18n/Localization** | [How-To Guide](./HOW-TO.md) → Localization |
| **Troubleshooting** | [Troubleshooting Guide](./TROUBLESHOOTING.md) |
| **FAQ** | [FAQ](./FAQ.md) |

---

## 💡 Quick Navigation

**Most Viewed Documents:**
1. [Quick Start](./QUICK-START.md) — Getting set up
2. [Architecture](./ARCHITECTURE.md) — Understanding the system
3. [Services & Validation](./SERVICES.md) — Building features
4. [Frontend Guide](./FRONTEND.md) — Creating interfaces
5. [How-To Guide](./HOW-TO.md) — Step-by-step recipes
6. [Troubleshooting](./TROUBLESHOOTING.md) — Solving problems
7. [Deployment](./DEPLOYMENT.md) — Going to production

**Other Resources:**
- **[Code Repository](https://github.com/dcardenasl/ci4-admin-starter)** — Source code and issues
- **[Backend API](https://github.com/dcardenasl/ci4-api-starter)** — Companion backend template
- **[CodeIgniter 4 Docs](https://codeigniter.com/user_guide/)** — Framework reference
- **[Tailwind CSS Docs](https://tailwindcss.com/docs)** — CSS framework
- **[Alpine.js Docs](https://alpinejs.dev/)** — JavaScript library

---

## 📝 Document Versions

All documentation is up-to-date with the latest stable release.

- **Last Updated:** 2026-04-15
- **Version:** Aligned with main branch
- **Status:** ✅ Production Ready

---

## 🤝 Contributing

Have suggestions or found errors in the documentation?

1. Open an issue on [GitHub](https://github.com/dcardenasl/ci4-admin-starter/issues)
2. Submit a pull request with improvements
3. See [CONTRIBUTING.md](../CONTRIBUTING.md) for guidelines

---

**Ready to get started?** → [Quick Start Guide](./QUICK-START.md)

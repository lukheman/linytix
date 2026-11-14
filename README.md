# Laravel Admin Dashboard Template

A modern, elegant admin dashboard template built with Laravel 12, Blade, and Bootstrap 5. Features a dark mode design, reusable Blade components, and user management capabilities.

## ✨ Features

- 🎨 **Modern UI** - Beautiful, modern theme with glassmorphism effects
- ⚡ **Blade + Controllers** - Server-rendered pages with clean Laravel controllers
- 🔐 **Authentication** - Login and registration with server-side validation
- 👥 **User Management** - Full CRUD operations for managing users
- 🧩 **Reusable Components** - Extensive library of Blade components for rapid development
- 📱 **Responsive Design** - Fully responsive layout that works on all devices
- 🎯 **Component Library** - Pre-built UI components including:
  - Buttons, Inputs, Badges, Alerts
  - Tables with pagination
  - Modals, Cards, and Progress Bars
  - Sidebar navigation with sections
  - Topbar with user menu

## 🚀 Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade, Bootstrap 5, Alpine.js
- **Build Tool**: Vite 7.x
- **Testing**: Pest PHP

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL/PostgreSQL/SQLite

## 🛠️ Installation

### Quick Setup

Run the automated setup script:

```bash
composer setup
```

This will:
- Install PHP dependencies
- Create `.env` file from `.env.example`
- Generate application key
- Run database migrations
- Create the public storage link for uploaded files
- Install npm dependencies
- Build frontend assets

### Manual Setup

If you prefer to set up manually:

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd minimal-gravity-admin
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**

   Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Create the public storage link**
   ```bash
   php artisan storage:link
   ```

7. **Install npm dependencies and build assets**
   ```bash
   npm install
   npm run build
   ```

## 🏃 Running the Application

> Make sure `APP_URL` in `.env` matches the URL you visit (for example `http://localhost:8000`). Uploaded avatars use this value when generating their public URLs.

### Development Mode

The easiest way to run the application in development mode:

```bash
composer dev
```

This command starts:
- Laravel development server (http://localhost:8000)
- Queue worker
- Laravel Pail (logs)
- Vite dev server (hot module replacement)

All services run concurrently with color-coded output.

### Access from Other Devices (LAN)

To make the development server reachable from a phone or another computer:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Set `APP_URL` in `.env` to the host machine's LAN address, for example:

```env
APP_URL=http://192.168.1.31:8000
```

Then open `http://192.168.1.31:8000` from the other device. Make sure your firewall allows port `8000`.

### Individual Services

Alternatively, run services separately:

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Terminal 3 (optional) - Queue worker
php artisan queue:listen

# Terminal 4 (optional) - Logs
php artisan pail
```

### Email Delivery

Local development defaults to `MAIL_MAILER=log`, which writes emails to `storage/logs/laravel.log` instead of sending them. To deliver real email (tickets, verification, refunds), configure SMTP in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=api
MAIL_PASSWORD=your-api-token
MAIL_FROM_ADDRESS=no-reply@your-domain.com
```

Verify the configuration:

```bash
php artisan ticketing:mail-test you@example.com
```

Then keep a queue worker running so ticket emails are processed:

```bash
php artisan queue:work --tries=3
```

See [`docs/ops/mail-delivery.md`](docs/ops/mail-delivery.md) for Mailtrap, Gmail SMTP, DNS, and troubleshooting.

## 📁 Project Structure

```
├── app/
│   └── Http/Controllers/
│       ├── Admin/                 # Dashboard, users, profile, component docs
│       ├── Auth/                  # Login and registration
│       └── Guest/                 # Public landing page
├── resources/
│   └── views/
│       ├── admin/                 # Admin pages and partials
│       ├── auth/                  # Login and register pages
│       ├── guest/                 # Public pages
│       └── components/
│           ├── form/              # Form components
│           ├── layout/            # Page layout components
│           ├── layouts/           # App and guest layout wrappers
│           └── ui/                # UI components
├── routes/
│   └── web.php                    # Web routes
└── database/
    └── migrations/                # Database migrations
```

## 🎨 Available Components

### Layout Components

Located in `resources/views/components/layout/`:

- `sidebar.blade.php` - Sidebar navigation
- `topbar.blade.php` - Top navigation bar
- `sidebar-link.blade.php` - Sidebar navigation links
- `sidebar-section.blade.php` - Sidebar section headers
- `page-header.blade.php` - Page header sections
- `modern-card.blade.php` - Modern styled cards
- `feature-card.blade.php` - Feature display cards
- `stat-card.blade.php` - Statistics cards
- `table-card.blade.php` - Table wrapper cards
- `table.blade.php` - Data tables
- `modal.blade.php` / `modal-form.blade.php` - Modal wrappers

### UI Components

Located in `resources/views/components/ui/`:

- `button.blade.php` - Reusable button component
- `input.blade.php` - Form input component
- `badge.blade.php` - Status badges
- `alert.blade.php` - Alert messages
- `confirm-modal.blade.php` - Confirmation dialogs
- `progress-bar.blade.php` - Progress indicators

### Layout Wrappers

- `resources/views/components/layouts/app.blade.php` - Authenticated admin layout
- `resources/views/components/layouts/guest.blade.php` - Public/auth layout

## 🔑 Authentication

The application includes a modern login and registration system:

- **Routes**: `/login`, `/register`
- **Controllers**: `App\Http\Controllers\Auth\LoginController`, `App\Http\Controllers\Auth\RegisterController`
- **Views**: `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`

Protected admin routes require authentication middleware.

## 👥 User Management

Full CRUD interface for managing users:

- **Route**: `/admin/users`
- **Controller**: `App\Http\Controllers\Admin\UserController`
- **Features**:
  - Create new users
  - Edit existing users
  - Delete users
  - Search and filter
  - Pagination

## 👤 Profile Management

Users can manage their own account:

- **Route**: `/admin/profile`
- **Controller**: `App\Http\Controllers\Admin\ProfileController`
- **Features**:
  - Update name and email
  - Upload or remove an avatar
  - Change password

## 🧪 Testing

Run the test suite:

```bash
composer test
# or
php artisan test
```

## 🎯 Usage Examples

### Creating a New Admin Page

1. Create a controller:
   ```bash
   php artisan make:controller Admin/YourPageController
   ```

2. Add a route in `routes/web.php`:
   ```php
   Route::get('/admin/your-page', [YourPageController::class, 'index'])
       ->name('admin.your-page')
       ->middleware('auth');
   ```

3. Wrap your view with the admin layout:
   ```blade
   <x-layouts.app title="Your Page Title">
       <x-layout.page-header title="Your Page Title" subtitle="Page description" />

       <x-layout.modern-card>
           {{-- Your content --}}
       </x-layout.modern-card>
   </x-layouts.app>
   ```

### Using Component Examples

**Button Component:**
```blade
<x-ui.button variant="primary" size="md">
    Save Changes
</x-ui.button>
```

**Input Component:**
```blade
<x-form.input
    label="Email"
    type="email"
    name="email"
    required
/>
```

**Alert Component:**
```blade
<x-ui.alert variant="success">
    Operation completed successfully!
</x-ui.alert>
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 🆘 Support

For issues, questions, or contributions, please open an issue on GitHub.

---

**Built with ❤️ using Laravel and Bootstrap**

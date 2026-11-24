# FoodBlog

An online food blogging and restaurant review platform built with **PHP (MVC) + MySQL**, designed to run out-of-the-box on **XAMPP**.
Visitors browse restaurants and dishes, members review dishes and share Food Experience blog posts, and admins manage the entire platform.

| Role | Permissions & Capabilities |
|---|---|
| **Visitor** | Browse/search restaurants and menu items, read reviews and Food Experience posts |
| **Member** | All of the above + register/login, edit profile (avatar, password), review menu items, write Food Experience posts, comment |
| **Admin** | Full restaurant & menu-item CRUD (with image upload), dashboard statistics, remove members / reviews / posts / comments |

---

## Quick Start (XAMPP Setup)

This project is **folder-name agnostic**. The base URL is automatically detected from where the project is placed inside XAMPP's `htdocs` directory.

### 1. Place the project in `htdocs`
Place this folder directly into your XAMPP `htdocs` directory (`C:\xampp\htdocs\`). Any folder name works:
* `C:\xampp\htdocs\webTec-project-foodBlog\` (from `git clone`)
* `C:\xampp\htdocs\webTec-project-foodBlog-main\` (from GitHub ZIP download)
* `C:\xampp\htdocs\foodBlog\`

### 2. Start XAMPP Services
Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

### 3. Open in Browser
Visit the corresponding URL in your browser:
* If in `webTec-project-foodBlog`: **http://localhost/webTec-project-foodBlog/**
* If in `webTec-project-foodBlog-main`: **http://localhost/webTec-project-foodBlog-main/**
* If in `foodBlog`: **http://localhost/foodBlog/**

> **Automatic Database Setup:** On the first visit, the app connects using default XAMPP credentials (`root`, empty password), creates the `food_blog` database, and imports `config/schema.sql` (tables + demo seed data) automatically.

---

## Database Configuration & Manual Import

| Setting | Value (file: `config/database.php`) |
|---|---|
| Database name | `food_blog` |
| Host / port | `localhost` / `3306` |
| User / password | `root` / *(empty string)* (XAMPP default) |

If your MySQL uses a custom port or root password, update `config/database.php` accordingly.

### Optional: Manual Database Import
If you prefer importing the schema manually:
* **phpMyAdmin**: Go to `http://localhost/phpmyadmin/` → click **Import** → choose `config/schema.sql` → click **Go**.
* **CLI**: Run `mysql -u root < config/schema.sql` from your terminal.

---

## Demo Accounts

The database comes pre-seeded with two accounts:

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@foodblog.com` | `Admin@1234` |
| **Member** | `member@foodblog.com` | `Member@1234` |

You can also create new accounts anytime at `/auth/register`.

---

## Available Routes

| Route | Description |
|---|---|
| `/home` | Home page with live interactive search |
| `/restaurants` | List all restaurants |
| `/restaurants/{id}/show` | Restaurant details & menu item catalog |
| `/restaurants/create`, `/edit`, `/delete` | Admin restaurant management |
| `/menu-items/{id}/show` | Dish details & customer reviews |
| `/menu-items/create`, `/edit`, `/delete` | Admin menu item management |
| `/food-experience` | Food Experience blog feed |
| `/food-experience/{id}/show` | Blog post details & discussion comments |
| `/auth/login`, `/register`, `/profile`, `/logout` | Authentication & profile settings |
| `/admin/dashboard`, `/members`, `/reviews` | Admin dashboard and moderation panels |
| `/api/...` | AJAX API endpoints for live search, reviews, comments, and moderation |

---

## Project Structure

```
├── .htaccess            # Clean URL rewrite rules & directory protection
├── .gitignore           # Git ignore rules for OS, IDE, and uploads
├── index.php            # Front controller and central router
├── config/
│   ├── app.php          # Dynamic BASE_URL detection, session & CSRF helpers
│   ├── database.php     # MySQL connection & auto-schema migration helper
│   └── schema.sql       # Database schema & demo seed data
├── controllers/         # MVC controllers (Auth, Home, Restaurant, MenuItem, FoodExperience, Admin, Api)
├── models/              # Data models with prepared statements
├── views/               # PHP templates (layouts, auth, home, restaurants, menu_items, food_experience, admin)
├── css/                 # Global styles
├── js/                  # AJAX handlers and real-time form validators
└── uploads/             # User uploads (menu items and profile avatars)
    ├── .htaccess        # Script execution protection for uploads
    ├── menu/            # Menu item images (.gitkeep preserved)
    └── profiles/        # User profile pictures (.gitkeep preserved)
```

---

## Troubleshooting

* **404 on every page except home:** Ensure Apache's `mod_rewrite` is enabled in `xampp/apache/conf/httpd.conf` (`LoadModule rewrite_module modules/mod_rewrite.so`) and `AllowOverride All` is set for `htdocs`.
* **Database connection failed:** Ensure MySQL is started in the XAMPP Control Panel and port `3306` is not blocked.
* **Uploads failing:** Ensure `uploads/menu` and `uploads/profiles` are writable by the web server. Uploaded images must be JPEG/PNG up to 2 MB.

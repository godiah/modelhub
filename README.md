# ModelHub

ModelHub is a Laravel-based platform designed to facilitate freelance job postings, vendor-based eCommerce, and an escrow-based payment system. It integrates authentication, CI/CD, and cloud deployment with Laravel Forge.

## 🚀 Features

-   **Authentication System** - User registration, login, and role-based access using Laravel Breeze.
-   **Freelance Job Module** - Enables clients to post jobs and freelancers to apply.
-   **E-Commerce** - Supports vendor dashboards and product management.
-   **Escrow Payment System** - Secure transactions between clients and freelancers.
-   **Admin Panel** - Manage users, vendors, freelancers, and transactions.
-   **GitHub CI/CD Integration** - Automated testing and deployments using Laravel Forge.

## 🛠️ Tech Stack

-   **Backend:** Laravel 12, PHP 8.2
-   **Frontend:** TailwindCSS
-   **Database:** MySQL
-   **Server Management:** Laravel Forge
-   **CI/CD:** GitHub Actions
-   **Containerization:** Docker & Laravel Sail

## 🏗️ Installation Guide

<!-- ### 1️⃣ Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/modelhub.git
cd modelhub
```

### 2️⃣ Install Dependencies

```bash
composer install
npm install && npm run dev
```

### 3️⃣ Set Up Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` file with database credentials.

### 4️⃣ Run Migrations

```bash
php artisan migrate --seed
```

### 5️⃣ Start Development Server

If using Laravel Sail (Docker):

```bash
./vendor/bin/sail up
```

Otherwise, use:

```bash
php artisan serve
```

## 📝 Git Workflow

### Branching Strategy

- `` - Stable production-ready code.
- `` - Ongoing development.
- `` - New features are developed here.

### Creating a Feature Branch

```bash
git checkout -b feature/authentication
git add .
git commit -m "Added authentication system"
git push origin feature/authentication
```

### Merging to Develop

```bash
git checkout develop
git merge feature/authentication
git push origin develop
```

### Merging to Main (After Testing)

```bash
git checkout main
git merge develop
git push origin main
```

## ⚙️ CI/CD with GitHub Actions

This project uses GitHub Actions for automated testing. Tests run when code is pushed to `develop` or a pull request is made to `main`.

To run tests locally:

```bash
php artisan test
```

## 🚀 Deployment with Laravel Forge

1. Create a server on [Laravel Forge](https://forge.laravel.com/).
2. Connect the GitHub repository and deploy the `main` branch.
3. Add the deployment script:
   ```bash
   cd /home/forge/modelhub
   git pull origin main
   composer install --no-dev --prefer-dist
   php artisan migrate --force
   php artisan config:clear
   php artisan cache:clear
   php artisan queue:restart
   ```
4. Click **Deploy Now** to launch the application. -->

## 📄 License

This project is open-source and available under the MIT License.

---

### 📬 Contact & Contributions

Feel free to contribute to the project! Fork the repo, create a feature branch, and open a pull request.

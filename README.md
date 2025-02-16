# Maintenance

## Requirements
- PHP 8.1 or higher
- Composer
- Symfony CLI (optional but recommended)
- MySQL

## Installation

1. **Clone the repository**:
   ```sh
   git clone https://github.com/victorSejournes22005043/Maintenance.git
   cd Maintenance
   ```

2. **Install dependencies**:
   ```sh
   composer install
   ```

3. **Set up environment variables**:
   ```sh
   cp .env .env.local
   ```
   Update the `.env.local` file with your database credentials.

4. **Create the database**:
   ```sh
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Start the development server**:
   ```sh
   symfony serve
   ```

## 🔧 Running Tests
To run tests, use PHPUnit:
```sh
php bin/phpunit
```


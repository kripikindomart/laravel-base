# Laravel Multi-Tenant Setup Guide

This guide will help you set up and run the Laravel Multi-Tenant application with demo data.

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js 18+ and NPM
- Git

## Installation Steps

### 1. Database Setup

Create a MySQL database for the application:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE laravel_multitenant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 2. Environment Configuration

The `.env` file should already be configured. Verify the database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_multitenant
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

Also verify the tenant configuration:

```env
APP_DOMAIN=localhost
TENANT_IDENTIFICATION=subdomain
TENANT_CUSTOM_DOMAIN_ENABLED=true
TENANT_DOMAIN_VERIFICATION=true
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate
```

This will create all necessary tables:
- tenants
- users
- roles
- permissions
- role_has_permissions
- model_has_roles
- model_has_permissions
- activity_logs
- service_logs
- error_logs
- login_logs
- super_admin_logs
- service_health_logs
- services
- service_configs

### 6. Seed Demo Data

```bash
php artisan db:seed
```

This will create:
- **3 Demo Tenants**: Demo Company, Acme Corporation, Tech Startup
- **18 Permissions** per tenant (users, roles, settings, logs, services)
- **3 Roles** per tenant (Administrator, Manager, User)
- **1 Super Admin** (admin@example.com)
- **3 Users per tenant** (admin, manager, regular user)

### 7. Build Frontend Assets

```bash
npm run build
```

For development with hot reload:
```bash
npm run dev
```

### 8. Start the Application

```bash
php artisan serve
```

The application will be available at: `http://localhost:8000`

## Demo Accounts

### Super Administrator
Access the admin panel at: `http://localhost:8000/admin`

- **Email**: admin@example.com
- **Password**: password
- **Access**: Full access to all tenants and super admin features

### Tenant Administrators

#### Demo Company
- **Email**: admin@demo.com
- **Password**: password
- **Access**: Full access to Demo Company tenant

#### Acme Corporation
- **Email**: admin@acme.com
- **Password**: password
- **Access**: Full access to Acme Corporation tenant

#### Tech Startup
- **Email**: admin@techstartup.com
- **Password**: password
- **Access**: Full access to Tech Startup tenant

### Tenant Managers

- **Demo Company**: manager@demo.com (password: password)
- **Acme Corporation**: manager@acme.com (password: password)
- **Tech Startup**: manager@techstartup.com (password: password)

### Regular Users

- **Demo Company**: user@demo.com (password: password)
- **Acme Corporation**: user@acme.com (password: password)
- **Tech Startup**: user@techstartup.com (password: password)

## Accessing Tenants

### Subdomain-based Access (Default)

For local development, you'll need to configure your hosts file to simulate subdomains:

**Windows**: `C:\Windows\System32\drivers\etc\hosts`
**Linux/Mac**: `/etc/hosts`

Add these lines:
```
127.0.0.1 demo.localhost
127.0.0.1 acme.localhost
127.0.0.1 techstartup.localhost
```

Then access tenants at:
- http://demo.localhost:8000
- http://acme.localhost:8000
- http://techstartup.localhost:8000

### Path-based Access (Alternative)

You can also change tenant identification type to 'path' in the admin panel, then access:
- http://localhost:8000/demo
- http://localhost:8000/acme
- http://localhost:8000/techstartup

### Custom Domain (Production)

In production, tenants can set up custom domains with DNS verification. See `CUSTOM_DOMAIN_GUIDE.md` for details.

## Testing the Application

### 1. Test Super Admin Access

1. Go to http://localhost:8000/admin
2. Login with admin@example.com / password
3. You should see the Filament admin panel
4. Navigate to "Tenants" to view all tenants
5. Navigate to "Users" to view all users across all tenants

### 2. Test Tenant Access

1. Add subdomain entries to your hosts file (see above)
2. Go to http://demo.localhost:8000
3. Login with admin@demo.com / password
4. You should only see data for Demo Company tenant

### 3. Test Multi-Tenancy Isolation

1. Login to Demo Company as admin@demo.com
2. Create a test user in Demo Company
3. Logout and login to Acme Corporation as admin@acme.com
4. Verify that you DON'T see the user you created in Demo Company
5. This confirms tenant data isolation is working

### 4. Test Role-Based Access Control

1. Login as admin@demo.com (Administrator role)
   - Should have full access to all features
2. Logout and login as manager@demo.com (Manager role)
   - Should be able to view/create/edit users
   - Should NOT be able to delete roles
3. Logout and login as user@demo.com (User role)
   - Should have limited access
   - Should only be able to view basic information

### 5. Test Activity Logging

The application automatically logs activities. To test:

1. Login as any user
2. Create, edit, or delete a resource
3. Check the activity logs in the admin panel
4. You should see logged activities with user, action, and timestamp

## Development Workflow

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (DANGER: drops all tables)
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Running Seeders

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=SuperAdminSeeder
```

### Creating New Resources

```bash
# Create Filament resource
php artisan make:filament-resource ResourceName

# Create Filament resource with pages
php artisan make:filament-resource ResourceName --generate
```

### Clearing Caches

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Troubleshooting

### Database Connection Error

If you see "SQLSTATE[HY000] [2002] Connection refused":
1. Make sure MySQL is running
2. Verify database credentials in `.env`
3. Create the database if it doesn't exist

### Permission Denied Errors

```bash
# On Linux/Mac, set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Subdomain Not Working

1. Verify hosts file configuration
2. Restart your web browser after modifying hosts
3. Clear browser cache
4. Use incognito/private mode to test

### Filament Panel Not Loading

1. Clear application cache: `php artisan optimize:clear`
2. Rebuild assets: `npm run build`
3. Verify you're using a super admin account for /admin panel

## Next Steps

1. **Customize Filament Resources**: Add more fields, filters, and actions
2. **Set Up Inertia Pages**: Create tenant dashboard pages using Vue 3
3. **Configure Services**: Set up internal and external service integrations
4. **Enable Custom Domains**: Configure DNS and SSL for production
5. **Add Tests**: Write feature and unit tests
6. **Deploy**: Set up production environment with proper domain configuration

## Documentation

- [Custom Domain Guide](CUSTOM_DOMAIN_GUIDE.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Filament Documentation](https://filamentphp.com/docs)
- [Laravel Documentation](https://laravel.com/docs)

## Support

For issues or questions:
1. Check the logs in `storage/logs/laravel.log`
2. Review the implementation summary
3. Consult Laravel and Filament documentation

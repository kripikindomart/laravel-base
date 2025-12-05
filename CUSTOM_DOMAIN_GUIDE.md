# 🌐 Custom Domain Configuration Guide

Panduan lengkap untuk menggunakan custom domain pada aplikasi Laravel Multi-Tenant.

---

## 📋 **Cara Kerja Custom Domain**

Aplikasi ini mendukung **3 jenis tenant identification**:

1. **Subdomain** (Default): `tenant1.yourdomain.com`
2. **Path-based**: `yourdomain.com/tenant1`
3. **Custom Domain** ⭐: `customdomain.com`, `brandshop.id`, dll

---

## 🚀 **Quick Start - Setup Custom Domain**

### **Langkah 1: Set Custom Domain untuk Tenant**

```php
use App\Models\Tenant;

$tenant = Tenant::find(1);

// Set custom domain
$success = $tenant->setCustomDomain('acmecorp.com');

if ($success) {
    echo "Custom domain set successfully!";
    echo "Domain needs verification before it can be used.";
} else {
    echo "Failed to set custom domain. Domain may already be in use.";
}
```

### **Langkah 2: Get DNS Configuration**

```php
$dnsInstructions = $tenant->getDnsInstructions();

/*
Output:
[
    'verification' => [
        'type' => 'TXT',
        'name' => '_tenant_verify',
        'value' => 'tenant-verification=abc123xyz...',
        'ttl' => 3600,
    ],
    'a_record' => [
        'type' => 'A',
        'name' => '@',
        'value' => '123.45.67.89',  // Your server IP
        'ttl' => 3600,
    ],
    'cname_record' => [
        'type' => 'CNAME',
        'name' => 'www',
        'value' => 'acmecorp.com',
        'ttl' => 3600,
    ],
]
*/
```

### **Langkah 3: Tenant Setup DNS di Domain Provider**

Tenant harus menambahkan DNS records di domain provider mereka (Cloudflare, GoDaddy, Namecheap, dll):

#### **DNS Records yang Diperlukan:**

1. **TXT Record** (untuk verification):
   ```
   Name: _tenant_verify
   Value: tenant-verification=abc123xyz...
   TTL: 3600
   ```

2. **A Record** (point domain ke server):
   ```
   Name: @ (atau root)
   Value: 123.45.67.89  (IP server Anda)
   TTL: 3600
   ```

3. **CNAME Record** (untuk www subdomain):
   ```
   Name: www
   Value: acmecorp.com
   TTL: 3600
   ```

### **Langkah 4: Verify Domain**

Setelah DNS records ditambahkan (tunggu 5-30 menit untuk DNS propagation):

```php
$verified = $tenant->verifyDomain();

if ($verified) {
    echo "Domain verified successfully!";
    echo "Custom domain is now active.";
} else {
    echo "Domain verification failed.";
    echo "Please check DNS records and try again.";
}
```

### **Langkah 5: Check Status**

```php
// Check if domain is verified
if ($tenant->isDomainVerified()) {
    echo "Domain is verified ✅";

    // Get tenant URL
    $url = $tenant->getUrl();
    echo "Tenant URL: {$url}";

    // Check SSL status
    $ssl = $tenant->checkSsl();
    if ($ssl['has_ssl'] && $ssl['is_valid']) {
        echo "SSL Certificate: Valid ✅";
    }
}
```

---

## 🔧 **Configuration**

### **Environment Variables**

Edit `.env` file:

```env
# Tenant Configuration
TENANT_IDENTIFICATION=subdomain
TENANT_CUSTOM_DOMAIN_ENABLED=true
APP_DOMAIN=yourdomain.com
CENTRAL_SUBDOMAIN=admin

# Domain Verification
TENANT_DOMAIN_VERIFICATION=true

# SSL Requirement
TENANT_REQUIRE_SSL=true
TENANT_WWW_REDIRECT=remove  # or 'add', 'none'
```

### **Config File** (`config/tenant.php`)

```php
'custom_domain' => [
    // Require domain verification before use
    'require_verification' => true,

    // Allowed TLDs (null = allow all)
    'allowed_tlds' => null,
    // Or restrict: ['com', 'id', 'co.id', 'net']

    // Blocked domains (security)
    'blocked_domains' => [
        'localhost',
        '127.0.0.1',
        'local.test',
    ],
],
```

---

## 💻 **Usage Examples**

### **Example 1: Create Tenant with Custom Domain**

```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'name' => 'Acme Corporation',
    'slug' => 'acme',
    'subdomain' => 'acme',  // Fallback subdomain
    'status' => 'active',
]);

// Set custom domain
$tenant->setCustomDomain('acmecorp.com');

// Get DNS instructions to show to tenant admin
$instructions = $tenant->getDnsInstructions();

return view('tenant.domain-setup', [
    'instructions' => $instructions,
    'tenant' => $tenant,
]);
```

### **Example 2: Verify Domain (Background Job)**

```php
use App\Models\Tenant;
use Illuminate\Console\Command;

class VerifyTenantDomains extends Command
{
    protected $signature = 'tenants:verify-domains';

    public function handle()
    {
        $tenants = Tenant::whereNotNull('domain')
            ->whereJsonDoesntContain('meta->domain_verified', true)
            ->get();

        foreach ($tenants as $tenant) {
            $this->info("Verifying domain for {$tenant->name}...");

            if ($tenant->verifyDomain()) {
                $this->info("✅ {$tenant->domain} verified!");
            } else {
                $this->warn("❌ {$tenant->domain} verification failed");
            }
        }
    }
}
```

Schedule di `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Check domain verification every 30 minutes
    $schedule->command('tenants:verify-domains')->everyThirtyMinutes();
}
```

### **Example 3: Remove Custom Domain**

```php
$tenant = Tenant::find(1);

// Remove custom domain (fallback to subdomain)
$tenant->removeCustomDomain();

echo "Custom domain removed. Tenant now uses: " . $tenant->getUrl();
// Output: https://acme.yourdomain.com
```

### **Example 4: Multi-Domain Support** (Optional)

```php
// If you enable multiple domains per tenant in config:
'features' => [
    'multiple_domains' => true,
],

// You can create a separate table for tenant domains:
Schema::create('tenant_domains', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('domain')->unique();
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_verified')->default(false);
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
});
```

---

## 🔐 **Security Features**

### **1. Domain Validation**

```php
$domainService = app(\App\Services\DomainService::class);

$validation = $domainService->validateDomain('example.com');

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Error: {$error}\n";
    }
}
```

Validasi otomatis check:
- ✅ Format domain valid
- ✅ Domain tidak di-block list
- ✅ Domain belum digunakan tenant lain
- ✅ TLD diizinkan (jika ada restriction)

### **2. DNS Verification**

Tenant harus membuktikan ownership domain dengan menambahkan TXT record:
```
_tenant_verify.example.com TXT "tenant-verification=abc123..."
```

### **3. SSL Check**

```php
$ssl = $tenant->checkSsl();

if ($ssl['has_ssl']) {
    echo "SSL Issuer: {$ssl['issuer']}\n";
    echo "Valid Until: {$ssl['valid_to']}\n";
    echo "Is Valid: " . ($ssl['is_valid'] ? 'Yes' : 'No') . "\n";
}
```

---

## 🌐 **DNS Provider Examples**

### **Cloudflare**

1. Login ke Cloudflare dashboard
2. Select domain
3. Go to DNS → Records
4. Add records:

```
Type    Name              Content
TXT     _tenant_verify    tenant-verification=abc123...
A       @                 123.45.67.89
CNAME   www               yourdomain.com
```

### **GoDaddy / Namecheap**

1. Login ke domain management
2. DNS Management
3. Add records sama seperti di atas

### **Google Domains**

1. DNS → Custom records
2. Add records:
   - TXT: `_tenant_verify` → `tenant-verification=abc123...`
   - A: `@` → `123.45.67.89`
   - CNAME: `www` → `yourdomain.com`

---

## 🔍 **Troubleshooting**

### **Problem: Domain tidak terdeteksi**

**Solution:**
```bash
# Check DNS propagation
dig acmecorp.com
dig TXT _tenant_verify.acmecorp.com

# atau online:
https://dnschecker.org
```

### **Problem: SSL Error**

**Solution:**
1. Pastikan domain sudah point ke server
2. Install SSL certificate (Let's Encrypt):
   ```bash
   sudo certbot --nginx -d acmecorp.com -d www.acmecorp.com
   ```

### **Problem: Verification Token Expired**

**Solution:**
```php
// Generate new token
$domainService = app(\App\Services\DomainService::class);
$newToken = $domainService->generateVerificationToken($tenant);

// Get new DNS instructions
$instructions = $tenant->getDnsInstructions();
```

---

## 📊 **Monitoring**

### **Check All Domains Status**

```php
use App\Models\Tenant;

$domains = Tenant::whereNotNull('domain')
    ->get()
    ->map(function ($tenant) {
        return [
            'tenant' => $tenant->name,
            'domain' => $tenant->domain,
            'verified' => $tenant->isDomainVerified(),
            'ssl' => $tenant->checkSsl(),
        ];
    });

return view('admin.domains-status', ['domains' => $domains]);
```

### **Dashboard Widget Example**

```php
// Show domain verification status in Filament dashboard

use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DomainStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Tenant::whereNotNull('domain')->count();
        $verified = Tenant::whereJsonContains('meta->domain_verified', true)->count();
        $pending = $total - $verified;

        return [
            Stat::make('Total Custom Domains', $total),
            Stat::make('Verified Domains', $verified)
                ->color('success'),
            Stat::make('Pending Verification', $pending)
                ->color('warning'),
        ];
    }
}
```

---

## 🎯 **Best Practices**

1. **Always Verify Domains**: Jangan allow unverified custom domains untuk production
2. **SSL is Required**: Enforce HTTPS untuk semua custom domains
3. **Monitor DNS Changes**: Run verification checks secara berkala
4. **Backup DNS Config**: Save DNS instructions untuk tenant reference
5. **Handle WWW Redirect**: Configure www redirect sesuai preferensi
6. **Rate Limit**: Limit berapa kali tenant bisa change domain (prevent abuse)

---

## 🚀 **Production Checklist**

- [ ] Configure `APP_DOMAIN` di `.env`
- [ ] Set `TENANT_REQUIRE_SSL=true`
- [ ] Setup SSL wildcard certificate untuk subdomains
- [ ] Configure Nginx/Apache untuk handle multiple domains
- [ ] Setup automatic domain verification cron job
- [ ] Configure DNS untuk central domain
- [ ] Test domain verification flow
- [ ] Setup monitoring untuk domain expiration
- [ ] Document DNS setup untuk tenants
- [ ] Configure rate limiting untuk domain changes

---

## 📞 **Support**

Jika ada masalah dengan custom domain setup, check:

1. DNS propagation (bisa 5-30 menit)
2. Server firewall settings
3. Web server (Nginx/Apache) configuration
4. SSL certificate status
5. Application logs: `storage/logs/laravel.log`

---

**Happy Domain Mapping! 🌐**

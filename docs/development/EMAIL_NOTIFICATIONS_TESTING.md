# Email Notifications Testing Guide

**Created:** 2025-10-29
**Module:** Ecommerce Email Notifications
**Status:** ✅ Complete

---

## Overview

This guide provides instructions for testing the email notification system implemented in Phase 4.1.

## Email Notification Types

1. **Order Confirmation** - Sent when order is created after payment
2. **Payment Confirmation** - Sent when payment is captured
3. **Shipping Notification** - Sent when order is marked as shipped
4. **Order Status Update** - Sent when order status changes
5. **Order Cancellation** - Sent when customer cancels order
6. **Return Request** - Sent when customer requests return

---

## Testing in Development

### 1. Configure Mail Driver

For development testing, use the `log` driver to write emails to log files:

```bash
# In .env
MAIL_MAILER=log
```

Emails will be written to: `storage/logs/laravel.log`

### 2. Start Queue Worker

Email notifications are sent asynchronously via queue jobs:

```bash
# Start queue worker
php artisan queue:work --queue=emails

# Or use queue:listen for auto-reload during development
php artisan queue:listen --queue=emails
```

### 3. Test Email Sending with Tinker

```bash
php artisan tinker
```

#### Test Order Confirmation Email

```php
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

$order = SalesOrder::with(['items.product', 'customer', 'checkoutSession'])->first();
$service = app(OrderNotificationService::class);

// Send synchronously (immediate)
$service->sendOrderConfirmation($order, false);

// Send asynchronously (queued)
$service->sendOrderConfirmation($order, true);
```

#### Test Payment Confirmation Email

```php
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

$transaction = PaymentTransaction::with('salesOrder')->first();
$service = app(OrderNotificationService::class);

$service->sendPaymentConfirmation($transaction, false);
```

#### Test Shipping Notification Email

```php
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

$order = SalesOrder::with(['items.product', 'customer'])->first();
$order->update(['tracking_number' => 'TRK-' . date('Ymd') . '-ABC123']);

$service = app(OrderNotificationService::class);
$service->sendShippingNotification($order, false);
```

#### Test Order Cancellation Email

```php
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

$order = SalesOrder::first();
$service = app(OrderNotificationService::class);

$service->sendOrderCancellation($order, 'Customer changed mind', false);
```

#### Test Return Request Email

```php
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

$order = SalesOrder::with(['items.product'])->first();
$service = app(OrderNotificationService::class);

$returnData = [
    'reason' => 'Product defective',
    'items' => [
        [
            'product_id' => $order->items->first()->product_id,
            'quantity' => 1,
            'reason' => 'Screen not working'
        ]
    ]
];

$service->sendReturnRequestConfirmation($order, $returnData, false);
```

---

## Testing with Mailtrap (Recommended)

[Mailtrap](https://mailtrap.io/) provides a fake SMTP server for testing emails without sending to real addresses.

### 1. Create Mailtrap Account

1. Sign up at https://mailtrap.io/
2. Create an inbox
3. Get SMTP credentials

### 2. Configure .env

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourapp.com"
MAIL_FROM_NAME="Your App Name"
```

### 3. Clear Config Cache

```bash
php artisan config:clear
```

### 4. Test Email Sending

Use the Tinker commands above. Emails will appear in your Mailtrap inbox with:
- Full HTML rendering
- Email headers
- SMTP details
- Preview on multiple devices

---

## Testing Complete Checkout Flow

### Step-by-Step Test

```bash
# 1. Start queue worker
php artisan queue:work --queue=emails &

# 2. Create a test order through API (use Postman or curl)
```

#### Create Order via API (Postman/Insomnia)

**1. Initiate Checkout:**
```http
POST /api/v1/checkout/initiate
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "cart_id": 1,
  "contact_email": "customer@test.com",
  "contact_phone": "+52-555-1234"
}
```

**2. Update Address:**
```http
PUT /api/v1/checkout/{sessionId}/address
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "shipping_address": {
    "address_line1": "Test Address 123",
    "city": "Test City",
    "state": "Test State",
    "country": "México",
    "postal_code": "12345"
  }
}
```

**3. Select Shipping:**
```http
PUT /api/v1/checkout/{sessionId}/shipping
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "shipping_method_id": 1
}
```

**4. Process Payment:**
```http
POST /api/v1/checkout/{sessionId}/payment
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "gateway": "mock",
  "payment_method": "card",
  "payment_data": {
    "card_number": "4242424242424242"
  }
}
```

**5. Confirm Payment:**
```http
POST /api/v1/payment/{transactionId}/confirm
Authorization: Bearer {your_token}
```

**Expected Emails:**
1. ✅ Payment Confirmation Email
2. ✅ Order Confirmation Email

### Test Order Status Changes

```bash
php artisan tinker
```

```php
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;

$order = SalesOrder::where('order_source', 'ecommerce')->first();
$service = app(OrderStatusService::class);

// Update to shipped (sends shipping notification + status update)
$service->updateStatus($order, 'shipped', 'Package shipped via FedEx');

// Update to delivered
$service->updateStatus($order, 'delivered', 'Package delivered successfully');

// Update to completed
$service->updateStatus($order, 'completed');
```

**Expected Emails:**
1. ✅ Shipping Notification (when shipped)
2. ✅ Order Status Update (for each status change)

### Test Order Cancellation

```http
POST /api/v1/my-orders/{orderId}/cancel
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "reason": "Changed my mind"
}
```

**Expected Emails:**
1. ✅ Order Cancellation Email

### Test Return Request

```http
POST /api/v1/my-orders/{orderId}/return
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "reason": "Product defective",
  "items": [
    {
      "product_id": 10,
      "quantity": 1,
      "reason": "Screen not working"
    }
  ]
}
```

**Expected Emails:**
1. ✅ Return Request Confirmation (to customer)
2. ✅ Return Request Alert (to admin)

---

## Checking Email Logs

### View Log File

```bash
tail -f storage/logs/laravel.log
```

### Search for Email Content

```bash
# Search for specific email type
grep "Order Confirmation" storage/logs/laravel.log

# View last 100 lines
tail -n 100 storage/logs/laravel.log

# Search for email to specific address
grep "customer@test.com" storage/logs/laravel.log
```

---

## Checking Queue Jobs

### View Queued Jobs

```bash
php artisan queue:monitor emails
```

### View Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry specific job
php artisan queue:retry {job_id}

# Retry all failed jobs
php artisan queue:retry all
```

### Clear Failed Jobs

```bash
php artisan queue:flush
```

---

## Testing Email Templates

### Preview Email Template in Browser

Create a test route (for development only):

```php
// In routes/web.php (temporary)
Route::get('/test-email/{type}', function ($type) {
    $order = \Modules\Sales\Models\SalesOrder::with(['items.product', 'customer', 'checkoutSession'])->first();

    switch ($type) {
        case 'confirmation':
            return new \Modules\Ecommerce\Mail\OrderConfirmationMail($order);

        case 'payment':
            $transaction = \Modules\Ecommerce\Models\PaymentTransaction::with('salesOrder')->first();
            return new \Modules\Ecommerce\Mail\PaymentConfirmationMail($transaction, $order);

        case 'shipping':
            $order->update(['tracking_number' => 'TRK-TEST-123']);
            return new \Modules\Ecommerce\Mail\ShippingNotificationMail($order);

        case 'cancellation':
            return new \Modules\Ecommerce\Mail\OrderCancellationMail($order, 'Test reason');

        default:
            return 'Invalid email type';
    }
})->middleware(['web']);
```

**Access URLs:**
- http://localhost/test-email/confirmation
- http://localhost/test-email/payment
- http://localhost/test-email/shipping
- http://localhost/test-email/cancellation

**⚠️ Important:** Remove this route before deploying to production!

---

## Production Testing

### 1. Use Real SMTP Provider

**Gmail Example:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourcompany.com"
MAIL_FROM_NAME="Your Company"
```

**Note:** For Gmail, you need to create an "App Password" (not your regular password):
1. Go to Google Account Settings
2. Security → 2-Step Verification
3. App Passwords → Generate

**SendGrid Example:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

### 2. Test with Real Email Addresses

Send test emails to your own email addresses before going live:

```php
php artisan tinker

use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;

// Update order email
$order = SalesOrder::first();
$order->checkoutSession->update(['contact_email' => 'your-real-email@gmail.com']);

// Send test email
$service = app(OrderNotificationService::class);
$service->sendOrderConfirmation($order, false);
```

### 3. Monitor Email Delivery

- Check spam folder
- Verify email formatting on multiple devices
- Test on multiple email clients (Gmail, Outlook, Apple Mail)

---

## Troubleshooting

### Emails Not Sending

**1. Check Queue Worker is Running**
```bash
ps aux | grep "queue:work"
```

If not running, start it:
```bash
php artisan queue:work --queue=emails --daemon
```

**2. Check Failed Jobs**
```bash
php artisan queue:failed
```

View error details and retry:
```bash
php artisan queue:retry all
```

**3. Check Mail Configuration**
```bash
php artisan config:clear
php artisan config:cache
```

### Emails Going to Spam

1. **Configure SPF/DKIM records** for your domain
2. **Use authenticated SMTP** provider (SendGrid, Mailgun, etc.)
3. **Warm up your IP** gradually increase email volume
4. **Avoid spam trigger words** in subject lines

### Email Template Not Rendering

**1. Clear View Cache**
```bash
php artisan view:clear
```

**2. Check View Path Registration**
Verify in `Modules/Ecommerce/app/Providers/EcommerceServiceProvider.php`:
```php
$this->loadViewsFrom([$sourcePath], 'ecommerce');
```

**3. Test View Rendering**
```bash
php artisan tinker

$order = \Modules\Sales\Models\SalesOrder::first();
view('ecommerce::emails.order-confirmation', [
    'order' => $order,
    'orderSummary' => [],
    'shippingInfo' => null,
    'isAdmin' => false
])->render();
```

---

## Email Template Customization

### Modifying Email Templates

Email templates are located in:
```
Modules/Ecommerce/resources/views/emails/
```

**Available Templates:**
- `layout.blade.php` - Base layout (modify colors, fonts, structure)
- `order-confirmation.blade.php`
- `payment-confirmation.blade.php`
- `shipping-notification.blade.php`
- `order-status-update.blade.php`
- `order-cancellation.blade.php`
- `order-return-request.blade.php`

### Customization Examples

**Change Primary Color:**
Edit `layout.blade.php`, find:
```css
background-color: #4f46e5;  /* Indigo-600 */
```

Replace with your brand color:
```css
background-color: #dc2626;  /* Red-600 */
```

**Add Company Logo:**
Edit `layout.blade.php` header section:
```html
<td class="email-header">
    <img src="{{ config('app.url') }}/images/logo.png" alt="Logo" style="max-width: 200px;">
    <h1>{{ config('app.name', 'ERP System') }}</h1>
</td>
```

---

## Performance Monitoring

### Monitor Queue Performance

```bash
# Watch queue in real-time
watch -n 1 php artisan queue:monitor emails

# Check queue size
php artisan queue:size emails
```

### Email Sending Metrics

Log email send times and success rates:

```php
// In OrderNotificationService.php, add logging
Log::info('Email sent', [
    'type' => 'order_confirmation',
    'order_id' => $order->id,
    'email' => $recipientEmail,
    'duration' => $duration,
]);
```

---

## Security Considerations

### 1. Rate Limiting

Implement rate limiting for email sending:

```php
// In OrderNotificationService.php
use Illuminate\Support\Facades\RateLimiter;

public function sendOrderConfirmation(SalesOrder $order, bool $async = true): void
{
    $key = 'email:order:' . $order->id;

    if (RateLimiter::tooManyAttempts($key, 1)) {
        return; // Skip if already sent recently
    }

    RateLimiter::hit($key, 300); // 5 minutes

    // Send email...
}
```

### 2. Email Validation

Always validate email addresses before sending:

```php
protected function getOrderEmail(SalesOrder $order): ?string
{
    $email = $order->checkoutSession->contact_email ?? $order->customer->email;

    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    return null;
}
```

### 3. Content Sanitization

Sanitize user-provided content in emails:

```php
// In email templates
{{ e($order->notes) }}  // Escape HTML
{{ strip_tags($userInput) }}  // Remove HTML tags
```

---

## Next Steps

1. ✅ Test all 6 email types in development
2. ✅ Configure Mailtrap for visual testing
3. ✅ Test complete checkout flow
4. ✅ Customize email templates with branding
5. ✅ Set up production SMTP provider
6. ✅ Monitor queue performance
7. ✅ Implement email analytics (optional)

---

**Status:** ✅ Email Notification System Complete
**Last Updated:** 2025-10-29
**Next Phase:** Phase 4.3 (Advanced Ecommerce) or Phase 5.1 (CFDI/Billing)

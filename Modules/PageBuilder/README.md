# 📄 PageBuilder Module

Modern page builder module with GrapesJS integration for dynamic content creation and management.

## Features

- ✅ **Visual Page Builder** - GrapesJS integration for WYSIWYG editing
- ✅ **JSON:API 5.x** - Full compliance with JSON:API specification  
- ✅ **Content Lifecycle** - 6 status options for complete content management
- ✅ **Slug Management** - Unique slug generation and validation
- ✅ **User Association** - Pages linked to creators/editors
- ✅ **Soft Deletes** - Data preservation with recovery options
- ✅ **Activity Logging** - Track all page changes with Spatie ActivityLog

## Status Management

The module supports comprehensive content lifecycle with 6 status options:

| Status | Description | Use Case |
|--------|-------------|----------|
| `draft` | Initial creation | Content being developed |
| `published` | Live content | Ready for public viewing |
| `active` | Featured content | Currently highlighted/promoted |
| `inactive` | Hidden content | Temporarily disabled |
| `archived` | Stored content | Long-term reference storage |
| `deleted` | Marked for removal | Scheduled for deletion |

📋 **[Full Status Reference →](STATUS_REFERENCE.md)**

## API Endpoints

All endpoints follow JSON:API 1.1 specification:

```http
GET    /api/v1/pages              # List all pages
POST   /api/v1/pages              # Create new page
GET    /api/v1/pages/{id}         # Get specific page
PATCH  /api/v1/pages/{id}         # Update page
DELETE /api/v1/pages/{id}         # Delete page
```

### Filtering & Sorting

```http
GET /api/v1/pages?filter[status]=published
GET /api/v1/pages?filter[slug]=homepage
GET /api/v1/pages?sort=title,-createdAt
GET /api/v1/pages?include=user
```

## Database Structure

### Pages Table
- `id` - Primary key
- `title` - Page title (required, max 255)
- `slug` - Unique URL slug (required, max 255)
- `html` - Generated HTML content (nullable)
- `css` - Custom CSS styles (nullable)
- `json` - GrapesJS editor data (nullable, JSON)
- `status` - Content lifecycle status (default: draft)
- `published_at` - Publication timestamp (nullable)
- `user_id` - Creator/owner reference (nullable)
- `created_at` / `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

## Testing

```bash
# Run all PageBuilder tests
php artisan test Modules/PageBuilder

# Run specific test suites
php artisan test Modules/PageBuilder/tests/Feature/PageStoreTest.php
php artisan test Modules/PageBuilder/tests/Feature/PageUpdateTest.php

# Test with specific filters
php artisan test --filter=PageFilterTest
```

## Usage Examples

### Creating a Page
```json
POST /api/v1/pages
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "Welcome Page",
      "slug": "welcome",
      "html": "<div class='hero'>Welcome!</div>",
      "css": ".hero { font-size: 2rem; }",
      "json": { "components": [...], "styles": [...] },
      "status": "draft"
    },
    "relationships": {
      "user": {
        "data": { "type": "users", "id": "1" }
      }
    }
  }
}
```

### Publishing a Page
```json
PATCH /api/v1/pages/123
{
  "data": {
    "type": "pages",
    "id": "123",
    "attributes": {
      "status": "published",
      "publishedAt": "2025-08-08T10:00:00Z"
    }
  }
}
```

## Permissions

Required permissions for operations:

- `pages.index` - View pages list
- `pages.show` - View specific page
- `pages.store` - Create new pages
- `pages.update` - Modify existing pages
- `pages.destroy` - Delete pages

## Factory Usage

```php
use Modules\PageBuilder\Models\Page;

// Create random page
$page = Page::factory()->create();

// Create with specific status
$draft = Page::factory()->draft()->create();
$published = Page::factory()->published()->create();
$active = Page::factory()->active()->create();

// Create with custom data
$page = Page::factory()->create([
    'title' => 'Custom Page',
    'status' => 'published'
]);
```

## Integration Notes

- **Frontend Integration**: JSON data contains GrapesJS components for editor reconstruction
- **SEO Ready**: Unique slugs and published_at timestamps for SEO optimization
- **Performance**: Includes activity logging for audit trails
- **Security**: Proper authorization with granular permissions

---

**Module Version**: 1.2.0  
**Laravel JSON:API**: 5.x  
**Last Updated**: 2025-08-08
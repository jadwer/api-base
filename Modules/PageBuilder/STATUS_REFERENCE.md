# 📋 PageBuilder Status Reference

## Available Status Values

The PageBuilder module supports 6 different status values for comprehensive content lifecycle management:

### Status Options

| Status | Description | Published At | Use Case |
|--------|-------------|--------------|----------|
| `draft` | Initial creation state | `null` | Content being created/edited |
| `published` | Ready for public viewing | Set to current time | Live public content |
| `active` | Currently active/live | Set to current time | Featured/priority content |
| `inactive` | Temporarily disabled | `null` | Content temporarily hidden |
| `archived` | Long-term storage | `null` | Old content kept for reference |
| `deleted` | Marked for deletion | `null` | Content marked for removal |

## API Usage

### Create Page with Status
```json
POST /api/v1/pages
{
  "data": {
    "type": "pages",
    "attributes": {
      "title": "My Page",
      "slug": "my-page",
      "html": "<div>Content</div>",
      "status": "draft"
    }
  }
}
```

### Update Page Status
```json
PATCH /api/v1/pages/{id}
{
  "data": {
    "type": "pages",
    "id": "123",
    "attributes": {
      "status": "published"
    }
  }
}
```

### Filter by Status
```http
GET /api/v1/pages?filter[status]=published
GET /api/v1/pages?filter[status]=active
```

## Factory Usage

```php
// Create pages with specific status
Page::factory()->draft()->create();
Page::factory()->published()->create();
Page::factory()->active()->create();
Page::factory()->inactive()->create();
Page::factory()->archived()->create();
Page::factory()->deleted()->create();
```

## Status Workflow Suggestions

```
draft → published → active
  ↓         ↓         ↓
archived ← inactive → deleted
```

**Created:** 2025-08-08
**Last Updated:** 2025-08-08
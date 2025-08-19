# 🚨 IMPORTANTE - URLs de Documentos Corregidas

## ❌ Problema que tenías:
El frontend estaba intentando acceder directamente a:
```
http://127.0.0.1:8000/storage/contacts/30/documents/archivo.pdf
```
Esto da **403 Forbidden** porque los documentos están protegidos.

## ✅ Solución:

### 1. **Usar las URLs correctas que devuelve la API**

Cuando subes un documento, la respuesta ahora incluye:

```json
{
  "data": {
    "type": "contact-documents",
    "id": "51",
    "attributes": {
      "downloadUrl": "http://localhost:8000/api/v1/contact-documents/51/download",
      "viewUrl": "http://localhost:8000/api/v1/contact-documents/51/view",
      // ... otros atributos
    }
  }
}
```

### 2. **Frontend debe usar estas URLs**

```javascript
// ❌ NO HAGAS ESTO
const badUrl = 'http://127.0.0.1:8000/storage/contacts/30/documents/archivo.pdf';

// ✅ HAZ ESTO
const response = await fetch('/api/v1/contact-documents/upload', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token },
  body: formData
});

const result = await response.json();

// Usar las URLs que devuelve la API
const downloadUrl = result.data.attributes.downloadUrl;
const viewUrl = result.data.attributes.viewUrl;
```

### 3. **Para mostrar documentos en el frontend**

```javascript
// Para descargar
function downloadDocument(documentId) {
  const url = `/api/v1/contact-documents/${documentId}/download`;
  window.open(url + '?token=' + token, '_blank');
}

// Para mostrar imagen en un <img>
function showImage(documentId) {
  const img = document.getElementById('preview');
  img.src = `/api/v1/contact-documents/${documentId}/view?token=${token}`;
}

// Para mostrar PDF en iframe
function showPDF(documentId) {
  const iframe = document.getElementById('pdf-viewer');
  iframe.src = `/api/v1/contact-documents/${documentId}/view?token=${token}`;
}
```

### 4. **Token en URL (para elementos HTML)**

Cuando uses las URLs en elementos HTML que no pueden enviar headers:

```html
<!-- Para imágenes -->
<img src="/api/v1/contact-documents/51/view?token=tu-bearer-token">

<!-- Para PDFs -->
<iframe src="/api/v1/contact-documents/51/view?token=tu-bearer-token"></iframe>

<!-- Para descargas -->
<a href="/api/v1/contact-documents/51/download?token=tu-bearer-token" target="_blank">
  Descargar documento
</a>
```

### 5. **Fetch con Authorization Header (más seguro)**

```javascript
// Para descargas programáticas
async function downloadFile(documentId) {
  const response = await fetch(`/api/v1/contact-documents/${documentId}/download`, {
    headers: { 'Authorization': 'Bearer ' + token }
  });
  
  if (response.ok) {
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'documento.pdf';
    a.click();
  }
}
```

## 🔒 Seguridad

- ✅ Los archivos están protegidos (no acceso directo)
- ✅ Requieren autenticación
- ✅ Solo usuarios autorizados pueden acceder
- ✅ URLs con UUIDs imposibles de adivinar

## 📝 Resumen para Frontend

1. **Nunca uses URLs de `/storage/...` directamente**
2. **Siempre usa los endpoints que proporcionamos:**
   - `/api/v1/contact-documents/{id}/download` 
   - `/api/v1/contact-documents/{id}/view`
3. **Incluye el token** siempre
4. **Usa las URLs que devuelve la API** en las respuestas

¡Ahora debería funcionar perfectamente! 🚀
# 🎯 Guía Simple para Documentos - Frontend

<!-- TODO: Refactorizar con JSON:API de manera empresarial -->
<!-- Esta guía usa endpoints "pegotes" que no siguen el estándar JSON:API -->
<!-- Migrar a Actions dentro del ContactDocumentController JSON:API estándar -->
<!-- Implementar file handling según JSON:API specification -->

## ✅ Tu solución implementada correctamente

Los endpoints de documentos ahora funcionan **exactamente como cualquier otro endpoint de la API**:

### 📷 Para mostrar imagen:
```javascript
// Opción 1: Con fetch (más seguro)
async function showImage(documentId) {
  const response = await fetch(`/api/v1/contact-documents/${documentId}/view`, {
    headers: { 'Authorization': 'Bearer ' + token }
  });
  
  if (response.ok) {
    const blob = await response.blob();
    const imageUrl = URL.createObjectURL(blob);
    document.getElementById('preview').src = imageUrl;
  }
}

// Opción 2: Directamente en HTML (si el browser maneja auth headers)
// Nota: Esto no funciona en todos los browsers para <img>
```

### 📄 Para mostrar PDF (React):
```javascript
// ⚠️ IMPORTANTE: Para React, usa async/await y maneja errores correctamente
async function showPDF(documentId, token) {
  try {
    const response = await fetch(`http://127.0.0.1:8000/api/v1/contact-documents/${documentId}/view`, {
      method: 'GET',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf,*/*'
      }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const blob = await response.blob();
    const pdfUrl = URL.createObjectURL(blob);
    
    // Para mostrar en iframe
    document.getElementById('pdf-viewer').src = pdfUrl;
    
    // O para descargar automáticamente
    const a = document.createElement('a');
    a.href = pdfUrl;
    a.download = `document-${documentId}.pdf`;
    a.click();
    
    // Liberar memoria
    setTimeout(() => URL.revokeObjectURL(pdfUrl), 100);
    
  } catch (error) {
    console.error('Error loading PDF:', error);
  }
}
```

### 📥 Para descargar:
```javascript
async function downloadDocument(documentId, filename) {
  const response = await fetch(`/api/v1/contact-documents/${documentId}/download`, {
    headers: { 'Authorization': 'Bearer ' + token }
  });
  
  if (response.ok) {
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }
}
```

### ✅ Para verificar documentos:
```javascript
// Verificar un documento
async function verifyDocument(documentId, token) {
  const response = await fetch(`http://127.0.0.1:8000/api/v1/contact-documents/${documentId}/verify`, {
    method: 'PATCH',
    headers: { 
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  if (response.ok) {
    const result = await response.json();
    console.log('Document verified:', result.data.attributes);
    return result.data.attributes;
  }
}

// Quitar verificación de un documento
async function unverifyDocument(documentId, token) {
  const response = await fetch(`http://127.0.0.1:8000/api/v1/contact-documents/${documentId}/unverify`, {
    method: 'PATCH',
    headers: { 
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  if (response.ok) {
    const result = await response.json();
    console.log('Document unverified:', result.data.attributes);
    return result.data.attributes;
  }
}
```

## 🔒 Estado Actual de Seguridad:

**✅ Funciona:** Los endpoints sirven los archivos correctamente  
**✅ Autenticación:** Requiere token Bearer válido  
**✅ CORS:** Configurado para localhost:3000
**⚠️ Pendiente:** Agregar autorización granular con permisos específicos

Los endpoints están protegidos por autenticación Sanctum, así que solo usuarios autenticados pueden acceder.

## 🎯 Uso Súper Simple:

```javascript
// 1. Subir documento
const formData = new FormData();
formData.append('contact_id', '27');
formData.append('document_type', 'rfc');
formData.append('file', fileInput.files[0]);

const uploadResponse = await fetch('/api/v1/contact-documents/upload', {
  method: 'POST',
  headers: { 'Authorization': 'Bearer ' + token },
  body: formData
});

// 2. Mostrar documento inmediatamente
if (uploadResponse.ok) {
  const result = await uploadResponse.json();
  const documentId = result.data.id;
  
  // Mostrar imagen
  showImage(documentId);
}
```

## 🚀 Ejemplo HTML Completo:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Documentos Simple</title>
</head>
<body>
    <h1>Documentos</h1>
    
    <!-- Subir -->
    <input type="file" id="fileInput" accept=".pdf,.jpg,.png">
    <button onclick="uploadFile()">Subir</button>
    
    <!-- Mostrar -->
    <div>
        <img id="preview" style="max-width: 300px; display: none;">
        <iframe id="pdfViewer" width="500" height="400" style="display: none;"></iframe>
    </div>

    <script>
        const token = '4|fKKzxTDeGQPDWYV8URorwDpzh9GfahHSOdEF9uCpe04521d2';
        
        async function uploadFile() {
            const file = document.getElementById('fileInput').files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('contact_id', '27');
            formData.append('document_type', 'otros');
            formData.append('file', file);
            
            const response = await fetch('/api/v1/contact-documents/upload', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                body: formData
            });
            
            if (response.ok) {
                const result = await response.json();
                showDocument(result.data.id, result.data.attributes.mimeType);
            }
        }
        
        async function showDocument(documentId, mimeType) {
            const response = await fetch(`/api/v1/contact-documents/${documentId}/view`, {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                
                if (mimeType.startsWith('image/')) {
                    document.getElementById('preview').src = url;
                    document.getElementById('preview').style.display = 'block';
                    document.getElementById('pdfViewer').style.display = 'none';
                } else if (mimeType === 'application/pdf') {
                    document.getElementById('pdfViewer').src = url;
                    document.getElementById('pdfViewer').style.display = 'block';
                    document.getElementById('preview').style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
```

¡Exactamente como querías - simple, elegante y seguro! 🎉

**Próximo paso:** Agregar la autorización con permisos específicos cuando sea necesario.
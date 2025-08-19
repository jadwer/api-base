# 📋 Guía para Frontend - API de Contactos y Documentos

## 🔐 Autenticación

**Todas las peticiones necesitan:**
```javascript
headers: {
  'Authorization': 'Bearer ' + token,
  'Accept': 'application/json'
}
```

---

## 👥 CONTACTOS

### ✅ Listar Contactos
```javascript
// GET /api/v1/contacts
const response = await fetch('/api/v1/contacts', {
  headers: { 'Authorization': 'Bearer ' + token }
});
const data = await response.json();
// data.data = array de contactos
```

### ✅ Ver Contacto con Relaciones
```javascript
// GET /api/v1/contacts/27?include=contactPeople,contactDocuments,contactAddresses
const response = await fetch('/api/v1/contacts/27?include=contactPeople,contactDocuments,contactAddresses', {
  headers: { 'Authorization': 'Bearer ' + token }
});
const data = await response.json();
// data.data = contacto con todas sus relaciones
// data.included = array con personas, documentos y direcciones
```

### ✅ Crear Contacto
```javascript
// POST /api/v1/contacts
const contactData = {
  data: {
    type: "contacts",
    attributes: {
      contactType: "company",           // "company" o "person"
      name: "Empresa ABC",
      legalName: "Empresa ABC S.A. de C.V.",
      taxId: "EMP123456789",
      email: "info@empresa.com",
      phone: "+52-55-1234-5678",
      website: "https://empresa.com",
      status: "active",               // "active" o "inactive"
      isCustomer: true,               // true/false
      isSupplier: false,              // true/false
      creditLimit: 50000.00,
      classification: "A",            // A, B, C
      paymentTerms: 30,               // días
      notes: "Cliente importante"
    }
  }
};

const response = await fetch('/api/v1/contacts', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/vnd.api+json'
  },
  body: JSON.stringify(contactData)
});
const result = await response.json();
// result.data.id = ID del nuevo contacto
```

---

## 📍 DIRECCIONES

### ✅ Agregar Dirección a Contacto
```javascript
// POST /api/v1/contact-addresses
const addressData = {
  data: {
    type: "contact-addresses",
    attributes: {
      contactId: 27,                    // ID del contacto
      addressType: "billing",          // "billing", "shipping", "office"
      addressLine1: "Av. Reforma 123",
      addressLine2: "Piso 5, Oficina 501",
      city: "Ciudad de México",
      state: "CDMX",
      country: "México",
      postalCode: "06600",
      isDefault: true                   // true/false
    }
  }
};

await fetch('/api/v1/contact-addresses', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/vnd.api+json'
  },
  body: JSON.stringify(addressData)
});
```

---

## 👤 PERSONAS DE CONTACTO

### ✅ Agregar Persona a Contacto
```javascript
// POST /api/v1/contact-people  
const personData = {
  data: {
    type: "contact-people",
    attributes: {
      contactId: 27,                    // ID del contacto
      name: "Juan Pérez",
      position: "Gerente de Compras",
      department: "Procurement", 
      email: "juan.perez@empresa.com",
      phone: "+52-55-1234-5679",
      mobile: "+52-55-9876-5432",
      isPrimary: true                   // true/false
    }
  }
};

await fetch('/api/v1/contact-people', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/vnd.api+json'
  },
  body: JSON.stringify(personData)
});
```

---

## 📁 DOCUMENTOS

### ✅ Subir Documento
```javascript
// POST /api/v1/contact-documents/upload
const fileInput = document.getElementById('file-input');
const file = fileInput.files[0];

const formData = new FormData();
formData.append('contact_id', '27');              // ID del contacto  
formData.append('document_type', 'rfc');          // Ver tipos abajo
formData.append('file', file);                    // El archivo
formData.append('notes', 'Documento principal');  // Opcional
formData.append('expires_at', '2025-12-31');      // Opcional

const response = await fetch('/api/v1/contact-documents/upload', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
    // NO pongas Content-Type aquí, FormData lo maneja automáticamente
  },
  body: formData
});

const result = await response.json();
if (response.ok) {
  console.log('Documento subido:', result.data.attributes.downloadUrl);
} else {
  console.error('Error:', result.message);
}
```

**Tipos de documento válidos:**
- `rfc` - RFC
- `cedula_fiscal` - Cédula Fiscal
- `ine` - INE/IFE
- `constancia_sat` - Constancia SAT
- `opinion_sat` - Opinión SAT
- `certificado_sello` - Certificado de Sello
- `comprobante_domicilio` - Comprobante de Domicilio
- `cotizacion` - Cotización
- `orden_compra` - Orden de Compra
- `factura` - Factura
- `contrato` - Contrato
- `otros` - Otros

**Tipos de archivo aceptados:**
- PDF: `.pdf`
- Imágenes: `.jpg`, `.jpeg`, `.png`, `.gif`
- Word: `.doc`, `.docx`
- Excel: `.xls`, `.xlsx`
- **Tamaño máximo:** 10MB

### ✅ Descargar Documento
```javascript
// Abrir descarga en nueva pestaña
const documentId = 51;
const downloadUrl = `/api/v1/contact-documents/${documentId}/download`;
window.open(downloadUrl + '?token=' + token, '_blank');

// O usar fetch para descarga programática
const response = await fetch(`/api/v1/contact-documents/${documentId}/download`, {
  headers: { 'Authorization': 'Bearer ' + token }
});
const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'documento.pdf'; // nombre del archivo
a.click();
```

### ✅ Ver/Preview Documento
```javascript
// Para mostrar imagen o PDF en el navegador
const documentId = 51;
const viewUrl = `/api/v1/contact-documents/${documentId}/view`;

// En un img tag (para imágenes)
document.getElementById('preview').src = viewUrl + '?token=' + token;

// En un iframe (para PDFs)
document.getElementById('pdf-viewer').src = viewUrl + '?token=' + token;
```

---

## 📋 FILTROS Y BÚSQUEDAS

### ✅ Filtrar por Contacto
```javascript
// Obtener direcciones de un contacto específico
const response = await fetch('/api/v1/contact-addresses?filter[contactId]=27', {
  headers: { 'Authorization': 'Bearer ' + token }
});

// Obtener personas de un contacto específico  
const response2 = await fetch('/api/v1/contact-people?filter[contactId]=27', {
  headers: { 'Authorization': 'Bearer ' + token }
});

// Obtener documentos de un contacto específico
const response3 = await fetch('/api/v1/contact-documents?filter[contactId]=27', {
  headers: { 'Authorization': 'Bearer ' + token }
});
```

### ✅ Filtrar Contactos
```javascript
// Solo clientes
const customers = await fetch('/api/v1/contacts?filter[isCustomer]=true', {
  headers: { 'Authorization': 'Bearer ' + token }
});

// Solo proveedores  
const suppliers = await fetch('/api/v1/contacts?filter[isSupplier]=true', {
  headers: { 'Authorization': 'Bearer ' + token }
});

// Por nombre (búsqueda)
const search = await fetch('/api/v1/contacts?filter[name]=Empresa', {
  headers: { 'Authorization': 'Bearer ' + token }
});
```

---

## 🔄 FLUJO COMPLETO - Crear Contacto con Todo

```javascript
async function crearContactoCompleto() {
  const token = 'tu-token-aqui';
  
  try {
    // 1. Crear el contacto
    const contactData = {
      data: {
        type: "contacts",
        attributes: {
          contactType: "company",
          name: "Mi Empresa",
          email: "info@miempresa.com",
          phone: "+52-55-1234-5678",
          isCustomer: true,
          isSupplier: false,
          status: "active"
        }
      }
    };
    
    const contactResponse = await fetch('/api/v1/contacts', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/vnd.api+json'
      },
      body: JSON.stringify(contactData)
    });
    
    const contact = await contactResponse.json();
    const contactId = contact.data.id;
    
    console.log('✅ Contacto creado con ID:', contactId);
    
    // 2. Agregar dirección
    const addressData = {
      data: {
        type: "contact-addresses",
        attributes: {
          contactId: parseInt(contactId),
          addressType: "billing",
          addressLine1: "Calle Principal 123",
          city: "Ciudad de México",
          state: "CDMX",
          country: "México",
          postalCode: "01000",
          isDefault: true
        }
      }
    };
    
    await fetch('/api/v1/contact-addresses', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/vnd.api+json'
      },
      body: JSON.stringify(addressData)
    });
    
    console.log('✅ Dirección agregada');
    
    // 3. Agregar persona
    const personData = {
      data: {
        type: "contact-people",
        attributes: {
          contactId: parseInt(contactId),
          name: "Ana García",
          position: "Gerente General",
          email: "ana@miempresa.com",
          phone: "+52-55-1234-5679",
          isPrimary: true
        }
      }
    };
    
    await fetch('/api/v1/contact-people', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/vnd.api+json'
      },
      body: JSON.stringify(personData)
    });
    
    console.log('✅ Persona agregada');
    
    // 4. Subir documento (si tienes un archivo)
    const fileInput = document.getElementById('file-input');
    if (fileInput.files.length > 0) {
      const formData = new FormData();
      formData.append('contact_id', contactId);
      formData.append('document_type', 'rfc');
      formData.append('file', fileInput.files[0]);
      formData.append('notes', 'RFC de la empresa');
      
      await fetch('/api/v1/contact-documents/upload', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
      });
      
      console.log('✅ Documento subido');
    }
    
    // 5. Obtener contacto completo
    const fullContactResponse = await fetch(`/api/v1/contacts/${contactId}?include=contactPeople,contactDocuments,contactAddresses`, {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    
    const fullContact = await fullContactResponse.json();
    console.log('🎉 Contacto completo:', fullContact);
    
  } catch (error) {
    console.error('❌ Error:', error);
  }
}
```

---

## ⚠️ ERRORES COMUNES

### 1. Token Expirado/Inválido
```javascript
// Respuesta: 401 Unauthorized
{
  "message": "Unauthenticated."
}
```

### 2. Archivo Muy Grande
```javascript
// Respuesta: 422 Validation Error
{
  "message": "The file field must not be greater than 10240 kilobytes."
}
```

### 3. Tipo de Archivo No Permitido
```javascript
// Respuesta: 422 Validation Error  
{
  "message": "The file field must be a file of type: pdf, jpg, jpeg, png, gif, doc, docx, xls, xlsx."
}
```

### 4. Campo Requerido Faltante
```javascript
// Respuesta: 422 Validation Error
{
  "errors": {
    "contact_id": ["The contact_id field is required."]
  }
}
```

---

## 🎯 URLs IMPORTANTES

- **Contactos:** `/api/v1/contacts`
- **Direcciones:** `/api/v1/contact-addresses` 
- **Personas:** `/api/v1/contact-people`
- **Documentos (JSON:API):** `/api/v1/contact-documents`
- **Subir Documento:** `/api/v1/contact-documents/upload`
- **Descargar:** `/api/v1/contact-documents/{id}/download`
- **Ver/Preview:** `/api/v1/contact-documents/{id}/view`

---

## 🚀 CONSEJOS

1. **Siempre incluye el token** en todas las peticiones
2. **Usa FormData** para subir archivos (no JSON)
3. **Usa JSON:API format** para crear/editar datos
4. **Filtra por contactId** para obtener datos relacionados
5. **Usa include** para obtener todo en una petición
6. **Maneja los errores 422** para mostrar validaciones al usuario
7. **parseInt()** los IDs cuando los uses en campos numéricos

¡Con esta guía el frontend debería poder trabajar perfectamente con la API! 🎉
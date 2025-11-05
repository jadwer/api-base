# CRM Module - Frontend Integration Guide

**Módulo CRM - Guía Completa para Integración Frontend**

**Versión:** Phase 1 (3/4 entidades completadas)
**Última actualización:** 2025-11-05

---

## Índice

1. [Resumen del Módulo](#resumen-del-módulo)
2. [Autenticación y Permisos](#autenticación-y-permisos)
3. [Entidades Implementadas](#entidades-implementadas)
4. [API Reference](#api-reference)
5. [Ejemplos de Integración](#ejemplos-de-integración)
6. [Manejo de Errores](#manejo-de-errores)
7. [Mejores Prácticas](#mejores-prácticas)

---

## Resumen del Módulo

El módulo CRM (Customer Relationship Management) gestiona las relaciones con clientes potenciales y campañas de marketing. Incluye:

### Entidades Completadas

| Entidad | Endpoint | Estado | Tests |
|---------|----------|--------|-------|
| **PipelineStage** | `/api/v1/pipeline-stages` | ✅ 100% | 65 tests ✓ |
| **Lead** | `/api/v1/leads` | ✅ 100% | 60+ tests ✓ |
| **Campaign** | `/api/v1/campaigns` | ✅ 100% | 45+ tests ✓ |
| **Activity** | `/api/v1/activities` | ⏳ Pendiente | - |

### Características Principales

- **Gestión de Leads:** Seguimiento completo del ciclo de vida de prospectos
- **Pipeline de Ventas:** Etapas configurables del proceso de ventas
- **Campañas de Marketing:** 6 tipos de campañas con métricas financieras
- **Relaciones:** Leads vinculados a campañas (many-to-many)
- **Validación en Español:** Todos los mensajes de error en español
- **JSON:API 1.1:** Cumplimiento completo del estándar

---

## Autenticación y Permisos

### Headers Requeridos

```javascript
const headers = {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/vnd.api+json',
  'Content-Type': 'application/vnd.api+json'
};
```

### Permisos del Módulo CRM

| Rol | Permisos |
|-----|----------|
| **admin** | Acceso completo (CRUD) a todas las entidades |
| **tech** | Solo lectura (index, show) en todas las entidades |
| **customer** | Sin acceso al módulo CRM |
| **guest** | Sin acceso (requiere autenticación) |

#### Lista de Permisos

```javascript
// PipelineStages
'crm.pipeline-stages.index'
'crm.pipeline-stages.show'
'crm.pipeline-stages.store'
'crm.pipeline-stages.update'
'crm.pipeline-stages.destroy'

// Leads
'crm.leads.index'
'crm.leads.show'
'crm.leads.store'
'crm.leads.update'
'crm.leads.destroy'

// Campaigns
'crm.campaigns.index'
'crm.campaigns.show'
'crm.campaigns.store'
'crm.campaigns.update'
'crm.campaigns.destroy'
```

---

## Entidades Implementadas

### 1. PipelineStage (Etapas del Pipeline)

#### Estructura de Datos

```typescript
interface PipelineStage {
  id: string;
  type: 'pipeline-stages';
  attributes: {
    name: string;                    // Nombre de la etapa
    order: number;                   // Orden de visualización (1-100)
    probability: number;             // Probabilidad de cierre (0-100)
    isActive: boolean;               // Estado activo/inactivo
    color?: string;                  // Color hex para UI (#RRGGBB)
    description?: string;            // Descripción de la etapa
    metadata?: Record<string, any>;  // Datos adicionales
    createdAt: string;               // ISO 8601
    updatedAt: string;               // ISO 8601
  };
  relationships?: {
    leads: {
      data: Array<{ type: 'leads'; id: string }>;
    };
  };
}
```

#### Validaciones

- **name:** Requerido, máximo 255 caracteres
- **order:** Requerido, número entre 1 y 100
- **probability:** Requerido, número entre 0 y 100
- **isActive:** Booleano, default: true
- **color:** Opcional, formato hex (#RRGGBB)

#### Ejemplos de Etapas Típicas

```javascript
const defaultStages = [
  { name: 'Prospección', order: 1, probability: 10, color: '#6B7280' },
  { name: 'Calificación', order: 2, probability: 25, color: '#3B82F6' },
  { name: 'Propuesta', order: 3, probability: 50, color: '#8B5CF6' },
  { name: 'Negociación', order: 4, probability: 75, color: '#F59E0B' },
  { name: 'Cerrado Ganado', order: 5, probability: 100, color: '#10B981' },
  { name: 'Cerrado Perdido', order: 6, probability: 0, color: '#EF4444', isActive: false }
];
```

---

### 2. Lead (Prospecto/Lead)

#### Estructura de Datos

```typescript
interface Lead {
  id: string;
  type: 'leads';
  attributes: {
    title: string;                   // Título del lead (ej: "Implementación ERP")
    status: LeadStatus;              // Estado del lead
    rating: LeadRating;              // Calificación (temperatura)
    source?: string;                 // Origen del lead
    companyName?: string;            // Nombre de la empresa
    email?: string;                  // Email de contacto
    phone?: string;                  // Teléfono de contacto
    estimatedValue?: number;         // Valor estimado (float)
    expectedCloseDate?: string;      // Fecha estimada de cierre (YYYY-MM-DD)
    actualCloseDate?: string;        // Fecha real de cierre (YYYY-MM-DD)
    convertedAt?: string;            // Fecha de conversión (ISO 8601)
    lostReason?: string;             // Razón de pérdida
    notes?: string;                  // Notas adicionales
    metadata?: Record<string, any>;  // Datos personalizados
    createdAt: string;               // ISO 8601
    updatedAt: string;               // ISO 8601
  };
  relationships?: {
    user: {                          // Usuario asignado
      data: { type: 'users'; id: string };
    };
    contact?: {                      // Contacto relacionado (opcional)
      data: { type: 'contacts'; id: string };
    };
    pipelineStage?: {                // Etapa actual
      data: { type: 'pipeline-stages'; id: string };
    };
    campaigns?: {                    // Campañas asociadas
      data: Array<{ type: 'campaigns'; id: string }>;
    };
  };
}

type LeadStatus = 'new' | 'contacted' | 'qualified' | 'proposal' | 'negotiation' | 'converted' | 'lost';

type LeadRating = 'hot' | 'warm' | 'cold';
```

#### Validaciones

- **title:** Requerido, máximo 255 caracteres
- **status:** Opcional, valores: new, contacted, qualified, proposal, negotiation, converted, lost (default: 'new')
- **rating:** Opcional, valores: hot, warm, cold (default: 'warm')
- **email:** Opcional, debe ser email válido
- **estimatedValue:** Opcional, número >= 0
- **userId:** Requerido (relación con User)

#### Estados del Lead (status)

| Estado | Descripción | Siguiente Paso |
|--------|-------------|----------------|
| **new** | Lead recién creado | Contactar |
| **contacted** | Primer contacto realizado | Calificar |
| **qualified** | Lead calificado como válido | Enviar propuesta |
| **proposal** | Propuesta enviada | Negociar |
| **negotiation** | En proceso de negociación | Cerrar |
| **converted** | Convertido a oportunidad/cliente | - |
| **lost** | Perdido (no convertido) | - |

#### Calificaciones (rating)

| Rating | Color | Descripción |
|--------|-------|-------------|
| **hot** | 🔴 Rojo | Alta probabilidad, acción inmediata |
| **warm** | 🟡 Amarillo | Interés medio, seguimiento regular |
| **cold** | 🔵 Azul | Baja prioridad, largo plazo |

---

### 3. Campaign (Campaña)

#### Estructura de Datos

```typescript
interface Campaign {
  id: string;
  type: 'campaigns';
  attributes: {
    name: string;                    // Nombre de la campaña
    type: CampaignType;              // Tipo de campaña
    status: CampaignStatus;          // Estado actual
    startDate: string;               // Fecha inicio (YYYY-MM-DD)
    endDate?: string;                // Fecha fin (YYYY-MM-DD)

    // Métricas Financieras
    budget?: number;                 // Presupuesto planeado
    actualCost?: number;             // Costo real
    expectedRevenue?: number;        // Ingresos esperados
    actualRevenue?: number;          // Ingresos reales

    // Información Adicional
    targetAudience?: string;         // Público objetivo
    description?: string;            // Descripción de la campaña
    metadata?: Record<string, any>;  // Datos personalizados (ej: platform, channels)

    createdAt: string;               // ISO 8601
    updatedAt: string;               // ISO 8601
  };
  relationships?: {
    user: {                          // Usuario responsable
      data: { type: 'users'; id: string };
    };
    leads?: {                        // Leads generados
      data: Array<{ type: 'leads'; id: string }>;
    };
  };
}

type CampaignType =
  | 'email'
  | 'social_media'
  | 'event'
  | 'webinar'
  | 'direct_mail'
  | 'telemarketing';

type CampaignStatus =
  | 'planning'
  | 'active'
  | 'paused'
  | 'completed'
  | 'cancelled';
```

#### Validaciones

- **name:** Requerido, máximo 255 caracteres
- **type:** Requerido, valores: email, social_media, event, webinar, direct_mail, telemarketing
- **status:** Opcional, valores: planning, active, paused, completed, cancelled (default: 'planning')
- **startDate:** Requerido, formato YYYY-MM-DD
- **endDate:** Opcional, debe ser >= startDate
- **budget, actualCost, expectedRevenue, actualRevenue:** Opcionales, números >= 0
- **userId:** Requerido (relación con User)

#### Tipos de Campaña

| Tipo | Descripción | Ejemplo metadata |
|------|-------------|------------------|
| **email** | Campañas de email marketing | `{ email_provider: 'Mailchimp', list_size: 5000 }` |
| **social_media** | Redes sociales (Facebook, LinkedIn, etc.) | `{ platforms: ['Facebook', 'LinkedIn'], ad_budget: 1000 }` |
| **event** | Eventos presenciales | `{ venue: 'Hotel XYZ', expected_attendees: 200 }` |
| **webinar** | Seminarios web | `{ platform: 'Zoom', duration_minutes: 60 }` |
| **direct_mail** | Correo directo físico | `{ pieces_sent: 1000, response_rate: 0.05 }` |
| **telemarketing** | Llamadas telefónicas | `{ calls_made: 500, contacts_reached: 150 }` |

#### Estados de Campaña

| Estado | Descripción | Acciones Disponibles |
|--------|-------------|---------------------|
| **planning** | En planificación | Editar, Activar, Cancelar |
| **active** | Campaña activa | Pausar, Completar, Cancelar |
| **paused** | Temporalmente pausada | Reactivar, Completar, Cancelar |
| **completed** | Finalizada exitosamente | Ver métricas |
| **cancelled** | Cancelada | Ver razón de cancelación |

#### Métricas ROI

```javascript
// Calcular ROI de campaña
function calculateCampaignROI(campaign) {
  const revenue = campaign.attributes.actualRevenue || 0;
  const cost = campaign.attributes.actualCost || 0;

  if (cost === 0) return 0;

  const roi = ((revenue - cost) / cost) * 100;
  return roi.toFixed(2); // Retorna porcentaje
}

// Ejemplo:
// budget: 50000, actualCost: 48000, actualRevenue: 125000
// ROI = ((125000 - 48000) / 48000) * 100 = 160.42%
```

---

## API Reference

### PipelineStages

#### Listar Etapas

```http
GET /api/v1/pipeline-stages
```

**Query Parameters:**
- `sort=order` - Ordenar por orden
- `sort=-createdAt` - Ordenar por fecha (desc)
- `filter[isActive]=true` - Solo activas
- `filter[name]=Prospección` - Buscar por nombre
- `page[size]=20` - Tamaño de página
- `page[number]=1` - Número de página

**Ejemplo JavaScript:**

```javascript
async function getPipelineStages() {
  const response = await fetch(
    '/api/v1/pipeline-stages?sort=order&filter[isActive]=true',
    { headers }
  );

  const { data } = await response.json();
  return data;
}
```

#### Crear Etapa

```http
POST /api/v1/pipeline-stages
```

**Request Body:**

```javascript
const newStage = {
  data: {
    type: 'pipeline-stages',
    attributes: {
      name: 'Calificación',
      order: 2,
      probability: 25,
      isActive: true,
      color: '#3B82F6',
      description: 'Etapa de calificación de leads'
    }
  }
};

const response = await fetch('/api/v1/pipeline-stages', {
  method: 'POST',
  headers,
  body: JSON.stringify(newStage)
});
```

#### Actualizar Etapa

```http
PATCH /api/v1/pipeline-stages/{id}
```

```javascript
const updateStage = {
  data: {
    type: 'pipeline-stages',
    id: '5',
    attributes: {
      probability: 30,
      isActive: false
    }
  }
};

await fetch(`/api/v1/pipeline-stages/${stageId}`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(updateStage)
});
```

#### Eliminar Etapa

```http
DELETE /api/v1/pipeline-stages/{id}
```

```javascript
await fetch(`/api/v1/pipeline-stages/${stageId}`, {
  method: 'DELETE',
  headers
});
```

---

### Leads

#### Listar Leads

```http
GET /api/v1/leads
```

**Query Parameters:**
- `sort=title` - Ordenar alfabéticamente
- `sort=-createdAt` - Más recientes primero
- `filter[status]=qualified` - Filtrar por estado
- `filter[rating]=hot` - Solo leads calientes
- `filter[userId]=5` - Por usuario asignado
- `include=user,contact,pipelineStage,campaigns` - Incluir relaciones

**Ejemplo con Relaciones:**

```javascript
async function getLeadsWithDetails() {
  const response = await fetch(
    '/api/v1/leads?include=user,pipelineStage&sort=-createdAt&page[size]=20',
    { headers }
  );

  const { data, included } = await response.json();

  // Procesar relaciones
  const leads = data.map(lead => ({
    ...lead,
    user: findIncluded(included, lead.relationships.user.data),
    stage: findIncluded(included, lead.relationships.pipelineStage?.data)
  }));

  return leads;
}

function findIncluded(included, ref) {
  if (!ref) return null;
  return included.find(item =>
    item.type === ref.type && item.id === ref.id
  );
}
```

#### Crear Lead

```http
POST /api/v1/leads
```

**Request Body:**

```javascript
const newLead = {
  data: {
    type: 'leads',
    attributes: {
      title: 'Implementación ERP Empresa ABC',
      status: 'new',
      rating: 'hot',
      source: 'website',
      companyName: 'Empresa ABC S.A.',
      email: 'contacto@abc.com',
      phone: '+52 55 1234 5678',
      estimatedValue: 250000.00,
      expectedCloseDate: '2025-12-31',
      notes: 'Contacto inicial vía formulario web. Interés en módulos de ventas e inventario.'
    },
    relationships: {
      user: {
        data: { type: 'users', id: '3' }
      },
      pipelineStage: {
        data: { type: 'pipeline-stages', id: '1' }
      }
    }
  }
};

const response = await fetch('/api/v1/leads', {
  method: 'POST',
  headers,
  body: JSON.stringify(newLead)
});

const { data } = await response.json();
console.log('Lead creado:', data.id);
```

#### Actualizar Lead

```javascript
// Cambiar estado y mover a siguiente etapa
const updateLead = {
  data: {
    type: 'leads',
    id: '15',
    attributes: {
      status: 'qualified',
      rating: 'hot',
      notes: 'Reunión realizada. Interés confirmado.'
    },
    relationships: {
      pipelineStage: {
        data: { type: 'pipeline-stages', id: '2' }
      }
    }
  }
};

await fetch(`/api/v1/leads/15`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(updateLead)
});
```

#### Convertir Lead

```javascript
// Marcar lead como convertido
const convertLead = {
  data: {
    type: 'leads',
    id: '15',
    attributes: {
      status: 'converted',
      actualCloseDate: new Date().toISOString().split('T')[0]
    }
  }
};

await fetch(`/api/v1/leads/15`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(convertLead)
});
```

---

### Campaigns

#### Listar Campañas

```http
GET /api/v1/campaigns
```

**Query Parameters:**
- `sort=name` - Ordenar por nombre
- `sort=-startDate` - Más recientes primero
- `filter[status]=active` - Solo activas
- `filter[type]=email` - Por tipo
- `filter[userId]=5` - Por responsable
- `include=user,leads` - Incluir relaciones

**Dashboard de Campañas:**

```javascript
async function getCampaignsDashboard() {
  // Obtener campañas activas con métricas
  const response = await fetch(
    '/api/v1/campaigns?filter[status]=active&sort=-startDate&include=leads',
    { headers }
  );

  const { data, included } = await response.json();

  // Calcular métricas agregadas
  const metrics = data.reduce((acc, campaign) => {
    const budget = campaign.attributes.budget || 0;
    const actualCost = campaign.attributes.actualCost || 0;
    const actualRevenue = campaign.attributes.actualRevenue || 0;

    return {
      totalBudget: acc.totalBudget + budget,
      totalCost: acc.totalCost + actualCost,
      totalRevenue: acc.totalRevenue + actualRevenue,
      campaignCount: acc.campaignCount + 1
    };
  }, { totalBudget: 0, totalCost: 0, totalRevenue: 0, campaignCount: 0 });

  metrics.averageROI = metrics.totalCost > 0
    ? ((metrics.totalRevenue - metrics.totalCost) / metrics.totalCost * 100).toFixed(2)
    : 0;

  return { campaigns: data, metrics };
}
```

#### Crear Campaña

```http
POST /api/v1/campaigns
```

**Request Body:**

```javascript
const newCampaign = {
  data: {
    type: 'campaigns',
    attributes: {
      name: 'Campaña Email Q4 2025',
      type: 'email',
      status: 'planning',
      startDate: '2025-10-01',
      endDate: '2025-12-31',
      budget: 50000.00,
      expectedRevenue: 150000.00,
      targetAudience: 'PYMEs sector manufactura',
      description: 'Campaña de email marketing enfocada en módulo de inventario',
      metadata: {
        email_provider: 'Mailchimp',
        list_size: 5000,
        send_frequency: 'weekly',
        ab_testing: true
      }
    },
    relationships: {
      user: {
        data: { type: 'users', id: '3' }
      }
    }
  }
};

const response = await fetch('/api/v1/campaigns', {
  method: 'POST',
  headers,
  body: JSON.stringify(newCampaign)
});
```

#### Actualizar Métricas de Campaña

```javascript
// Actualizar costos y resultados reales
const updateMetrics = {
  data: {
    type: 'campaigns',
    id: '8',
    attributes: {
      actualCost: 48500.00,
      actualRevenue: 175000.00,
      status: 'completed',
      endDate: '2025-11-15'
    }
  }
};

await fetch(`/api/v1/campaigns/8`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify(updateMetrics)
});
```

#### Asociar Leads a Campaña

```javascript
// Vincular múltiples leads a una campaña
const addLeadsToCampaign = {
  data: [
    { type: 'leads', id: '12' },
    { type: 'leads', id: '15' },
    { type: 'leads', id: '18' }
  ]
};

await fetch(`/api/v1/campaigns/8/relationships/leads`, {
  method: 'POST',
  headers,
  body: JSON.stringify(addLeadsToCampaign)
});

// Desvincular leads
await fetch(`/api/v1/campaigns/8/relationships/leads`, {
  method: 'DELETE',
  headers,
  body: JSON.stringify({ data: [{ type: 'leads', id: '12' }] })
});
```

---

## Ejemplos de Integración

### Ejemplo 1: Panel de Leads con Kanban

```javascript
// Componente React: LeadsKanban
import { useState, useEffect } from 'react';

function LeadsKanban() {
  const [stages, setStages] = useState([]);
  const [leads, setLeads] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadKanbanData();
  }, []);

  async function loadKanbanData() {
    try {
      // Cargar etapas y leads en paralelo
      const [stagesRes, leadsRes] = await Promise.all([
        fetch('/api/v1/pipeline-stages?sort=order&filter[isActive]=true', { headers }),
        fetch('/api/v1/leads?include=pipelineStage,user&filter[status]=new,contacted,qualified', { headers })
      ]);

      const { data: stagesData } = await stagesRes.json();
      const { data: leadsData, included } = await leadsRes.json();

      setStages(stagesData);
      setLeads(leadsData.map(lead => ({
        ...lead,
        stage: findIncluded(included, lead.relationships.pipelineStage?.data),
        user: findIncluded(included, lead.relationships.user.data)
      })));
    } catch (error) {
      console.error('Error loading kanban data:', error);
    } finally {
      setLoading(false);
    }
  }

  async function moveLeadToStage(leadId, newStageId) {
    const updateData = {
      data: {
        type: 'leads',
        id: leadId,
        relationships: {
          pipelineStage: {
            data: { type: 'pipeline-stages', id: newStageId }
          }
        }
      }
    };

    await fetch(`/api/v1/leads/${leadId}`, {
      method: 'PATCH',
      headers,
      body: JSON.stringify(updateData)
    });

    // Recargar datos
    loadKanbanData();
  }

  if (loading) return <div>Cargando...</div>;

  return (
    <div className="kanban-board">
      {stages.map(stage => (
        <KanbanColumn
          key={stage.id}
          stage={stage}
          leads={leads.filter(lead => lead.stage?.id === stage.id)}
          onDropLead={moveLeadToStage}
        />
      ))}
    </div>
  );
}
```

### Ejemplo 2: Formulario de Creación de Lead

```javascript
// Componente React: CreateLeadForm
function CreateLeadForm({ onSuccess }) {
  const [formData, setFormData] = useState({
    title: '',
    status: 'new',
    rating: 'warm',
    companyName: '',
    email: '',
    phone: '',
    estimatedValue: '',
    userId: '',
    pipelineStageId: ''
  });

  const [users, setUsers] = useState([]);
  const [stages, setStages] = useState([]);
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    loadFormOptions();
  }, []);

  async function loadFormOptions() {
    const [usersRes, stagesRes] = await Promise.all([
      fetch('/api/v1/users?filter[role]=admin,tech', { headers }),
      fetch('/api/v1/pipeline-stages?sort=order&filter[isActive]=true', { headers })
    ]);

    const { data: usersData } = await usersRes.json();
    const { data: stagesData } = await stagesRes.json();

    setUsers(usersData);
    setStages(stagesData);

    // Seleccionar primer usuario y primera etapa por defecto
    if (usersData.length > 0) setFormData(prev => ({ ...prev, userId: usersData[0].id }));
    if (stagesData.length > 0) setFormData(prev => ({ ...prev, pipelineStageId: stagesData[0].id }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    const requestBody = {
      data: {
        type: 'leads',
        attributes: {
          title: formData.title,
          status: formData.status,
          rating: formData.rating,
          companyName: formData.companyName || null,
          email: formData.email || null,
          phone: formData.phone || null,
          estimatedValue: formData.estimatedValue ? parseFloat(formData.estimatedValue) : null
        },
        relationships: {
          user: {
            data: { type: 'users', id: formData.userId }
          },
          pipelineStage: {
            data: { type: 'pipeline-stages', id: formData.pipelineStageId }
          }
        }
      }
    };

    try {
      const response = await fetch('/api/v1/leads', {
        method: 'POST',
        headers,
        body: JSON.stringify(requestBody)
      });

      if (response.ok) {
        const { data } = await response.json();
        onSuccess?.(data);
      } else {
        const { errors: apiErrors } = await response.json();
        // Procesar errores de validación
        const errorMap = {};
        apiErrors.forEach(error => {
          const field = error.source?.pointer?.split('/').pop();
          errorMap[field] = error.detail;
        });
        setErrors(errorMap);
      }
    } catch (error) {
      console.error('Error creating lead:', error);
      setErrors({ general: 'Error al crear el lead' });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <div>
        <label>Título del Lead *</label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => setFormData({ ...formData, title: e.target.value })}
          required
        />
        {errors.title && <span className="error">{errors.title}</span>}
      </div>

      <div>
        <label>Estado</label>
        <select
          value={formData.status}
          onChange={(e) => setFormData({ ...formData, status: e.target.value })}
        >
          <option value="new">Nuevo</option>
          <option value="contacted">Contactado</option>
          <option value="qualified">Calificado</option>
        </select>
      </div>

      <div>
        <label>Calificación</label>
        <select
          value={formData.rating}
          onChange={(e) => setFormData({ ...formData, rating: e.target.value })}
        >
          <option value="hot">🔴 Caliente</option>
          <option value="warm">🟡 Tibio</option>
          <option value="cold">🔵 Frío</option>
        </select>
      </div>

      <div>
        <label>Empresa</label>
        <input
          type="text"
          value={formData.companyName}
          onChange={(e) => setFormData({ ...formData, companyName: e.target.value })}
        />
      </div>

      <div>
        <label>Email</label>
        <input
          type="email"
          value={formData.email}
          onChange={(e) => setFormData({ ...formData, email: e.target.value })}
        />
        {errors.email && <span className="error">{errors.email}</span>}
      </div>

      <div>
        <label>Valor Estimado</label>
        <input
          type="number"
          step="0.01"
          value={formData.estimatedValue}
          onChange={(e) => setFormData({ ...formData, estimatedValue: e.target.value })}
        />
      </div>

      <div>
        <label>Asignar a *</label>
        <select
          value={formData.userId}
          onChange={(e) => setFormData({ ...formData, userId: e.target.value })}
          required
        >
          {users.map(user => (
            <option key={user.id} value={user.id}>
              {user.attributes.name}
            </option>
          ))}
        </select>
      </div>

      <div>
        <label>Etapa Inicial *</label>
        <select
          value={formData.pipelineStageId}
          onChange={(e) => setFormData({ ...formData, pipelineStageId: e.target.value })}
          required
        >
          {stages.map(stage => (
            <option key={stage.id} value={stage.id}>
              {stage.attributes.name} ({stage.attributes.probability}%)
            </option>
          ))}
        </select>
      </div>

      {errors.general && <div className="error-general">{errors.general}</div>}

      <button type="submit" disabled={submitting}>
        {submitting ? 'Creando...' : 'Crear Lead'}
      </button>
    </form>
  );
}
```

### Ejemplo 3: Dashboard de Campañas

```javascript
// Componente React: CampaignsDashboard
function CampaignsDashboard() {
  const [campaigns, setCampaigns] = useState([]);
  const [metrics, setMetrics] = useState({});
  const [filter, setFilter] = useState('active');

  useEffect(() => {
    loadCampaigns();
  }, [filter]);

  async function loadCampaigns() {
    const endpoint = `/api/v1/campaigns?filter[status]=${filter}&sort=-startDate&include=user,leads`;
    const response = await fetch(endpoint, { headers });
    const { data, included } = await response.json();

    // Calcular métricas
    const totals = data.reduce((acc, campaign) => {
      const attrs = campaign.attributes;
      return {
        totalBudget: acc.totalBudget + (attrs.budget || 0),
        totalCost: acc.totalCost + (attrs.actualCost || 0),
        totalRevenue: acc.totalRevenue + (attrs.actualRevenue || 0),
        campaignCount: acc.campaignCount + 1
      };
    }, { totalBudget: 0, totalCost: 0, totalRevenue: 0, campaignCount: 0 });

    totals.roi = totals.totalCost > 0
      ? (((totals.totalRevenue - totals.totalCost) / totals.totalCost) * 100).toFixed(2)
      : 0;

    setCampaigns(data);
    setMetrics(totals);
  }

  return (
    <div className="campaigns-dashboard">
      <div className="metrics-cards">
        <MetricCard
          title="Campañas Activas"
          value={metrics.campaignCount}
          icon="📊"
        />
        <MetricCard
          title="Presupuesto Total"
          value={formatCurrency(metrics.totalBudget)}
          icon="💰"
        />
        <MetricCard
          title="Ingresos Generados"
          value={formatCurrency(metrics.totalRevenue)}
          icon="📈"
        />
        <MetricCard
          title="ROI Promedio"
          value={`${metrics.roi}%`}
          icon="🎯"
          color={metrics.roi > 100 ? 'green' : 'orange'}
        />
      </div>

      <div className="filters">
        <button onClick={() => setFilter('active')} className={filter === 'active' ? 'active' : ''}>
          Activas
        </button>
        <button onClick={() => setFilter('completed')} className={filter === 'completed' ? 'active' : ''}>
          Completadas
        </button>
        <button onClick={() => setFilter('planning')} className={filter === 'planning' ? 'active' : ''}>
          En Planificación
        </button>
      </div>

      <div className="campaigns-list">
        {campaigns.map(campaign => (
          <CampaignCard key={campaign.id} campaign={campaign} />
        ))}
      </div>
    </div>
  );
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value);
}
```

---

## Manejo de Errores

### Estructura de Error JSON:API

```javascript
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "El nombre de la campaña es obligatorio.",
      "source": {
        "pointer": "/data/attributes/name"
      }
    }
  ]
}
```

### Función Helper para Procesar Errores

```javascript
function parseJsonApiErrors(errors) {
  if (!errors || !Array.isArray(errors)) return {};

  const errorMap = {};

  errors.forEach(error => {
    // Extraer campo del pointer: "/data/attributes/name" -> "name"
    const pointer = error.source?.pointer || '';
    const parts = pointer.split('/');
    const field = parts[parts.length - 1];

    if (field) {
      errorMap[field] = error.detail;
    } else {
      // Error general
      errorMap.general = error.detail;
    }
  });

  return errorMap;
}

// Uso:
try {
  const response = await fetch('/api/v1/leads', { method: 'POST', headers, body });

  if (!response.ok) {
    const { errors } = await response.json();
    const errorMap = parseJsonApiErrors(errors);

    // errorMap = { title: "El título es obligatorio.", email: "El email debe ser válido." }
    console.error('Errores de validación:', errorMap);
  }
} catch (error) {
  console.error('Error de red:', error);
}
```

### Mensajes de Error Comunes

| Campo | Error | Mensaje en Español |
|-------|-------|-------------------|
| name | required | El nombre es obligatorio. |
| email | invalid | El email debe ser una dirección válida. |
| estimatedValue | min | El valor estimado no puede ser negativo. |
| endDate | after | La fecha de fin debe ser posterior a la fecha de inicio. |
| type | in | El tipo debe ser uno de los valores permitidos. |

---

## Mejores Prácticas

### 1. Paginación

Siempre usa paginación para listas grandes:

```javascript
// ✅ Bueno
fetch('/api/v1/leads?page[size]=50&page[number]=1', { headers });

// ❌ Malo (puede traer miles de registros)
fetch('/api/v1/leads', { headers });
```

### 2. Incluir Relaciones Solo Cuando Sean Necesarias

```javascript
// ✅ Bueno - Solo incluye lo que necesitas
fetch('/api/v1/leads?include=user', { headers });

// ❌ Malo - Incluye todo innecesariamente
fetch('/api/v1/leads?include=user,contact,pipelineStage,campaigns', { headers });
```

### 3. Usar Filtros para Reducir Carga

```javascript
// ✅ Bueno - Filtra en servidor
fetch('/api/v1/leads?filter[status]=qualified&filter[rating]=hot', { headers });

// ❌ Malo - Trae todo y filtra en cliente
const { data } = await fetch('/api/v1/leads', { headers }).then(r => r.json());
const filtered = data.filter(lead => lead.attributes.status === 'qualified');
```

### 4. Cacheo Inteligente

```javascript
// Ejemplo con React Query
import { useQuery } from '@tanstack/react-query';

function useLeads(filters = {}) {
  const queryKey = ['leads', filters];

  return useQuery({
    queryKey,
    queryFn: () => fetchLeads(filters),
    staleTime: 5 * 60 * 1000, // 5 minutos
    cacheTime: 10 * 60 * 1000, // 10 minutos
  });
}
```

### 5. Manejo de Relaciones Many-to-Many

```javascript
// Vincular leads a campaña (agregar sin eliminar existentes)
await fetch(`/api/v1/campaigns/${campaignId}/relationships/leads`, {
  method: 'POST', // POST = agregar
  headers,
  body: JSON.stringify({
    data: [
      { type: 'leads', id: '12' },
      { type: 'leads', id: '15' }
    ]
  })
});

// Reemplazar todos los leads de una campaña
await fetch(`/api/v1/campaigns/${campaignId}/relationships/leads`, {
  method: 'PATCH', // PATCH = reemplazar
  headers,
  body: JSON.stringify({
    data: [
      { type: 'leads', id: '20' }
    ]
  })
});

// Desvincular leads específicos
await fetch(`/api/v1/campaigns/${campaignId}/relationships/leads`, {
  method: 'DELETE', // DELETE = eliminar
  headers,
  body: JSON.stringify({
    data: [
      { type: 'leads', id: '12' }
    ]
  })
});
```

### 6. Actualizaciones Optimistas

```javascript
// Actualización optimista en UI
function updateLeadStatus(leadId, newStatus) {
  // 1. Actualizar UI inmediatamente
  setLeads(prevLeads =>
    prevLeads.map(lead =>
      lead.id === leadId
        ? { ...lead, attributes: { ...lead.attributes, status: newStatus } }
        : lead
    )
  );

  // 2. Enviar actualización al servidor
  fetch(`/api/v1/leads/${leadId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify({
      data: {
        type: 'leads',
        id: leadId,
        attributes: { status: newStatus }
      }
    })
  })
  .catch(error => {
    // 3. Revertir si falla
    console.error('Error updating lead:', error);
    loadLeads(); // Recargar desde servidor
  });
}
```

### 7. Validación en Cliente

```javascript
// Validar antes de enviar al servidor
function validateLeadForm(formData) {
  const errors = {};

  if (!formData.title || formData.title.trim() === '') {
    errors.title = 'El título es obligatorio';
  }

  if (formData.email && !isValidEmail(formData.email)) {
    errors.email = 'Email inválido';
  }

  if (formData.estimatedValue && parseFloat(formData.estimatedValue) < 0) {
    errors.estimatedValue = 'El valor no puede ser negativo';
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
```

---

## Próximos Pasos

### Entidad Pendiente: Activity

La entidad Activity (Actividades) está planificada para Phase 1 del módulo CRM. Permitirá:

- Registrar interacciones con leads (llamadas, emails, reuniones, notas)
- Historial completo de actividades
- Programación de tareas futuras
- Vinculación con leads, contacts y campaigns

**Estructura preliminar:**

```typescript
interface Activity {
  type: 'call' | 'email' | 'meeting' | 'note' | 'task';
  subject: string;
  description?: string;
  activityDate: string;
  duration?: number; // minutos
  outcome?: string;
  userId: string; // responsable
  leadId?: string;
  contactId?: string;
  campaignId?: string;
}
```

### Roadmap CRM Module

- [x] **Phase 1.1:** PipelineStage (Completado - 65 tests)
- [x] **Phase 1.2:** Lead (Completado - 60+ tests)
- [x] **Phase 1.3:** Campaign (Completado - 45+ tests)
- [ ] **Phase 1.4:** Activity (Pendiente)
- [ ] **Phase 2:** Opportunities (Oportunidades de venta)
- [ ] **Phase 3:** Quotes (Cotizaciones/Propuestas)
- [ ] **Phase 4:** Custom actions (convertir lead, cerrar oportunidad, etc.)

---

## Soporte y Recursos

### Documentación Adicional

- [JSON:API Specification](https://jsonapi.org/)
- [Laravel JSON:API Documentation](https://laraveljsonapi.io/)
- [General Frontend Integration Guide](/docs/FRONTEND_INTEGRATION_GUIDE.md)

### Testing de Endpoints

Puedes probar los endpoints usando herramientas como:

- **Postman/Insomnia:** Importa la colección JSON:API
- **cURL:** Ejemplos en esta guía
- **Cliente HTTP de VS Code:** Rest Client extension

### Reporte de Issues

Si encuentras errores o inconsistencias:

1. Revisa los tests del módulo: `Modules/CRM/tests/Feature/`
2. Verifica la documentación de validaciones en los Request classes
3. Consulta los schemas para ver campos disponibles: `Modules/CRM/app/JsonApi/V1/*/`

---

**Última actualización:** 2025-11-05
**Versión del módulo:** Phase 1 (3/4 entidades)
**Estado:** En desarrollo activo

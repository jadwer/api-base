<?php

/**
 * Catalogo de permisos: fuente unica de nombres legibles.
 *
 * Los ~554 permisos siguen la grilla `recurso.verbo`. En lugar de 554
 * entradas a mano, el catalogo define 118 recursos (nombre en espanol,
 * genero y modulo de negocio) y plantillas por verbo CRUD; los verbos
 * especiales (timbrar, aprobar, exportar...) van en `overrides` por
 * nombre completo. PermissionCatalog::resolve() compone label y
 * description; PermissionCatalogSeeder los persiste en la tabla
 * permissions (columnas label/description/module/resource).
 *
 * Un permiso nuevo DEBE agregarse aqui: el test de integridad
 * PermissionCatalogCoverageTest falla si un permiso sembrado no tiene
 * entrada (regla 7: dato del sistema valido por construccion).
 */

return [

    // Plantillas por verbo CRUD. Placeholders: {singular} {plural} {article}.
    'verbs' => [
        'index' => [
            'label' => 'Ver lista de {plural}',
            'description' => 'Permiso que permite ver la lista de {plural}',
        ],
        'show' => [
            'label' => 'Ver {singular}',
            'description' => 'Permiso que permite ver el detalle de {article} {singular}',
        ],
        'store' => [
            'label' => 'Agregar {singular}',
            'description' => 'Permiso que permite agregar {article} {singular}',
        ],
        'update' => [
            'label' => 'Editar {singular}',
            'description' => 'Permiso que permite editar {article} {singular}',
        ],
        'destroy' => [
            'label' => 'Borrar {singular}',
            'description' => 'Permiso que permite borrar {article} {singular}',
        ],
    ],

    // Modulos de negocio (agrupacion para la UI de roles).
    'modules' => [
        'usuarios' => 'Usuarios',
        'roles-permisos' => 'Roles y permisos',
        'contactos' => 'Contactos',
        'productos' => 'Productos',
        'inventario' => 'Inventario',
        'cotizaciones' => 'Cotizaciones',
        'ventas' => 'Ventas',
        'comisiones' => 'Comisiones',
        'compras' => 'Compras',
        'finanzas' => 'Finanzas',
        'contabilidad' => 'Contabilidad',
        'reportes' => 'Reportes',
        'facturacion' => 'Facturación CFDI',
        'crm' => 'CRM',
        'ecommerce' => 'E-commerce',
        'rrhh' => 'Recursos Humanos',
        'page-builder' => 'Page Builder',
        'correos' => 'Correos',
        'configuracion' => 'Configuración',
        'sistema' => 'Sistema',
    ],

    // resource => [module, singular, plural, article (un|una)]
    'resources' => [
        // Usuarios
        'users' => ['usuarios', 'usuario', 'usuarios', 'un'],
        'profile' => ['usuarios', 'perfil propio', 'perfiles', 'un'],

        // Roles y permisos
        'roles' => ['roles-permisos', 'rol', 'roles', 'un'],
        'permissions' => ['roles-permisos', 'permiso', 'permisos', 'un'],

        // Contactos
        'contacts' => ['contactos', 'contacto', 'contactos', 'un'],
        'contact-addresses' => ['contactos', 'dirección de contacto', 'direcciones de contacto', 'una'],
        'contact-documents' => ['contactos', 'documento de contacto', 'documentos de contacto', 'un'],
        'contact-people' => ['contactos', 'persona de contacto', 'personas de contacto', 'una'],
        'customers' => ['contactos', 'cliente', 'clientes', 'un'],
        'suppliers' => ['contactos', 'proveedor', 'proveedores', 'un'],

        // Productos
        'products' => ['productos', 'producto', 'productos', 'un'],
        'categories' => ['productos', 'categoría', 'categorías', 'una'],
        'brands' => ['productos', 'marca', 'marcas', 'una'],
        'units' => ['productos', 'unidad de medida', 'unidades de medida', 'una'],
        'product-images' => ['productos', 'imagen de producto', 'imágenes de producto', 'una'],
        'product-variants' => ['productos', 'variante de producto', 'variantes de producto', 'una'],
        'variant-attributes' => ['productos', 'atributo de variante', 'atributos de variante', 'un'],
        'variant-attribute-values' => ['productos', 'valor de atributo de variante', 'valores de atributo de variante', 'un'],

        // Inventario
        'warehouses' => ['inventario', 'almacén', 'almacenes', 'un'],
        'warehouse-locations' => ['inventario', 'ubicación de almacén', 'ubicaciones de almacén', 'una'],
        'stocks' => ['inventario', 'existencia', 'existencias', 'una'],
        'inventory-movements' => ['inventario', 'movimiento de inventario', 'movimientos de inventario', 'un'],
        'product-batches' => ['inventario', 'lote de producto', 'lotes de producto', 'un'],
        'product-conversions' => ['inventario', 'conversión de producto', 'conversiones de producto', 'una'],
        'fractionations' => ['inventario', 'fraccionamiento', 'fraccionamientos', 'un'],
        'inventory.cycle-counts' => ['inventario', 'conteo cíclico', 'conteos cíclicos', 'un'],
        'backorders' => ['inventario', 'pedido pendiente por stock', 'pedidos pendientes por stock', 'un'],
        'shipments' => ['inventario', 'envío', 'envíos', 'un'],
        'shipment-items' => ['inventario', 'partida de envío', 'partidas de envío', 'una'],

        // Cotizaciones
        'quotes' => ['cotizaciones', 'cotización', 'cotizaciones', 'una'],
        'quote-items' => ['cotizaciones', 'partida de cotización', 'partidas de cotización', 'una'],

        // Ventas
        'sales-orders' => ['ventas', 'orden de venta', 'órdenes de venta', 'una'],
        'sales-order-items' => ['ventas', 'partida de orden de venta', 'partidas de orden de venta', 'una'],
        'remissions' => ['ventas', 'remisión', 'remisiones', 'una'],
        'remission-items' => ['ventas', 'partida de remisión', 'partidas de remisión', 'una'],
        'discount-rules' => ['ventas', 'regla de descuento', 'reglas de descuento', 'una'],

        // Comisiones
        'commissions' => ['comisiones', 'comisión', 'comisiones', 'una'],

        // Compras
        'purchase-orders' => ['compras', 'orden de compra', 'órdenes de compra', 'una'],
        'purchase-order-items' => ['compras', 'partida de orden de compra', 'partidas de orden de compra', 'una'],
        'purchase' => ['compras', 'compra', 'compras', 'una'],
        'budgets' => ['compras', 'presupuesto', 'presupuestos', 'un'],
        'budget-allocations' => ['compras', 'asignación de presupuesto', 'asignaciones de presupuesto', 'una'],

        // Finanzas
        'ap-invoices' => ['finanzas', 'factura por pagar', 'facturas por pagar', 'una'],
        'ar-invoices' => ['finanzas', 'factura por cobrar', 'facturas por cobrar', 'una'],
        'ar-payments' => ['finanzas', 'pago de cliente', 'pagos de clientes', 'un'],
        'payments' => ['finanzas', 'pago', 'pagos', 'un'],
        'payment-applications' => ['finanzas', 'aplicación de pago', 'aplicaciones de pago', 'una'],
        'payment-methods' => ['finanzas', 'método de pago', 'métodos de pago', 'un'],
        'bank-accounts' => ['finanzas', 'cuenta bancaria', 'cuentas bancarias', 'una'],
        'bank-transactions' => ['finanzas', 'transacción bancaria', 'transacciones bancarias', 'una'],
        'exchange-rates' => ['finanzas', 'tipo de cambio', 'tipos de cambio', 'un'],
        'exchange-rate-policies' => ['finanzas', 'política de tipo de cambio', 'políticas de tipo de cambio', 'una'],

        // Contabilidad
        'accounts' => ['contabilidad', 'cuenta contable', 'cuentas contables', 'una'],
        'account-balances' => ['contabilidad', 'saldo contable', 'saldos contables', 'un'],
        'account-mappings' => ['contabilidad', 'mapeo contable', 'mapeos contables', 'un'],
        'journals' => ['contabilidad', 'diario contable', 'diarios contables', 'un'],
        'journal-entries' => ['contabilidad', 'asiento contable', 'asientos contables', 'un'],
        'journal-lines' => ['contabilidad', 'línea de asiento', 'líneas de asiento', 'una'],
        'journal-sequences' => ['contabilidad', 'consecutivo de diario', 'consecutivos de diario', 'un'],
        'fiscal-periods' => ['contabilidad', 'período fiscal', 'períodos fiscales', 'un'],

        // Reportes
        'reports.analytics' => ['reportes', 'dashboard analítico', 'dashboards analíticos', 'un'],
        'reports.ap-aging-reports' => ['reportes', 'reporte de antigüedad por pagar', 'reportes de antigüedad por pagar', 'un'],
        'reports.ar-aging-reports' => ['reportes', 'reporte de antigüedad por cobrar', 'reportes de antigüedad por cobrar', 'un'],
        'reports.balance-sheets' => ['reportes', 'balance general', 'balances generales', 'un'],
        'reports.cash-flows' => ['reportes', 'flujo de efectivo', 'flujos de efectivo', 'un'],
        'reports.income-statements' => ['reportes', 'estado de resultados', 'estados de resultados', 'un'],
        'reports.purchase-by-product-reports' => ['reportes', 'reporte de compras por producto', 'reportes de compras por producto', 'un'],
        'reports.purchase-by-supplier-reports' => ['reportes', 'reporte de compras por proveedor', 'reportes de compras por proveedor', 'un'],
        'reports.sales-advanced' => ['reportes', 'reporte avanzado de ventas', 'reportes avanzados de ventas', 'un'],
        'reports.sales-by-customer-reports' => ['reportes', 'reporte de ventas por cliente', 'reportes de ventas por cliente', 'un'],
        'reports.sales-by-product-reports' => ['reportes', 'reporte de ventas por producto', 'reportes de ventas por producto', 'un'],
        'reports.sales-history' => ['reportes', 'histórico de ventas', 'históricos de ventas', 'un'],
        'reports.trial-balances' => ['reportes', 'balanza de comprobación', 'balanzas de comprobación', 'una'],

        // Facturacion CFDI
        'billing.cfdi-invoices' => ['facturacion', 'factura CFDI', 'facturas CFDI', 'una'],
        'billing.cfdi-items' => ['facturacion', 'partida de factura CFDI', 'partidas de factura CFDI', 'una'],
        'billing.company-settings' => ['facturacion', 'configuración fiscal de la empresa', 'configuraciones fiscales', 'una'],
        'billing.document-legends' => ['facturacion', 'leyenda de documento', 'leyendas de documento', 'una'],
        'billing.invoice-series' => ['facturacion', 'serie de facturación', 'series de facturación', 'una'],
        'billing.payment-transactions' => ['facturacion', 'transacción de pago Stripe', 'transacciones de pago Stripe', 'una'],

        // CRM
        'crm.activities' => ['crm', 'actividad de CRM', 'actividades de CRM', 'una'],
        'crm.campaigns' => ['crm', 'campaña', 'campañas', 'una'],
        'crm.leads' => ['crm', 'lead', 'leads', 'un'],
        'crm.opportunities' => ['crm', 'oportunidad', 'oportunidades', 'una'],
        'crm.pipeline-stages' => ['crm', 'etapa de pipeline', 'etapas de pipeline', 'una'],
        'crm.quotes' => ['crm', 'cotización de CRM', 'cotizaciones de CRM', 'una'],
        'crm.quote-items' => ['crm', 'partida de cotización de CRM', 'partidas de cotización de CRM', 'una'],

        // E-commerce
        'ecommerce.shopping-carts' => ['ecommerce', 'carrito de compras', 'carritos de compras', 'un'],
        'ecommerce.cart-items' => ['ecommerce', 'artículo de carrito', 'artículos de carrito', 'un'],
        'ecommerce.checkout-sessions' => ['ecommerce', 'sesión de checkout', 'sesiones de checkout', 'una'],
        'ecommerce.coupons' => ['ecommerce', 'cupón', 'cupones', 'un'],
        'ecommerce.currencies' => ['ecommerce', 'moneda', 'monedas', 'una'],
        'ecommerce.inventory-reservations' => ['ecommerce', 'reserva de inventario', 'reservas de inventario', 'una'],
        'ecommerce.payment-transactions' => ['ecommerce', 'transacción de pago de la tienda', 'transacciones de pago de la tienda', 'una'],
        'ecommerce.product-answers' => ['ecommerce', 'respuesta de producto', 'respuestas de producto', 'una'],
        'ecommerce.product-questions' => ['ecommerce', 'pregunta de producto', 'preguntas de producto', 'una'],
        'ecommerce.product-comparisons' => ['ecommerce', 'comparación de productos', 'comparaciones de productos', 'una'],
        'ecommerce.product-comparison-items' => ['ecommerce', 'elemento de comparación', 'elementos de comparación', 'un'],
        'ecommerce.product-reviews' => ['ecommerce', 'reseña de producto', 'reseñas de producto', 'una'],
        'ecommerce.shipping-methods' => ['ecommerce', 'método de envío', 'métodos de envío', 'un'],
        'ecommerce.wishlists' => ['ecommerce', 'lista de deseos', 'listas de deseos', 'una'],
        'ecommerce.wishlist-items' => ['ecommerce', 'artículo de lista de deseos', 'artículos de lista de deseos', 'un'],

        // Recursos Humanos
        'hr.employees' => ['rrhh', 'empleado', 'empleados', 'un'],
        'hr.departments' => ['rrhh', 'departamento', 'departamentos', 'un'],
        'hr.positions' => ['rrhh', 'puesto', 'puestos', 'un'],
        'hr.attendances' => ['rrhh', 'asistencia', 'asistencias', 'una'],
        'hr.leaves' => ['rrhh', 'permiso laboral', 'permisos laborales', 'un'],
        'hr.leave-types' => ['rrhh', 'tipo de permiso laboral', 'tipos de permiso laboral', 'un'],
        'hr.payroll-periods' => ['rrhh', 'período de nómina', 'períodos de nómina', 'un'],
        'hr.payroll-items' => ['rrhh', 'concepto de nómina', 'conceptos de nómina', 'un'],
        'hr.performance-reviews' => ['rrhh', 'evaluación de desempeño', 'evaluaciones de desempeño', 'una'],

        // Page Builder
        'page' => ['page-builder', 'página', 'páginas', 'una'],

        // Correos
        'email-templates' => ['correos', 'plantilla de correo', 'plantillas de correo', 'una'],
        'system-emails' => ['correos', 'correo del sistema', 'correos del sistema', 'un'],

        // Configuracion
        'sales.folio-sequences' => ['configuracion', 'secuencia de folios', 'secuencias de folios', 'una'],

        // Sistema
        'audit' => ['sistema', 'registro de auditoría', 'registros de auditoría', 'un'],
        'audit-logs' => ['sistema', 'bitácora de auditoría', 'bitácoras de auditoría', 'una'],
        'system-health' => ['sistema', 'estado del sistema', 'estados del sistema', 'un'],
        'idempotency-keys' => ['sistema', 'llave de idempotencia', 'llaves de idempotencia', 'una'],
    ],

    // Verbos fuera de la grilla CRUD: nombre completo => [label, description].
    'overrides' => [
        'profile.show' => ['Ver perfil propio', 'Permiso que permite ver el perfil de la propia cuenta'],
        'profile.update' => ['Editar perfil propio', 'Permiso que permite editar el perfil de la propia cuenta'],

        'permissions.assign' => ['Asignar permisos', 'Permiso que permite asignar permisos a un rol o usuario'],
        'permissions.revoke' => ['Revocar permisos', 'Permiso que permite revocar permisos de un rol o usuario'],

        'commissions.pay' => ['Pagar comisiones', 'Permiso que permite marcar comisiones como pagadas'],

        'purchase.approve-new-supplier' => ['Aprobar proveedor nuevo', 'Permiso que permite aprobar órdenes de compra con proveedor nuevo'],
        'purchase.approve-tier1' => ['Aprobar compras nivel 1', 'Permiso que permite aprobar órdenes de compra del nivel 1 de monto'],
        'purchase.approve-tier2' => ['Aprobar compras nivel 2', 'Permiso que permite aprobar órdenes de compra del nivel 2 de monto'],
        'purchase.approve-tier3' => ['Aprobar compras nivel 3', 'Permiso que permite aprobar órdenes de compra del nivel 3 de monto'],

        'billing.cfdi-invoices.stamp' => ['Timbrar factura', 'Permiso que permite timbrar una factura CFDI ante el PAC'],
        'billing.cfdi-invoices.cancel' => ['Cancelar factura', 'Permiso que permite solicitar la cancelación de una factura CFDI'],
        'billing.cfdi-invoices.cancellation-status' => ['Consultar estatus de cancelación', 'Permiso que permite consultar el estatus de cancelación de una factura CFDI'],
        'billing.cfdi-invoices.download-pdf' => ['Descargar PDF de factura', 'Permiso que permite descargar el PDF de una factura CFDI'],
        'billing.cfdi-invoices.download-xml' => ['Descargar XML de factura', 'Permiso que permite descargar el XML de una factura CFDI'],
        'billing.cfdi-invoices.generate-pdf' => ['Generar PDF de factura', 'Permiso que permite generar el PDF de una factura CFDI'],
        'billing.cfdi-invoices.generate-xml' => ['Generar XML de factura', 'Permiso que permite generar el XML de una factura CFDI'],
        'billing.cfdi-invoices.preview-pdf' => ['Previsualizar PDF de factura', 'Permiso que permite previsualizar el PDF de una factura CFDI antes de timbrar'],
        'billing.cfdi-invoices.prefactura' => ['Generar prefactura', 'Permiso que permite generar una prefactura desde una orden o cotización'],
        'billing.cfdi-invoices.payment-complement' => ['Emitir complemento de pago', 'Permiso que permite emitir un complemento de pago (REP) para una factura'],
        'billing.cfdi-invoices.validate' => ['Validar factura', 'Permiso que permite validar los datos de una factura CFDI'],

        'billing.company-settings.test-pac' => ['Probar conexión con el PAC', 'Permiso que permite probar la conexión con el proveedor de timbrado'],
        'billing.company-settings.upload-certificate' => ['Subir certificado CSD', 'Permiso que permite subir el certificado de sello digital'],
        'billing.company-settings.upload-key' => ['Subir llave privada CSD', 'Permiso que permite subir la llave privada del sello digital'],

        'audit.export' => ['Exportar auditoría', 'Permiso que permite exportar los registros de auditoría'],

        'system-health.index' => ['Ver estado del sistema', 'Permiso que permite ver el resumen de salud del sistema'],
        'system-health.database' => ['Ver salud de base de datos', 'Permiso que permite ver el diagnóstico de la base de datos'],
        'system-health.errors' => ['Ver errores del sistema', 'Permiso que permite ver los errores recientes del sistema'],
        'system-health.metrics' => ['Ver métricas del sistema', 'Permiso que permite ver las métricas de desempeño del sistema'],
        'system-health.queue' => ['Ver estado de colas', 'Permiso que permite ver el estado de las colas de trabajos'],
        'system-health.storage' => ['Ver almacenamiento', 'Permiso que permite ver el estado del almacenamiento en disco'],
    ],
];

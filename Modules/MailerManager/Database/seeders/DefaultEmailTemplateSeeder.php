<?php

namespace Modules\MailerManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MailerManager\Models\EmailTemplate;
use Modules\MailerManager\Models\SystemEmail;

class DefaultEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        // Auto-assign templates to system emails
        $this->assignTemplatesToSystemEmails();
    }

    protected function assignTemplatesToSystemEmails(): void
    {
        $templateIds = EmailTemplate::pluck('id', 'slug')->toArray();

        $mapping = [
            'sales.quote_requested.admin' => 'quote-requested-admin',
            'sales.quote_requested.customer' => 'quote-requested-customer',
            'sales.quote_sent' => 'quote-sent',
            'sales.quote_converted.customer' => 'quote-converted',
            'sales.quote_converted.admin' => 'quote-converted',
            'ecommerce.order_confirmation.customer' => 'order-confirmation-customer',
            'ecommerce.order_confirmation.admin' => 'order-confirmation-admin',
            'ecommerce.payment_confirmation' => 'payment-confirmation',
            'ecommerce.shipping_notification' => 'shipping-notification',
            'ecommerce.order_status_update' => 'order-status-update',
            'ecommerce.order_cancellation' => 'order-cancellation',
            'ecommerce.order_return.customer' => 'order-return-customer',
            'ecommerce.order_return.admin' => 'order-return-admin',
            'auth.welcome' => 'welcome',
        ];

        foreach ($mapping as $key => $slug) {
            if (isset($templateIds[$slug])) {
                SystemEmail::where('key', $key)->update(['email_template_id' => $templateIds[$slug]]);
            }
        }
    }

    protected function getTemplates(): array
    {
        return [
            $this->quoteRequestedAdmin(),
            $this->quoteRequestedCustomer(),
            $this->quoteSent(),
            $this->quoteConverted(),
            $this->orderConfirmationCustomer(),
            $this->orderConfirmationAdmin(),
            $this->paymentConfirmation(),
            $this->shippingNotification(),
            $this->orderStatusUpdate(),
            $this->orderCancellation(),
            $this->orderReturnCustomer(),
            $this->orderReturnAdmin(),
            $this->welcome(),
        ];
    }

    protected function baseStyles(): string
    {
        return '
            body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f7; }
            .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: #1a3764; color: #ffffff; padding: 20px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 22px; font-weight: bold; }
            .content { padding: 30px; color: #333333; line-height: 1.6; }
            .content h2 { color: #1a3764; margin-top: 0; }
            .info-box { background: #f0f4f8; border-left: 4px solid #1a3764; padding: 15px; margin: 15px 0; border-radius: 4px; }
            .success-box { background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; margin: 15px 0; border-radius: 4px; }
            .warning-box { background: #fffbeb; border-left: 4px solid #d97706; padding: 15px; margin: 15px 0; border-radius: 4px; }
            .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .items-table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px; text-align: left; font-size: 13px; color: #64748b; }
            .items-table td { border-bottom: 1px solid #e2e8f0; padding: 10px; font-size: 14px; }
            .items-table .text-right { text-align: right; }
            .totals-table { width: 100%; margin: 10px 0; }
            .totals-table td { padding: 5px 10px; font-size: 14px; }
            .totals-table .total-row td { font-weight: bold; font-size: 16px; border-top: 2px solid #1a3764; color: #1a3764; }
            .button { display: inline-block; background: #1a3764; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
            .footer { background: #f8fafc; padding: 20px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
            .label { font-weight: bold; color: #64748b; font-size: 13px; }
        ';
    }

    protected function quoteRequestedAdmin(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Nueva Solicitud de Cotizacion</h1></div>'
            . '<div class="content">'
            . '<p>Se ha recibido una nueva solicitud de cotizacion que requiere revision.</p>'
            . '<div class="info-box">'
            . '<p class="label">Datos de la solicitud</p>'
            . '<p><strong>Numero:</strong> {{quote_number}}</p>'
            . '<p><strong>Fecha:</strong> {{quote_date}}</p>'
            . '<p><strong>Cliente:</strong> {{customer_name}}</p>'
            . '<p><strong>Email:</strong> {{customer_email}}</p>'
            . '</div>'
            . '<table class="items-table"><thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>'
            . '<tbody>{{#each items}}<tr><td>{{name}}</td><td>{{sku}}</td><td>{{quantity}}</td><td class="text-right">{{price|currency}}</td><td class="text-right">{{subtotal|currency}}</td></tr>{{/each}}</tbody></table>'
            . '<table class="totals-table"><tr class="total-row"><td class="text-right">Total:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr></table>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Solicitud de Cotizacion (Admin)',
            'slug' => 'quote-requested-admin',
            'description' => 'Notificacion al admin cuando se recibe una solicitud de cotizacion',
            'subject' => 'Nueva solicitud de cotizacion {{quote_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function quoteRequestedCustomer(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Solicitud Recibida</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Hemos recibido su solicitud de cotizacion <strong>{{quote_number}}</strong>.</p>'
            . '<div class="info-box"><p>Nuestro equipo revisara su solicitud y le enviara la cotizacion formal con precios y tiempos de entrega a la brevedad.</p></div>'
            . '<table class="items-table"><thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th></tr></thead>'
            . '<tbody>{{#each items}}<tr><td>{{name}}</td><td>{{sku}}</td><td>{{quantity}}</td></tr>{{/each}}</tbody></table>'
            . '<p>Si tiene alguna pregunta sobre su solicitud, no dude en contactarnos.</p>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Solicitud de Cotizacion (Cliente)',
            'slug' => 'quote-requested-customer',
            'description' => 'Confirmacion al cliente de que su solicitud fue recibida',
            'subject' => 'Su solicitud de cotizacion {{quote_number}} ha sido recibida',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function quoteSent(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Cotizacion {{quote_number}}</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Adjunto encontrara la cotizacion solicitada. A continuacion le presentamos un resumen:</p>'
            . '<div class="info-box">'
            . '<p class="label">Datos de la cotizacion</p>'
            . '<p><strong>Numero:</strong> {{quote_number}}</p>'
            . '<p><strong>Fecha:</strong> {{quote_date}}</p>'
            . '<p><strong>Valida hasta:</strong> {{valid_until}}</p>'
            . '</div>'
            . '<table class="items-table"><thead><tr><th>Producto</th><th>Cantidad</th><th class="text-right">Precio Unitario</th><th class="text-right">Subtotal</th></tr></thead>'
            . '<tbody>{{#each items}}<tr><td>{{name}}</td><td>{{quantity}}</td><td class="text-right">{{price|currency}}</td><td class="text-right">{{subtotal|currency}}</td></tr>{{/each}}</tbody></table>'
            . '<table class="totals-table">'
            . '<tr><td class="text-right">Subtotal:</td><td class="text-right">{{subtotal|currency}}</td></tr>'
            . '<tr><td class="text-right">I.V.A.:</td><td class="text-right">{{tax|currency}}</td></tr>'
            . '<tr class="total-row"><td class="text-right">Total:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr>'
            . '</table>'
            . '<p>Por favor revise la cotizacion adjunta para mas detalles.</p>'
            . '<div class="success-box"><p>Para aceptar esta cotizacion, responda a este correo o comuniquese con nuestro equipo de ventas.</p></div>'
            . '{{#if notes}}<div class="info-box"><p class="label">Notas:</p><p>{{notes}}</p></div>{{/if}}'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Cotizacion Enviada',
            'slug' => 'quote-sent',
            'description' => 'Email enviado al cliente con la cotizacion formal y PDF adjunto',
            'subject' => 'Cotizacion {{quote_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function quoteConverted(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Cotizacion Convertida a Orden</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Su cotizacion <strong>{{quote_number}}</strong> ha sido convertida en la orden de venta <strong>{{order_number}}</strong>.</p>'
            . '<div class="success-box"><p>Nuestro equipo procesara su pedido a la brevedad.</p></div>'
            . '<table class="totals-table"><tr class="total-row"><td class="text-right">Total:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr></table>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Cotizacion Convertida a Orden',
            'slug' => 'quote-converted',
            'description' => 'Notificacion cuando una cotizacion se convierte en orden de venta',
            'subject' => 'Su cotizacion {{quote_number}} ha sido convertida en orden',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderConfirmationCustomer(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Confirmacion de Orden</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Gracias por su orden. Su pedido <strong>{{order_number}}</strong> ha sido recibido.</p>'
            . '<div class="info-box"><p><strong>Fecha:</strong> {{order_date}}</p><p><strong>Estado:</strong> {{status}}</p></div>'
            . '<table class="items-table"><thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>'
            . '<tbody>{{#each items}}<tr><td>{{name}}</td><td>{{sku}}</td><td>{{quantity}}</td><td class="text-right">{{unit_price|currency}}</td><td class="text-right">{{subtotal|currency}}</td></tr>{{/each}}</tbody></table>'
            . '<table class="totals-table">'
            . '<tr><td class="text-right">Subtotal:</td><td class="text-right">{{subtotal|currency}}</td></tr>'
            . '<tr><td class="text-right">I.V.A.:</td><td class="text-right">{{tax|currency}}</td></tr>'
            . '<tr class="total-row"><td class="text-right">Total:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr>'
            . '</table>'
            . '{{#if shipping_address}}<div class="info-box"><p class="label">Direccion de envio:</p><p>{{shipping_address}}</p></div>{{/if}}'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Confirmacion de Orden (Cliente)',
            'slug' => 'order-confirmation-customer',
            'description' => 'Confirmacion al cliente de que su orden fue recibida',
            'subject' => 'Confirmacion de tu orden {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderConfirmationAdmin(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Nueva Orden Recibida</h1></div>'
            . '<div class="content">'
            . '<p>Se ha recibido una nueva orden que requiere atencion.</p>'
            . '<div class="info-box">'
            . '<p><strong>Orden:</strong> {{order_number}}</p>'
            . '<p><strong>Cliente:</strong> {{customer_name}}</p>'
            . '<p><strong>Email:</strong> {{customer_email}}</p>'
            . '<p><strong>Fecha:</strong> {{order_date}}</p>'
            . '</div>'
            . '<table class="items-table"><thead><tr><th>Producto</th><th>SKU</th><th>Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>'
            . '<tbody>{{#each items}}<tr><td>{{name}}</td><td>{{sku}}</td><td>{{quantity}}</td><td class="text-right">{{unit_price|currency}}</td><td class="text-right">{{subtotal|currency}}</td></tr>{{/each}}</tbody></table>'
            . '<table class="totals-table"><tr class="total-row"><td class="text-right">Total:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr></table>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Nueva Orden (Admin)',
            'slug' => 'order-confirmation-admin',
            'description' => 'Notificacion al admin de nueva orden recibida',
            'subject' => 'Nueva orden recibida: {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function paymentConfirmation(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Pago Confirmado</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Hemos recibido su pago exitosamente.</p>'
            . '<div class="success-box">'
            . '<p><strong>Orden:</strong> {{order_number}}</p>'
            . '<p><strong>Monto:</strong> {{payment_amount|currency}} {{currency}}</p>'
            . '<p><strong>Metodo:</strong> {{payment_method}}</p>'
            . '<p><strong>Fecha:</strong> {{payment_date}}</p>'
            . '<p><strong>Transaccion:</strong> {{transaction_id}}</p>'
            . '</div>'
            . '<p>Su pedido sera procesado a la brevedad.</p>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Confirmacion de Pago',
            'slug' => 'payment-confirmation',
            'description' => 'Confirmacion al cliente de pago procesado',
            'subject' => 'Pago confirmado - Orden {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function shippingNotification(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Tu Pedido ha sido Enviado</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Su pedido <strong>{{order_number}}</strong> ha sido enviado.</p>'
            . '<div class="success-box">'
            . '<p><strong>Paqueteria:</strong> {{carrier}}</p>'
            . '<p><strong>Numero de rastreo:</strong> {{tracking_number}}</p>'
            . '<p><strong>Entrega estimada:</strong> {{estimated_delivery}}</p>'
            . '</div>'
            . '{{#if shipping_address}}<div class="info-box"><p class="label">Direccion de entrega:</p><p>{{shipping_address}}</p></div>{{/if}}'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Notificacion de Envio',
            'slug' => 'shipping-notification',
            'description' => 'Se envia al cliente cuando su pedido ha sido enviado',
            'subject' => 'Tu pedido {{order_number}} ha sido enviado',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderStatusUpdate(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Actualizacion de tu Orden</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>El estado de su orden <strong>{{order_number}}</strong> ha cambiado.</p>'
            . '<div class="info-box">'
            . '<p><strong>Estado anterior:</strong> {{previous_status}}</p>'
            . '<p><strong>Nuevo estado:</strong> {{new_status}}</p>'
            . '</div>'
            . '{{#if notes}}<div class="info-box"><p>{{notes}}</p></div>{{/if}}'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Cambio de Estado de Orden',
            'slug' => 'order-status-update',
            'description' => 'Se envia cuando el estado de una orden cambia',
            'subject' => 'Actualizacion de tu orden {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderCancellation(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Orden Cancelada</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Su orden <strong>{{order_number}}</strong> ha sido cancelada.</p>'
            . '{{#if cancellation_reason}}<div class="warning-box"><p><strong>Razon:</strong> {{cancellation_reason}}</p></div>{{/if}}'
            . '<table class="totals-table"><tr class="total-row"><td class="text-right">Total cancelado:</td><td class="text-right">{{total|currency}} {{currency}}</td></tr></table>'
            . '<p>Si tiene alguna pregunta, no dude en contactarnos.</p>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Cancelacion de Orden',
            'slug' => 'order-cancellation',
            'description' => 'Se envia al cliente cuando su orden es cancelada',
            'subject' => 'Orden {{order_number}} cancelada',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderReturnCustomer(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Solicitud de Devolucion Recibida</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{customer_name}},</p>'
            . '<p>Hemos recibido su solicitud de devolucion para la orden <strong>{{order_number}}</strong>.</p>'
            . '<div class="info-box"><p><strong>Razon:</strong> {{return_reason}}</p></div>'
            . '<div class="success-box"><p>Nuestro equipo revisara su solicitud y le contactara a la brevedad con los pasos a seguir.</p></div>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Devolucion (Cliente)',
            'slug' => 'order-return-customer',
            'description' => 'Confirmacion al cliente de solicitud de devolucion recibida',
            'subject' => 'Solicitud de devolucion recibida - Orden {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function orderReturnAdmin(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Nueva Solicitud de Devolucion</h1></div>'
            . '<div class="content">'
            . '<p>Se ha recibido una nueva solicitud de devolucion.</p>'
            . '<div class="info-box">'
            . '<p><strong>Orden:</strong> {{order_number}}</p>'
            . '<p><strong>Cliente:</strong> {{customer_name}}</p>'
            . '<p><strong>Email:</strong> {{customer_email}}</p>'
            . '<p><strong>Razon:</strong> {{return_reason}}</p>'
            . '</div>'
            . '<div class="warning-box"><p>Accion requerida: Revisar la solicitud y contactar al cliente.</p></div>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Devolucion (Admin)',
            'slug' => 'order-return-admin',
            'description' => 'Notificacion al admin de nueva solicitud de devolucion',
            'subject' => 'Nueva solicitud de devolucion - Orden {{order_number}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'transactional',
        ];
    }

    protected function welcome(): array
    {
        $html = '<div class="email-container">'
            . '<div class="header"><h1>Bienvenido a {{company_name}}</h1></div>'
            . '<div class="content">'
            . '<p>Estimado/a {{user_name}},</p>'
            . '<p>Tu cuenta ha sido creada exitosamente.</p>'
            . '<p>Ya puedes acceder al sistema para explorar nuestro catalogo de productos, solicitar cotizaciones y gestionar tus pedidos.</p>'
            . '<div style="text-align: center; margin: 25px 0;">'
            . '<a href="{{login_url}}" class="button">Acceder al Sistema</a>'
            . '</div>'
            . '<p>Si tienes alguna duda, no dudes en contactarnos.</p>'
            . '</div>'
            . '<div class="footer"><p><strong>{{company_name}}</strong></p></div>'
            . '</div>';

        return [
            'name' => 'Bienvenida al Sistema',
            'slug' => 'welcome',
            'description' => 'Email de bienvenida cuando un usuario se registra',
            'subject' => 'Bienvenido a {{company_name}}',
            'html' => $html,
            'css' => $this->baseStyles(),
            'status' => 'active',
            'category' => 'notification',
        ];
    }
}

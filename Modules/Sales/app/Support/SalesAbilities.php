<?php

namespace Modules\Sales\Support;

use Illuminate\Support\Facades\Gate;
use Modules\Sales\Models\Quote;

/**
 * Habilidades con nombre de las acciones de ventas (flujo guiado, F2, D3).
 * Cada accion que la guia ofrece tiene una habilidad que usan el controlador
 * y el proveedor de la guia: una sola fuente para "puede hacerlo". Todas
 * delegan al permiso de hoy (sin cambio de comportamiento).
 *
 * Regla: una accion es permiso de Spatie O habilidad derivada, nunca ambas.
 * Con `register_permission_check_method`, una fila en `permissions` con el
 * nombre de una habilidad la concederia antes de este closure. Por eso
 * `sales-orders.confirm` NO esta aqui: es un permiso (D4), asignable por
 * usuario desde Usuarios. La prueba de contrato lo vigila.
 */
final class SalesAbilities
{
    /** Habilidades derivadas (closure) y el permiso al que delegan hoy. */
    public const DERIVED = [
        'quotes.send' => 'quotes.update',
        'quotes.accept' => 'quotes.update',
        'quotes.reject' => 'quotes.update',
        'quotes.convert' => 'quotes.update',
        'quotes.cancel' => 'quotes.update',
        'quotes.duplicate' => 'quotes.store',
        'sales-orders.cancel' => 'sales-orders.update',
        'sales-orders.remit' => 'remissions.store',
        'remissions.print' => 'remissions.update',
        'remissions.deliver' => 'remissions.update',
        'remissions.cancel' => 'remissions.update',
        'sales-orders.invoice' => 'billing.cfdi-invoices.store',
    ];

    /** Acciones que son permiso de Spatie (no closure). */
    public const PERMISSIONS = ['sales-orders.confirm'];

    /**
     * Habilidades atadas a rol, como el endpoint que protegen: marcar entregado a
     * mano (POST orders/{id}/status) siempre fue solo de god y admin.
     */
    public const ROLE_BOUND = ['sales-orders.deliver' => ['god', 'admin']];

    public static function register(): void
    {
        foreach (self::DERIVED as $ability => $permission) {
            Gate::define($ability, fn ($user) => $user->can($permission));
        }

        // El cliente del portal acepta o rechaza SUS cotizaciones (correo del
        // contacto = correo del usuario), aunque no tenga quotes.update.
        foreach (['quotes.accept', 'quotes.reject'] as $ability) {
            Gate::define($ability, fn ($user, ?Quote $quote = null) => $user->can('quotes.update')
                || ($quote !== null && self::isOwner($user, $quote)));
        }

        // Cancelar pedido: el permiso o los roles de acceso total, como hoy.
        Gate::define('sales-orders.cancel', fn ($user) => $user->can('sales-orders.update') || $user->hasAnyRole(['god', 'admin']));

        foreach (self::ROLE_BOUND as $ability => $roles) {
            Gate::define($ability, fn ($user) => $user->hasAnyRole($roles));
        }
    }

    public static function isOwner($user, Quote $quote): bool
    {
        $quote->loadMissing('contact');

        return $quote->contact !== null && $quote->contact->email === $user->email;
    }
}

<?php

namespace Modules\Inventory\Exceptions;

/**
 * Regla de inventario que el usuario puede corregir (existencia insuficiente, lote de otro
 * almacen, ajuste a negativo...). El mensaje va en espanol y se muestra tal cual: el alta de
 * movimientos la convierte en 422. Extiende RuntimeException para que los llamadores que
 * atrapan \Exception (ventas, compras, fraccionamiento) sigan funcionando igual.
 */
class InventoryRuleException extends \RuntimeException
{
}

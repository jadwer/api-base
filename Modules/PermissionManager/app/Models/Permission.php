<?php

namespace Modules\PermissionManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PermissionManager\Database\Factories\PermissionFactory;
use Modules\PermissionManager\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ["name", "guard_name", "label", "description", "module", "resource"];

    /**
     * Busqueda para la UI de roles: matchea nombre tecnico, label
     * legible o descripcion.
     */
    public function scopeSearchFilter($query, string $value)
    {
        return $query->where(function ($q) use ($value) {
            $like = '%' . $value . '%';
            $q->where('name', 'like', $like)
                ->orWhere('label', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /** Label legible del modulo de negocio, derivado del catalogo. */
    public function getModuleLabelAttribute(): ?string
    {
        return PermissionCatalog::moduleLabel($this->module);
    }

    /** Label legible del recurso en plural, derivado del catalogo. */
    public function getResourceLabelAttribute(): ?string
    {
        return PermissionCatalog::resourceLabel($this->resource);
    }

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}

<?php

namespace Modules\User\Models;

/**
 * ✅ MODELO USER PRINCIPAL DEL PROYECTO
 *
 * Este es el modelo User configurado en config/auth.php
 * Extiende el modelo base de Laravel con funcionalidades adicionales del proyecto.
 *
 * Características adicionales vs App\Models\User:
 * - SoftDeletes: Eliminación lógica (requiere columna deleted_at)
 * - LogsActivity: Registro de actividad (Spatie Activity Log)
 * - Campo 'status': active/inactive/suspended
 * - Guard 'api': Configurado para Spatie Permission
 *
 * Tabla: users
 * Migración: database/migrations/0001_01_01_000000_create_users_table.php
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Modules\User\Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PermissionManager\Models\Role;
use Modules\User\Database\Factories\UserFactory;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles, LogsActivity, SoftDeletes, HasFactory;

    /**
     * IMPORTANTE: Guard configurado para Spatie Permission
     * Todos los roles y permisos usan el guard 'api'
     */
    protected $guard_name = 'api';

    /**
     * Campos fillable
     * Valores de status: active, inactive, suspended
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status', // Campo del módulo User
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * NOTA: El campo 'deleted_at' es gestionado automáticamente por el trait SoftDeletes
     * Migración: database/migrations/0001_01_01_000000_create_users_table.php línea 22
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

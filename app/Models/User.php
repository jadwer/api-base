<?php

namespace App\Models;

/**
 * ⚠️ ADVERTENCIA: ESTE MODELO NO SE USA EN EL PROYECTO ⚠️
 *
 * El modelo User activo del proyecto es:
 * @see \Modules\User\Models\User
 *
 * Este archivo (App\Models\User) es el modelo base de Laravel que NO se utiliza.
 *
 * Configuración actual en config/auth.php:
 * 'model' => Modules\User\Models\User::class
 *
 * El modelo del módulo User incluye:
 * - SoftDeletes trait (columna deleted_at)
 * - LogsActivity trait (Spatie Activity Log)
 * - Campo 'status' adicional
 * - Guard 'api' configurado
 *
 * NO modifiques este archivo. Si necesitas cambiar el modelo User,
 * edita: Modules/User/app/Models/User.php
 */

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

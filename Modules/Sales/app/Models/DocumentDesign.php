<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Diseno editado de un documento (tabla document_layouts). Ver
 * Modules\Sales\Support\Documents\DocumentDesigns.
 *
 * @property int $id
 * @property string $document_type
 * @property array<string, mixed> $layout
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DocumentDesign extends Model
{
    protected $table = 'document_layouts';

    protected $fillable = ['document_type', 'layout', 'updated_by'];

    protected $casts = [
        'layout' => 'array',
    ];
}

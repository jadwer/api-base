<?php

namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class Audit extends Activity
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }


}

<?php

namespace App\Models\Concerns;

use App\Concerns\HasTranslatedAttributeLabels;
use App\Concerns\TracksUpdatedBy;

trait ModelSupport
{
    use Auditable;
    use HasTranslatedAttributeLabels;
    use TracksUpdatedBy;
}

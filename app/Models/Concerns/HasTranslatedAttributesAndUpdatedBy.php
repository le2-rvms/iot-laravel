<?php

namespace App\Models\Concerns;

use App\Concerns\HasTranslatedAttributeLabels;
use App\Concerns\TracksUpdatedBy;

trait HasTranslatedAttributesAndUpdatedBy
{
    use HasTranslatedAttributeLabels;
    use TracksUpdatedBy;
}

<?php

namespace App\Http\Resources\Tip;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TipCollection extends ResourceCollection
{
    public $collects = TipResource::class;
}

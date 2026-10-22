<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    use BelongsToCafe;

    protected $guarded = [];
}
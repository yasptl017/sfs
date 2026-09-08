<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetCode extends Model
{
    protected $fillable = [
        'budget_code',
        'operating_head',
        'scheme',
        'object_class',
        'object_code',
        'description',
        'item',
        'model',
        'scheme_year',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Model;

class BudgetCode extends Model
{ use BelongsToFinancialYear;
    protected $fillable = ['rate', 'target', 'payment_mode', 'wing', 'major_head', 'demand_no', 
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

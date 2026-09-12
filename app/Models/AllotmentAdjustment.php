<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AllotmentAdjustment extends Model { protected $fillable=['division_id','from_budget_code_id','to_budget_code_id','amount','remark','data']; protected function casts(): array { return ['data'=>'array','amount'=>'decimal:2']; } }

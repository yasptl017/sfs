<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LcEntry extends Model { protected $fillable=['division_id','serial_number','scheme','class','entry_date','amount']; protected function casts(): array { return ['entry_date'=>'date','amount'=>'decimal:2']; } }

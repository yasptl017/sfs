<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillAdviceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'bill_register_no',
        'advice_no',
        'report_type',
        'tharav_descriptions',
        'report_data',
    ];

    protected $casts = [
        'report_data' => 'array',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }

    public function getReportTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            'gst_report' => 'GST Report',
            'bill_report' => 'Bill Reports',
            'deduction_report' => 'Deduction Reports',
            'range_report' => 'Reports for Ranges',
            default => ucfirst(str_replace('_', ' ', $this->report_type)),
        };
    }
}

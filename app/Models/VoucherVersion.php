<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An older copy of a voucher from an earlier export, kept for audit. */
class VoucherVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_rows' => 'array', 'diff_fields' => 'array', 'differs_from_live' => 'boolean'];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function getKindLabelAttribute(): string
    {
        return match ($this->version_kind) {
            'history00_snapshot' => 'History export (15 Aug 2026)',
            'division_workbook_snapshot' => 'Division workbook (6 Nov 2026)',
            'before_edit' => 'Before edit on '.$this->created_at?->format('d-m-Y H:i'),
            'deleted' => 'Deleted on '.$this->created_at?->format('d-m-Y H:i'),
            default => str_starts_with((string) $this->version_kind, 'advice:') ? 'Division processing ('.str_replace('_', ' ', substr($this->version_kind, 7)).') on '.$this->created_at?->format('d-m-Y H:i') : $this->version_kind,
        };
    }

    /** The older value of one spreadsheet header (first line row of the snapshot). */
    public function olderValue(string $header): mixed
    {
        $snap = json_decode($this->snapshot, true);

        return $snap['rows'][0]['c'][$header] ?? null;
    }

    /** Older line items as [sor_code, description, qty, unit, rate, amount]. */
    public function olderItems(): array
    {
        $snap = json_decode($this->snapshot, true);

        return array_map(fn ($row) => [
            $row['c']['SOR Code'] ?? null, $row['c']['Work Description'] ?? null, $row['c']['No. of Unit'] ?? null,
            $row['c']['Unit'] ?? null, $row['c']['SOR Rate'] ?? null, $row['c']['Amount'] ?? null,
        ], $snap['rows'] ?? []);
    }
}

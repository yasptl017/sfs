<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_name',
        'office_name_gujarati',
        'officer_name',
        'officer_designation',
        'officer_designation_gujarati',
        'office_address',
        'city',
        'taluka',
        'district',
        'pincode',
        'phone',
        'mobile',
        'email',
        'fax',
        'ddo_code',
        'tan_no',
        'logo_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if (!empty($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }

        return asset('images/gujarat-forest-logo.svg');
    }

    public function hasCustomLogo(): bool
    {
        return !empty($this->logo_path);
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->office_address,
            $this->taluka ? "Ta: {$this->taluka}" : null,
            $this->city,
            $this->district ? "Dist: {$this->district}" : null,
            $this->pincode ? "PIN - {$this->pincode}" : null,
        ]);

        return implode(', ', $parts);
    }

    public function getOfficeNameEnAttribute(): ?string
    {
        return $this->office_name;
    }

    public function setOfficeNameEnAttribute($value): void
    {
        $this->attributes['office_name'] = $value;
    }

    public function getOfficeNameGuAttribute(): ?string
    {
        return $this->office_name_gujarati;
    }

    public function setOfficeNameGuAttribute($value): void
    {
        $this->attributes['office_name_gujarati'] = $value;
    }

    public function getOfficerNameGuAttribute(): ?string
    {
        return $this->officer_name;
    }

    public function getOfficerDesignationGuAttribute(): ?string
    {
        return $this->officer_designation_gujarati;
    }

    public function setOfficerDesignationGuAttribute($value): void
    {
        $this->attributes['officer_designation_gujarati'] = $value;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->office_name ?: ($this->user?->name ?? 'Forest Department Office');
    }

    public function getDisplayNameGujaratiAttribute(): string
    {
        return $this->office_name_gujarati ?: ($this->user?->isRange() ? 'પરિક્ષેત્ર વન કચેરી' : 'સામાજિક વનીકરણ વિભાગ');
    }

    public function getDisplayOfficerNameAttribute(): string
    {
        return $this->officer_name ?: ($this->user?->name ?? '');
    }

    public function getDisplayDesignationAttribute(): string
    {
        if ($this->officer_designation) {
            return $this->officer_designation;
        }

        return $this->user?->isRange() ? 'Range Forest Officer' : 'Deputy Conservator of Forests';
    }

    public function getDisplayDesignationGujaratiAttribute(): string
    {
        if ($this->officer_designation_gujarati) {
            return $this->officer_designation_gujarati;
        }

        return $this->user?->isRange() ? 'પરિક્ષેત્ર વન અધિકારી' : 'નાયબ વન સંરક્ષક';
    }
}

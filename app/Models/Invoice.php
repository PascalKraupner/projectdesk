<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'number',
        'number_sequence',
        'status',
        'issue_date',
        'payment_terms_days',
        'due_date',
        'currency',
        'period_start',
        'period_end',
        'total_amount',
        'recipient_name',
        'recipient_contact_person',
        'recipient_street',
        'recipient_postal_code',
        'recipient_city',
        'recipient_country',
        'recipient_vat_id',
        'issued_at',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_terms_days' => 'integer',
            'number_sequence' => 'integer',
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Issued]);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', InvoiceStatus::Cancelled);
    }
}

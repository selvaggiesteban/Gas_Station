<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'tax_rate',
        'currency',
        'pdf_path',
        'status',
        'notes',
        'holded_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationship to subscription
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if invoice has PDF
     */
    public function hasPdf(): bool
    {
        return $this->pdf_path && Storage::exists($this->pdf_path);
    }

    /**
     * Get PDF URL
     */
    public function getPdfUrl(): ?string
    {
        if (!$this->hasPdf()) {
            return null;
        }

        return Storage::url($this->pdf_path);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): bool
    {
        return $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): bool
    {
        return $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Mark invoice as cancelled
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for pending (draft/sent) invoices
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * Generate next invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = config('invoicing.prefix', 'FACT');
        $year = date('Y');

        // Get last invoice number for this year
        $lastInvoice = self::whereYear('issue_date', $year)
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract number from last invoice
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = config('invoicing.start_number', 1);
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $newNumber);
    }

    /**
     * Calculate tax amount based on subtotal
     */
    public function calculateTax(): float
    {
        return round($this->subtotal * ($this->tax_rate / 100), 2);
    }

    /**
     * Calculate total amount
     */
    public function calculateTotal(): float
    {
        return $this->subtotal + $this->calculateTax();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }

            // Calculate tax and total if not set
            if ($invoice->subtotal && !$invoice->tax_amount) {
                $invoice->tax_amount = $invoice->calculateTax();
            }
            if ($invoice->subtotal && !$invoice->total_amount) {
                $invoice->total_amount = $invoice->calculateTotal();
            }
        });
    }
}
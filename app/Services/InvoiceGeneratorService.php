<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceGeneratorService
{
    /**
     * Generate PDF for an invoice
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['subscription.plan', 'user']);

        // Ensure directories exist
        $directory = 'invoices/' . date('Y') . '/' . date('m');
        Storage::makeDirectory($directory);

        // Generate PDF
        $pdf = PDF::loadView('invoices.template', [
            'invoice' => $invoice,
            'company' => $this->getCompanyData(),
        ]);

        // Set PDF options
        $pdf->setPaper('a4');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        // Save PDF
        $filename = $invoice->invoice_number . '.pdf';
        $path = $directory . '/' . $filename;

        Storage::put($path, $pdf->output());

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Get company data for invoice
     */
    private function getCompanyData(): array
    {
        return [
            'name' => config('invoicing.company_name', 'Academicus S.L.'),
            'cif' => config('invoicing.company_cif', 'B12345678'),
            'address' => config('invoicing.company_address', 'Calle Principal 123, 28001 Madrid'),
            'email' => config('invoicing.company_email', 'facturacion@academicus.com'),
            'phone' => config('invoicing.company_phone', '+34 912 345 678'),
            'logo' => config('invoicing.company_logo', null),
        ];
    }

    /**
     * Format amount for display
     */
    public static function formatAmount(float $amount, string $currency = 'EUR'): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        return number_format($amount, 2, ',', '.') . ' ' . $symbol;
    }

    /**
     * Format date for display
     */
    public static function formatDate($date): string
    {
        if (!$date) {
            return '-';
        }

        return $date->format('d/m/Y');
    }

    /**
     * Get invoice status label
     */
    public static function getStatusLabel(string $status): string
    {
        return match($status) {
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'paid' => 'Pagada',
            'cancelled' => 'Cancelada',
            default => $status,
        };
    }

    /**
     * Get invoice status color
     */
    public static function getStatusColor(string $status): string
    {
        return match($status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'paid' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * List user's invoices
     */
    public function index()
    {
        $invoices = Invoice::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show invoice details
     */
    public function show(Invoice $invoice)
    {
        // Ensure user can only view their own invoices
        if ($invoice->user_id !== Auth::id() && !Auth::user()->hasRole('Admin')) {
            abort(403);
        }

        return view('invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        // Ensure user can only download their own invoices
        if ($invoice->user_id !== Auth::id() && !Auth::user()->hasRole('Admin')) {
            abort(403, 'No tienes permiso para descargar esta factura.');
        }

        // In a real app, this would generate a PDF using DomPDF or Snappy.
        // For now, we return a simple HTML view that can be printed to PDF,
        // or just a simulated response if PDF generation isn't set up.
        
        return view('invoices.show', compact('invoice'));
    }
}

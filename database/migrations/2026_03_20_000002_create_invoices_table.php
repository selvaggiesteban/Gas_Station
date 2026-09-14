<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('invoice_number', 50)->unique()->comment('e.g., FACT-2024-000001');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 10, 2)->comment('Amount before tax');
            $table->decimal('tax_amount', 10, 2)->comment('Tax amount');
            $table->decimal('total_amount', 10, 2)->comment('Total with tax');
            $table->decimal('tax_rate', 5, 2)->default(21.00)->comment('VAT rate %');
            $table->string('currency', 3)->default('EUR');
            $table->string('pdf_path')->nullable()->comment('Path to PDF file');
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('invoice_number');
            $table->index('issue_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

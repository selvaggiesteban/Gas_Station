<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HoldedService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.holded.key', '');
        $this->baseUrl = config('services.holded.base_url', 'https://api.holded.com/api/');
    }

    /**
     * Create or update a contact in Holded
     */
    public function syncContact(User $user): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Holded API key not configured.');
            return null;
        }

        try {
            $payload = [
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'client',
                'code' => $user->nif,
                'vatnumber' => $user->nif,
                'phone' => $user->phone,
                'billAddress' => [
                    'address' => $user->address,
                    'city' => $user->city,
                    'province' => $user->province,
                    'postalCode' => $user->postal_code,
                    'country' => 'España',
                    'countryCode' => 'ES'
                ]
            ];

            // If we already have a holded_id, we should update
            if (!empty($user->holded_id)) {
                $response = Http::withHeaders(['key' => $this->apiKey])
                    ->put($this->baseUrl . 'invoicing/v1/contacts/' . $user->holded_id, $payload);
                
                if ($response->successful()) {
                    return $user->holded_id;
                }
            }

            // Otherwise create
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->post($this->baseUrl . 'invoicing/v1/contacts', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $holdedId = $data['id'] ?? null;
                
                if ($holdedId) {
                    $user->holded_id = $holdedId;
                    $user->save();
                    return $holdedId;
                }
            }

            Log::error('Holded contact sync failed', [
                'response' => $response->body(),
                'user' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Holded contact sync error', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Create an invoice in Holded
     */
    public function createInvoice(Invoice $localInvoice): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Holded API key not configured.');
            return null;
        }

        try {
            $user = $localInvoice->user;
            $holdedContactId = $user->holded_id ?? $this->syncContact($user);

            if (!$holdedContactId) {
                Log::error('Cannot create Holded invoice without contact ID');
                return null;
            }

            $payload = [
                'contactId' => $holdedContactId,
                'desc' => "Pedido Academicus #" . ($localInvoice->invoice_number),
                'date' => time(),
                'items' => [
                    [
                        'name' => 'Suscripción Academicus',
                        'desc' => "Acceso a cursos de preparación",
                        'units' => 1,
                        'subtotal' => (float) $localInvoice->subtotal,
                        'tax' => (float) $localInvoice->tax_rate,
                    ]
                ],
                'applyContactTax' => false,
                'channelId' => 'Academicus Web'
            ];

            $response = Http::withHeaders(['key' => $this->apiKey])
                ->post($this->baseUrl . 'invoicing/v1/documents/invoice', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $holdedInvoiceId = $data['id'] ?? null;

                if ($holdedInvoiceId) {
                    $localInvoice->holded_id = $holdedInvoiceId;
                    $localInvoice->save();
                    
                    // Automate sending after creation
                    $this->sendInvoice($holdedInvoiceId, $user->email);
                    
                    return $holdedInvoiceId;
                }
            }

            Log::error('Holded invoice creation failed', [
                'response' => $response->body(),
                'invoice' => $localInvoice->id
            ]);

        } catch (\Exception $e) {
            Log::error('Holded invoice creation error', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Send invoice by email through Holded
     */
    public function sendInvoice(string $holdedInvoiceId, string $email): bool
    {
        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->post($this->baseUrl . 'invoicing/v1/documents/invoice/' . $holdedInvoiceId . '/send', [
                    'emails' => [$email]
                ]);

            if ($response->successful()) {
                Log::info('Invoice sent successfully via Holded', ['invoice_id' => $holdedInvoiceId, 'email' => $email]);
                return true;
            }

            Log::error('Holded send invoice failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Holded send invoice error', ['error' => $e->getMessage()]);
        }

        return false;
    }
}

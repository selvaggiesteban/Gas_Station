<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartService
{
    private string $sessionKey = 'academicus_cart';

    /**
     * Get all items in the cart
     */
    public function getItems(): array
    {
        return Session::get($this->sessionKey, []);
    }

    /**
     * Add a product to the cart
     */
    public function add(Product $product): void
    {
        $cart = $this->getItems();

        // Check if product is already in cart
        if (!isset($cart[$product->id])) {
            $cart[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'course_name' => $product->course->name ?? 'Curso',
                'type' => $product->type,
                'duration' => $product->duration_days,
            ];
        }

        Session::put($this->sessionKey, $cart);
    }

    /**
     * Remove an item from the cart
     */
    public function remove(int $productId): void
    {
        $cart = $this->getItems();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        Session::put($this->sessionKey, $cart);
    }

    /**
     * Clear the entire cart
     */
    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    /**
     * Calculate subtotal
     */
    public function getSubtotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['price'];
        }
        return $total;
    }

    /**
     * Calculate tax (assuming inclusive price)
     */
    public function getTaxAmount(float $rate = 21.0): float
    {
        $subtotal = $this->getSubtotal();
        $base = $subtotal / (1 + ($rate / 100));
        return round($subtotal - $base, 2);
    }

    /**
     * Get cart count
     */
    public function getCount(): int
    {
        return count($this->getItems());
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->getCount() === 0;
    }
}

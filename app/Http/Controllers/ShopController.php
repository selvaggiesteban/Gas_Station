<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Course;
use App\Models\Order;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPendingTransfer;

class ShopController extends Controller
{
    private CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Public Catalog / Landing
     */
    public function landing()
    {
        $courses = Course::with(['products' => function($q) {
            $q->where('is_active', true)->orderBy('display_order');
        }])->get();

        return view('shop.landing', compact('courses'));
    }

    /**
     * Show Cart Summary
     */
    public function cart()
    {
        $items = $this->cart->getItems();
        $total = $this->cart->getSubtotal();

        return view('shop.cart', compact('items', 'total'));
    }

    /**
     * Add product to cart
     */
    public function addToCart(Product $product)
    {
        $this->cart->add($product);

        return redirect()->route('carrito.ver')->with('success', 'Producto añadido al carrito.');
    }

    /**
     * Remove product from cart
     */
    public function removeFromCart(int $productId)
    {
        $this->cart->remove($productId);

        return redirect()->route('carrito.ver')->with('info', 'Producto eliminado del carrito.');
    }

    /**
     * Public Catalog - All Courses
     */
    public function catalog()
    {
        $courses = Course::with(['products' => function($q) {
            $q->where('is_active', true)->orderBy('display_order');
        }])->where('is_active', true)->get();

        return view('shop.catalog', compact('courses'));
    }

    /**
     * Course Detail Page
     */
    public function courseDetail(Course $course)
    {
        $course->load(['products' => function($q) {
            $q->where('is_active', true)->orderBy('display_order');
        }]);

        return view('shop.course-detail', compact('course'));
    }

    /**
     * Show Checkout Form
     */
    public function checkout()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('tienda.inicio')->with('error', 'Tu carrito está vacío.');
        }

        $items = $this->cart->getItems();
        $total = $this->cart->getSubtotal();
        $user = Auth::user();

        return view('shop.checkout', compact('items', 'total', 'user'));
    }

    /**
     * Process Checkout Submission
     */
    public function processCheckout(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nif' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'payment_method' => 'required|in:stripe,transfer',
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('tienda.inicio')->with('error', 'El carrito está vacío.');
        }

        $subtotal = $this->cart->getSubtotal();
        $discountAmount = 0;
        $couponId = null;

        $couponCode = Session::get('applied_coupon');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid(null, $subtotal)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
            }
        }

        $netAmount = $subtotal - $discountAmount;
        $taxRate = config('academy.tax_rate', 21) / 100;
        $taxAmount = round($netAmount * $taxRate, 2);
        $totalAmount = $netAmount + $taxAmount;

        try {
            $order = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $subtotal, $taxAmount, $discountAmount, $totalAmount, $couponId) {
                $newOrder = Order::create([
                    'user_id' => Auth::id(),
                    'order_number' => Order::generateOrderNumber(),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'coupon_id' => $couponId,
                    'email' => $request->email,
                    'name' => $request->name,
                    'nif' => $request->nif,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'province' => $request->province,
                    'postal_code' => $request->postal_code,
                    'company_name' => $request->company_name,
                    'payment_method' => $request->payment_method,
                ]);

                foreach ($this->cart->getItems() as $item) {
                    $newOrder->items()->create([
                        'product_id' => $item['id'],
                        'name' => $item['name'],
                        'price' => $item['price'],
                    ]);
                }

                return $newOrder;
            });

            Session::forget('applied_coupon');

        } catch (\Exception $e) {
            Log::error('Order Creation Failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Ocurrió un error al generar tu pedido. Por favor, inténtalo de nuevo.');
        }

        if ($request->payment_method === 'transfer') {
            try {
                Mail::to($order->email)->send(new OrderPendingTransfer($order));
                return redirect()->route('shop.payment.success', ['order' => $order->order_number])
                    ->with('success', 'Pedido realizado. Revisa tu email para las instrucciones de pago.');
            } catch (\Exception $e) {
                Log::error('Mail Send Failed for Transfer', ['error' => $e->getMessage()]);
                return redirect()->route('shop.payment.success', ['order' => $order->order_number]);
            }
        }

        try {
            $stripe = app(StripeService::class);
            $session = $stripe->createCheckoutSessionFromOrder($order);

            if ($session['success']) {
                return redirect($session['url']);
            }

            return back()->with('error', 'No se pudo iniciar el pago con Stripe.');
        } catch (\Exception $e) {
            Log::error('Stripe Checkout Error', ['error' => $e->getMessage()]);
            return back()->with('error', 'El sistema de pagos no está disponible en este momento. Prueba con Transferencia Bancaria.');
        }
    }

    /**
     * Payment Success Page
     */
    public function success(Request $request)
    {
        $order = null;
        if ($request->has('session_id')) {
            $order = Order::where('stripe_session_id', $request->session_id)->first();
        } elseif ($request->has('order')) {
            $order = Order::where('order_number', $request->order)->first();
        }

        $this->cart->clear();

        return view('shop.success', compact('order'));
    }

    /**
     * Payment Failure Page
     */
    public function failure()
    {
        return view('shop.failure');
    }
}

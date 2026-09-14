<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    /**
     * Display list of coupons (User view / public validation)
     */
    public function index()
    {
        return redirect()->route('tienda.inicio');
    }

    /**
     * Admin view list
     */
    public function adminIndex()
    {
        $coupons = Coupon::with('creator')->latest()->paginate(15);
        return view('coupons.admin-index', compact('coupons'));
    }

    public function create()
    {
        $plans = Plan::all();
        return view('coupons.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code|max:20',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        Coupon::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'is_active' => true,
        ]));

        return redirect()->route('gestor-contenido.cupones.admin-lista')->with('success', 'Cupón creado correctamente.');
    }

    /**
     * AJAX: Apply coupon to cart
     */
    public function apply(Request $request)
    {
        $coupon = Coupon::findByCode($request->code);

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'Cupón no válido o expirado'], 422);
        }

        session(['applied_coupon' => $coupon->code]);

        return response()->json([
            'success' => true,
            'message' => 'Cupón aplicado: ' . $coupon->code,
            'discount_label' => $coupon->formatted_value
        ]);
    }

    /**
     * AJAX: Remove coupon
     */
    public function remove()
    {
        session()->forget('applied_coupon');
        return response()->json(['success' => true]);
    }
}

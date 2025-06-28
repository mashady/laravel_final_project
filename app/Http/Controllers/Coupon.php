<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Http\Resources\CouponResource;

class CouponController extends Controller
{
    public function index()
    {
        return CouponResource::collection(Coupon::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'discount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ]);
        $coupon = Coupon::create($data);
        return new CouponResource($coupon);
    }

    public function show(Coupon $coupon)
    {
        return new CouponResource($coupon);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'discount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ]);
        $coupon->update($data);
        return new CouponResource($coupon);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted']);
    }

    public function validateCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $coupon = Coupon::where('code', $request->code)->first();
        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Invalid or expired coupon'], 422);
        }
        return new CouponResource($coupon);
    }
}
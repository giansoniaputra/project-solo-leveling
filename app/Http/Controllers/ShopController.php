<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use App\Models\ShopPurchase;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'items' => ShopItem::orderBy('cost')->get(),
            'points' => $request->user()->points,
        ]);
    }

    public function purchase(Request $request, ShopItem $item)
    {
        $user = $request->user();

        if ($user->points < $item->cost) {
            return response()->json(['message' => "Not enough points for {$item->name}."], 422);
        }

        $user->decrement('points', $item->cost);

        ShopPurchase::create([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'points_spent' => $item->cost,
        ]);

        return response()->json([
            'message' => "Enjoy your {$item->name}, sir.",
            'points' => $user->fresh()->points,
        ]);
    }
}

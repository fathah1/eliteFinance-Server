<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        return Business::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $business = Business::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
        ]);

        return response()->json($business, 201);
    }
}

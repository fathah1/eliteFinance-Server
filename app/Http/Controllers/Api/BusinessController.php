<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $this->ownerUserId($request);

        return Business::where('user_id', $ownerId)
            ->when(!$this->isSuperUser($request), function ($q) use ($request) {
                $ids = $this->businessIds($request);
                $q->whereIn('id', $ids ?: [0]);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Only super user can create business'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $business = Business::create([
            'user_id' => $this->ownerUserId($request),
            'name' => $data['name'],
        ]);

        return response()->json($business, 201);
    }

    public function destroy(Request $request, Business $business)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Only super user can delete business'], 403);
        }
        if ($business->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $business->markDeleted();

        return response()->json(['message' => 'Business deleted']);
    }
}

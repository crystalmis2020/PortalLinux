<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\SlotLocator;

class SlotLocatorController extends Controller
{
    /**
     * Display the slot locator grid.
     */
    public function index()
    {
        return view('slot-locator.index');
    }

    public function getItems(string $coordinates)
    {

        $items = SlotLocator::where('coordinates', $coordinates)
                            ->orderBy('id', 'asc')
                            ->get();

        return response()->json([
            'success' => true,
            'items'   => $items,
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'coordinates' => ['required', 'string', 'max:10'],
            'items'       => ['required', 'string', 'max:255'],
        ]);

        $slot = SlotLocator::create([
            'coordinates' => $data['coordinates'],
            'items'       => $data['items'],
            'added_by'    => Auth::user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item saved successfully.',
            'slot'    => $slot,
        ]);
    }

    public function updateItem(Request $request, SlotLocator $slotLocator)
    {
        $data = $request->validate([
            'items' => ['required', 'string', 'max:255'],
        ]);

        $slotLocator->update([
            'items' => $data['items'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'slot'    => $slotLocator,
        ]);
    }

    public function destroyItem(SlotLocator $slotLocator)
    {
        $slotLocator->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }
}

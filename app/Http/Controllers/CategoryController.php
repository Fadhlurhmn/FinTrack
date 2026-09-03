<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request->user()->categories()->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = $request->user()->categories()->create($validated);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        return $category;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:income,expense',
            'icon' => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    // di CategoryController@destroy
    public function destroy(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        if ($category->transactions()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih punya transaksi.'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }

    private function authorizeOwner(Request $request, Category $category){
        if ($category->user_id !== $request->user()->id){
            abort(403, 'Unauthorized');
        }
    }
}

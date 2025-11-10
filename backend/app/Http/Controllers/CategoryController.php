<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::main()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }
}

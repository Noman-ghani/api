<?php

namespace App\Http\Controllers\Portal\Inventory;

use App\Helpers\Helpers;
use App\Models\Inventory\Categories;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Lumen\Routing\Controller;

class CategoriesController extends Controller
{
    public function browse(Request $request)
    {
        $categories = Categories::with("products")->whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->get("search")) {
            $categories->where("name", "like", "%" . $request->get("search") . "%");
        }
        
        return $categories->orderBy("name")->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "name" => [
                "required",
                Rule::unique("inventory_categories")
                ->where("business_id", Helpers::getJWTData("business_id"))
                ->ignore($id)
            ]
        ]);

        $category = Categories::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $category->name = $request->name;
        $category->save();

        return ["success" => true, "message" => __("Category saved successfully.")];
    }

    public function delete_by_id($id)
    {
        $category = Categories::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();

        if (!$category) {
            return ["success" => false, "message" => "Record not found"];
        }

        $category->delete();
        return ["success" => true, "message" => "Category deleted successfully."];
    }
}
<?php

namespace App\Http\Controllers\Portal\Inventory;

use App\Helpers\Helpers;
use App\Models\Inventory\Brands;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class BrandsController extends Controller
{
    public function browse(Request $request)
    {
        $brands = Brands::with("products")->where("business_id", Helpers::getJWTData("business_id"));

        if ($request->get("search")) {
            $brands->where("name", "like", "%" . $request->get("search") . "%");
        }
        
        return $brands->orderBy("name")->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "name" => "required"
        ]);

        $brand = Brands::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $brand->name = $request->name;
        $brand->save();

        return ["success" => true, "message" => __("Brand saved successfully.")];
    }

    public function delete_by_id($id)
    {
        $brand = Brands::where("business_id", Helpers::getJWTData("business_id"))->where("id", $id)->firstOrFail();

        if (!$brand) {
            return ["success" => false, "message" => "Record not found"];
        }

        $brand->delete();
        return ["success" => true, "message" => "Brand deleted successfully."];
    }
}
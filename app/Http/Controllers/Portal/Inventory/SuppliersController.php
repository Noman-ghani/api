<?php

namespace App\Http\Controllers\Portal\Inventory;

use App\Helpers\Helpers;
use App\Models\Inventory\Suppliers;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class SuppliersController extends Controller
{
    public function browse(Request $request)
    {
        $suppliers = Suppliers::whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->has("with-products")) {
            $suppliers->with("products");
        }
        if ($request->has("with-phone-country")) {
            $suppliers->with("phone_country");
        }
        
        return $suppliers->orderBy("name")->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "name" => "required",
            "first_name" => "required",
            "last_name" => "required",
            "email" => "nullable|email"
        ]);

        $supplier = Suppliers::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $supplier->name = $request->name;
        $supplier->description = $request->description;
        $supplier->first_name = $request->first_name;
        $supplier->last_name = $request->last_name;
        $supplier->email = $request->email;
        $supplier->phone_country_id = $request->phone_number ? $request->phone_country_id : null;
        $supplier->phone_number = $request->phone_number;
        $supplier->street = $request->street;
        $supplier->suburb = $request->suburb;
        $supplier->state_id = $request->state_id;
        $supplier->city_id = $request->city_id;
        $supplier->zip_code = $request->zipcode;
        $supplier->save();

        return ["success" => true, "message" => __("Supplier saved successfully.")];
    }

    public function get_by_id($id)
    {
        return Suppliers::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
    }

    public function delete_by_id($id)
    {
        $supplier = Suppliers::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
        $supplier->delete();
        return ["success" => true, "message" => "Supplier deleted successfully."];
    }
}
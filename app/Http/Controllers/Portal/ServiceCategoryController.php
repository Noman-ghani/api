<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\Services;
use App\Models\ServicesCategories;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Lumen\Routing\Controller;

class ServiceCategoryController extends Controller
{
    public function browse()
    {
        return ServicesCategories::whereBusinessId(Helpers::getJWTData("business_id"))->get();
    }
    
    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "title" => [
                "required",
                Rule::unique("services_categories")
                ->where("business_id", Helpers::getJWTData("business_id"))
                ->ignore($id)
            ],
            "color" => "required"
        ]);

        $model = ServicesCategories::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $model->title = $request->title;
        $model->color = $request->color;
        $model->description = $request->description;

        if ($model->save()) {
            return ["success" => true, "message" => __("Service category created successfully.")];
        }

        return ["success" => false, "message" => __("An error occurred while saving service category.")];
    }

    public function delete_by_id(Request $request, $id)
    {
        $serviceCategory = ServicesCategories::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();

        // if service category is assigned to any service, do not allow it to be deleted.
        if (Services::whereCategoryId($serviceCategory->id)->exists()) {
            return ["success" => false, "message" => __("Service category cannot be deleted as it belongs to services.")];
        }

        $serviceCategory->delete();

        return ["success" => true, "message" => __("Service category deleted successfully.")];
    }
}
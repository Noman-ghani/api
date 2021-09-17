<?php

namespace App\Http\Controllers\MarketPlace;

use App\Models\ServicesCategories;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ServiceCategoryController extends Controller
{
    public function browse(Request $request)
    {
        return ServicesCategories::where("business_id", $request->business_id)->get();
    }
}
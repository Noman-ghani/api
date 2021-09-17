<?php 

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Helpers\Helpers;
use App\Models\Cities;
use App\Models\Countries;
use App\Models\States;
use App\Models\Timezones;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(["prefix" => "clients-api", "namespace" => "MarketPlace"], function () use ($router) {
    
    //Auth Routes
    $router->group(["prefix" => "auth"], function () use ($router) {
        $router->post("/login", "CustomersController@login");
        $router->get('/google', 'CustomersController@redirectToGoogle');
        $router->get('/google/callback', 'CustomersController@handleGoogleCallback');
        $router->get('/facebook', 'CustomersController@redirectToFacebook');
        $router->get('/facebook/callback', 'CustomersController@handleFacebookCallback');
        $router->post("/forgot-password", "CustomersController@forgotPassword");
        $router->get("/reset-password/{token}", "CustomersController@resetPassword");
        $router->get("/check-token-expiry/{type}/{token}", "CustomersController@checkTokenExpiry");
        $router->post("/reset-password/{token}", "CustomersController@changePassword");
    });

    //Customer Public Routes
    $router->group(["prefix" => "customers"], function () use ($router) {
        $router->post("/", "CustomersController@store");
    });

    // Businesses public Routes
    $router->group(["prefix" => "business"], function () use ($router) {
        $router->get("/", "BusinessController@browse");
    });
    
    // Businesses public Routes
    $router->group(["prefix" => "branch"], function () use ($router) {
        $router->get("/", "BranchController@browse");
        $router->get("/all", "BranchController@getAllBranches");
    });
    
    // Businesses public Routes
    $router->group(["prefix" => "staff"], function () use ($router) {
        $router->get("/shifts", "StaffShiftsController@browse");
    });

    $router->group(["prefix" => "services"], function () use ($router) {
        $router->get("/", "ServiceController@browse");

        $router->group(["prefix" => "categories"], function () use ($router) {
            $router->get("/", "ServiceCategoryController@browse");
        });
    });

    // Deals public Routes
    $router->group(["prefix" => "deals"], function () use ($router) {
        $router->get("/", "DealsController@browse");
        $router->get("/{slug}", "DealsController@get_by_id");
        $router->get("/filter/price-list", "DealsController@getFilterPriceList");
    });
    

    $router->group(["prefix" => "taxes"], function () use ($router) {
        $router->get("/get_by_service", "TaxController@get_by_service");
        $router->get("/get_by_product", "TaxController@get_by_product");
    });

    $router->group(["middleware" => "clientAuth:marketPlace"], function () use ($router) {
        $router->get("user", "CustomersController@user");

        $router->group(["prefix" => "customers"], function () use ($router) {
            $router->put("/image/{id}", "CustomersController@updateProfileImage");
            $router->put("/{id}", "CustomersController@store");
            $router->put("/phone/{id}", "CustomersController@addphone");
        });
        
        $router->group(["prefix" => "appointments"], function () use ($router) {
            $router->get("/all/", "AppointmentsController@getMyALLAppointments");
            $router->post("/", "AppointmentsController@scheduleAppointment");
        });

        $router->group(["prefix" => "invoices"], function () use ($router) {
            $router->get("/", "InvoicesController@browse");
            $router->get("/{id}", "InvoicesController@get_by_id");
        });
        
    });
    

    $router->group(["prefix" => "appointments"], function () use ($router) {
        $router->get("/", "AppointmentsController@getAppointments");
    });
    
});
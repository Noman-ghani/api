<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Helpers\Helpers;
use App\Models\Cities;
use App\Models\Countries;
use App\Models\States;
use App\Models\Timezones;
use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

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

$router->get("thermal-printer", function () {
    try {
        $connector = new NetworkPrintConnector("192.168.14.138", 9100);
        $printer = new Printer($connector);
        $printer->text("Hello World!\n");
        $printer->cut();
        $printer->close();
    } catch (\Exception $e) {
        echo $e -> getMessage();
    }
});
$router->get("pdf-attach/{id}", "Portal\InvoicesController@downloadPdf");

$router->get("verify-email", "Portal\UserController@verifyEmail");
$router->get("verify-marketplace-email", "MarketPlace\CustomersController@verifyEmail");
$router->get("incoming-sms", "Portal\ClientsController@doActionOnSms");
$router->post("incoming-sms", "Portal\ClientsController@doActionOnSms");
$router->get("test-new-sms", "Portal\UserController@test");

$router->group(["prefix" => "utilities"], function () use ($router) {
    $router->get("business-types", function () {
        return Helpers::getBusinessTypes();
    });

    $router->get("/countries", function (Request $request) {
        $model = new Countries();
        $model = $model->whereIsActive(1);
        
        if ($request->has("phone_code")) {
            $model = $model->wherePhoneCode($request->phone_code);
        }

        if ($request->has("withcites")) {
            $model->with(["states" => function($state) {
                $state->with(["cities" => function($query) {
                    $query->whereIsActive(1);
                }]);
                $state = $state->whereHas("cities", function($query) {
                    $query->whereIsActive(1);
                });
            }]);
        }

        return $model->get();
    });
    
    $router->get("/states", function (Request $request) {
        return States::whereCountryId($request->country_id)->get();
    });
    
    $router->get("/cities", function (Request $request) {
        return Cities::whereStateId($request->state_id)->get();
    });
    
    $router->get("/timezones", function () {
        return Timezones::orderBy("offset")->get();
    });

    $router->get("durations", function () {
        return Helpers::getDurations();
    });

    $router->get("treatment-types", function (Request $request) {
        $treatmentTypes = Helpers::getTreatmentTypes();
        if($request->has("filter-wiith-service")){

            $treatmentTypes = Helpers::getDealsFilteredTreatments($request);
        }
        return $treatmentTypes;
    });

    $router->get("appointment-cancellation-reasons", function () {
        return Helpers::getAppointmentCancellationReasons();
    });

    $router->get("increase-stock-reasons", function () {
        return Helpers::getIncreaseStockReasons();
    });

    $router->get("decrease-stock-reasons", function () {
        return Helpers::getDecreaseStockReasons();
    });

    $router->group(["prefix" => "payment-methods"], function () use ($router) {
        $router->get("/{country_id}", "PaymentMethodsController@browse");
    });

    $router->get("expenses-categories", function () {
        return Helpers::getExpensesCategories();
    });

    $router->get("sms-packages", function () {
        return Helpers::getSMSPackages();
    });

    $router->get("subscription-packages", function () {
        return Helpers::getSubscriptionPackages();
    });

    $router->get("user-access", function () {
        return Helpers::getStaffRoles();
    });
});

$router->get("locale", function (Request $request) {
    foreach ([
        "HTTP_CLIENT_IP",
        "HTTP_X_FORWARDED_FOR",
        "HTTP_X_FORWARDED",
        "HTTP_X_CLUSTER_CLIENT_IP",
        "HTTP_FORWARDED_FOR",
        "HTTP_FORWARDED",
        "REMOTE_ADDR"
    ] as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    $ip = $request->ip();
                }
            }
        }
    }

    if (in_array($ip, ["127.0.0.1", "::1"])) {
        $ip = trim(shell_exec("dig +short myip.opendns.com @resolver1.opendns.com"));
    }
    
    $data = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

    return [
        "country" => $data->geoplugin_countryName,
        "timezone" => $data->geoplugin_timezone
    ];
});

$router->group(["prefix" => "pay"], function () use ($router) {
    $router->group(["prefix" => "easypaisa", "namespace" => "PaymentMethods"], function () use ($router) {
        $router->get("url", "EasyPaisaController@generateUrl");
        $router->post("confirm", "EasyPaisaController@onTransactionComplete");
    });

    $router->group(["prefix" => "keenu", "namespace" => "PaymentMethods"], function () use ($router) {
        $router->get("banks", "KeenuController@getBanks");
        $router->get("generate-secured-hash", "KeenuController@generateSecuredHash");
        $router->post("confirm", "KeenuController@onTransactionComplete");
    });
});


$router->group(["namespace" => "Portal"], function ($router) {
    $router->post("/user/login", "UserController@login");
    $router->post("/forgot-password", "UserController@forgotPassword");
    $router->get("/reset-password/{token}", "UserController@resetPassword");
    $router->get("/check-token-expiry/{type}/{token}", "UserController@checkTokenExpiry");
    $router->post("/reset-password/{token}", "UserController@changePassword");
    $router->post("/business", "BusinessController@signup");

    $router->group(["middleware" => "auth"], function () use ($router) {
        $router->get("user", "UserController@user");
        
        $router->group(["prefix" => "profile"], function () use ($router) {
            $router->get("/", "UserController@profile");
            $router->put("/", "UserController@updateProfile");
        });

        $router->group(["prefix" => "business"], function () use ($router) {
            $router->get("/settings", "BusinessController@getSettings");
            $router->put("/settings", "BusinessController@UpdateSettings");
            $router->put("update-tax-settings", "BusinessController@updateTaxSettings");
            $router->put("update-sales-settings", "BusinessController@updateSalesSettings");

        });

        $router->group(["prefix" => "branches"], function () use ($router) {
            $router->get("/", "BranchController@browse");
            $router->post("/", "BranchController@store");
            $router->put("/{id}", "BranchController@store");
            $router->get("/{id}", "BranchController@get_by_id");
            $router->put("/update_tax_defaults/{id}", "BranchController@update_tax_defaults");
            $router->put("/invoice_sequences/{id}", "BranchController@invoice_sequences");
        });

        $router->group(["prefix" => "taxes"], function () use ($router) {
            $router->get("/", "TaxController@browse");
            $router->get("/get_by_product", "TaxController@get_by_product");
            $router->get("/get_by_service", "TaxController@get_by_service");
            $router->post("/{type}", "TaxController@store");
            $router->put("/{type}/{id}", "TaxController@store");
            $router->delete("/{id}", "TaxController@delete_by_id");
        });

        $router->group(["prefix" => "discounts"], function () use ($router) {
            $router->get("/", "DiscountsController@browse");
            $router->post("/", "DiscountsController@store");
            $router->put("/{id}", "DiscountsController@store");
            $router->delete("/{id}", "DiscountsController@delete_by_id");
        });

        $router->group(["prefix" => "staff"], function () use ($router) {
            $router->group(["prefix" => "shifts"], function () use ($router) {
                $router->get("/", "StaffShiftsController@browse");
                $router->post("/", "StaffShiftsController@store");
                $router->delete("/", "StaffShiftsController@delete");
            });

            $router->group(["prefix" => "closed-dates"], function () use ($router) {
                $router->get("/", "ClosedDatesController@browse");
                $router->post("/", "ClosedDatesController@store");
                $router->put("/{id}", "ClosedDatesController@store");
                $router->delete("/{id}", "ClosedDatesController@delete");
            });

            $router->get("/", "StaffController@browse");
            $router->get("/{id}", "StaffController@get_by_id");
            $router->post("/", "StaffController@store");
            $router->put("/{id}", "StaffController@store");
        });

        $router->group(["prefix" => "clients"], function () use ($router) {
            $router->get("/download-import-file", "ClientsController@downloadImportFile");
            $router->get("/", "ClientsController@browse");
            $router->post("/", "ClientsController@store");
            $router->get("/{id}", "ClientsController@get_by_id");
            $router->put("/{id}", "ClientsController@store");
            $router->put("/block/{id}", "ClientsController@block");
            $router->put("/unblock/{id}", "ClientsController@unblock");
            $router->get("/appointments/{id}", "ClientsController@getAppointments");
            $router->get("/products/{id}", "ClientsController@getProducts");
            $router->get("/deals/{id}", "ClientsController@getDeals");
            $router->get("/summary/{id}", "ClientsController@getSummary");
            $router->post("/import", "ClientsController@importClients");
        });

        $router->group(["prefix" => "services"], function () use ($router) {
            $router->group(["prefix" => "categories"], function () use ($router) {
                $router->get("/", "ServiceCategoryController@browse");
                $router->post("/", "ServiceCategoryController@store");
                $router->put("/{id}", "ServiceCategoryController@store");
                $router->delete("/{id}", "ServiceCategoryController@delete_by_id");
            });
            
            $router->get("/", "ServiceController@browse");
            $router->get("/{id}", "ServiceController@get_by_id");
            
            $router->group(["prefix" => "service"], function () use ($router) {
                $router->post("/", "ServiceController@store_service");
                $router->put("/{id}", "ServiceController@store_service");
            });
        });

        $router->group(["prefix" => "inventory"], function () use ($router) {
            $router->group(["prefix" => "products"], function () use ($router) {
                $router->group(["prefix" => "stocks"], function () use ($router) {
                    $router->get("/{id}", "Inventory\StocksController@browse");
                    $router->post("/increase/{branch_id}/{product_id}", "Inventory\StocksController@increase");
                    $router->post("/decrease/{branch_id}/{product_id}", "Inventory\StocksController@decrease");
                });

                $router->get("/", "Inventory\ProductsController@browse");
                $router->post("/", "Inventory\ProductsController@store");
                $router->put("/{id}", "Inventory\ProductsController@store");
                $router->get("/{id}", "Inventory\ProductsController@get_by_id");
            });
            
            $router->group(["prefix" => "brands"], function () use ($router) {
                $router->get("/", "Inventory\BrandsController@browse");
                $router->post("/", "Inventory\BrandsController@store");
                $router->put("/{id}", "Inventory\BrandsController@store");
                $router->delete("/{id}", "Inventory\BrandsController@delete_by_id");
            });

            $router->group(["prefix" => "categories"], function () use ($router) {
                $router->get("/", "Inventory\CategoriesController@browse");
                $router->post("/", "Inventory\CategoriesController@store");
                $router->put("/{id}", "Inventory\CategoriesController@store");
                $router->delete("/{id}", "Inventory\CategoriesController@delete_by_id");
            });

            $router->group(["prefix" => "suppliers"], function () use ($router) {
                $router->get("/", "Inventory\SuppliersController@browse");
                $router->post("/", "Inventory\SuppliersController@store");
                $router->get("/{id}", "Inventory\SuppliersController@get_by_id");
                $router->put("/{id}", "Inventory\SuppliersController@store");
                $router->delete("/{id}", "Inventory\SuppliersController@delete_by_id");
            });
        });

        $router->group(["prefix" => "invoices"], function () use ($router) {
            $router->get("/", "InvoicesController@browse");
            $router->post("/", "InvoicesController@create");
            $router->get("/{id}", "InvoicesController@get_by_id");
            $router->put("/complete-sale/{id}", "InvoicesController@completeSale");
            $router->put("/void/{id}", "InvoicesController@voidInvoice");
            $router->post("/refund/{id}", "InvoicesController@refundInvoice");
            $router->get("download/{id}", "InvoicesController@downloadPdf");
        });

        $router->group(["prefix" => "sales"], function () use ($router) {
            $router->group(["prefix" => "daily-sales"], function () use ($router) {
                $router->get("/", "Sales\DailySalesController@getDailySalesData");
            });
        });

        $router->group(["prefix" => "appointments"], function () use ($router) {
            $router->get("/", "AppointmentsController@getAppointments");
            $router->post("/{branch_id}", "AppointmentsController@scheduleAppointment");
            $router->get("/{id}", "AppointmentsController@get_by_id");
            $router->put("/change-status/{id}", "AppointmentsController@changeStatus");
            $router->put("/{branch_id}/{id}", "AppointmentsController@scheduleAppointment");
        });

        $router->group(["prefix" => "deals"], function () use ($router) {
            $router->get("/", "DealsController@browse");
            $router->post("/", "DealsController@store");
            $router->get("/{id}", "DealsController@get_by_id");
            $router->put("/{id}", "DealsController@store");
        });
        
        $router->group(["prefix" => "reports"], function () use ($router) {
            $router->get("finances-summary", "ReportsController@financesSummary");
            $router->get("payments-summary", "ReportsController@paymentsSummary");
            $router->get("discounts-summary", "ReportsController@discountsSummary");
            $router->get("taxes-summary", "ReportsController@taxesSummary");
            $router->get("sms-summary", "ReportsController@smsSummary");
            $router->get("sales-by-service", "ReportsController@salesByService");
            $router->get("sales-by-product", "ReportsController@salesByProduct");
            $router->get("sales-by-deal", "ReportsController@salesByDeal");
            $router->get("sales-by-staff", "ReportsController@salesByStaff");
            $router->get("sales-by-location", "ReportsController@salesByLocation");
            $router->get("staff-commission-summary", "ReportsController@staffCommissionSummary");
            $router->get("staff-commission-detailed", "ReportsController@staffCommissionDetailed");
            
            $router->group(["prefix" => "expenses-summary"], function () use ($router) {
                $router->get("/", "ReportsController@expensesSummary");
                $router->post("/", "ReportsController@saveExpenses");
                $router->put("/{id}", "ReportsController@saveExpenses");
            });
        });

        $router->group(["prefix" => "sms-templates"], function () use ($router) {
            $router->get("/", "SmsTemplatesController@browse");
            $router->put("/{id}", "SmsTemplatesController@store");
        });
        
        $router->group(["prefix" => "sms-campaign"], function () use ($router) {
            $router->get("/", "SmsCampaignController@browse");
            $router->get("/{id}", "SmsCampaignController@get_by_id");
            $router->post("/", "SmsCampaignController@store");
            $router->put("/{id}", "SmsCampaignController@store");
            $router->post("/run/{id}", "SmsCampaignController@runCampaign");
            $router->delete("/{id}", "SmsCampaignController@delete_by_id");
        });

        $router->post("contact-support", "UserController@contactSupport");
    });
});

$router->get("/{code}", "ShortUrlController@browse");
$router->get("/shorturl/{id}", "ShortUrlController@get_by_id");

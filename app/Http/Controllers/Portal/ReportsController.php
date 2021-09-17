<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\ExpensesSummary;
use App\Models\SmsHistory;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;

class ReportsController extends Controller
{
    public function financesSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);
    
        $offset = $request->business->timezone->offset;
        $whereSql = "";
    
        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = {$request->branch_id}";
        }

        $salesSql = "
            SELECT
                SUM(IF (`invoices`.`status` NOT IN ('voided', 'refunded'), `invoices`.`grosstotal`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` NOT IN ('voided', 'refunded'), `invoices`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoices`.`grosstotal` - `invoices`.`discount`, 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` = 'voided', `invoices`.`grosstotal`, 0)) AS `voided`,
                (SUM(IF (`invoices`.`status` NOT IN ('voided', 'refunded'), `invoices`.`tax`, 0)) - SUM(IF(`invoices`.`status` IN ('refunded'), `invoices`.`tax`, 0))) AS `taxes`
            FROM `invoices`
            WHERE 1
                {$whereSql}
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
        ";

        $paymentsSql = "
            SELECT
                `payment_methods`.`title` AS `payment_method`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoices`.`grandtotal`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoices`.`grandtotal`, 0)) AS `total_payments`
            FROM `payment_methods`
            INNER JOIN `payment_methods_countries` ON (
                `payment_methods_countries`.`payment_method_id` = `payment_methods`.`id` AND
                `payment_methods_countries`.`country_id` = {$request->business->country_id}
            )
            LEFT JOIN `invoices` ON (
                `invoices`.`payment_method_id` = `payment_methods`.`id` AND
                `invoices`.`status` NOT IN ('voided') AND
                `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            )
            GROUP BY `payment_methods`.`id`
        ";
    
        return [
            "sales" => DB::select($salesSql)[0],
            "payments" => DB::select($paymentsSql)
        ];
    }
    
    public function paymentsSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);
    
        $offset = $request->business->timezone->offset;
        $whereSql = "";
    
        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = {$request->branch_id}";
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = {$request->staff_id}";
        }

        $sql = "
            SELECT
                `payment_methods`.`title` AS `payment`,
                COUNT(DISTINCT `invoices`.`id`) AS `transactions`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * ((`invoice_items`.`price` + `invoice_items`.`tax`) - `invoice_items`.`discount`) , 0)) AS `gross_payments`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * ((`invoice_items`.`price` + `invoice_items`.`tax`) - `invoice_items`.`discount`), 0)) AS `refunds`
            FROM `invoices`
            INNER JOIN `payment_methods` ON (
                `payment_methods`.`id` = `invoices`.`payment_method_id`
            )
            INNER JOIN `invoice_items` ON (
                `invoice_items`.`invoice_id` = `invoices`.`id`
            )
            WHERE 1
                {$whereSql}
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoices`.`payment_method_id`
        ";
    
        return DB::select($sql);
    }

    public function discountsSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);
    
        $offset = $request->business->timezone->offset;
        $whereSql = "";
    
        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = {$request->branch_id}";
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = {$request->staff_id}";
        }
        
        $discountsSql = "
            SELECT
                `invoice_items_discounts`.`title` AS `discount`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `items_discounted`,
                SUM(IF (`invoices`.`status` <> 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) - SUM(IF (`invoices`.`status` = 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) AS `items_value`,
                SUM(IF (`invoices`.`status` <> 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_refunds`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id`
            )
            INNER JOIN `invoice_items_discounts` ON (
                `invoice_items_discounts`.`invoice_item_id` = `invoice_items`.`id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`discount` > 0
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items_discounts`.`title`
        ";

        $discountsByServiceSql = "
            SELECT
                `invoice_items`.`title` AS `service_name`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `items_discounted`,
                SUM(IF (`invoices`.`status` <> 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) - SUM(IF (`invoices`.`status` = 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) AS `items_value`,
                SUM(IF (`invoices`.`status` <> 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_refunds`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`discount` > 0
                AND `invoice_items`.`service_id` IS NOT NULL
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`service_id`
        ";

        $discountsByProductSql = "
            SELECT
                `invoice_items`.`title` AS `product_name`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `items_discounted`,
                SUM(IF (`invoices`.`status` <> 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) - SUM(IF (`invoices`.`status` = 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) AS `items_value`,
                SUM(IF (`invoices`.`status` <> 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_refunds`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`discount` > 0
                AND `invoice_items`.`product_id` IS NOT NULL
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`product_id`
        ";

        $discountsByStaffSql = "
            SELECT
                CONCAT(`staff`.`first_name`, ' ', `staff`.`last_name`) AS `staff_name`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `items_discounted`,
                SUM(IF (`invoices`.`status` <> 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) - SUM(IF (`invoices`.`status` = 'refunded', ((`invoice_items`.`price` + `invoice_items`.`tax`) * `invoice_items`.`quantity`), 0)) AS `items_value`,
                SUM(IF (`invoices`.`status` <> 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', (`invoice_items`.`discount` * `invoice_items`.`quantity`), 0)) AS `discount_refunds`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id`
            )
            INNER JOIN `staff` ON (
                `staff`.`id` = `invoice_items`.`staff_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`discount` > 0
                AND `invoice_items`.`staff_id` IS NOT NULL
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`staff_id`
        ";

        return [
            "discounts" => DB::select($discountsSql),
            "discountsByService" => DB::select($discountsByServiceSql),
            "discountsByProduct" => DB::select($discountsByProductSql),
            "discountsByStaff" => DB::select($discountsByStaffSql)
        ];
    }

    public function taxesSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = {$request->branch_id}";
        }
        
        $sql = "
            SELECT
                `invoice_items_taxes`.`title` AS `tax`,
                `branches`.`name` AS `branch`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `item_sales`,
                `invoice_items_taxes`.`rate` AS `rate`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`tax` * `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`tax` * `invoice_items`.`quantity`, 0)) AS `amount`
            FROM `invoice_items_taxes`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items_taxes`.`invoice_id`
            )
            INNER JOIN `invoice_items` ON (
                `invoice_items`.`invoice_id` = `invoices`.`id`
            )
            INNER JOIN `branches` ON (
                `branches`.`id` = `invoices`.`branch_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items_taxes`.`title`, `branches`.`name`, `invoice_items_taxes`.`rate`
        ";

        return DB::select($sql);
    }

    public function salesByService(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }

        $sql = "
            SELECT
                `invoice_items`.`title` AS `service`,
                COUNT(DISTINCT `invoices`.`id`) AS `sales_qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`price`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * (`invoice_items`.`price` - `invoice_items`.`discount`), 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) AS `tax`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`service_id` IS NOT NULL
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`service_id`
        ";

        return DB::select($sql);
    }

    public function salesByProduct(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }

        $sql = "
            SELECT
                `invoice_items`.`title` AS `product`,
                COUNT(DISTINCT `invoices`.`id`) AS `sales_qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`price`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * (`invoice_items`.`price` - `invoice_items`.`discount`), 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) AS `tax`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`product_id` IS NOT NULL
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`product_id`
        ";

        return DB::select($sql);
    }

    public function salesByDeal(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }

        $sql = "
            SELECT
                `invoice_items`.`title` AS `deal`,
                COUNT(DISTINCT `invoices`.`id`) AS `sales_qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`price`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * (`invoice_items`.`price` - `invoice_items`.`discount`), 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) AS `tax`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`deal_id` IS NOT NULL
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`deal_id`
        ";

        return DB::select($sql);
    }

    public function salesByStaff(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }

        $sql = "
            SELECT
                CONCAT(`staff`.`first_name`, ' ', `staff`.`last_name`) AS `staff`,
                COUNT(DISTINCT `invoices`.`id`) AS `sales_qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`price`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * (`invoice_items`.`price` - `invoice_items`.`discount`), 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) AS `tax`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            INNER JOIN `staff` ON (
                `staff`.`id` = `invoice_items`.`staff_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`staff_id`
        ";

        return DB::select($sql);
    }

    public function salesByLocation(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }

        $sql = "
            SELECT
                `branches`.`name` AS `location`,
                COUNT(DISTINCT `invoices`.`id`) AS `sales_qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`price`, 0)) AS `gross_sales`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`discount`, 0)) AS `discounts`,
                SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * (`invoice_items`.`price` - `invoice_items`.`discount`), 0)) AS `refunds`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity` * `invoice_items`.`tax`, 0)) AS `tax`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            INNER JOIN `branches` ON (
                `branches`.`id` = `invoices`.`branch_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoices`.`branch_id`
        ";

        return DB::select($sql);
    }

    public function expensesSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $expensesSummary = ExpensesSummary::with(["branch", "staff"])->whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->has("branch_id")) {
            $expensesSummary->whereBranchId($request->branch_id);
        }
        if ($request->has("staff_id")) {
            $expensesSummary->whereStaffId($request->staff_id);
        }
        if ($request->has("category") && !empty($request->category)) {
            $expensesSummary->whereCategory($request->category);
        }

        $expensesSummary->whereRaw("created_at >= DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR)");
        $expensesSummary->whereRaw("created_at <= DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)");
        $data = $expensesSummary->get();

        if (!empty($data)) {
            $data->map(function ($item) {
                $item->category = Helpers::getExpensesCategories($item->category);
                return $item;
            });
        }

        return $data;
    }

    public function smsSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        
        return SmsHistory::whereBusinessId(Helpers::getJWTData("business_id"))
        ->whereRaw("created_at >= DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR)")
        ->whereRaw("created_at <= DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)")
        ->orderBy("id", "DESC")
        ->get();
    }

    public function saveExpenses(Request $request, $id = null)
    {
        $this->validate($request, [
            "branch_id" => "required",
            "category" => "required",
            "title" => "required",
            "price" => "required"
        ]);

        $staff = Staff::whereUserId(Auth::user()->id)->whereRole("owner")->firstOrFail();

        $expensesSummary = ExpensesSummary::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $expensesSummary->branch_id = $request->branch_id;
        $expensesSummary->staff_id = $staff->id;
        $expensesSummary->category = $request->category;
        $expensesSummary->title = $request->title;
        $expensesSummary->price = $request->price;
        $expensesSummary->save();

        return ["success" => true, "message" => __("Expense saved successfsully")];
    }

    public function staffCommissionSummary(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }
        
        $byStaffSql = "
            SELECT
                `invoice_items`.`staff_id`,
                CONCAT(`staff`.`first_name`, ' ', `staff`.`last_name`) AS `staff`,
                SUM(IF (`invoice_items`.`service_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) - SUM(IF (`invoice_items`.`service_id` IS NOT NULL AND `invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `service_sales_total`,
                SUM(IF (`invoice_items`.`service_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `service_commission_total`,
                SUM(IF (`invoice_items`.`product_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) - SUM(IF (`invoice_items`.`product_id` IS NOT NULL AND `invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `product_sales_total`,
                SUM(IF (`invoice_items`.`product_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `product_commission_total`,
                SUM(IF (`invoice_items`.`deal_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) - SUM(IF (`invoice_items`.`deal_id` IS NOT NULL AND `invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `deal_sales_total`,
                SUM(IF (`invoice_items`.`deal_id` IS NOT NULL AND `invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `deal_commission_total`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            INNER JOIN `staff` ON (
                `staff`.`id` = `invoice_items`.`staff_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`staff_id` IS NOT NULL
                AND `invoice_items`.`staff_commission_value` > 0
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`staff_id`
        ";

        $byServiceSql = "
            SELECT
                `invoice_items`.`title` AS `service`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `sales_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `refund_amount`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `commission_total`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`service_id` IS NOT NULL
                AND `invoice_items`.`staff_commission_value` > 0
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`service_id`
        ";

        $byProductSql = "
            SELECT
                `invoice_items`.`title` AS `product`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `sales_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `refund_amount`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `commission_total`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`product_id` IS NOT NULL
                AND `invoice_items`.`staff_commission_value` > 0
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`product_id`
        ";

        $byDealSql = "
            SELECT
                `invoice_items`.`title` AS `deal`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) - SUM(IF (`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `qty`,
                SUM(IF (`invoices`.`status` <> 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `sales_amount`,
                SUM(IF (`invoices`.`status` = 'refunded', IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)), 0)) AS `refund_amount`,
                SUM(IF (`invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0)) AS `commission_total`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`deal_id` IS NOT NULL
                AND `invoice_items`.`staff_commission_value` > 0
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoice_items`.`deal_id`
        ";

        return [
            "byStaff" => DB::select($byStaffSql),
            "byService" => DB::select($byServiceSql),
            "byProduct" => DB::select($byProductSql),
            "byDeal" => DB::select($byDealSql)
        ];
    }

    public function staffCommissionDetailed(Request $request)
    {
        $this->validate($request, [
            "start_date" => "required",
            "end_date" => "required"
        ]);

        $offset = $request->business->timezone->offset;
        $whereSql = "";

        if ($request->has("branch_id")) {
            $whereSql .= "\nAND `invoices`.`branch_id` = " . $request->branch_id;
        }
        if ($request->has("staff_id")) {
            $whereSql .= "\nAND `invoice_items`.`staff_id` = " . $request->staff_id;
        }
        if ($request->has("item_type")) {
            switch ($request->item_type) {
                case "service":
                    $whereSql .= "\nAND `invoice_items`.`service_id` IS NOT NULL";
                    break;
                case "product":
                    $whereSql .= "\nAND `invoice_items`.`product_id` IS NOT NULL";
                    break;
                case "deal":
                    $whereSql .= "\nAND `invoice_items`.`deal_id` IS NOT NULL";
                    break;
            }
        }

        $sql = "
            SELECT
                DATE_ADD(`invoice_items`.`created_at`, INTERVAL '{$offset}' HOUR) AS `invoice_date`,
                `invoices`.`id` AS `invoice_id`,
                `invoices`.`invoice_number`,
                `invoices`.`status` AS `invoice_status`,
                CONCAT(`staff`.`first_name`, ' ', `staff`.`last_name`) AS `staff`,
                `invoice_items`.`title` AS `item_sold`,
                CASE
                    WHEN `invoice_items`.`service_id` IS NOT NULL THEN 'Service'
                    WHEN `invoice_items`.`product_id` IS NOT NULL THEN 'Product'
                    WHEN `invoice_items`.`deal_id` IS NOT NULL THEN 'Deal'
                END AS `item_type`,
                `invoice_items`.`quantity` AS `qty`,
                IF (`invoice_items`.`staff_commission_logic` = 1, (`invoice_items`.`price` * `invoice_items`.`quantity`), (((`invoice_items`.`price` - `invoice_items`.`discount`) + `invoice_items`.`tax`) * `invoice_items`.`quantity`)) AS `sale_value`,
                IF (`invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_rate`, 0) AS `commission_rate`,
                IF (`invoices`.`status` <> 'refunded', `invoice_items`.`staff_commission_value` * `invoice_items`.`quantity`, 0) AS `commission_amount`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id` AND
                `invoices`.`status` <> 'voided' AND
                `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
            )
            INNER JOIN `staff` ON (
                `staff`.`id` = `invoice_items`.`staff_id`
            )
            WHERE 1
                {$whereSql}
                AND `invoice_items`.`staff_id` IS NOT NULL
                AND `invoice_items`.`staff_commission_value` > 0
                AND `invoice_items`.`created_at` BETWEEN DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)
            ORDER BY `invoices`.`id` DESC
        ";

        return DB::select($sql);
    }
}
<?php

namespace App\Http\Controllers\Portal\Sales;

use App\Helpers\Helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;

class DailySalesController extends Controller
{
    public function getDailySalesData(Request $request)
    {
        $this->validate($request, [
            "dateRange" => "required"
        ]);

        $dateRange = json_decode($request->dateRange);
        $startDate = Carbon::createFromDate($dateRange->startDate)->toDateString() . " 00:00:00";
        $endDate = Carbon::createFromDate($dateRange->endDate)->toDateString() . " 23:59:59";
        $offset = $request->business->timezone->offset;
        
        $transactionsSummaryData = DB::select("
            SELECT
                CASE
                    WHEN `invoice_items`.`service_id` IS NOT NULL THEN 'Service'
                    WHEN `invoice_items`.`product_id` IS NOT NULL THEN 'Product'
                END AS `type`,
                SUM(IF(`invoices`.`status` <> 'refunded', `invoice_items`.`quantity`, 0)) AS `sales_qty`,
                SUM(IF(`invoices`.`status` = 'refunded', `invoice_items`.`quantity`, 0)) AS `refund_qty`,
                ((SUM(IF(`invoices`.`status` IN ('completed', 'unpaid'), ((`invoice_items`.`price` + `invoice_items`.`tax`) - `invoice_items`.`discount`) * `invoice_items`.`quantity`, 0))) - (SUM(IF(`invoices`.`status` IN ('refunded'), ((`invoice_items`.`price` + `invoice_items`.`tax`) - `invoice_items`.`discount`) * `invoice_items`.`quantity`, 0)))) AS `gross_total`
            FROM `invoice_items`
            INNER JOIN `invoices` ON (
                `invoices`.`id` = `invoice_items`.`invoice_id`
            )
            WHERE 1
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$startDate} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$endDate} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `type`
        ");

        $cashMovementSummaryData = DB::select("
            SELECT
                `payment_methods`.`title` AS `payment_type`,
                SUM(IF(`invoices`.`status` <> 'refunded', `invoices`.`grandtotal`, 0)) AS  `payments_collected`,
	            SUM(IF(`invoices`.`status` = 'refunded', `invoices`.`grandtotal`, 0)) AS `refunds_paid`
            FROM `invoices`
            INNER JOIN `payment_methods` ON (
                `payment_methods`.`id` = `invoices`.`payment_method_id`
            )
            WHERE 1
                AND `invoices`.`status` <> 'voided'
                AND `invoices`.`business_id` = " . Helpers::getJWTData("business_id") . "
                AND `invoices`.`created_at` BETWEEN DATE_ADD('{$startDate} 00:00:00', INTERVAL '{$offset}' HOUR) AND DATE_ADD('{$endDate} 23:59:59', INTERVAL '{$offset}' HOUR)
            GROUP BY `invoices`.`payment_method_id`
        ");

        return [
            "cashMovementSummary" => $cashMovementSummaryData,
            "transactionsSummary" => $transactionsSummaryData
        ];
    }
}
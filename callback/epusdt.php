<?php
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit;
}

require_once(__DIR__ . "/../../../init.php");
require_once(__DIR__ . "/../../../includes/functions.php");
require_once(__DIR__ . "/../../../includes/gatewayfunctions.php");
require_once(__DIR__ . "/../../../includes/invoicefunctions.php");
require_once(__DIR__ . "/../epusdt.php");

use Illuminate\Database\Capsule\Manager as Capsule;

$gatewaymodule = "epusdt";

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated");

$body = file_get_contents("php://input");
$data = json_decode($body, true);

$signature = epusdt_sign($data, $GATEWAY["token"]);
if ($signature != $data['signature']) {
    die("Signature Error");
}

if ($data['status'] != 2) {
    exit("ok");
}

function convert_helper($invoiceid, $amount)
{
    $setting = Capsule::table("tblpaymentgateways")->where("gateway", "epusdt")->where("setting", "convertto")->first();
    // 系统没多货币 , 直接返回
    if (empty($setting)) {
        return $amount;
    }

    // 获取用户ID 和 用户使用的货币ID
    $data = Capsule::table("tblinvoices")->where("id", $invoiceid)->first();
    $userid = $data->userid;
    $currency = getCurrency($userid);

    // 返回转换后的
    return convertCurrency($amount, $setting->value, $currency["id"]);
}

# Get Returned Variables
$invoice_id = explode("-", $data['order_id'])[2];
$transid = $data['trade_id'];
$amount = $data['amount'];
$fee = 0;

$amount = convert_helper($invoice_id, $amount);

checkCbTransID($transid);
addInvoicePayment($invoice_id, $transid, $amount, $fee, $gatewaymodule);
logTransaction($GATEWAY["name"], $data, "Successful");
exit("ok");

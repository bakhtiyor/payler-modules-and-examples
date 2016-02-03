<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>
<?php

CModule::IncludeModule("payler.payler");
CModule::IncludeModule("sozdavatel.shop");

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

$order_id	= $_REQUEST["order_id"];
$key		= COption::GetOptionString("sozdavatel.shop", "PAYMENT_ROBOKASSA_LOGIN");
$test		= (COption::GetOptionString("sozdavatel.shop", "PAYMENT_ROBOKASSA_TEST") == "Y");

$bCorrectPayment = True;
$error = "";

if ( !(isset($_REQUEST["order_id"])) || (strripos($order_id, '|') == false) )
{
	$error = "ORDER_NOT_FOUND";
	$bCorrectPayment = False;
}

$order_id_real = substr($order_id, 0, strripos($order_id, '|')); 

if (!($arOrder = CSZDShop::GetOrderByID($order_id_real)) )
{
	$error = "ORDER_NOT_FOUND";
	$bCorrectPayment = False;
}

//echo $order_id.'   __  ';
$payler = new CPayler($test);

$data = array (
    "key" => $key,
    "order_id" => $order_id,
);

$result = $payler->POSTtoGateAPI($data, "GetAdvancedStatus");

if($result['status'] == 'Charged') {
	$arPaidStatus = CSZDShop::GetEnumPropList($arOrder["IBLOCK_ID"], "PAID_STATUS");
	$arProps = array(
		"PAID_SYSTEM_ID" => "Payler",
		"PAID_STATUS" => $arPaidStatus["Y"]["ID"],
		"PAID_PRICE" => $result['amount']/100,
		"PAID_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
	);
	foreach ($arProps as $code=>$value)
	{
		CIBlockElement::SetPropertyValues($arOrder["ID"], $arOrder["IBLOCK_ID"], Array($code => $value), $code);
	}
	$_SESSION["SZD_SHOP_PAY_INFO_".$arOrder["ID"]]["STATUS"] = "PAID";	
	LocalRedirect("/bitrix/tools/payler/success.php?order_id=".$order_id_real);

} else {
	$_SESSION["SZD_SHOP_PAY_INFO_".$arOrder["ID"]]["STATUS"] = "ERROR";
	$_SESSION["SZD_SHOP_PAY_INFO_".$arOrder["ID"]]["ERROR"] = $error;
	LocalRedirect("/bitrix/tools/payler/fail.php?order_id=".$order_id);
};

LocalRedirect("/bitrix/tools/payler/fail.php?order_id=".$order_id);

?>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>

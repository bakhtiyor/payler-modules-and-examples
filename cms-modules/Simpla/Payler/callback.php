<?php

chdir ('../../');

//require_once('api/Simpla.php');
require_once('payment/Payler/Payler.php');

$payler = new Payler();

$payler_order_id = $_GET['order_id'];
if(empty($payler_order_id))
{
    $payler_order_id = $_POST['order_id'];
}

$order_id = substr($payler_order_id, 0, strripos($payler_order_id, '-'));

if(empty($order_id)) {
	die('Возникла проблема при обработке заказа. Пожалуйста, обратитесь в службу поддержки сайта');
}

$order = $payler->orders->get_order(intval($order_id));

if(empty($order)) {
	die('Оплачиваемый заказ не найден');
}

if($order->paid) {
    //Заказ был ранее оплачен, перенаправляем пользователя на страницу заказа
    header('Refresh: 0; url='.$payler->config->root_url.'/order/'.$order->url);
	die('Заказ был оплачен ранее');
}

$method = $payler->payment->get_payment_method(intval($order->payment_method_id));

if(empty($method)) {
	die("Неизвестный метод оплаты");
}

$status = $payler->GetStatus($payler_order_id);  

if(empty($status)) {
    //Не удалось получить статус заказа, или заказ не оплачен. Перенаправляем на страницу заказа
    header('Refresh: 0; url='.$payler->config->root_url.'/order/'.$order->url);
	die("Возникла проблема при оплате");
}

//Проверка наличия товара - из модуля для Робокассы
//Если не требуется обновлять количество товара, можно убрать 
$purchases = $payler->orders->get_purchases(array('order_id'=>intval($order->id)));
foreach($purchases as $purchase) {
	$variant = $payler->variants->get_variant(intval($purchase->variant_id));
	if(empty($variant) || (!$variant->infinity && $variant->stock < $purchase->amount))	{
		die("Нехватка товара $purchase->product_name $purchase->variant_name");
	}
}

//Установка статуса, что заказ оплачен
$payler->orders->update_order(intval($order->id), array('paid'=>1));

//Списание товара со склада - из модуля для Робокассы
//Если не требуется обновлять количество товара, можно убрать 
$payler->orders->close(intval($order->id));
$payler->notify->email_order_user(intval($order->id));
$payler->notify->email_order_admin(intval($order->id));

//Перенаправляем пользователя на страницу успешной оплаты
header('Refresh: 0; url='.$payler->config->root_url.'/order/'.$order->url);
exit("OK".$order_id."\n");


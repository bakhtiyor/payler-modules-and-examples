<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Заказ оплачен");
?>
<?php

$order_id = $_GET['order_id'];

echo 'Заказ '.$order_id. ' успешно оплачен. Мы перезвоним вам в ближайшее время!';

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>

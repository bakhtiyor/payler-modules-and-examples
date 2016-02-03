<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Не удалось оплатить заказ");
?>

<?php

$order_id = $_GET['order_id'];
$order_id_real = substr($order_id, 0, strripos($order_id, '|')); 

echo 'Заказ '.$order_id_real. ' не удалось оплатить. Номер платежа: '.$order_id ;

?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>




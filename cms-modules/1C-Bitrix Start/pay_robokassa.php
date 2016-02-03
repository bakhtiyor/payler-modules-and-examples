<?
    include($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/sale_payment/payler/payler.classes.php");
    
    // class name = CSZDShopPay_<delivery_id>
    // class must have method "GetForm()"
    class CSZDShopPay_robokassa
    {
        static function GetForm($orderID, $orderPrice = false)
        {
            if (!$orderID)
                return false;
                
            if (!$orderPrice)
                $orderPrice = $_SESSION["SZD_SHOP_PAY_INFO_".$orderID]["TOTAL_PRICE"];
            
            $key    = COption::GetOptionString("sozdavatel.shop", "PAYMENT_ROBOKASSA_LOGIN");
            $test   = (COption::GetOptionString("sozdavatel.shop", "PAYMENT_ROBOKASSA_TEST") == "Y");

            if($test == "Y") { $test = true;} else {$test = false;};

            $payler = new CPayler($test);
            $data = array(
                "key"=>$key,
                "type"=>'OneStep',
                "order_id"=>$orderID.'|'.time(),
                "amount"=>$orderPrice * 100,
            );

            $session_data = $payler->POSTtoGateAPI($data, "StartSession");
            
            $session_id = $session_data['session_id'];

            if (!isset($session_data['session_id'])) {
                $error_message = '';
                if($session_data['error']) {
                    $error_message = ' (ошибка: '.$session_data['error']['code'].')';
                }
		$old_session = $payler->POSTtoGateAPI($data, "FindSession");
		if(isset($old_session['id'])) {
			$session_id = $old_session['id'];
		} else {
		
	                echo ('Ошибка инициализации сессии'.$error_message.'.<br>Если ошибка повторяется - обратитесь, пожалуйста, в службу поддержки магазина или на 24@payler.com');
		        $arForm = Array();
		        return $arForm;
		}
            }
            
            $arForm = Array(
                "ACTION" => ($test) ? "https://sandbox.payler.com/gapi/Pay": "https://secure.payler.com/gapi/Pay",
                "METHOD" => "post",
                "TARGET" => "_blank",
                "INPUTS_HIDDEN" => Array(
                    "session_id" => $session_id,
                ),
            );
            return $arForm;
        }
        
        static function GetID()
        {
            return "payler";
        }
        
        static function GetInfo($orderID, $orderPrice = false)
        {
            $arInfo = Array(
                "ID"            => CSZDShopPay_robokassa::GetID(),
                "FORM"            => CSZDShopPay_robokassa::GetForm($orderID, $orderPrice),
                "NAME"            => iconv("windows-1251", LANG_CHARSET, "Payler"),
                "NAME2"            => iconv("windows-1251", LANG_CHARSET, "Payler"),
                "DESCRIPTION"    => iconv("windows-1251", LANG_CHARSET, "Оплата заказа через платежный шлюз Payler"),
            );
            return $arInfo;
        }
    }
?> 

<?php

require_once('api/Simpla.php');

class Payler extends Simpla
{	
    private function CurlSendPost ($url, $data) 
    {	
        $headers = array(
            'Content-type: application/x-www-form-urlencoded',
            'Cache-Control: no-cache',
            'charset="utf-8"',
    	);
        
        $data = http_build_query($data);

        $options = array (
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_VERBOSE => 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data,            
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, $options);
    	$json = curl_exec($ch);
    	
        if ($json == false) {
            die ('Curl error: ' . curl_error($ch) . '<br>');
        }

        $result = json_decode($json, TRUE);
    	curl_close($ch);
        
    	return $result;
    }    
    
    private function POSTtoGateAPI ($data, $method, $order_id) 
    {
        $result = $this->CurlSendPost($this->GetURL($order_id).$method, $data);
        return $result;
    }
    
    private function GetURL($order_id)
    {
        $order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_settings = $this->payment->get_payment_settings($payment_method->id);
    
        if($payment_settings['test']) {
            $url = "https://sandbox.payler.com/gapi/";
        } else {
            $url = "https://secure.payler.com/gapi/";
        }
        
        return $url;
    }
    
    private function StartSession($order_id)
    {
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_settings = $this->payment->get_payment_settings($payment_method->id);

		$price = $this->money->convert($order->total_price, $payment_method->currency_id, false);

		$payler_key = $payment_settings['key'];
		$payler_type = $payment_settings['type'];
		
    	$payler_order_id = $order->id . '-' . substr(md5(time() . mt_rand(1,1000000)), 0, 8);
		
        $data = array(
            "key" => $payler_key,
            "type" => $payler_type,
            "order_id" => $payler_order_id,
            "amount" => $price * 100,
        );

        $session_data = $this->POSTtoGateAPI($data, "StartSession", $order_id);
            
        $session_id = $session_data['session_id'];

        if (!isset($session_data['session_id'])) {
            $error_message = '';
            if($session_data['error']) {
                $error_message = ' (ошибка: '.$session_data['error']['code'].')';
            }
        
    		$old_session = $this->POSTtoGateAPI($data, "FindSession", $order_id);
	    	if(isset($old_session['id'])) {
	    		$session_id = $old_session['id'];
	    	} else {
		
	            $error_message = 'Ошибка инициализации сессии'.$error_message.'.<br>Если ошибка повторяется - обратитесь, пожалуйста, в службу поддержки магазина или на 24@payler.com';
                return "";	            
	        }
        }
    
        return $session_id;
    }

    public function GetStatus($payler_order_id)
    {
        $order_id = substr($payler_order_id, 0, strripos($payler_order_id, '-'));
        if(empty($order_id)) {
        	$error_message = ('Возникла проблема при обработке заказа. Пожалуйста, обратитесь в службу поддержки сайта');
        }
    
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_settings = $this->payment->get_payment_settings($payment_method->id);

		$price = $this->money->convert($order->total_price, $payment_method->currency_id, false) * 100;

		$payler_key = $payment_settings['key'];

        $data = array(
            "key" => $payler_key,
            "order_id" => $payler_order_id,
        );

        $status_data = $this->POSTtoGateAPI($data, "GetStatus", $order_id);
        $status = $status_data['status'];
        $amount = $status_data['amount'];

        if (!isset($status_data['status'])) {
            $error_message = '';
            if($status_data['error']) {
                $error_message = ' (ошибка: '.$status_data['error']['code'].')';
            }else {
                $error_message = ' Ошибка при обработке платежа';            
            }
        } else {
            if(($status == 'Charged') || ($status == 'Authorized')) {
                if( $price == $amount) {
                    return $status;
                } else {
                    $error_message = 'Возникла проблема при обработке заказа. Пожалуйста, обратитесь в службу поддержки сайта';
                }
            } else {
                $error_message = 'Заказ не был оплачен. Если вы считаете, что возникла ошибка, обратитесь в службу поддержки сайта';
            }
        }
        return "";
    }

	public function checkout_form($order_id, $button_text = null) 
	{
		if(empty($button_text)) {
			$button_text = 'Перейти к оплате';
        }			

		$session_id = $this->StartSession($order_id);
	    if(!empty($session_id)) {
    		$button =	"<form accept-charset='cp1251' action='". $this->GetURL($order_id)."Pay' method=POST>".
	    				"<input type=hidden name=session_id value='$session_id'>".
	    				"<input type=submit class=checkout_button value='Перейти к оплате &#8594;'>".
	    				"</form>";
	    } else {
    		$button =	"<form accept-charset='cp1251'>".
	    				"<input type=hidden name=session_id value='$session_id'>".
	    				"<input type=submit class=checkout_button value='Ошибка при создании платежной сессии'>".
	    				"</form>";
	    }

		return $button;
	}



}

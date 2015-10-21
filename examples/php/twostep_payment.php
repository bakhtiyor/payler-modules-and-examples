<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<style type="text/css" media ="all">
	@import url('css/style.css'); 
	</style>
</head>
<body>
    <?php
    include 'payler_class.php';
    include 'settings.php';

    echo '<table>';
    echo '<tr><th>Двухстадийный платеж</th></tr>';

    /*
    Создаем платежную сессию через метод StartSession
    Начинаем оплату сформированного заказа через метод Pay. Пользователь будет перенаправлен на сайт Payler
    */

    $payler = new Payler($test);

    $order_id = time();   // номер заказа, должен быть уникальным
    $amount   = 100;      // стоимость в копейках
    $product  = "стол";   // описание товара или заказа
    $total    = 1;        // количество товаров 
    $currency = "RUB";    // код валюты
    $type = "TwoStep";    // тип платежа

    $data = array (                // Параметры, отмеченные в описании звездочкой (*), обязательны для заполнения

        'key' => $key,             // *Идентификатор продавца, выдается продавцу при регистрации вместе с параметрами доступа
        'type' => $type,           // *Тип сессии, определяет количество стадий платежа (одностадийный или двухстадийный)
                                   //     допустимые значения - "OneStep"|"TwoStep"|1|2
        'order_id' => $order_id,   // *Идентификатор заказа (платежа). Для каждой сессии должен быть уникальным 
                                   //     (строка, максимум 100 символов, только печатные символы ASCII)
        'currency' => $currency,   //  Валюта платежа. По умолчанию - рубли
                                   //     допустимые значения - "RUB"|"USD"|"EUR"
        'amount' => $amount,       // *Сумма платежа 
                                   //     в зависимости от валюты - в копейках|центах|евроцентах
        'product' => $product,     //  Описание товара или заказа
                                   //     (строка, максимум 256 символов)
        'total' => $total,         //  Количество товаров в заказе
                                   //     (вещественное число)
      /*'template' => $template,   //  Используемый шаблон платежной формы. Если не задан, используется шаблон по умолчанию
                                   //     (строка, максимум 100 символов)
        'lang' => 'ru'             //  Предпочитаемый язык платежной формы и ответов сервера. По умолчанию - русский.
                                   //     допустимые значения - "ru"|"en"
        'userdata' => 'dt'         //  Пользовательские данные, которые необходимо сохранить вместе с платежом
                                   //     (строка, максимум - 10 KiB). Для получения - см. в API GetAdvancedStatus
        'recurrent' => 'TRUE',     //  Флаг, показывает, требуется ли создать шаблон для рекурентных платежей на основании текущего
                                   //     допустимые значения - 'TRUE'|'FALSE'|1|0
        'pay_page_param_<имя парамметра>' => 'text'
                                   //  Параметр для отображения на странице платежной формы. Для использования см. template
                                   //     (строка, максимум - 100 символов)
       */
        );                         // Параметры, отмеченные в описании звездочкой (*), обязательны для заполнения

    //Создаем платежную сессию 
    $session_data = $payler->POSTtoGateAPI($data, "StartSession");

    echo '<tr><td>';
    
    echo 'Полученный ответ на StartSession: <br/>';
    var_dump($session_data);

    echo '</td></tr><tr><td>';
    
    //Если заполнен session_id, сессию удалось создать 
    if(isset($session_data['session_id'])) {
 
        $session_id = $session_data['session_id'];
        
        /*
        Оплачиваем заказ, параметр - session_id.
        Для оплаты заказа пользователь перенаправляется на сайт Payler, после успешной или неуспешной оплаты возвращается 
        на сайт по ссылке, указанной в настройках учетной записи Payler. Для получения результата оплаты на странице 
        возврата необходимо вызвать GetStatus с параметрами - идентификатор заказа ($order_id) и идентификатор продавца ($key)
        */   
        $pay = $payler->Pay($session_id);

        echo $pay;
    }else {
        echo 'Не удалось создать сессию и провести оплату. Возможные причины: <br/>
                 Используется неправильный платежный ключ ('.$key.') <br/>
                 При создании сессии используется неуникальный номер заказа ('.$order_id.') <br/>
                 Некорректно указаны прочие параметры <br/>
                 IP сервера не включен в белый список в настройках учетной записи Payler ('.$_SERVER['SERVER_ADDR'].') <br/>
                 На сервере не установлена библиотека cURL или JSON <br/>
                 На сервере отключены функции curl_init, curl_setopt_array, curl_exec, json_decode, сurl_close';
    }
    echo '</td></tr></table>';    
        
    ?>

</body>
</html>

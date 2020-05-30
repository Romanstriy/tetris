<?
AddEventHandler("sale", "OnOrderSave", "SaleComponentOrderCompleteHandler");

//2222222222222
function requestToCRM($data, $method)
{
    $queryUrl = 'https://dagroup.bitrix24.ua/rest/237/24qx13aldxd0on80/' . $method . ".json";
    $result = array();
    $queryData = http_build_query($data);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, 1);

    if (isset($response['error'])) {
        $text = "\ndate: " . date('d.m.Y h:i:s A') . "\nmethod: $method";
        file_put_contents('log_leadGenerate_error.txt', $text . "\n" . print_r($response, true) . print_r($data, true), FILE_APPEND);
    }

    return $response;
}


function SaleComponentOrderCompleteHandler($ID, $arFields, $arOrder, $isNew)
{
    if (!empty($arOrder) && is_array($arOrder)) {

        $title = 'Заказ#' . $arOrder['ID'] . ' с сайта denyan-lash.com.ua';

        $fields = array(
            'filter' => array(
                'TITLE' => $title,
            ),
        );
        $duplicates = requestToCRM($fields, 'crm.lead.list');

        if (empty($duplicates['result'])) {

            $orders = '<table border="1">';
            $orders .= "<tr><td>Название</td><td>Количество</td><td>Цена</td></tr>";
            foreach ($arOrder['BASKET_ITEMS'] as $orderData) {
                $QUANTITY = (int)$orderData['QUANTITY'];
                $PRICE = (int)$orderData['PRICE'];
                $orders .= "<tr><td>{$orderData['NAME']}</td><td style='white-space: nowrap;'>{$QUANTITY}</td><td style='white-space: nowrap;'>{$PRICE}</td></tr>";
            }
            $orders .= '</table>';

            $fields = array(
                'fields' => array(
                    'TITLE' => $title, // Назва лида
                    'OPPORTUNITY' => $arOrder['PRICE'],
                    'CURRENCY_ID' => $arOrder['CURRENCY'],
                    'NAME' => !empty($arOrder['PAYER_NAME']) ? $arOrder['PAYER_NAME'] : '',
                    'LAST_NAME' => !empty($arOrder['ORDER_PROP'][6]) ? $arOrder['ORDER_PROP'][6] : '',
                    'SECOND_NAME' => !empty($arOrder['ORDER_PROP'][4]) ? $arOrder['ORDER_PROP'][4] : '',
                    'UF_CRM_1560954034' => !empty($arOrder['ORDER_PROP'][31]) ? $arOrder['ORDER_PROP'][31] : '',
                    'ADDRESS' => !empty($arOrder['DELIVERY_LOCATION']) ? $arOrder['DELIVERY_LOCATION'] : '',
                    'PHONE' => array(
                        array(
                            'VALUE' => !empty($arOrder['ORDER_PROP'][7]) ? $arOrder['ORDER_PROP'][7] : '',
                            'VALUE_TYPE' => 'WORK'
                        )
                    ),
                    'EMAIL' => array(
                        array(
                            'VALUE' => !empty($arOrder['USER_EMAIL']) ? $arOrder['USER_EMAIL'] : '',
                            'VALUE_TYPE' => 'WORK'
                        )
                    ),
                    'COMMENTS' => $orders . '<br>Комментарий к заказу: ' . $arOrder['USER_DESCRIPTION'], // Все данные с формы

                    'UTM_CAMPAIGN' => !empty($form['UTM_CAMPAIGN']) ? $form['UTM_CAMPAIGN'] : '', // Обозначение рекламной кампании
                    'UTM_CONTENT'  => !empty($form['UTM_CONTENT']) ? $form['UTM_CONTENT'] : '',   // Содержание кампании
                    'UTM_MEDIUM'   => !empty($form['UTM_MEDIUM']) ? $form['UTM_MEDIUM'] : '',     // Тип трафика
                    'UTM_SOURCE'   => !empty($form['UTM_SOURCE']) ? $form['UTM_SOURCE'] : '',     // Рекламная система

                    'ASSIGNED_BY_ID' => 281, // Ответственный за лид
                )
            );
            $leadData = requestToCRM($fields, 'crm.lead.add');

        }
    }


    if (!CModule::IncludeModule('sale')) {
        return true;
    }
    if ($isNew) {
        $order = Bitrix\Sale\Order::load($ID);

        $propertyCollection = $order->getPropertyCollection();

        //получить свойства
        $resName = $propertyCollection->getItemByOrderPropertyId(3);
        $resSurname = $propertyCollection->getItemByOrderPropertyId(6);
        $resMiddleName = $propertyCollection->getItemByOrderPropertyId(4);
        $resFullName = $propertyCollection->getItemByOrderPropertyId(14);
        $groupValue = $propertyCollection->getItemByOrderPropertyId(31);

        //взять уже имеющиеся
        $name = $resName->getValue();
        $surname = $resSurname->getValue();
        $middleName = $resMiddleName->getValue();

        //записать ФИО
        $resFullName->setValue($surname . ' ' . $name . ' ' . $middleName);


        // проверить группу и записать
        global $USER;
        if (
            in_array(6, $USER->GetUserGroupArray()) ||
            in_array(12, $USER->GetUserGroupArray()) ||
            in_array(14, $USER->GetUserGroupArray()) ||
            in_array(15, $USER->GetUserGroupArray()) ||
            in_array(16, $USER->GetUserGroupArray()) ||
            in_array(23, $USER->GetUserGroupArray()) ||
            in_array(24, $USER->GetUserGroupArray()) ||
            in_array(25, $USER->GetUserGroupArray()) ||
            in_array(26, $USER->GetUserGroupArray()) ||
            in_array(29, $USER->GetUserGroupArray()) ||
            in_array(30, $USER->GetUserGroupArray()) ||
            in_array(31, $USER->GetUserGroupArray()) ||
            in_array(32, $USER->GetUserGroupArray()) ||
            in_array(33, $USER->GetUserGroupArray()) ||
            in_array(34, $USER->GetUserGroupArray()) ||
            in_array(35, $USER->GetUserGroupArray()) ||
            in_array(36, $USER->GetUserGroupArray()) ||
            in_array(37, $USER->GetUserGroupArray())
        ) {
            $groupValue->setValue("Представитель");
        } else {

            $groupValue->setValue("Клиент");
        }


        // сохранить
        $order->save();
    }


    /*
        $db_vals = CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $ID, "ORDER_PROPS_ID" => 14));
        if (!($arVals = $db_vals->Fetch())) {
            $db_vals = CSaleOrderPropsValue::GetList(
                array("SORT" => "ASC"),
                array(
                    "ORDER_ID" => $ID,
                    "ORDER_PROPS_ID" => array("6", "3", "4"),
                )
            );

            while ($arVal = $db_vals->Fetch()) {
                $arVals[$arVal["ORDER_PROPS_ID"]] = $arVal["VALUE"];
            }

            $fio = $arVals['6'] . ' ' . $arVals['3'] . ' ' . $arVals['4'];

            $arFields = array(
                "ORDER_ID" => $ID,
                "ORDER_PROPS_ID" => "14",
                "NAME" => "Полное ФИО",
                "CODE" => "FFIO",
                "VALUE" => $fio
            );

            CSaleOrderPropsValue::Add($arFields);
        }
        global $USER;

        $db_vals = CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $ID, "ORDER_PROPS_ID" => 31));
        if ($arVals = $db_vals->Fetch()) {
            if (
                 in_array(6, $USER->GetUserGroupArray()) ||
                 in_array(24, $USER->GetUserGroupArray()) ||
                 in_array(25, $USER->GetUserGroupArray()) ||
                 in_array(11, $USER->GetUserGroupArray()) ||
                 in_array(12, $USER->GetUserGroupArray()) ||
                 in_array(16, $USER->GetUserGroupArray()) ||
                 in_array(14, $USER->GetUserGroupArray()) ||
                 in_array(15, $USER->GetUserGroupArray()) ||
                 in_array(23, $USER->GetUserGroupArray())
             ) {

                 AddMessage2Log("ghbdtn");
                $arFields = array(
                    "ORDER_ID" => $ID,
                    "ORDER_PROPS_ID" => "31",
                    "NAME" => "Группа пользователя",
                    "CODE" => "REPRES",
                    "VALUE" => 'Представитель'
                );

                CSaleOrderPropsValue::Add($arFields);
            } else {
                $arFields = array(
                    "ORDER_ID" => $ID,
                    "ORDER_PROPS_ID" => "31",
                    "NAME" => "Группа пользователя",
                    "CODE" => "REPRES",
                    "VALUE" => 'Клиент'
                );

                CSaleOrderPropsValue::Add($arFields);
            }
        }
    */

}



//wordpress вордпрес.
//public_html/wp-content/plugins/woocommerce/includes/class-wc-checkout.php
// Save the order.
$order_id = $order->save();

$items1 = WC()->cart->get_cart();

foreach($items1 as $item => $values) {
    $_product =  wc_get_product( $values['data']->get_id());
    $price = get_post_meta($values['product_id'] , '_price', true);

    $items2[] = "<b>".$_product->get_title().'</b>   Quantity: '.$values['quantity']."  Price: ".$price;

}

$totalprice = $order->get_total();

if($data["payment_method"] == 'emt'){
    $payment = "Interac e-Transfer";
}elseif($data["payment_method"] == 'beanstream'){
    $payment = "Credit Card";
}


$com = $com. "<br \>".
    "Street address - ".$data["billing_address_1"]. "<br \>".
    "Apt number/suite - ".$data["billing_address_2"]. "<br \>".
    "Country - ".$data["billing_country"]. "<br \>".
    "State - ".$data["billing_state"]. "<br \>".
    "Town / City - ".$data["billing_city"]. "<br \>".
    "Postal Code - ".$data["billing_postcode"]. "<br \>".
    "Delivery Time - ".$data["billing_delivery_time"]. "<br \>".
    "Delivery Instructions - ".$data["billing_delivery_instructions"]. "<br \>".
    "Payment - ".$payment. "<br \>".
    "Goods in the order  - ".implode(",", $items2). "<br \>".
    "Total order amount - ".$totalprice. "<br \>".
    "Order number: - ".$order_id;




if ( !empty( $totalprice ) ){

    function requestToCRM($data, $method){
        $queryUrl = 'https://nutrition-balance.bitrix24.com/rest/669/r1wme3j6tv4buwso/'.$method.".json";

        $result = array();

        $queryData = http_build_query($data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, 1);

        if (isset($response['error'])) {
            $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
        }else{
        }

        return $response;
    }

    if(($totalprice != '')&&($data['billing_postcode'] != '')){
        $data = array(
            'fields' => array(
                "TITLE" => "Order from the site nutritionbalance.ca",
                "NAME" => urldecode($data['billing_first_name']).' '.urldecode($data['billing_last_name']),
                "COMMENTS" => $com,
                "PHONE" => array(array("VALUE" => urldecode($data['billing_phone']), "VALUE_TYPE" => "WORK")),
                "EMAIL" => array(array("VALUE" => urldecode($data['billing_email']), "VALUE_TYPE" => "WORK" )),
                "SOURCE_ID" => "WEB",
                "ASSIGNED_BY_ID" => 673,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }
}

//public_html/wp-admin/admin-ajax.php
if ( !empty( $_REQUEST ) ){

    function requestToCRM($data, $method){
        $queryUrl = 'https://nutrition-balance.bitrix24.com/rest/669/r1wme3j6tv4buwso/'.$method.".json";

        $result = array();

        $queryData = http_build_query($data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, 1);

        if (isset($response['error'])) {
            $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
        }else{
        }

        return $response;
    }

    if(($_REQUEST['action'] == 'formcraft3_form_submit')&&($_REQUEST['id'] == '2')){
        $data = array(
            'fields' => array(
                "TITLE" => "Contact form page nutritionbalance.ca",
                "NAME" => urldecode($_REQUEST['field1'][0]),
                "COMMENTS" => urldecode($_REQUEST['field4']),
                "PHONE" => array(array("VALUE" => urldecode($_REQUEST['field2'][0]), "VALUE_TYPE" => "WORK")),
                "EMAIL" => array(array("VALUE" => urldecode($_REQUEST['field3']), "VALUE_TYPE" => "WORK" )),
                "SOURCE_ID" => "WEB",
                "ASSIGNED_BY_ID" => 673,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }
    if(($_REQUEST['action'] == 'formcraft3_form_submit')&&($_REQUEST['id'] == '4')){
        $data = array(
            'fields' => array(
                "TITLE" => "Free Consultation form page nutritionbalance.ca",
                "NAME" => urldecode($_REQUEST['field1'][0]),
                "COMMENTS" => urldecode($_REQUEST['field4']),
                "PHONE" => array(array("VALUE" => urldecode($_REQUEST['field2'][0]), "VALUE_TYPE" => "WORK")),
                "EMAIL" => array(array("VALUE" => urldecode($_REQUEST['field3']), "VALUE_TYPE" => "WORK" )),
                "SOURCE_ID" => "WEB",
                "ASSIGNED_BY_ID" => 673,
            )
        );
        $leadAddResult2 = requestToCRM($data,'crm.lead.add');
    }
}






------------------------------------------------------------------------------------------------------------------------------------------------------------------
//JOOMLA
//Замовлення в битрикс 24 повністю з товарами і створенням користувача
///mgf.com.ua/components/com_virtuemart/views/cart/tmpl\order_done.php

//відправка в битрикс24 формування змінних
$BT = $this->cart->BT;
$orderDetailss = $this->cart->orderDetails;

$order_number = $orderDetailss[details][BT]->order_number;
$title = "Заказ з сайту 'http://mgf.com.ua' - №".$order_number;
$telef = $BT[phone_1];
$name = $BT[first_name];
$mail = $BT[email];
$sum = $orderDetailss[details][BT]->order_total;
$paymentmethod_id = $orderDetailss[details][BT]->virtuemart_paymentmethod_id;
$shipmentmethod_id = $orderDetailss[details][BT]->virtuemart_shipmentmethod_id;
$item1 = $orderDetailss[items][0]->order_item_name;
$item2 = $orderDetailss[items][1]->order_item_name;
$item3 = $orderDetailss[items][2]->order_item_name;
$item4 = $orderDetailss[items][3]->order_item_name;
$item5 = $orderDetailss[items][4]->order_item_name;
$itemquantity1 = $orderDetailss[items][0]->product_quantity;
$itemquantity2 = $orderDetailss[items][1]->product_quantity;
$itemquantity3 = $orderDetailss[items][2]->product_quantity;
$itemquantity4 = $orderDetailss[items][3]->product_quantity;
$itemquantity5 = $orderDetailss[items][4]->product_quantity;




if($item5!=""){
    $com =  "1 - ".$item1.". /кол-".$itemquantity1. '<br \>'.
        "2 - ".$item2.". /кол-".$itemquantity2. '<br \>'.
        "3 - ".$item3.". /кол-".$itemquantity3. '<br \>'.
        "4 - ".$item4.". /кол-".$itemquantity4. '<br \>'.
        "5 - ".$item5.". /кол-".$itemquantity5. '<br \>'.
        "Сума - ".round($sum). "грн.";
}
elseif($item4!=""){
    $com =  "1 - ".$item1.". /кол-".$itemquantity1. '<br \>'.
        "2 - ".$item2.". /кол-".$itemquantity2. '<br \>'.
        "3 - ".$item3.". /кол-".$itemquantity3. '<br \>'.
        "4 - ".$item4.". /кол-".$itemquantity4. '<br \>'.
        "Сума - ".round($sum). "грн.";
}
elseif($item3!=""){
    $com =  "1 - ".$item1.". /кол-".$itemquantity1. '<br \>'.
        "2 - ".$item2.". /кол-".$itemquantity2. '<br \>'.
        "3 - ".$item3.". /кол-".$itemquantity3. '<br \>'.
        "Сума - ".round($sum). "грн.";
}
elseif($item2!=""){
    $com =  "1 - ".$item1.". /кол-".$itemquantity1. '<br \>'.
        "2 - ".$item2.". /кол-".$itemquantity2. '<br \>'.
        "Сума - ".round($sum). "грн.";
}
elseif($item1!=""){
    $com =  "1 - ".$item1.". /кол-".$itemquantity1. '<br \>'.
        "Сума - ".round($sum). "грн.";
}



if($shipmentmethod_id == 1){
    $shipment = 45;
}elseif($shipmentmethod_id == 2){
    $shipment = 47;
}
if($paymentmethod_id == 3){
    $payment = 49;
}elseif($paymentmethod_id == 4){
    $payment = 51;
}


function requestToCRM($data, $method){
    $queryUrl = 'https://elit-edelweiss.bitrix24.ua/rest/29/2un31xb47mfdfj1z/'.$method.".json";

    $result = array();

    $queryData = http_build_query($data);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, 1);

    if (isset($response['error'])) {
        $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
        file_put_contents('log_error.txt', "\n". $text . print_r($response, true) . print_r($data, true), FILE_APPEND);
    }

    return $response;
}


if(isset($order_number)){

    $data = array();
    $data['id'] = $order_number;
    $data['name'] = $name;
    $data['phone'] = $telef;
    $data['email'] = $mail;
    $data['price'] = round($sum);
    $data['comment'] = $com;
    $data['dostavka'] = $shipment;
    $data['payment'] = $payment;
    $source = "WEB";
    $assigned = 29;

    $contactPhoneGetResult = requestToCRM(array(
        'filter' => array('PHONE' => $data['phone'],
        )), 'crm.contact.list');
    $companyPhoneGetResult = requestToCRM(array(
        'filter' => array('PHONE' => $data['phone'],
        )), 'crm.company.list');

    $dealAddArray = array(
        "TITLE" => $title,
        "TYPE_ID" => "SALE",
        "STAGE_ID" => "NEW",
        "SOURCE_ID" => $source,
        "OPENED" => "Y",
        "CURRENCY_ID" => "UAH",
        "COMMENTS" => $com,
        "ASSIGNED_BY_ID" => $assigned,
        "OPPORTUNITY" => $data['price'],
        "UF_CRM_1551969344174" => $data['dostavka'],
        "UF_CRM_1551969396367" => $data['payment'],
    );

    if(count($companyPhoneGetResult['result'])>0){
        $dealAddArray["COMPANY_ID"] = $companyPhoneGetResult['result'][0]['ID'];
    }else{
        if(count($contactPhoneGetResult['result'])>0){
            $contactAddResult['result'] = $contactPhoneGetResult['result'][0]['ID'];
        }else{
            $params = array("REGISTER_SONET_EVENT" => "Y");

            $contactAddArray = array(
                "NAME" => $data['name'],
                //"LAST_NAME" => $data['sname'],
                "OPENED" => "Y",
                "ASSIGNED_BY_ID" => $assigned,
                "TYPE_ID" => "CLIENT",
                "PHONE" => array(array( "VALUE" => $data['phone'], "VALUE_TYPE" => "MOBILE" )),
            );
            if(!strpos($data['email'], '@localhost')){
                $contactAddArray["EMAIL"] = array(array( "VALUE" => $data['email'], "VALUE_TYPE" => "WORK" ));
            }
            $contactAddResult = requestToCRM(array(
                'fields' => $contactAddArray,
                'params' => $params
            ), 'crm.contact.add');
        }
        $dealAddArray["CONTACT_ID"] = $contactAddResult['result'];
    }

    //file_put_contents('deal_add_fields.txt',print_r($dealAddArray,true));
    $dealAddResult = requestToCRM(array(
        'fields' => $dealAddArray,
        'params' => $params
    ), 'crm.deal.add');


}
//JOOMLA
//RSform
////mgf.com.ua/administrator/components/com_rsform/helpers/rsform.php
		$post = $_POST['form'];

		function requestToCRM($data, $method){
            $queryUrl = 'https://elit-edelweiss.bitrix24.ua/rest/29/2un31xb47mfdfj1z/'.$method.".json";

            $result = array();

            $queryData = http_build_query($data);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, 1);

            if (isset($response['error'])) {
                $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
            }else{
            }

            return $response;
        }

	if($_POST['form']["formId"]==3){
        $data = array(
            'fields' => array(
                "TITLE" => "Заявка з форми на странице 'Контакты' на сайті http://mgf.com.ua",
                "NAME" => $_POST['form']["name"],
                "COMMENTS" => $_POST['form']["message"],
                "EMAIL" => array(array("VALUE" => $_POST['form']["email"], "VALUE_TYPE" => "WORK" )),
                "PHONE" => array(array("VALUE" => $_POST['form']["phone"], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }

-------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//JOOMLA
//Форма з головної сторінки
///mgf.com.ua/modules/mod_itf_call_me_back/tmpl/default.php

function requestToCRM($data, $method){
$queryUrl = 'https://elit-edelweiss.bitrix24.ua/rest/29/2un31xb47mfdfj1z/'.$method.".json";

    $result = array();

    $queryData = http_build_query($data);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, 1);

    if (isset($response['error'])) {
        $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
    }else{
    }

    return $response;
}

if($_REQUEST['action']=="CallMeUp"){
    $data = array(
        'fields' => array(
            "TITLE" => "Заявка з форми 'Заказать звонок' на сайті http://mgf.com.ua/",
            "NAME" => $_REQUEST['contact_name'],
            "PHONE" => array(array("VALUE" => $_REQUEST['contact_phone'], "VALUE_TYPE" => "WORK")),
            "ASSIGNED_BY_ID" => 29,
        )
    );
    $leadAddResult = requestToCRM($data,'crm.lead.add');
}

-------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//JOOMLA
//RSform
///hunter.ua/administrator/components/com_rsform/helpers/rsform.php
		$post = $_POST['form'];


		function requestToCRM($data, $method){
            $queryUrl = 'https://elit-edelweiss.bitrix24.ua/rest/29/2un31xb47mfdfj1z/'.$method.".json";

            $result = array();

            $queryData = http_build_query($data);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, 1);

            if (isset($response['error'])) {
                $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
            }else{
            }

            return $response;
        }

	if($_POST['form']["formId"]==7){
        $data = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Заказать звонок' на сайті https://hunter.ua",
                "NAME" => $_POST['form']["Name"],
                "PHONE" => array(array("VALUE" => $_POST['form']["Telefon"], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }elseif($_POST['form']["formId"]==5){
        $data = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Online Заказ' на сайті https://hunter.ua",
                "NAME" => $_POST['form']["Name"],
                "COMMENTS" => $_POST['form']["Text"],
                "EMAIL" => array(array("VALUE" => $_POST['form']["Email"], "VALUE_TYPE" => "WORK" )),
                "PHONE" => array(array("VALUE" => $_POST['form']["Telefon"], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }elseif($_POST['form']["formId"]==4){
        $data = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Контакты/Заказать звонок' на сайті https://hunter.ua",
                "NAME" => $_POST['form']["Name"],
                "PHONE" => array(array("VALUE" => $_POST['form']["Tel"], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }elseif($_POST['form']["formId"]==3){
        if($_POST['form']["General"][0]=="info@hunter.ua"){
            $tem = "Общие вопросы";
        }elseif($_POST['form']["General"][0]=="berkuta@hunter.ua"){
            $tem = "Технические вопросы";
        }
        $data = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Связаться с нами' на сайті https://hunter.ua - ".$tem,
                "NAME" => $_POST['form']["Name"],
                "COMMENTS" => $_POST['form']["Text"],
                "EMAIL" => array(array("VALUE" => $_POST['form']["Email"], "VALUE_TYPE" => "WORK" )),
                "PHONE" => array(array("VALUE" => $_POST['form']["Telefon"], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data,'crm.lead.add');
    }


-------------------------------------------------------------------------------------------------------------------------------------------------------------------------

//JOOMLA
//com_contactus
////smartstreet.com.ua/components/com_contactus/controllers/add.php
		$data = $app->input->post->getArray($_POST);


		function requestToCRM($data1, $method){
            $queryUrl = 'https://elit-edelweiss.bitrix24.ua/rest/29/2un31xb47mfdfj1z/'.$method.".json";

            $result = array();

            $queryData = http_build_query($data1);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, 1);

            if (isset($response['error'])) {
                $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
            }else{
            }

            return $response;
        }

	if($data['module_id']==116){
        $data1 = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Заказать выездную презентацию освещения' на сайті https://smartstreet.com.ua/",
                "NAME" => $data['name'],
                "EMAIL" => array(array("VALUE" => $data['email'], "VALUE_TYPE" => "WORK")),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data1,'crm.lead.add');
    }elseif($data['module_id']==112){
        $data1 = array(
            'fields' => array(
                "TITLE" => "Заявка з форми 'Обратная связь' на сайті https://smartstreet.com.ua/",
                "NAME" => $data['name'],
                "COMMENTS" => $data['message'],
                "EMAIL" => array(array("VALUE" => $data['email'], "VALUE_TYPE" => "WORK" )),
                "ASSIGNED_BY_ID" => 29,
            )
        );
        $leadAddResult = requestToCRM($data1,'crm.lead.add');
    }



---------------------------------------------------------------------------------------------------------------------------------------------------------------------

//opencart
//Заказ передавати весь. http://bezpeka.zp.ua/
///www/system/modification/catalog/controller/checkout/success.php



	if(isset($order_id)){

				if(isset($data["products"][0]["name"])){
                    $w1 = $data["products"][0]["name"];
                    $f1 = ', Кол.'.' - '.$data["products"][0]["quantity"].', Цена.'.' - '.$data["products"][0]["price"]. " ";
                }
				if(isset($data["products"][1]["name"])){
                    $w2 = $data["products"][1]["name"];
                    $f2 = ', Кол.'.' - '.$data["products"][1]["quantity"].', Цена.'.' - '.$data["products"][1]["price"]. " ";
                }else{
                    $w2="";
                    $f2="";
                }
				if(isset($data["products"][2]["name"])){
                    $w3 = $data["products"][2]["name"];
                    $f3 = ', Кол.'.' - '.$data["products"][2]["quantity"].', Цена.'.' - '.$data["products"][2]["price"]. " ";
                }else{
                    $w3="";
                    $f3="";
                }
				if(isset($data["products"][3]["name"])){
                    $w4 = $data["products"][3]["name"];
                    $f4 = ', Кол.'.' - '.$data["products"][3]["quantity"].', Цена.'.' - '.$data["products"][3]["price"]. " ";
                }else{
                    $w4="";
                    $f4="";
                }
				if(isset($data["products"][4]["name"])){
                    $w5 = $data["products"][4]["name"];
                    $f5 = ', Кол.'.' - '.$data["products"][4]["quantity"].', Цена.'.' - '.$data["products"][4]["price"]. " ";
                }else{
                    $w5="";
                    $f5="";
                }
				if(isset($data["products"][5]["name"])){
                    $w6 = $data["products"][5]["name"];
                    $f6 = ', Кол.'.' - '.$data["products"][5]["quantity"].', Цена.'.' - '.$data["products"][5]["price"]. " ";
                }else{
                    $w6="";
                    $f6="";
                }
				if(isset($order_info["total"])){
                    $tot = round($order_info["total"]).' грн.';
                }
				$com = '';

				if($w6!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "2 - ".$w2.". ".$f2. "<br \>".
                        "3 - ".$w3.". ".$f3. "<br \>".
                        "4 - ".$w4.". ".$f4. "<br \>".
                        "5 - ".$w5.". ".$f5. "<br \>".
                        "6 - ".$w6.". ".$f6. "<br \>".
                        "Сумма - ".$tot;
                }elseif($w5!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "2 - ".$w2.". ".$f2. "<br \>".
                        "3 - ".$w3.". ".$f3. "<br \>".
                        "4 - ".$w4.". ".$f4. "<br \>".
                        "5 - ".$w5.". ".$f5. "<br \>".
                        "Сумма - ".$tot;
                }
                elseif($w4!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "2 - ".$w2.". ".$f2. "<br \>".
                        "3 - ".$w3.". ".$f3. "<br \>".
                        "4 - ".$w4.". ".$f4. "<br \>".
                        "Сумма - ".$tot;
                }
                elseif($w3!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "2 - ".$w2.". ".$f2. "<br \>".
                        "3 - ".$w3.". ".$f3. "<br \>".
                        "Сумма - ".$tot;
                }
                elseif($w2!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "2 - ".$w2.". ".$f2. "<br \>".
                        "Сумма - ".$tot;
                }
                elseif($w1!=""){
                    $com =  "1 - ".$w1.". ".$f1. "<br \>".
                        "Сумма - ".$tot;
                }


				}

				// if($data["shipping_code"]=="flat336377.flat336377"){
					// $dost = "Доставка службой Деливери";
				// }elseif($data["shipping_code"]=="flat262895.flat262895"){
					// $dost = "Доставка службой Новая почта";
				// }elseif($data["shipping_code"]=="flat289597.flat289597"){
					// $dost = "Доставка службой Интайм";
				// }elseif($data["shipping_code"]=="flat628461.flat628461"){
					// $dost = "Доставка службой САТ";
				// }elseif($data["shipping_code"]=="flat447002.flat447002"){
					// $dost = "самовывоз со склада г. Запорожье";
				// }elseif($data["shipping_code"]=="flat830530.flat830530"){
					// $dost = "Указать иной способ доставки";
				// }

				// if($data["payment_code"]=="cod"){
					// $payment = "Оплата при получении";
				// }elseif($data["payment_code"]=="cod841447"){
					// $payment = "Оплата на карту Приватбанка";
				// }elseif($data["payment_code"]=="cod380356"){
					// $payment = "Оплата на расчетный счет предприятия";
				// }
				$dost = $data["shipping_method"];
				$payment = $data["payment_method"];

				$com = $com. "<br \>".
                    "Способ доставки - ".$dost. "<br \>".
                    "Способ оплаты - ".$payment. "<br \>".
                    "№ от. перевозчика  - ".$order_info["payment_address_1"]. "<br \>".
                    "Комментарий - ".$data["comment"]. "<br \>".
                    "Номер заказа: - ".$data["order_id"];


	function requestToCRM($data1, $method){
        $queryUrl = 'https://alex.bitrix24.ua/rest/11/cpf0dyhiqb5z5uiq/'.$method.".json";

        $result = array();

        $queryData = http_build_query($data1);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, 1);

        if (isset($response['error'])) {
            $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
        }else{
        }

        return $response;
    }
  if(stristr($order_info["email"], 'localhost') === FALSE) {
  }else{
      $order_info["email"] = "";
  }
if(isset($order_id)){
    $data1 = array(
        'fields' => array(
            "TITLE" => "Заявка з форми 'Оформление заказа' на сайті http://bezpeka.zp.ua/",
            "NAME" => $order_info['firstname'],
            "PHONE" => array(array("VALUE" => $order_info['telephone'], "VALUE_TYPE" => "WORK")),
            "COMMENTS" => $com,
            "EMAIL" => array(array("VALUE" => $order_info["email"], "VALUE_TYPE" => "WORK" )),
            "UF_CRM_1548768852595" => $order_info["payment_city"],
            "SOURCE_ID" => 1,
            "ASSIGNED_BY_ID" => 17,
        )
    );
    $leadAddResult = requestToCRM($data1,'crm.lead.add');
}


----------------------------------------------------------------------------------------------------------------------------------------------------------------------

// www/system/library/cart.php http://chameleon.net.ua/  opencart
//правив ключ який складався з Array   [1562:YToyOntpOjExMzU7czo0OiI0NzUzIjtpOjEwMzY7czo0OiI0Mjg5Ijt9] => Array

$this->data[$product[0]] = array(

///www/catalog/controller/checkout/success.php


    $this->response->setOutput($this->render());


				if($products[0]["opt"][$products[0]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok = "Подарок";
                }else{
                    $podarok = "Нет подарка";
                }
				if($products[0]["opt"][$products[0]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer = $products[0]["opt"][$products[0]["product_id"]]["option"][0]['option_value'];
                }elseif($products[0]["opt"][$products[0]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer = $products[0]["opt"][$products[0]["product_id"]]["option"][1]['option_value'];
                }
				if($products[1]["opt"][$products[1]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok1 = "Подарок";
                }else{
                    $podarok1 = "Нет подарка";
                }
				if($products[1]["opt"][$products[1]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer1 = $products[1]["opt"][$products[1]["product_id"]]["option"][0]['option_value'];
                }elseif($products[1]["opt"][$products[1]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer1 = $products[1]["opt"][$products[1]["product_id"]]["option"][1]['option_value'];
                }
				if($products[2]["opt"][$products[2]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok2 = "Подарок";
                }else{
                    $podarok2 = "Нет подарка";
                }
				if($products[2]["opt"][$products[2]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer2 = $products[2]["opt"][$products[2]["product_id"]]["option"][0]['option_value'];
                }elseif($products[2]["opt"][$products[2]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer2 = $products[2]["opt"][$products[2]["product_id"]]["option"][1]['option_value'];
                }
				if($products[3]["opt"][$products[3]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok3 = "Подарок";
                }else{
                    $podarok3 = "Нет подарка";
                }
				if($products[3]["opt"][$products[3]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer3 = $products[3]["opt"][$products[3]["product_id"]]["option"][0]['option_value'];
                }elseif($products[3]["opt"][$products[3]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer3 = $products[3]["opt"][$products[3]["product_id"]]["option"][1]['option_value'];
                }
				if($products[4]["opt"][$products[4]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok4 = "Подарок";
                }else{
                    $podarok4 = "Нет подарка";
                }
				if($products[4]["opt"][$products[4]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer4 = $products[4]["opt"][$products[4]["product_id"]]["option"][0]['option_value'];
                }elseif($products[4]["opt"][$products[4]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer4 = $products[4]["opt"][$products[4]["product_id"]]["option"][1]['option_value'];
                }
				if($products[5]["opt"][$products[5]["product_id"]]["option"][0]['name']=="Подарок"){
                    $podarok5 = "Подарок";
                }else{
                    $podarok5 = "Нет подарка";
                }
				if($products[5]["opt"][$products[5]["product_id"]]["option"][0]['name']=="Размер"){
                    $razmer5 = $products[5]["opt"][$products[5]["product_id"]]["option"][0]['option_value'];
                }elseif($products[5]["opt"][$products[5]["product_id"]]["option"][1]['name']=="Размер"){
                    $razmer5 = $products[5]["opt"][$products[5]["product_id"]]["option"][1]['option_value'];
                }


				if(isset($order_id)){

                    if(isset($products[0]["nam"])){
                        $w1 = $products[0]["nam"];
                        $f1 = ', Кол.'.' - '.$products[0]["quantity"].', Цена.'.' - '.round($products[0]["price"]). " грн.".'; Подарок'.' - '.$podarok.'; Размер'.' - '.$razmer;
                    }
                    if(isset($products[1]["nam"])){
                        $w2 = $products[1]["nam"];
                        $f2 = ', Кол.'.' - '.$products[1]["quantity"].', Цена.'.' - '.round($products[1]["price"]). " грн.".'; Подарок'.' - '.$podarok1.'; Размер'.' - '.$razmer1;
                    }else{
                        $w2="";
                        $f2="";
                    }
                    if(isset($products[2]["nam"])){
                        $w3 = $products[2]["nam"];
                        $f3 = ', Кол.'.' - '.$products[2]["quantity"].', Цена.'.' - '.round($products[2]["price"]). " грн.".'; Подарок'.' - '.$podarok2.'; Размер'.' - '.$razmer2;
                    }else{
                        $w3="";
                        $f3="";
                    }
                    if(isset($products[3]["nam"])){
                        $w4 = $products[3]["nam"];
                        $f4 = ', Кол.'.' - '.$products[3]["quantity"].', Цена.'.' - '.round($products[3]["price"]). " грн.".'; Подарок'.' - '.$podarok3.'; Размер'.' - '.$razmer3;
                    }else{
                        $w4="";
                        $f4="";
                    }
                    if(isset($products[4]["nam"])){
                        $w5 = $products[4]["nam"];
                        $f5 = ', Кол.'.' - '.$products[4]["quantity"].', Цена.'.' - '.round($products[4]["price"]). " грн.".'; Подарок'.' - '.$podarok4.'; Размер'.' - '.$razmer4;
                    }else{
                        $w5="";
                        $f5="";
                    }
                    if(isset($products[5]["nam"])){
                        $w6 = $products[5]["nam"];
                        $f6 = ', Кол.'.' - '.$products[5]["quantity"].', Цена.'.' - '.round($products[5]["price"]). " грн.".'; Подарок'.' - '.$podarok5.'; Размер'.' - '.$razmer5;
                    }else{
                        $w6="";
                        $f6="";
                    }
                    if(isset($order_info["total"])){
                        $tot = round($order_info["total"]).' грн.';
                    }
                    $com = '';

                    if($w6!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "2 - ".$w2.". ".$f2. "<br \>".
                            "3 - ".$w3.". ".$f3. "<br \>".
                            "4 - ".$w4.". ".$f4. "<br \>".
                            "5 - ".$w5.". ".$f5. "<br \>".
                            "6 - ".$w6.". ".$f6. "<br \>".
                            "Сумма - ".$tot;
                    }elseif($w5!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "2 - ".$w2.". ".$f2. "<br \>".
                            "3 - ".$w3.". ".$f3. "<br \>".
                            "4 - ".$w4.". ".$f4. "<br \>".
                            "5 - ".$w5.". ".$f5. "<br \>".
                            "Сумма - ".$tot;
                    }
                    elseif($w4!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "2 - ".$w2.". ".$f2. "<br \>".
                            "3 - ".$w3.". ".$f3. "<br \>".
                            "4 - ".$w4.". ".$f4. "<br \>".
                            "Сумма - ".$tot;
                    }
                    elseif($w3!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "2 - ".$w2.". ".$f2. "<br \>".
                            "3 - ".$w3.". ".$f3. "<br \>".
                            "Сумма - ".$tot;
                    }
                    elseif($w2!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "2 - ".$w2.". ".$f2. "<br \>".
                            "Сумма - ".$tot;
                    }
                    elseif($w1!=""){
                        $com =  "1 - ".$w1.". ".$f1. "<br \>".
                            "Сумма - ".$tot;
                    }


                }

				$dost = $order_info["shipping_method"];
				$payment = $order_info["payment_method"];

				if($order_info["payment_company"]!=""){
                    $payment_company = $order_info["payment_company"];
                }else{
                    $payment_company = "не заполнено";
                }

				if($order_info["payment_address_2"]!=""){
                    $payment_address_2 = $order_info["payment_address_2"];
                }else{
                    $payment_address_2 = "не заполнено";
                }




				$com = $com. "<br \>".
                    "Способ доставки - ".$dost. "<br \>".
                    "Способ оплаты - ".$payment. "<br \>".
                    "Адрес  - ".$order_info["payment_address_1"]. "<br \>".
                    "Комментарий - ".$order_info["comment"]. "<br \>".
                    "Название организации - ".$payment_company. "<br \>".
                    "№ отделения Новая почта - ".$payment_address_2. "<br \>".
                    "Название ФОП - ".$this->session->data['simple']['checkout_customer']['custom_fop']. "<br \>".
                    "Номер заказа: - ".$order_info["order_id"];



// echo"<pre>";
// print_r($com);
// echo"</pre>";
// echo"<pre>";
// print_r($products);
// echo"</pre>";
// die();


	// echo"<pre>";
// print_r($com);
// echo"</pre>";

// echo"<pre>";
// print_r($order_info);
// echo"</pre>";
	// echo"<pre>";
// print_r($this->session->data['simple']['checkout_customer']['custom_fop']);
// echo"</pre>";
// die();



	function requestToCRM($data1, $method){
        $queryUrl = 'https://alex.bitrix24.ua/rest/11/cpf0dyhiqb5z5uiq/'.$method.".json";

        $result = array();

        $queryData = http_build_query($data1);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, 1);

        if (isset($response['error'])) {
            $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
        }else{
        }

        return $response;
    }
  if(stristr($order_info["email"], 'localhost') === FALSE) {
  }else{
      $order_info["email"] = "";
  }
if(isset($order_id)){
    $data1 = array(
        'fields' => array(
            "TITLE" => "Заявка з форми 'Оформление заказа' на сайті http://chameleon.net.ua",
            "NAME" => $order_info['firstname'],
            "LAST_NAME" => $order_info['lastname'],
            "PHONE" => array(array("VALUE" => $order_info['telephone'], "VALUE_TYPE" => "WORK")),
            "COMMENTS" => $com,
            "EMAIL" => array(array("VALUE" => $order_info["email"], "VALUE_TYPE" => "WORK" )),
            "UF_CRM_1548768852595" => $order_info["payment_city"],
            "SOURCE_ID" => 2,
            "ASSIGNED_BY_ID" => 23,
            'UTM_SOURCE'   => !empty($_COOKIE['UTM_SOURCE']) ? $_COOKIE['UTM_SOURCE'] : '',
            'UTM_MEDIUM'   => !empty($_COOKIE['UTM_MEDIUM']) ? $_COOKIE['UTM_MEDIUM'] : '',
            'UTM_TERM'     => !empty($_COOKIE['UTM_TERM']) ? $_COOKIE['UTM_TERM'] : '',
            'UTM_CONTENT'  => !empty($_COOKIE['UTM_CONTENT']) ? $_COOKIE['UTM_CONTENT'] : '',
            'UTM_CAMPAIGN' => !empty($_COOKIE['UTM_CAMPAIGN']) ? $_COOKIE['UTM_SOURCE'] : '',
        )
    );
    $leadAddResult = requestToCRM($data1,'crm.lead.add');
}



  	}
---------------------------------------------------------------------------------------------------------------------------------------------------------------
//заказать звонок opencart http://chameleon.net.ua/
///www/catalog/controller/module/callme.php
	if (isset($this->request->post['tel'])) {

function requestToCRM($data1, $method){
    $queryUrl = 'https://alex.bitrix24.ua/rest/11/cpf0dyhiqb5z5uiq/'.$method.".json";

    $result = array();

    $queryData = http_build_query($data1);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, 1);

    if (isset($response['error'])) {
        $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
    }else{
    }

    return $response;
}

						$data1 = array(
                            'fields' => array(
                                "TITLE" => "Заявка з форми 'Заказать обратный звонок' на сайті http://chameleon.net.ua",
                                "NAME" => $this->request->post['name'],
                                "PHONE" => array(array("VALUE" => $this->request->post['tel'], "VALUE_TYPE" => "WORK")),
                                "COMMENTS" => $this->request->post['enquiry'],
                                "SOURCE_ID" => 2,
                                "ASSIGNED_BY_ID" => 23,
                                'UTM_SOURCE'   => !empty($_COOKIE['UTM_SOURCE']) ? $_COOKIE['UTM_SOURCE'] : '',
                                'UTM_MEDIUM'   => !empty($_COOKIE['UTM_MEDIUM']) ? $_COOKIE['UTM_MEDIUM'] : '',
                                'UTM_TERM'     => !empty($_COOKIE['UTM_TERM']) ? $_COOKIE['UTM_TERM'] : '',
                                'UTM_CONTENT'  => !empty($_COOKIE['UTM_CONTENT']) ? $_COOKIE['UTM_CONTENT'] : '',
                                'UTM_CAMPAIGN' => !empty($_COOKIE['UTM_CAMPAIGN']) ? $_COOKIE['UTM_SOURCE'] : '',
                            )
                        );
						$leadAddResult = requestToCRM($data1,'crm.lead.add');

				}
---------------------------------------------------------------------------------------------------------------------------------------------------
//wordpress  https://growthcapital.com.ua/
///wp-content/themes/enfold/framework/php/class-form-generator.php


			function requestToCRM($data, $method){
$queryUrl = 'https://growthcapital.bitrix24.ua/rest/1/7w6ysr8bc6qzf4e0/'.$method.".json";

			$result = array();

			$queryData = http_build_query($data);

			$curl = curl_init();
			curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_POST => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $queryUrl,
                CURLOPT_POSTFIELDS => $queryData,
            ));

			$response = curl_exec($curl);
			curl_close($curl);

			$response = json_decode($response, 1);

			if (isset($response['error'])) {
                $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
            }else{
            }

			return $response;
		}
		// присваивает <body text='black'>
		//$maill = str_replace("%40", "@", $_POST['avia_2_1']);

		if($_POST['avia_generated_form1'] == 1){
            $data = array(
                'fields' => array(
                    "TITLE" => "Заявка з форми на странице 'Контакты' на сайті https://growthcapital.com.ua",
                    "NAME" => urldecode($_POST['avia_1_1']),
                    "COMMENTS" => urldecode($_POST['avia_3_1']),
                    "EMAIL" => array(array("VALUE" => urldecode($_POST['avia_2_1']), "VALUE_TYPE" => "WORK" )),
                    "SOURCE_ID" => 2,
                    "ASSIGNED_BY_ID" => 9,
                )
            );
            $leadAddResult = requestToCRM($data,'crm.lead.add');
        }




//laravel https://www.tulikivi.com.ua/ua
// /tulikivi.com.ua/app/Http/Controllers/messegecontroller.php
		//auspex code begin
			function requestToCRM($data1, $method){
                $queryUrl = 'https://www.intercom24.in.ua/rest/1/1bi1gm0xopn0oz9s/'.$method.".json";

                $result = array();

                $queryData = http_build_query($data1);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_POST => 1,
                    CURLOPT_HEADER => 0,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $queryUrl,
                    CURLOPT_POSTFIELDS => $queryData,
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response, 1);

                if (isset($response['error'])) {
                    $text = "\ndate: ".date('d.m.Y h:i:s A')."\nmethod: $method";
                }else{
                }

                return $response;
            }
			if(isset($messege->name)){
                $seonew = json_decode($messege->seo, 1);
                //dd($seonew);
                $data1 = array(
                    'fields' => array(
                        "TITLE" => "Туликиви.".$messege->theme,
                        "NAME" => $messege->name,
                        "PHONE" => array(array("VALUE" => $messege->phone, "VALUE_TYPE" => "WORK")),
                        "COMMENTS" => $messege->message,
                        "EMAIL" => array(array("VALUE" => $messege->email, "VALUE_TYPE" => "WORK" )),
                        "UF_CRM_1539784235439" => $messege->url,
                        "UF_CRM_1521106764553" => $messege->product_image,
                        "UF_CRM_1556888599611" => $messege->product_name,
                        "UF_CRM_1516309836652" => $messege->city,
                        "UF_CRM_1556887593161" => $messege->time,
                        "SOURCE_ID" => "WEB",
                        "ASSIGNED_BY_ID" => 1,
                        'UTM_SOURCE'   => !empty($seonew["second_way"]['utm_source']) ? $seonew["second_way"]['utm_source'] : '',
                        'UTM_MEDIUM'   => !empty($seonew["second_way"]['utm_medium']) ? $seonew["second_way"]['utm_medium'] : '',
                        'UTM_TERM'     => !empty($seonew["second_way"]['utm_term']) ? $seonew["second_way"]['utm_term'] : '',
                        'UTM_CONTENT'  => !empty($seonew["second_way"]['utm_content']) ? $seonew["second_way"]['utm_content'] : '',
                        'UTM_CAMPAIGN' => !empty($seonew["second_way"]['utm_campaign']) ? $seonew["second_way"]['utm_campaign'] : '',
                    )
                );
                $leadAddResult = requestToCRM($data1,'crm.lead.add');
            }
			//auspex code end


Fusion-style.ua
C:\Users\Roman\AppData\Local\Temp\fz3temp-2\OrdersMailer.php
/fusion-style.ua/www/application/modules/shop/models/Services

*********************************************************************************************************
//intelex cms https://www.osvitapol.info/
//добавити віджет на сайт
<aa312a5b-6150-4ed4-abcd-96659ceabd07>\osvitapol.info\template\index.tpl.php

//Перехопити форму
<aa312a5b-6150-4ed4-abcd-96659ceabd07>\osvitapol.info\frm.php
            if ( !empty( $_POST ) ) {

                function requestToCRM($data, $method)
                {
                    $queryUrl = 'https://polandoppl.net/rest/3/k7jx0o0vcewvq9r7/' . $method . ".json";

                    $result = array();

                    $queryData = http_build_query($data);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_POST => 1,
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => $queryUrl,
                        CURLOPT_POSTFIELDS => $queryData,
                    ));

                    $response = curl_exec($curl);
                    curl_close($curl);

                    $response = json_decode($response, 1);

                    if (isset($response['error'])) {
                        $text = "\ndate: " . date('d.m.Y h:i:s A') . "\nmethod: $method";
                    } else {
                    }

                    return $response;
                }

                if ($_POST["name"] != "") {
                    $data = array(
                        'fields' => array(
                            "TITLE" => "Заявка з сайта https://www.osvitapol.info/",
                            "NAME" => $_POST["name"],
                            "COMMENTS" => $_POST["quest"],
                            "SOURCE_ID" => 1,
                            "ADDRESS_CITY" => $_POST["city"],
                            "EMAIL" => array(array("VALUE" => $_POST["email"], "VALUE_TYPE" => "WORK")),
                            "PHONE" => array(array("VALUE" => $_POST["phone"], "VALUE_TYPE" => "WORK")),
                            "ASSIGNED_BY_ID" => 11,
                            "OBSERVERS" => 12,
                        )
                    );
                    $leadAddResult = requestToCRM($data, 'crm.lead.add');
                }

            }










<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

// Исправляем баг с json_encode
ini_set('serialize_precision', 10);

/*  Atol Online API v4 */

class KovSpace_Atol
{
    public $apiUrl = 'https://online.atol.ru/possystem/v4';
    public $apiUrlTest = 'https://testonline.atol.ru/possystem/v4';
    public $login;
    public $pass;
    public $group;
    public $token;
    public $response;

    public function __construct($login, $pass, $group, $isTest = false)
    {
        $this->login = $login;
        $this->pass = $pass;
        $this->group = $group;
        if ($isTest) {
            $this->apiUrl = $this->apiUrlTest;
        }
        $this->getToken();
    }

    public function post($url, $fields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:  application/json; charset=utf-8',
            'Token: ' . $this->token,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->response = json_decode(curl_exec($ch));
    }

    public function getToken()
    {
        $url = $this->apiUrl . '/getToken';
        $fields['login'] = $this->login;
        $fields['pass'] = $this->pass;
        $this->post($url, $fields);
        if (isset($this->response->token)) {
            $this->token = $this->response->token;
        }
    }

    public function sell($fields)
    {
        $url = $this->apiUrl . '/' . $this->group . '/sell';
        $this->post($url, $fields);
    }

    public function makeReceipt($orderId, $companyEmail, $compnanySno, $compnanyInn, $companyPaymentAddress, $cashier, $roundPrice = false, $expandModificationName = false, $externalId = NULL)
    {
        $total = 0;
        $aItems = [];
        $aVats = [];

        $oShop_Order = Core_Entity::factory('Shop_Order', $orderId);

        // Проверяем на наличие заказа
        if (!$oShop_Order->shop_id) {
            die('Такого заказа не существует!');
        }

        $oShop_Orders_Items = $oShop_Order->Shop_Order_Items;
        $aShop_Orders_Items = $oShop_Orders_Items->findAll();
        foreach ($aShop_Orders_Items as $oShop_Order_Item) {

            $price = $roundPrice
                ? round($oShop_Order_Item->price)
                : (float) $oShop_Order_Item->price;

            $total += $price * $oShop_Order_Item->quantity;

            $name = $oShop_Order_Item->name;
            if ($expandModificationName) {
                $oShop_Item = Core_Entity::factory('Shop_Item', $oShop_Order_Item->shop_item_id);
                if ($oShop_Item->modification_id) {
                    $name = $oShop_Item->Modification->name . ' :: ' . $oShop_Item->name;
                }
            }

            $aItem['name'] = $name;
            $aItem['price'] = $price;
            $aItem['quantity'] = (int) $oShop_Order_Item->quantity;
            $aItem['sum'] = $price * $oShop_Order_Item->quantity;
            $aItem['payment_method'] = 'full_payment';
            $aItem['payment_object'] = $oShop_Order_Item->name == 'Доставка' ? 'service' : 'commodity';

            $rate = $oShop_Order_Item->rate == 0 ? 20 : $oShop_Order_Item->rate;
            $vatType = 'vat' . $rate;

            if (!isset($aVats[$rate])) {
                $aVats[$rate]['type'] = $vatType;
                $aVats[$rate]['sum'] = round($price * $rate / 100, 2);
            } else {
                $aVats[$rate]['sum'] += round($price * $rate / 100, 2);
            }

            $aItem['vat']['type'] = $vatType;
            $aItems[] = $aItem;
        }

        $aVats = array_values($aVats); // сбрасываем ключи массива

        $fields['external_id'] = $externalId ? $externalId : $oShop_Order->id;
        $fields['receipt']['client']['email'] = $oShop_Order->email;
        $fields['receipt']['company']['email'] = $companyEmail;
        $fields['receipt']['company']['sno'] = $compnanySno;
        $fields['receipt']['company']['inn'] = $compnanyInn;
        $fields['receipt']['company']['payment_address'] = $companyPaymentAddress;
        $fields['receipt']['items'] = $aItems;
        $fields['receipt']['payments'][0]['type'] = 2; // предварительная оплата (зачет аванса и предыдущих платежей)
        $fields['receipt']['payments'][0]['sum'] = $total;
        $fields['receipt']['vats'] = $aVats;
        $fields['receipt']['total'] = $total;
        $fields['receipt']['cashier'] = $cashier;
        $fields['timestamp'] = date('d.m.Y H:i:s');

        return $fields;
    }
}

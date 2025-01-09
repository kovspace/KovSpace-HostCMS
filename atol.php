<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

// Исправляем баг с json_encode
ini_set('serialize_precision', 10);

/**
 * Atol Online API v4
 * @link https://atol.online/upload/iblock/dff/4yjidqijkha10vmw9ee1jjqzgr05q8jy/API_atol_online_v4.pdf
 */
class KovSpace_Atol
{
    public string $apiUrl = 'https://online.atol.ru/possystem/v4';
    public string $apiUrlTest = 'https://testonline.atol.ru/possystem/v4';
    public string $login;
    public string $pass;
    public string $group;
    public ?string $token = null;
    public object $response;

    public function __construct(string $login, string $pass, string $group, bool $isTest = false)
    {
        $this->login = $login;
        $this->pass = $pass;
        $this->group = $group;
        if ($isTest) {
            $this->apiUrl = $this->apiUrlTest;
        }
        $this->getToken();
    }

    public function post($url, $fields): void
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

    public function getToken(): void
    {
        $url = $this->apiUrl . '/getToken';
        $fields['login'] = $this->login;
        $fields['pass'] = $this->pass;
        $this->post($url, $fields);
        if (isset($this->response->token)) {
            $this->token = $this->response->token;
        }
    }

    public function sell($fields): void
    {
        $url = $this->apiUrl . '/' . $this->group . '/sell';
        $this->post($url, $fields);
    }

    public function makeReceipt(
        int     $checkType,
        int     $orderId,
        string  $companyEmail,
        string  $compnanySno,
        string  $compnanyInn,
        string  $companyPaymentAddress,
        ?string $cashier = null,
        bool    $roundPrice = false,
        bool    $expandModificationName = false,
        ?string $externalId = null,
    )
    {
        $total = 0;
        $aItems = [];
        $aVats = [];

        $oShop_Order = Core_Entity::factory('Shop_Order', $orderId);

        // Проверяем на наличие заказа
        if (!$oShop_Order->shop_id) {
            die('Такого заказа не существует!');
        }

        $config = [];
        $configFile = CMS_FOLDER . 'config/atol.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        }
        $configRate = $config['rate'] ?? null;

        $oShop_Orders_Items = $oShop_Order->Shop_Order_Items;
        $aShop_Orders_Items = $oShop_Orders_Items->findAll();
        foreach ($aShop_Orders_Items as $oShop_Order_Item) {
            $aItem = [];

            $price = $roundPrice
                ? round($oShop_Order_Item->price)
                : (float)$oShop_Order_Item->price;

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
            $aItem['quantity'] = (int)$oShop_Order_Item->quantity;
            $aItem['sum'] = $price * $oShop_Order_Item->quantity;
            $aItem['payment_method'] = $checkType == 1 ? 'full_prepayment' : 'full_payment';
            $aItem['payment_object'] = $oShop_Order_Item->name == 'Доставка' ? 'service' : 'commodity';

            $rate = $oShop_Order_Item->rate ?? $configRate;
            $vatType = is_null($rate) ? 'none' : 'vat' . $rate;
            $vatKey = $rate ?? 'none';

            if (!isset($aVats[$vatKey])) {
                $aVats[$vatKey]['type'] = $vatType;
                $aVats[$vatKey]['sum'] = round($price * $rate / 100, 2);
            } else {
                $aVats[$vatKey]['sum'] += round($price * $rate / 100, 2);
            }

            $aItem['vat']['type'] = $vatType;

            // Маркировка
            $oShop_Order_Item_Code = Core_Entity::factory('Shop_Order_Item_Code')->getByShop_Order_Item_Id($oShop_Order_Item->id);
            if ($oShop_Order_Item_Code) {
                $decoder = new KovSpace_MarkingDecoder($oShop_Order_Item_Code->code);
                if (!$decoder->error) {
                    $aItem['nomenclature_code'] = $decoder->productCode;
                }
            }

            $aItems[] = $aItem;
        }

        $aVats = array_values($aVats); // сбрасываем ключи массива

        $fields['external_id'] = $externalId ?: $oShop_Order->id . '-' . $checkType;
        $fields['receipt']['client']['email'] = $oShop_Order->email;
        $fields['receipt']['company']['email'] = $companyEmail;
        $fields['receipt']['company']['sno'] = $compnanySno;
        $fields['receipt']['company']['inn'] = $compnanyInn;
        $fields['receipt']['company']['payment_address'] = $companyPaymentAddress;
        $fields['receipt']['items'] = $aItems;
        $fields['receipt']['payments'][0]['type'] = $checkType; // безналичный или предварительная оплата (зачет аванса и предыдущих платежей)
        $fields['receipt']['payments'][0]['sum'] = $total;
        $fields['receipt']['vats'] = $aVats;
        $fields['receipt']['total'] = $total;
        $fields['timestamp'] = date('d.m.Y H:i:s');
        if ($cashier) {
            $fields['receipt']['cashier'] = $cashier;
        }

        return $fields;
    }
}

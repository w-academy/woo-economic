<?php
/**
 * Woo Economic main functions for talking to economic
 *
 * @author kristianrasmussen.com
 */

function woo_economic_create_economic_client()
{
    $wsdlUrl = 'https://www.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL';

    $client = new SoapClient($wsdlUrl, array("trace" => 1, "exceptions" => 1));


    $agreement = get_option(WOO_ECONOMIC_OPTION_ECONOMIC_AGREEMENT_NAME);
    // Todo hash that password
    $username = get_option(WOO_ECONOMIC_OPTION_ECONOMIC_USER_NAME);
    $password = get_option(WOO_ECONOMIC_OPTION_ECONOMIC_PASSWD_NAME);
    _log("woo_economic_create_economic_client loaded agreementNumber: " . $agreement . " usernamme: " . $username . " and password: ******");
    if (!$agreement || !$username || !$password)
        die("You need to specify e-conomic agreementnumber, username, and password");
    _log("woo_economic_create_economic_client - options turned out ok!");
    _log("woo_economic_create_economic_client agreement: " . $agreement . " user: " . $username . " password: " . $password);
    $client->Connect(array(
        'agreementNumber' => $agreement,
        'userName' => $username,
        'password' => $password));
    _log("woo_economic_create_economic_client - client created");

    return $client;
}

/**
 * @param $product_id
 * @return product sku added the product_offset if the product haven't originally been synced from e-conomic
 */
function woo_economic_get_product_id(WC_Product $product)
{
    $synced_from_economic = get_post_meta($product->id, WOO_ECONOMIC_SYNCED_FROM_ECONOMIC_NAME, true);
    $product_sku = null;
    if (isset($synced_from_economic) && $synced_from_economic) {
        $product_sku = $product->sku;
    } else {
        $product_offset = get_option(WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME);
        $product_sku = $product_offset + $product->sku;
    }
    return $product_sku;
}

/**
 * @param $product_id
 * @return product sku - it subtracts offset if product number is greater than offset
 */
function woo_economic_get_product_id_from_economic($product_id)
{
    $product_offset = get_option(WOO_ECONOMIC_OPTION_PRODUCT_OFFSET_NAME);
    if ($product_id < $product_offset) // this is an economic product - don't subtract offset
        return $product_id;
    else
        return $product_id - $product_offset;
}


$woo_economic_product_lock = false;

function woo_economic_save_product_to_economic(WC_Product $product)
{

    _log("woo_economic_save_product_to_economic creating client");
    try {
      $client = woo_economic_create_economic_client();
    } catch (Exception $exception) {
        _log("woo_economic_save_product_to_economic could not connect to e-economic " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not connect to e-conomic</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
        return;
    }

    _log("woo_economic_save_product_to_economic client ready? - sku: " . $product->sku . " title: " . $product->get_title() . " desc: " . $product->post->post_content);
    try
    {

        $product_sku = woo_economic_get_product_id($product);
        _log("woo_economic_save_product_to_economic - trying to find product in economic");

        /// Find product by number
        $product_handle = $client->Product_FindByNumber(array(
            'number' => $product_sku))->Product_FindByNumberResult;


        //debug_client($client);
        /// Product doesn't exist - create product with name
        if (!$product_handle) {
            $productGroupHandle = $client->ProductGroup_FindByNumber(array(
                'number' => get_option(WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME)))->ProductGroup_FindByNumberResult;
            $product_handle = $client->Product_Create(array(
                'number' => $product_sku,
                'productGroupHandle' => $productGroupHandle,
                'name' => $product->get_title()))->Product_CreateResult;
            _log("woo_economic_save_product_to_economic - product create:" . $product->get_title());
        }

        /// Product -> GetData
        $product_data = $client->Product_GetData(array(
            'entityHandle' => $product_handle))->Product_GetDataResult;

        /// Product -> updateFromData with details
        $client->Product_UpdateFromData(array(
            'data' => (object)array(
                'Handle' => $product_data->Handle,
                'Number' => $product_data->Number,
                'ProductGroupHandle' => $product_data->ProductGroupHandle,
                'Name' => $product->get_title(),
                'Description' => $product->post->post_content,
                'BarCode' => "",
                'SalesPrice' => (isset($product->price) && !empty($product->price) ? $product->price : 0.0),
                'CostPrice' => (isset($product_data->CostPrice) ? $product_data->CostPrice : 0.0),
                'RecommendedPrice' => $product_data->RecommendedPrice,
                'UnitHandle' => (object)array(
                    'Number' => 1
                ),
                'IsAccessible' => true,
                'Volume' => $product_data->Volume,
                'DepartmentHandle' => $product_data->DepartmentHandle,
                'DistributionKeyHandle' => $product_data->DistrubutionKeyHandle,
                'InStock' => $product_data->InStock,
                'OnOrder' => $product_data->OnOrder,
                'Ordered' => $product_data->Ordered,
                'Available' => $product_data->Available)))->Product_UpdateFromDataResult;
        _log("woo_economic_save_product_to_economic - Updated product: " . $product->get_title());

        $client->Disconnect();
    }
    catch (Exception $exception)
    {
        _log("woo_economic_save_product_to_economic could not create product: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not create product: ". $product->get_title(). "</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }

}


function debug_client($client)
{
  if (is_null($client)) {
    _log("Client is null");
  } else {
    _log("------------");
    _log($client->__getLastRequestHeaders());
    _log("------------");
    _log($client->__getLastRequest());
    _log("------------");
  }
}


$woo_economic_user_fields = array(
    'billing_phone',
    'billing_email',
    'billing_country',
    'billing_address_1',
    'billing_address_2',
    'billing_state',
    'billing_postcode',
    'billing_city',
    'billing_company',
    'billing_last_name',
    'billing_first_name',
    'vat_number',

    'shipping_phone',
    'shipping_email',
    'shipping_country',
    'shipping_address_1',
    'shipping_address_2',
    'shipping_state',
    'shipping_postcode',
    'shipping_city',
    'shipping_company',
    'shipping_last_name',
    'shipping_first_name'
);

function woo_economic_save_customer_to_economic(WP_User $user)
{
    global $woo_economic_user_fields;
    _log("woo_economic_save_customer_to_economic creating client");
    $client = woo_economic_create_economic_client();

    try {
        $debtorHandle = woo_economic_get_user_debtor_handle($user, $client);
        if (!isset($debtorHandle)) { // The debtor doesn't exist - lets create it

            $debtor_grouphandle_meta = get_option(WOO_ECONOMIC_OPTION_CUSTOMER_GROUP_NAME);
            _log("woo_economic_save_customer_to_economic debtor group: " . $debtor_grouphandle_meta);
            _log("woo_economic_save_customer_to_economic name: " . $user->get('first_name') . " " . $user->get('last_name'));
            _log("woo_economic_save_customer_to_economic billing_comnpany: " . $user->get('billing_company'));


            $debtor_grouphandle = $client->DebtorGroup_FindByNumber(array(
                'number' => $debtor_grouphandle_meta
            ))->DebtorGroup_FindByNumberResult;
            $debtorHandle = $client->Debtor_Create(array(
                'nubmer' => woo_economic_get_customer_id($user),
                'debtorGroupHandle' => $debtor_grouphandle,
                'name' => $user->get('billing_company'),
                'vatZone' => 'HomeCountry' // todo remember to make switch over eu countries, your own and international.
            ))->Debtor_CreateResult;
            if (isset($debtorHandle)) {
                _log("woo_economic_save_customer_to_economic handle returned: " . $debtorHandle->Number);
                update_user_meta($user->ID, WOO_ECONOMIC_OPTION_ECONOMIC_DEBTOR_NAME, $debtorHandle->Number);
            }

            foreach ($woo_economic_user_fields as $meta_key) {
                woo_economic_save_customer_meta_data($user, $meta_key, $user->get($meta_key));
            }
            // todo the rest of the debtor should be saved as individual meta fields, since that is how update user will take place
        }
    } catch (Exception $exception) {
        _log("woo_economic_save_customer_to_economic could not create debtor: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not create debtor.</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }
}

function woo_economic_get_user_debtor_handle(WP_User $user, SoapClient &$client)
{
    $debtorNumber = $user->get(WOO_ECONOMIC_OPTION_ECONOMIC_DEBTOR_NAME);
    _log("woo_economic_get_user_debtor_handle trying to load " . $debtorNumber);
    if (!isset($debtorNumber) || empty($debtorNumber)) {
        _log("woo_economic_get_user_debtor_handle no handle found");
        return null;
    }

    $debtor_handle = $client->Debtor_FindByNumber(array(
        'number' => $debtorNumber
    ))->Debtor_FindByNumberResult;

    if (isset($debtor_handle))
        _log("woo_economic_get_user_debtor_handle debtor found for user->id " . $user->ID);
    else {
        _log("woo_economic_get_user_debtor_handle debtor not found");
        return null;
    }

    return $debtor_handle;
}

function woo_economic_get_customer_id(WP_User $user)
{
    $customer_offset = get_option(WOO_ECONOMIC_OPTION_CUSTOMER_OFFSET_NAME);
    $result = $customer_offset + $user->ID;
    _log("woo_economic_get_customer_id id: " . $result);
    return $result;
}

function woo_economic_save_customer_meta_data(WP_User $user, $meta_key, $meta_value)
{
    _log("woo_economic_save_customer_meta_data creating client");
    $client = woo_economic_create_economic_client();
    $debtor_handle = woo_economic_get_user_debtor_handle($user, $client);
    if (!isset($debtor_handle)) {
        _log("woo_economic_save_customer_meta_data debtor not found, can not update meta");
        return;
    }
    try {

        if ($meta_key == 'billing_phone') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $client->Debtor_SetTelephoneAndFaxNumber(array(
                'debtorHandle' => $debtor_handle,
                'value' => $meta_value
            ));
        }

        if ($meta_key == 'billing_email') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $client->Debtor_SetEmail(array(
                'debtorHandle' => $debtor_handle,
                'value' => $meta_value
            ));

        }
        if ($meta_key == 'billing_country') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $countries = new WC_Countries();
            $country = $countries->countries[$meta_value];
            _log("woo_economic_save_customer_meta_data country: " . $country);
            $client->Debtor_SetCountry(array(
                'debtorHandle' => $debtor_handle,
                'value' => $country
            ));
        }
        if ($meta_key == 'billing_address_1' || $meta_key == 'billing_address_2' || $meta_key == 'billing_state') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $adr1 = ($meta_key == 'billing_address_1') ? $meta_value : $user->get('billing_address_1');
            $adr2 = ($meta_key == 'billing_address_2') ? $meta_value : $user->get('billing_address_2');
            $state = ($meta_key == 'billing_state') ? $meta_value : $user->get('billing_state');
            $billing_country = $user->get('billing_country');
            $countries = new WC_Countries();

            $formatted_state = (isset($state)) ? $countries->states[$billing_country][$state] : "";
            $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
            _log("woo_economic_save_customer_meta_data adr1: " . $adr1 . " adr2: " . $adr2 . " state " . $formatted_state);
            _log("woo_economic_save_customer_meta_data formatted_adr: " . $formatted_adr);
            $client->Debtor_SetAddress(array(
                'debtorHandle' => $debtor_handle,
                'value' => $formatted_adr
            ));

        }

        if ($meta_key == 'billing_postcode') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $client->Debtor_SetPostalCode(array(
                'debtorHandle' => $debtor_handle,
                'value' => $meta_value
            ));

        }
        if ($meta_key == 'billing_city') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $client->Debtor_SetCity(array(
                'debtorHandle' => $debtor_handle,
                'value' => $meta_value
            ));

        }
        if ($meta_key == 'billing_company') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $client->Debtor_SetName(array(
                'debtorHandle' => $debtor_handle,
                'value' => $meta_value
            ));

        }

        if ($meta_key == 'billing_first_name' || $meta_key == 'billing_last_name') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $first = ($meta_key == 'billing_first_name') ? $meta_value : $user->get('billing_first_name');
            $last = ($meta_key == 'billing_last_name') ? $meta_value : $user->get('billing_last_name');
            $name = $first . " " . $last;
            $debtor_contact_handle = $client->DebtorContact_Create(array(
                'debtorHandle' => $debtor_handle,
                'name' => $name))->DebtorContact_CreateResult;
            $client->Debtor_SetAttention(array(
                'debtorHandle' => $debtor_handle,
                'valueHandle' => $debtor_contact_handle
            ));
        }

        /*
        *
        *  Time to add delivery address to the debtor - we need to make a function to compare delivery addresses
        *
        *
        */


        if ($meta_key == 'shipping_address_1' || $meta_key == 'shipping_address_2' || $meta_key == 'shipping_state') {
            _log("woo_economic_save_customer_meta_data key: " . $meta_key . " value: " . $meta_value);
            $adr1 = ($meta_key == 'shipping_address_1') ? $meta_value : $user->get('shipping_address_1');
            $adr2 = ($meta_key == 'shipping_address_2') ? $meta_value : $user->get('shipping_address_2');
            $state = ($meta_key == 'shipping_state') ? $meta_value : $user->get('shipping_state');
            $shipping_country = $user->get('shipping_country');
            $countries = new WC_Countries();
            $formatted_state = (isset($state)) ? $countries->states[$shipping_country][$state] : "";
            $formatted_adr = trim("$adr1\n$adr2\n$formatted_state");
            _log("woo_economic_save_customer_meta_data adr1: " . $adr1 . " adr2 " . $adr2 . " state " . $formatted_state);
            $client->Debtor_SetAddress(array(
                'debtorHandle' => $debtor_handle,
                'value' => $formatted_adr
            ));
        }

    } catch (Exception $exception) {
        _log("woo_economic_save_customer_meta_data could not update debtor: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not update debtor.</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }


}


function woo_economic_save_invoice_to_economic(WP_User $user, WC_Order $order)
{
    $reference = $order->id;
    woo_economic_save_create_invoice_in_economic($user, $order, $reference, false);
}

function woo_economic_save_create_invoice_in_economic(WP_User $user, WC_Order $order, $reference, $refund)
{
    _log("woo_economic_save_create_invoice_in_economic creating client");
    $client = woo_economic_create_economic_client();
    $debtor_handle = woo_economic_get_user_debtor_handle($user, $client);
    if (!isset($debtor_handle)) {
        die("woo_economic_save_create_invoice_in_economic debtor not found, can not create invoice");
    }
    try {

        $invoice_number = woo_economic_get_invoice_number($client, $reference, $debtor_handle);
        if (!$refund && isset($invoice_number)) {
            _log("woo_economic_save_create_invoice_in_economic invoice already exists");
            return;
        }

        $curent_invoice_handle = woo_economic_get_currentinvoice($client, $reference, $debtor_handle);

        $countries = new WC_Countries();

        $address = null;
        $city = null;
        $postalcode = null;
        $country = null;

        if (isset($order->shipping_address_1) || !empty($order->shipping_address_1)) {
            $formatted_state = $countries->states[$order->shipping_country][$order->shipping_state];
            $address = trim($order->shipping_address_1 . "\n" . $order->shipping_address_2 . "\n" . $formatted_state);
            $city = $order->shipping_city;
            $postalcode = $order->shipping_postcode;
            $country = $countries->countries[$order->shipping_country];
        } else {
            $formatted_state = $countries->states[$order->billing_country][$order->billing_state];
            $address = trim($order->billing_address_1 . "\n" . $order->billing_address_2 . "\n" . $formatted_state);
            $city = $order->billing_city;
            $postalcode = $order->billing_postcode;
            $country = $countries->countries[$order->billing_country];
        }

        $client->CurrentInvoice_SetDeliveryAddress(array(
            'currentInvoiceHandle' => $curent_invoice_handle,
            'value' => $address
        ));
        $client->CurrentInvoice_SetDeliveryCity(array(
            'currentInvoiceHandle' => $curent_invoice_handle,
            'value' => $city
        ));
        $client->CurrentInvoice_SetDeliveryPostalCode(array(
            'currentInvoiceHandle' => $curent_invoice_handle,
            'value' => $postalcode
        ));
        $client->CurrentInvoice_SetDeliveryCountry(array(
            'currentInvoiceHandle' => $curent_invoice_handle,
            'value' => $country
        ));

        woo_economic_handle_orderlines($order, $curent_invoice_handle, $client, $refund);

        /// DONE


    } catch (Exception $exception) {
        _log("woo_economic_save_create_invoice_in_economic could not update debtor: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not create invoice.</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }
}

function woo_economic_get_invoice_number(SoapClient &$client, &$reference, &$debtor_handle)
{
    $handles = $client->Invoice_FindByOtherReference(array(
        'otherReference' => $reference
    ))->Invoice_FindByOtherReferenceResult;

    $invoice_handle = null;
    foreach ($handles as $handle) {
        if (is_object($handle)) {
            $invoice_handle = $handle;
            _log("woo_economic_does_invoice_exist handle is object number: " . $invoice_handle->Number);
        }
        if (is_array($handle)) {
            foreach ($handle as $ihandle) {
                $invoice_handle = $ihandle;
                _log("woo_economic_does_invoice_exist handle is array number: " . $invoice_handle->Number);
                break;
            }
        }

    }

    if (isset($invoice_handle))
        _log("woo_economic_does_invoice_exist invoice " . $invoice_handle->Number . " exists");
    else
        _log("woo_economic_does_invoice_exist doesn't exist for ref. " . $reference);

    return $invoice_handle;
}


function woo_economic_get_currentinvoice(SoapClient &$client, &$reference, &$debtor_handle)
{
    $handles = $client->CurrentInvoice_FindByOtherReference(array(
        'otherReference' => $reference
    ))->CurrentInvoice_FindByOtherReferenceResult;

    $current_invoice_handle = null;
    foreach ($handles as $handle) {
        _log("woo_economic_save_invoice_to_economic handle: " . $handle->Id);
        $current_invoice_handle = $handle;
    }


    if (!isset($current_invoice_handle)) {
        $current_invoice_handle = $client->CurrentInvoice_Create(array(
            'debtorHandle' => $debtor_handle
        ))->CurrentInvoice_CreateResult;
        _log("woo_economic_save_invoice_to_economic current_invoice_handle: " . $current_invoice_handle->Id);
        $client->CurrentInvoice_SetOtherReference(array(
            'currentInvoiceHandle' => $current_invoice_handle,
            'value' => $reference
        ));
    }
    _log("current invoice handle found: " . $current_invoice_handle->Id);
    return $current_invoice_handle;
}

function woo_economic_handle_orderlines(WC_Order $order, $current_invoice_handle, SoapClient &$client, $refund)
{
    _log("woo_economic_handle_orderlines begin " . $current_invoice_handle->Id);
    $shipping_product_id = get_option(WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME);
    $all_current_invoice_line_handles = $client->CurrentInvoice_GetLines(array(
        'currentInvoiceHandle' => $current_invoice_handle
    ))->CurrentInvoice_GetLinesResult;
    _log("woo_economic_handle_orderlines - get all lines");
    $line_handles = array(); // currentinvoiceline_handles
    foreach ($all_current_invoice_line_handles as $handle) {
        if (is_object($handle)) {
            _log("woo_economic_handle_orderlines got line id: " . $handle->Id);
            _log("woo_economic_handle_orderlines got line number: " . $handle->Number);
            array_push($line_handles, $handle);
        }
        if (is_array($handle)) {
            foreach ($handle as $line_handle) {
                _log("woo_economic_handle_orderlines got line id: " . $line_handle->Id);
                _log("woo_economic_handle_orderlines got line number: " . $line_handle->Number);
                array_push($line_handles, $line_handle);
            }
        }

    }
    $lines = array();
    if (!empty($line_handles)) {
        _log("woo_economic_handle_orderlines match order line count and product with woo and delete if not there");
        foreach ($line_handles as $handle) {
            $line = woo_economic_get_currentinvoice_orderline_from_economic($handle, $client);
            _log("woo_economic_handle_orderlines line: " . $line->Number);
            _log("woo_economic_handle_orderlines product: " . $line->ProductHandle->Number);
            $lines[$line->ProductHandle->Number] = $line;
        }
        foreach ($lines as $line) {
            $line_product_id = woo_economic_get_product_id_from_economic($line->ProductHandle->Number);
            $found = false;
            _log("woo_economic_handle_orderlines matching " . $line_product_id);
            foreach ($order->get_items() as $item) {
                $product = $order->get_product_from_item($item);
                if ($product->sku == $line_product_id) {
                    _log("woo_economic_handle_orderlines found " . $line_product_id . " in order");
                    $found = true;
                    break;
                }
            }
            if (!$found && $line_product_id != $shipping_product_id) {
                _log("woo_economic_handle_orderlines product_id" . $line_product_id . " not found in order, deleting line");
                $client->CurrentInvoiceLine_Delete(array(
                    'currentInvoiceLineHandle' => $line->Handle
                ));
            }
        }
    }

    foreach ($order->get_items() as $item) {
        $product = $order->get_product_from_item($item);
        $line = $lines[woo_economic_get_product_id($product)];
        $current_invoice_line_handle = null;
        if (!isset($line)) {
            $current_invoice_line_handle = woo_economic_create_curenctinvoice_orderline($current_invoice_handle, woo_economic_get_product_id($product), $client);
        } else {
            $current_invoice_line_handle = $line->Handle;
        }

        _log("woo_economic_handle_orderlines updating qty on id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number);
        $quantity = ($refund) ? $item['qty'] * -1 : $item['qty'];
        $client->CurrentInvoiceLine_SetQuantity(array(
            'currentInvoiceLineHandle' => $current_invoice_line_handle,
            'value' => $quantity
        ));
        _log("woo_economic_handle_orderlines updated line");
    }

    if (empty($line_handles)) {
        _log("woo_economic_handle_orderlines adding shipping order line: " . $shipping_product_id);
        $handle = woo_economic_create_curenctinvoice_orderline($current_invoice_handle, $shipping_product_id, $client);
        $client->CurrentInvoiceLine_SetQuantity(array(
            'currentInvoiceLineHandle' => $handle,
            'value' => 1
        ));

    }
}

function woo_economic_create_curenctinvoice_orderline($current_invoice_handle, $product_id, SoapClient &$client)
{
    $current_invoice_line_handle = $client->CurrentInvoiceLine_Create(array(
        'invoiceHandle' => $current_invoice_handle
    ))->CurrentInvoiceLine_CreateResult;
    _log("woo_economic_create_curenctinvoice_orderline added line id: " . $current_invoice_line_handle->Id . " number: " . $current_invoice_line_handle->Number . " product_id: " . $product_id);
    $product_handle = $client->Product_FindByNumber(array(
        'number' => $product_id
    ))->Product_FindByNumberResult;
    $client->CurrentInvoiceLine_SetProduct(array(
        'currentInvoiceLineHandle' => $current_invoice_line_handle,
        'valueHandle' => $product_handle
    ));
    $product = $client->Product_GetData(array(
        'entityHandle' => $product_handle
    ))->Product_GetDataResult;
    $client->CurrentInvoiceLine_SetDescription(array(
        'currentInvoiceLineHandle' => $current_invoice_line_handle,
        'value' => $product->Name
    ));
    $client->CurrentInvoiceLine_SetUnitNetPrice(array(
        'currentInvoiceLineHandle' => $current_invoice_line_handle,
        'value' => $product->SalesPrice
    ));

    _log("added product to line ");
    return $current_invoice_line_handle;
}


function woo_economic_get_currentinvoice_orderline_from_economic(&$handle, SoapClient &$client)
{
    _log("woo_economic_get_currentinvoice_orderline_from_economic id: " . $handle->Id . " numbner: " . $handle->Number);
    $invoice_line = $client->CurrentInvoiceLine_GetData(array(
        'entityHandle' => $handle
    ))->CurrentInvoiceLine_GetDataResult;

    return $invoice_line;
}

function woo_economic_save_refund_to_economic(WP_User $user, WC_Order $order)
{
    $reference = $order->id . " refunded";
    woo_economic_save_create_invoice_in_economic($user, $order, $reference, true);
}

function woo_economic_synchronize_to_eco()
{
    // Can we access e-conomic?
    try {
      $client = woo_economic_create_economic_client();
    } catch (Exception $exception) {
        _log("woo_economic_synchronize_to_eco could not connect to e-economic " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not connect to e-conomic</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
        return;
    }

    _log("woo_economic_synchronize_to_eco starting");
    $products = woo_economic_load_woocommerce_products();

    foreach ($products as $product) {
        _log('woo_economic_synchronize_to_eco saving product: ' . $product->get_title() . " sku: " . $product->sku);
        _log('isset($product->sku): '. isset($product->sku) );
        _log('!empty($product->sku): '.!empty($product->sku));
        _log('isset($product->title): '.isset($product->title));
        _log('!empty($product->title): '.!empty($product->title));
        $title = $product->get_title();
        if (isset($product->sku) && !empty($product->sku) &&
            isset($title) && !empty($title)) {
            woo_economic_save_product_to_economic($product);
        } else {
            print("<p><b>Could not create product: '". $product->get_title() ."' and id: '".$product->id."' in e-conomic.<br/>");
            print("Please update it with: ");
            print("<ol>");
            if (!isset($product->sku) || empty($product->sku))
                print("<li>SKU</li>");
            if (!isset($title) || empty($title))
                print("<li>Title</li>");
            print("</ol>");
            print("</b></p><hr/>");
        }
    }

}

function woo_economic_create_dummy_data()
{
    $new_post = array(
        'post_title' => 'Product ',
        'post_status' => 'publish',
        'post_type' => 'product'
    );


    /**
     * You need some kind of lock mechanism to prevent updates of the product back to e-conomic
     * since this is update_post_meta - you need to disable the hook to save_post
     */

    for ($i = 1; $i <= 21; $i++) {
        _log("woo_economic_synchronize_to_woo creating product: " . $i);
        $new_post['post_title'] = 'Product ' . $i;
        $postId = wp_insert_post($new_post);
        update_post_meta($postId, '_sku', $i + 100);
        update_post_meta($postId, '_sale_price', $i * 2.0);
        update_post_meta($postId, '_regular_price', $i * 3.0);
        update_post_meta($postId, '_price', $i * 3.0);
    }
}


function woo_economic_synchronize_to_woo()
{
    _log("woo_economic_synchronize_to_woo creating client");
    try {
      $client = woo_economic_create_economic_client();
    } catch (Exception $exception) {
        _log("woo_economic_synchronize_to_woo could not connect to e-economic " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not connect to e-conomic</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
        return;
    }
    _log("woo_economic_synchronize_to_woo starting");
    global $woo_economic_product_lock;

    try {
        $all_product_handles = $client->Product_GetAll()->Product_GetAllResult;
        $product_handles = woo_economic_transform_product_handles($all_product_handles);
        _log("woo_economic_synchronize_to_woo raising product lock - synchronizing to woocomerce from e-conomic.");
        $woo_economic_product_lock = true;
        /**
         * load all products into an array and index after sku
         */
        $products = woo_economic_load_woocommerce_products();
        $sku_post_ids = woo_economic_transform_wc_product_array_to_sku_postid_array($products);
        $product_group = get_option(WOO_ECONOMIC_OPTION_PRODUCT_GROUP_NAME);
        $shipping_product = get_option(WOO_ECONOMIC_OPTION_SHIPPING_PRODUCT_NUMBER_NAME);
        foreach ($product_handles as $handle) {
            woo_economic_synchronize_product($handle, $client, $product_group, $shipping_product, $sku_post_ids);
        }
    } catch (Exception $exception) {
        _log("woo_economic_synchronize_to_woo could not update debtor: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not synchronize products to woocommerce.</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }
    _log("woo_economic_synchronize_to_woo releasing product lock - synchronizing to woocomerce from e-conomic DONE!");
    $woo_economic_product_lock = false;
}


function woo_economic_transform_product_handles($all_product_handles)
{
    $product_handles = array();
    foreach ($all_product_handles as $handle) {
        if (is_object($handle)) {
            _log("woo_economic_synchronize_to_woo handle->number: " . $handle->Number);
            array_push($product_handles, $handle);
        }
        if (is_array($handle)) {
            foreach ($handle as $product_handle) {
                _log("woo_economic_synchronize_to_woo handle->number: " . $product_handle->Number);
                array_push($product_handles, $product_handle);
            }
        }
    }
    return $product_handles;
}

function woo_economic_transform_wc_product_array_to_sku_postid_array(array &$products)
{
    $sku = array();
    foreach ($products as $product) {
        if (isset($product->sku)) {
            $sku[$product->sku] = $product->id;
            _log('woo_economic_transform_wc_product_array_to_sku_postid_array sku[' . $product->sku . ']->' . $product->id);
        }
    }
    return $sku;
}


function woo_economic_load_woocommerce_products()
{
    $args = array('post_type' => 'product', 'nopaging' => true);
    $product_query = new WP_Query($args);
    $posts = $product_query->get_posts();
    $products = array();
    foreach ($posts as $post) {
        _log('woo_economic_synchronize_to_eco id: ' . $post->ID);
        array_push($products, new WC_Product($post->ID));
    }
    return $products;
}

function woo_economic_synchronize_product($product_handle, &$client, $product_group, $shipping_product, array &$sku_post_ids)
{
    _log("--------------------------- woo_economic_synchronize_product getting product data for handle: " . $product_handle->Number);
    $product_data = $client->Product_GetData(array(
        'entityHandle' => $product_handle))->Product_GetDataResult;

    /**
     * products with product number < product offset should not be synced!!
     */
    if (isset($product_group) && $product_data->ProductGroupHandle->Number != $product_group) {
        _log("woo_economic_synchronize_product skipping product " . $product_data->Name . " not in group " . $product_group);
        return;
    }
    if (isset($shipping_product) && $product_data->Number == $shipping_product) {
        _log("woo_economic_synchronize_product skipping product " . $product_data->Name . " is shipping product ");
        return;
    }


    /**
     * Maybe you need to load all products before making the synchronization
     */


    /**
     * figure out if the product allready exists?
     *
     */
    $postId = 0;


    $product_id = woo_economic_get_product_id_from_economic($product_data->Number);
    if (array_key_exists($product_id, $sku_post_ids)) {
        /**
         * update
         */
        $postId = $sku_post_ids['' . $product_id];
        $post_data = array(
            'ID' => $postId,
            'post_title' => $product_data->Name,
            'post_content' => $product_data->Description,
        );
        wp_update_post($post_data);
        _log('woo_economic_synchronize_product updating post postid: ' . $postId . " for product " . $product_data->Name);
    } else {
        /**
         * insert
         */
        $post_data = array(
            'post_title' => $product_data->Name,
            'post_content' => $product_data->Description,
            'post_status' => 'publish',
            'post_type' => 'product'
        );
        $postId = wp_insert_post($post_data);
        update_post_meta($postId, WOO_ECONOMIC_SYNCED_FROM_ECONOMIC_NAME, true);
        _log('woo_economic_synchronize_product created new postid: ' . $postId);
    }
    if ($postId != 0) {
        _log("woo_economic_synchronize_product updating metadata on postid: " . $postId . " for product " . $product_data->Name);
        update_post_meta($postId, '_sku', $product_id); // You need to transform sku - if offset has been added
        update_post_meta($postId, '_regular_price', $product_data->SalesPrice);
        update_post_meta($postId, '_price', $product_data->SalesPrice);
        update_post_meta($postId, '_visibility', ($product_data->IsAccessible ? 'visible' : 'hidden'));
    }

}


function woo_economic_create_debtor_payment(WP_User $user, WC_Order $order)
{

    /**
     * Do you need a admin setting for specifying which cashbook to add the debtor
     * payment in? Perhaps you should make a popup or select, so the user can choose the
     * cashbook - that would be great functionality!
     *
     * Nevertheless it has to be present at this point.
     */

    _log("woo_economic_create_debtor_payment creating client");
    $client = woo_economic_create_economic_client();
    _log("woo_economic_create_debtor_payment starting");

    try {
        $debtor_handle = woo_economic_get_user_debtor_handle($user, $client);
        $invoice_number = woo_economic_get_invoice_number($client, $order->id, $debtor_handle);
        if (!isset($invoice_number)) {
            $current_invoice_handle = woo_economic_get_currentinvoice($client, $order->id, $debtor_handle);

            $invoice_number = $client->CurrentInvoice_Book(array(
                'currentInvoiceHandle' => $current_invoice_handle
            ))->CurrentInvoice_BookResult;
        }
        $invoice = $client->Invoice_GetData(array(
            'entityHandle' => $invoice_number
        ))->Invoice_GetDataResult;

        _log("woo_economic_create_debtor_payment amount: " . $invoice->GrossAmount);

        $cashbook = get_option(WOO_ECONOMIC_OPTION_CASHBOOK_NAME);
        $cashbook = $client->CashBook_FindByName(array(
            'name' => $cashbook
        ))->CashBook_FindByNameResult;

        $contra_account = $client->Account_FindByNumber(array(
            'number' => 5820
        ))->Account_FindByNumberResult;

        _log("woo_economic_create_debtor_payment cb: " . $cashbook->Number);
        _log("woo_economic_create_debtor_payment dh: " . $debtor_handle->Number);
        _log("woo_economic_create_debtor_payment acc: " . $contra_account->Number);

        $cashbook_entry = $client->CashBookEntry_CreateDebtorPayment(array(
            'cashBookHandle' => array(
                'Number' => $cashbook->Number
            ),
            'debtorHandle' => array(
                'Number' => $debtor_handle->Number
            ),
            'contraAccountHandle' => array(
                'Number' => $contra_account->Number
            )
        ))->CashBookEntry_CreateDebtorPaymentResult;

        $client->CashBookEntry_SetAmount(array(
            'cashBookEntryHandle' => array(
                'Id1' => $cashbook_entry->Id1,
                'Id2' => $cashbook_entry->Id2
            ),
            'value' => $invoice->GrossAmount
        ));
        $client->CashBookEntry_SetDebtorInvoiceNumber(array(
            'cashBookEntryHandle' => array(
                'Id1' => $cashbook_entry->Id1,
                'Id2' => $cashbook_entry->Id2
            ),
            'value' => $invoice->Number
        ));
        $client->CashBookEntry_SetText(array(
            'cashBookEntryHandle' => array(
                'Id1' => $cashbook_entry->Id1,
                'Id2' => $cashbook_entry->Id2
            ),
            'value' => 'Invoice no. ' . $invoice->Number
        ));

    } catch (Exception $exception) {
        _log("woo_economic_create_debtor_payment could not create debtor payment: " . $exception->getMessage());
        debug_client($client);
        print("<p><b>Could not create debtorpayment from order.</b></p>");
        print("<p><i>" . $exception->getMessage() . "</i></p>");
    }


}

?>

<?php
/*
 * Dynamic Price Updater V3.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * This module is released under the GNU/GPL licence
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

class DPU extends base {

  /*
   * Local instantiation of the shopping cart
   *
   * @var object
   */
  protected $shoppingCart;
  /*
   * The type of message being sent (error or success)
   *
   * @var string
   */
  protected $responseType = 'success';
  /*
   * Array of lines to be sent back.  The key of the array provides the attribute to identify it at the client side
   * The array value is the text to be inserted into the node
   *
   * @var array
   */
  var $responseText = array();

  /*
   * Constructor
   *
   * @param obj The Zen Cart database class
   * @return DPU
   */
  public function __construct() {
    global $db;
    // grab the shopping cart class and instantiate it
    $this->shoppingCart = new shoppingCart();
  }

  /*
   * Wrapper to call all methods to generate the output
   *
   * @return void
   */
  public function getDetails($outputType = "XML") {
    $this->insertProduct();
    $this->shoppingCart->calculate();
    $show_dynamic_price_updater_sidebox = true;
    if ($show_dynamic_price_updater_sidebox == true) {
      $this->getSideboxContent();
    }
    $this->prepareOutput();
    $this->dumpOutput($outputType);
  }

  /*
   * Wrapper to call all methods relating to returning multiple prices for category pages etc.
   *
   * @return void
   */
  public function getMulti() {
    $this->insertProducts();
  }

  /*
   * Prepares the shoppingCart contents for transmission
   *
   * @return void
   */
  protected function prepareOutput() {
    global $currencies, $db;
    $prefix = '';
    switch (true) {
        case (!isset($_POST['pspClass'])):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        case ($_POST['pspClass'] == "productSpecialPrice"):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        case ($_POST['pspClass'] == "productSalePrice"):
            $prefix = PRODUCT_PRICE_SALE;
            break;
        case ($_POST['pspClass'] == "productSpecialPriceSale"):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        case ($_POST['pspClass'] == "productPriceDiscount"):
            $prefix = PRODUCT_PRICE_DISCOUNT_PREFIX;
            break;
        case ($_POST['pspClass'] == "normalprice"):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        case ($_POST['pspClass'] == "productFreePrice"):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        case ($_POST['pspClass'] == "productBasePrice"):
            $prefix = UPDATER_PREFIX_TEXT;
            break;
        default:
            $prefix = UPDATER_PREFIX_TEXT;
            // Add a notifier to allow updating this prefix if the ones above do not exist.
            $this->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_PSP_CLASS');
        break;
    }
    $this->responseText['priceTotal'] = $prefix;
    $product_check = $db->Execute("SELECT products_tax_class_id FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . (int)$_POST['products_id'] . "'" . " LIMIT 1");
    if (DPU_SHOW_CURRENCY_SYMBOLS == 'false') {
      $decimal_places = $currencies->get_decimal_places($_SESSION['currency']);
      $this->responseText['priceTotal'] .= number_format($this->shoppingCart->total, $decimal_places);
    } else {
      $this->responseText['priceTotal'] .= $currencies->display_price($this->shoppingCart->total, 0 /*zen_get_tax_rate($product_check->fields['products_tax_class_id'])*//* 0 */ /* DISPLAY_PRICE_WITH_TAX */);
    }

    $this->responseText['weight'] = (string)$this->shoppingCart->weight;
    if (DPU_SHOW_QUANTITY == 'true') {
      foreach ($this->shoppingCart->contents as $key => $value) {
        if ($_SESSION['cart']->contents[$key]['qty'] > 0) { // Hides quantity if the selected variant/options are not in the existing cart.
          $this->responseText['quantity'] = sprintf(DPU_SHOW_QUANTITY_FRAME, (float)$_SESSION['cart']->contents[$key]['qty']);
        }
      }
    }
  }

  /*
   * Inserts multiple non-attributed products into the shopping cart
   *
   * @return void
   */
  protected function insertProducts() {
    foreach ($_POST['products_id'] as $id => $qty) {
      $this->shoppingCart->contents[] = array((int)$id);
      $this->shoppingCart->contents[(int)$id] = array('qty' => (float)$qty);
    }

    var_dump($this->shoppingCart);
    die();
  }

  /*
   * Inserts the product into the shoppingCart content array
   *
   * @returns void
   */
  protected function insertProduct() {
//    $this->shoppingCart->contents[$_POST['products_id']] = array('qty' => (float)$_POST['cart_quantity']);
    $attributes = array();

    foreach ($_POST as $key => $val) {
      if (is_array($val)) {
        foreach ($val as $k => $v) {
          $attributes[$k] = $v;
        }
      }
    }

    if (is_array($attributes) && sizeof($attributes)) {
      $products_id = zen_get_uprid((int)$_POST['products_id'], $attributes);
      $this->shoppingCart->contents[$products_id] = array('qty' => (float)$_POST['cart_quantity']);

      foreach ($attributes as $option => $value) {
        //CLR 020606 check if input was from text box.  If so, store additional attribute information
        //CLR 020708 check if text input is blank, if so do not add to attribute lists
        //CLR 030228 add htmlspecialchars processing.  This handles quotes and other special chars in the user input.
        $attr_value = NULL;
        $blank_value = FALSE;
        if (strstr($option, TEXT_PREFIX)) {
          if (trim($value) == NULL) {
            $blank_value = TRUE;
          } else {
            $option = substr($option, strlen(TEXT_PREFIX));
            $attr_value = stripslashes($value);
            $value = PRODUCTS_OPTIONS_VALUES_TEXT_ID;
//            $product_info['attributes_values'][$option] = $attr_value;
            $this->shoppingCart->contents[$products_id]['attributes_values'][$option] = $attr_value;
          }
        }

        if (!$blank_value) {
          if (is_array($value)) {
            foreach ($value as $opt => $val) {
//              $product_info['attributes'][$option . '_chk' . $val] = $val;
              $this->shoppingCart->contents[$products_id]['attributes'][$option . '_chk' . $val] = $val;
            }
          } else {
//            $product_info['attributes'][$option] = $value;
            $this->shoppingCart->contents[$products_id]['attributes'][$option] = $value;
          }
        }
      }
    }
  }

  /*
   * Prepares the output for the Updater's sidebox display
   *
   */
  protected function getSideboxContent() {
    global $currencies, $db;

    $product_check = $db->Execute("SELECT products_tax_class_id FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . (int)$_POST['products_id'] . "'" . " LIMIT 1");
    $product = $db->Execute("SELECT products_id, products_price, products_tax_class_id, products_weight,
                      products_priced_by_attribute, product_is_always_free_shipping, products_discount_type, products_discount_type_from,
                      products_virtual, products_model
                      FROM " . TABLE_PRODUCTS . "
                      WHERE products_id = '" . (int)$_POST['products_id'] . "'");

    $prid = $product->fields['products_id'];
    $products_tax = zen_get_tax_rate(0);
    $products_price = $product->fields['products_price'];
    $qty = (float)$_POST['cart_quantity'];
    $out = array();
    $global_total;

    if (is_array($this->shoppingCart->contents[$_POST['products_id']]['attributes'])) {
//    while (isset($this->shoppingCart->contents[$_POST['products_id']]['attributes']) && list($option, $value) = each($this->shoppingCart->contents[$_POST['products_id']]['attributes'])) {
    foreach ($this->shoppingCart->contents[$_POST['products_id']]['attributes'] as $option => $value) {
      $adjust_downloads ++;

      $attribute_price = $db->Execute("SELECT *
                                FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
                                WHERE products_id = '" . (int)$prid . "'
                                AND options_id = '" . (int)$option . "'
                                AND options_values_id = '" . (int)$value . "'");

      $data = $db->Execute("SELECT products_options_values_name
                     FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . "
                     WHERE products_options_values_id = " . (int)$value);
      $name = $data->fields['products_options_values_name'];

      $new_attributes_price = 0;
      $discount_type_id = '';
      $sale_maker_discount = '';
      $total = 0;

      if ($attribute_price->fields['product_attribute_is_free'] == '1' and zen_get_products_price_is_free((int)$prid)) {
        // no charge for attribute
      } else {
        // + or blank adds
        if ($attribute_price->fields['price_prefix'] == '-') {
          // appears to confuse products priced by attributes
          if ($product->fields['product_is_always_free_shipping'] == '1' or $product->fields['products_virtual'] == '1') {
            $shipping_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $this->free_shipping_price -= $qty * zen_add_tax(($shipping_attributes_price), $products_tax);
          }
          if ($attribute_price->fields['attributes_discounted'] == '1') {
            // calculate proper discount for attributes
            $new_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $total -= $qty * zen_add_tax(($new_attributes_price), $products_tax);
          } else {
            $total -= $qty * zen_add_tax($attribute_price->fields['options_values_price'], $products_tax);
          }
          $total = $total;
        } else {
          // appears to confuse products priced by attributes
          if ($product->fields['product_is_always_free_shipping'] == '1' or $product->fields['products_virtual'] == '1') {
            $shipping_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $this->free_shipping_price += $qty * zen_add_tax(($shipping_attributes_price), $products_tax);
          }
          if ($attribute_price->fields['attributes_discounted'] == '1') {
            // calculate proper discount for attributes
            $new_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $total += $qty * zen_add_tax(($new_attributes_price), $products_tax);
            // echo $product->fields['products_id'].' - '.$attribute_price->fields['products_attributes_id'].' - '. $attribute_price->fields['options_values_price'].' - '.$qty."\n";
          } else {
            $total += $qty * zen_add_tax($attribute_price->fields['options_values_price'], $products_tax);
          }
        }
      }
      $global_total += $total;
      $qty2 = sprintf('<span class="DPUSideboxQuantity">' . DPU_SIDEBOX_QUANTITY_FRAME . '</span>', $_POST['cart_quantity']);
      $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($total, 0 /* ?? Should this tax be applied? zen_get_tax_rate($product_check->fields['products_tax_class_id'])*/));
      $out[] = sprintf(DPU_SIDEBOX_FRAME, $name, $total, $qty2);
    }
    }

    $out[] = sprintf('<hr />' . DPU_SIDEBOX_TOTAL_FRAME, $currencies->display_price($this->shoppingCart->total, 0));

    $qty2 = sprintf('<span class="DPUSideboxQuantity">' . DPU_SIDEBOX_QUANTITY_FRAME . '</span>', $_POST['cart_quantity']);
    $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($this->shoppingCart->total - $global_total, 0));
    array_unshift($out, sprintf(DPU_SIDEBOX_FRAME, DPU_BASE_PRICE, $total, $qty2));

    $this->responseText['sideboxContent'] = implode('', $out);
  }

  /**
   * DEPRECATED -- Seriously? For the love of all that's normal WHY THROW AN ERROR AGAIN?!?!
   * Performs an error dump
   *
   * @param mixed $errorMsg
   */
  /* function throwError($errorMsg) {
    $this->responseType = 'error';
    $this->responseText[] = $errorMsg;

    $this->dumpOutput();
  } */

  /*
   * Formats the response and flushes with the appropriate headers
   * This should be called last as it issues an exit
   *
   * @return void
   */
  protected function dumpOutput($outputType = "XML") {
    if ($outputType == "XML") {
    // output the header for XML
    header("content-type: text/xml");
    // set the XML file DOCTYPE
    echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
    // set the responseType
    echo '<root>' . "\n" . '<responseType>' . $this->responseType . '</responseType>' . "\n";
    // now loop through the responseText nodes
    foreach ($this->responseText as $key => $val) {
      echo '<responseText' . (!is_numeric($key) && !empty($key) ? ' type="' . $key . '"' : '') . '><![CDATA[' . $val . ']]></responseText>' . "\n";
    }

    die('</root>');
    } elseif ($outputType == "JSON") {
      $data = array();

      // output the header for JSON
      header('Content-Type: application/json');

      // DO NOT set a JSON file DOCTYPE as there is none to be included.
//      echo '<?xml version="1.0" encoding="UTF-8" ' . "\n";

      // set the responseType
      $data['responseType'] = $this->responseType;
      // now loop through the responseText nodes
      foreach ($this->responseText as $key => $val) {
          if (!is_numeric($key) && !empty($key)) {
            $data['data'][$key] = $val;
          }
      }

      die(json_encode($data));
    }
  }
}

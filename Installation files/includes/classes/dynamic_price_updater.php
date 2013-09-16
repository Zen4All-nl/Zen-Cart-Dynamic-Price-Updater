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

class DPU {
  
  /*
   * Local instantiation of the shopping cart
   *
   * @var object
   */
  var $shoppingCart;
  /*
   * The type of message being sent (error or success)
   *
   * @var string
   */
  var $responseType = 'success';
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
  function __construct() {
    global $db;
    // grab the shopping cart class and instantiate it
    $this->shoppingCart = new shoppingCart();
  }

  /*
   * Wrapper to call all methods to generate the output
   *
   * @return void
   */
  function getDetails() {
    $this->insertProduct();
    $this->shoppingCart->calculate();
    $show_dynamic_price_updater_sidebox = true;
      if ($show_dynamic_price_updater_sidebox == true) $this->getSideboxContent();
    $this->prepareOutput();
    $this->dumpOutput();
  }

  /*
   * Wrapper to call all methods relating to returning multiple prices for category pages etc.
   *
   * @return void
   */
  function getMulti() {
    $this->insertProducts();
  }

  /*
   * Prepares the shoppingCart contents for transmission
   *
   * @return void
   */
  function prepareOutput() {
    global $currencies,$db;
    $this->responseText['priceTotal'] = UPDATER_PREFIX_TEXT;
    $product_check = $db->Execute("select products_tax_class_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$_POST['products_id'] . "'" . " limit 1");
    if (DPU_SHOW_CURRENCY_SYMBOLS == 'false') {
      $this->responseText['priceTotal'] .= number_format($this->shoppingCart->total, 2);
    } else {
      $this->responseText['priceTotal'] .= $currencies->display_price($this->shoppingCart->total, 0);
    }

    $this->responseText['weight'] = (string)$this->shoppingCart->weight;
    if (DPU_SHOW_QUANTITY == 'true') {
      $this->responseText['quantity'] = sprintf(DPU_SHOW_QUANTITY_FRAME, $this->shoppingCart->contents[$_POST['products_id']]['qty']);
    }
  }

  /*
   * Inserts multiple non-attributed products into the shopping cart
   *
   * @return void
   */
  function insertProducts() {
    foreach ($_POST['products_id'] as $id => $qty) {
      $this->shoppingCart->contents[] = array($id);
      $this->shoppingCart->contents[$id] = array('qty' => (float)$qty);
    }

    var_dump($this->shoppingCart);
    die();
  }

  /*
   * Inserts the product into the shoppingCart content array
   *
   * @returns void
   */
  function insertProduct() {
    $this->shoppingCart->contents[$_POST['products_id']] = array('qty' => (float)$_POST['cart_quantity']);
    $attributes = array();

    foreach ($_POST as $key => $val) {
      if (is_array($val)) {
        foreach ($val as $k => $v) {
          $attributes[$k] = $v;
        }
      }
    }

    if (is_array($attributes)) {
      reset($attributes);
      while (list($option, $value) = each($attributes)) {
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
            $this->shoppingCart->contents[$_POST['products_id']]['attributes_values'][$option] = $attr_value;
          }
        }

        if (!$blank_value) {
          if (is_array($value) ) {
            reset($value);
            while (list($opt, $val) = each($value)) {
              $this->shoppingCart->contents[$_POST['products_id']]['attributes'][$option.'_chk'.$val] = $val;
            }
          } else {
            $this->shoppingCart->contents[$_POST['products_id']]['attributes'][$option] = $value;
          }
        }
      }
    }
  }

  /*
   * Prepares the output for the Updater's sidebox display
   *
   */
  function getSideboxContent() {
    global $currencies,$db;

    $product_check = $db->Execute("select products_tax_class_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$_POST['products_id'] . "'" . " limit 1");
    $product = $db->Execute("select products_id, products_price, products_tax_class_id, products_weight,
                      products_priced_by_attribute, product_is_always_free_shipping, products_discount_type, products_discount_type_from,
                      products_virtual, products_model
                      from " . TABLE_PRODUCTS . "
                      where products_id = '" . (int)$_POST['products_id'] . "'");

    $prid = $product->fields['products_id'];
    $products_tax = zen_get_tax_rate(0);
    $products_price = $product->fields['products_price'];
    $qty = $_POST['cart_quantity'];
    $out = array();
    $global_total;
    reset($this->shoppingCart->contents[$_POST['products_id']]['attributes']);
    while (list($option, $value) = each($this->shoppingCart->contents[$_POST['products_id']]['attributes'])) {
      $adjust_downloads ++;

      $attribute_price = $db->Execute("select *
                                from " . TABLE_PRODUCTS_ATTRIBUTES . "
                                where products_id = '" . (int)$prid . "'
                                and options_id = '" . (int)$option . "'
                                and options_values_id = '" . (int)$value . "'");

      $data = $db->Execute("select products_options_values_name
                     from ".TABLE_PRODUCTS_OPTIONS_VALUES."
                     where products_options_values_id = $value");
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
            $this->free_shipping_price -= $qty * zen_add_tax( ($shipping_attributes_price), $products_tax);
          }
          if ($attribute_price->fields['attributes_discounted'] == '1') {
            // calculate proper discount for attributes
            $new_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $total -= $qty * zen_add_tax( ($new_attributes_price), $products_tax);
          } else {
            $total -= $qty * zen_add_tax($attribute_price->fields['options_values_price'], $products_tax);
          }
          $total = $total;
        } else {
          // appears to confuse products priced by attributes
          if ($product->fields['product_is_always_free_shipping'] == '1' or $product->fields['products_virtual'] == '1') {
            $shipping_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $this->free_shipping_price += $qty * zen_add_tax( ($shipping_attributes_price), $products_tax);
          }
          if ($attribute_price->fields['attributes_discounted'] == '1') {
            // calculate proper discount for attributes
            $new_attributes_price = zen_get_discount_calc($product->fields['products_id'], $attribute_price->fields['products_attributes_id'], $attribute_price->fields['options_values_price'], $qty);
            $total += $qty * zen_add_tax( ($new_attributes_price), $products_tax);
            // echo $product->fields['products_id'].' - '.$attribute_price->fields['products_attributes_id'].' - '. $attribute_price->fields['options_values_price'].' - '.$qty."\n";
          } else {
            $total += $qty * zen_add_tax($attribute_price->fields['options_values_price'], $products_tax);
          }
        }
      }
      $global_total += $total;
      $qty2 = sprintf('<span class="DPUSideboxQuantity">'. DPU_SIDEBOX_QUANTITY_FRAME . '</span>', $_POST['cart_quantity']);
      $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($total, 0));
      $out[] = sprintf(DPU_SIDEBOX_FRAME, $name, $total, $qty2);
    }

    $out[] = sprintf('<hr />' . DPU_SIDEBOX_TOTAL_FRAME, $currencies->display_price($this->shoppingCart->total, 0));

    $qty2 = sprintf('<span class="DPUSideboxQuantity">' . DPU_SIDEBOX_QUANTITY_FRAME . '</span>', $_POST['cart_quantity']);
    $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($this->shoppingCart->total-$global_total, 0));
    array_unshift($out, sprintf(DPU_SIDEBOX_FRAME, DPU_BASE_PRICE, $total, $qty2));

    $this->responseText['sideboxContent'] = implode('', $out);
  }

  /*
   * Performs an error dump
   *
   * @param mixed $errorMsg
   */
  function throwError($errorMsg) {
    $this->responseType = 'error';
    $this->responseText[] = $errorMsg;

    $this->dumpOutput();
  }

  /*
   * Formats the response and flushes with the appropriate headers
   * This should be called last as it issues an exit
   *
   * @return void
   */
  function dumpOutput() {
    // output the header for XML
    header ("content-type: text/xml");
    // set the XML file DOCTYPE
    echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
    // set the responseType
    echo '<root>'."\n".'<responseType>'.$this->responseType.'</responseType>'."\n";
    // now loop through the responseText nodes
    foreach ($this->responseText as $key => $val) {
      echo '<responseText'.(!is_numeric($key) && !empty($key) ? ' type="'.$key.'"' : '').'><![CDATA['.$val.']]></responseText>'."\n";
    }

    die('</root>');
  }
}
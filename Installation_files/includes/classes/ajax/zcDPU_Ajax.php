<?php

/**
 * Dynamic Price Updater V4.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
class zcDPU_Ajax extends base {
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
   * Array of attributes that could be associated with the product but have not been added by the customer to support
   *   identifying the minimum price of a product from the point of having selected an attribute when other attributes have not
   *   been selected.  (This is a setup contrary to recommendations by ZC, but is a condition that perhaps is best addressed regardless.)
   * @var array
   */
  protected $new_attributes = array();

  /**
   * Constructor
   *
   * @param obj The Zen Cart database class
   * @return DPU
   */
  public function __construct()
  {
    // grab the shopping cart class and instantiate it
    $this->shoppingCart = new shoppingCart();
  }

  /**
   * Wrapper to call all methods to generate the output
   *
   * @return void
   */
  public function getDetails()
  {
    $this->setCurrentPage();
    $this->insertProduct();
    $this->shoppingCart->calculate();
    $this->removeExtraSelections();
    $show_dynamic_price_updater_sidebox = true;
    if ($show_dynamic_price_updater_sidebox == true) {
      $this->getSideboxContent();
    }
    $this->prepareOutput();

    $data = array();
    $data['responseType'] = $this->responseType;
    // now loop through the responseText nodes
    foreach ($this->responseText as $key => $val) {
      if (!is_numeric($key) && !empty($key)) {
        $data['data'][$key] = $val;
      }
    }
    return $data;
  }

  /**
   * Prepares the shoppingCart contents for transmission
   *
   * @return void
   */
  protected function prepareOutput()
  {
    global $currencies, $db;
    $this->prefix = '';
    $this->preDiscPrefix = '';

    if (!defined('DPU_ATTRIBUTES_MULTI_PRICE_TEXT')) {
      define('DPU_ATTRIBUTES_MULTI_PRICE_TEXT', 'start_at_least');
    }

    $this->priceDisplay = DPU_ATTRIBUTES_MULTI_PRICE_TEXT;
    $this->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_PRICE_DISPLAY');

    switch (true) {
      //case ($this->product_stock <= 0 && (($this->num_options == $this->unused && !empty($this->new_temp_attributes)) || ($this->num_options > $this->unused && !empty($this->unused)))):
      case ($this->attributeDisplayStartAtPrices() && $this->num_options == $this->unused && !empty($this->new_temp_attributes)):
        $this->prefix = UPDATER_PREFIX_TEXT_STARTING_AT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT_STARTING_AT;
        break;
      case ($this->attributeDisplayAtLeastPrices() && $this->num_options > $this->unused && !empty($this->unused)):
        $this->prefix = UPDATER_PREFIX_TEXT_AT_LEAST;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT_AT_LEAST;
        break;
      case (!isset($_POST['pspClass'])):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      case ($_POST['pspClass'] == "productSpecialPrice"):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      case ($_POST['pspClass'] == "productSalePrice"):
        $this->prefix = PRODUCT_PRICE_SALE;
        $this->preDiscPrefix = PRODUCT_PRICE_SALE;
        break;
      case ($_POST['pspClass'] == "productSpecialPriceSale"):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      case ($_POST['pspClass'] == "productPriceDiscount"):
        $this->prefix = PRODUCT_PRICE_DISCOUNT_PREFIX;
        $this->preDiscPrefix = PRODUCT_PRICE_DISCOUNT_PREFIX;
        break;
      case ($_POST['pspClass'] == "normalprice"):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      case ($_POST['pspClass'] == "productFreePrice"):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      case ($_POST['pspClass'] == "productBasePrice"):
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        break;
      default:
        $this->prefix = UPDATER_PREFIX_TEXT;
        $this->preDiscPrefix = UPDATER_PREFIX_TEXT;
        // Add a notifier to allow updating this prefix if the ones above do not exist.
        $this->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_PSP_CLASS');
        break;
    }
    $this->responseText['priceTotal'] = $this->prefix;
    $this->responseText['preDiscPriceTotalText'] = $this->preDiscPrefix;

    $product_check = $db->Execute("SELECT products_tax_class_id
                                   FROM " . TABLE_PRODUCTS . "
                                   WHERE products_id = " . (int)$_POST['products_id'] . "
                                   LIMIT 1");
    if (DPU_SHOW_CURRENCY_SYMBOLS == 'false') {
      $decimal_places = $currencies->get_decimal_places($_SESSION['currency']);
      $decimal_point = $currencies->currencies[$_SESSION['currency']]['decimal_point'];
      $thousands_point = $currencies->currencies[$_SESSION['currency']]['thousands_point'];
      /* use of number_format is governed by the instruction from the php manual: 
       *  http://php.net/manual/en/function.number-format.php
       * By providing below all four values, they will be assigned/used as provided above.
       *  At time of this comment, if only one parameter is used below (remove/comment out the comma to the end of $thousands_point)
       *   then just the number will come back with a comma used at every thousands group (ie. 1,000).  
       *  With the first two parameters provided, a comma will be used at every thousands group and a decimal (.) for every part of the whole number.
       *  The only other option to use this function is to provide all four parameters with the third and fourth parameters identifying the
       *   decimal point and thousands group separater, respectively.
       */
      $this->responseText['priceTotal'] .= number_format($this->shoppingCart->total, $decimal_places, $decimal_point, $thousands_point);
      $this->responseText['preDiscPriceTotal'] = number_format($this->shoppingCart->total_before_discounts, $decimal_places, $decimal_point, $thousands_point);
    } else {
      $this->responseText['priceTotal'] .= $currencies->display_price($this->shoppingCart->total, 0 /* zen_get_tax_rate($product_check->fields['products_tax_class_id']) *//* 0 */ /* DISPLAY_PRICE_WITH_TAX */);
      $this->responseText['preDiscPriceTotal'] = $currencies->display_price($this->shoppingCart->total_before_discounts, 0 /* zen_get_tax_rate($product_check->fields['products_tax_class_id']) *//* 0 */ /* DISPLAY_PRICE_WITH_TAX */);
    }

    switch (true) {
      case ($this->product_stock > 0): // No consideration made yet on allowing quantity to go less than 0.
        $this->responseText['stock_quantity'] = $this->product_stock;
        break;
      case (true):
        $out_of_stock = false;
        if ((STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true')) {
          $out_of_stock = true;
        }
      case ($out_of_stock && $this->num_options == $this->unused && !empty($this->new_temp_attributes)):
        // No selections made yet, stock is 0 or less and not allowed to checkout.
        $this->responseText['stock_quantity'] = $this->product_stock;
        if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'quantity_replace') {
          $this->responseText['stock_quantity'] = DPU_OUT_OF_STOCK_IMAGE;
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'after') {
          $this->responseText['stock_quantity'] .= DPU_OUT_OF_STOCK_IMAGE;
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'before') {
          $this->responseText['stock_quantity'] = DPU_OUT_OF_STOCK_IMAGE . $this->responseText['stock_quantity'];
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'price_replace_only') {
          $this->responseText['priceTotal'] = DPU_OUT_OF_STOCK_IMAGE;
          $this->responseText['preDiscPriceTotal'] = DPU_OUT_OF_STOCK_IMAGE;
        }
        break;
      case ($out_of_stock && $this->num_options > $this->unused && !empty($this->unused)):
        // Not all selections have been made, stock is 0 or less and not allowed to checkout.
        $this->responseText['stock_quantity'] = $this->product_stock;
        if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'quantity_replace') {
          $this->responseText['stock_quantity'] = DPU_OUT_OF_STOCK_IMAGE;
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'after') {
          $this->responseText['stock_quantity'] .= DPU_OUT_OF_STOCK_IMAGE;
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'before') {
          $this->responseText['stock_quantity'] = DPU_OUT_OF_STOCK_IMAGE . $this->responseText['stock_quantity'];
        } else if (defined('DPU_SHOW_OUT_OF_STOCK_IMAGE') && DPU_SHOW_OUT_OF_STOCK_IMAGE === 'price_replace_only') {
          $this->responseText['priceTotal'] = DPU_OUT_OF_STOCK_IMAGE;
          $this->responseText['preDiscPriceTotal'] = DPU_OUT_OF_STOCK_IMAGE;
        }
        break;
      default:
        // Selections are complete and stock is 0 or less.
        $this->responseText['stock_quantity'] = $this->product_stock;
        break;
    }

    $this->responseText['weight'] = (string)$this->shoppingCart->weight;
    if (DPU_SHOW_QUANTITY == 'true') {
      foreach ($this->shoppingCart->contents as $key => $value) {
        if ($_SESSION['cart']->contents[$key]['qty'] > 0) { // Hides quantity if the selected variant/options are not in the existing cart.
          $this->responseText['quantity'] = sprintf(DPU_SHOW_QUANTITY_FRAME, convertToFloat($_SESSION['cart']->contents[$key]['qty']));
        }
      }
    }
  }

  /**
   * Removes attributes that were added to help calculate the total price in absence of attributes having a default selection
   *   and the product being priced by attributes.
   */
  protected function removeExtraSelections()
  {
    if (!empty($this->new_attributes)) {
      foreach ($this->shoppingCart->contents as $products_id => $cart_contents) {
        // If there were attributes that were added to support calculating
        //   the further additional minimum price.  Removing it will restore
        //   the cart to the data collected directly from the page.
        if (array_key_exists($products_id, $this->new_attributes) && is_array($this->new_attributes[$products_id])) {

          foreach ($this->new_attributes[$products_id] as $option => $value) {
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
                unset($this->shoppingCart->contents[$products_id]['attributes_values'][$option]); // = $attr_value;
              }
            }

            if (!$blank_value) {
              if (is_array($value)) {
                foreach ($value as $opt => $val) {
                  unset($this->shoppingCart->contents[$products_id]['attributes'][$option . '_chk' . $val]); // = $val;
                }
              } else {
                unset($this->shoppingCart->contents[$products_id]['attributes'][$option]); // = $value;
              }
            }
          } // EOF foreach of the new_attributes
        } // EOF if $this->new_attributes
      } // foreach on cart
    } // if $this->new_attributes
  }

  /**
   * Tests for the need to show all types of prices to be displayed by and of each individual function to display text of a price.
   * @return bool
   */
  protected function attributesDisplayMultiplePrices()
  {

    $response = ($this->attributeDisplayStartAtPrices() && $this->attributeDisplayAtLeastPrices());

    return $response;
  }

  /**
   * Helper function to test for the need to show Start At price text.
   * @return bool
   */
  protected function attributeDisplayStartAtPrices()
  {

    $response = ($this->priceDisplay === 'start_at_least' || $this->priceDisplay === 'start_at');

    return $response;
  }

  /**
   * Helper function to test for the need to show At Least price text.
   * @return bool
   */
  protected function attributeDisplayAtLeastPrices()
  {

    $response = ($this->priceDisplay === 'start_at_least' || $this->priceDisplay === 'at_least');

    return $response;
  }

  /**
   * Inserts the product into the shoppingCart content array
   *
   * @returns void
   */
  protected function insertProduct()
  {
    global $db;
    $attributes = array();
    $temp = explode('|', $_POST['attributes']);
    foreach ($temp as $item) {
      $tempArray = explode('~', $item);
      $test = str_replace('id[', '', $tempArray[0]);
      $test1 = str_replace(']', '', $test);
      $attributes[$test1] = $tempArray[1];
    }

    if (!empty($attributes) || zen_has_product_attributes_values($_POST['products_id'])) {
      // If product is priced by attribute then determine which attributes had not been added, 
      //  add them to the attribute list such that product added to the cart is fully defined with the minimum value(s), though 
      //  at the moment seems that similar would be needed even for not priced by attribute possibly... Will see... Maybe someone will report if an issue.

      if (!defined('DPU_PROCESS_ATTRIBUTES')) {
        define('DPU_PROCESS_ATTRIBUTES', 'all');
      }

      $product_check_result = false;
      if (DPU_PROCESS_ATTRIBUTES !== 'all') {
        $product_check = $db->Execute("SELECT products_priced_by_attribute
                                       FROM " . TABLE_PRODUCTS . "
                                       WHERE products_id = " . (int)$_POST['products_id']);
        $product_check_result = $product_check->fields['products_priced_by_attribute'] == '1';
      }

      // do not select display only attributes and attributes_price_base_included is true
      $product_att_query = $db->Execute("SELECT pa.options_id, pa.options_values_id, pa.attributes_display_only, pa.attributes_price_base_included, po.products_options_type, ROUND(CONCAT(pa.price_prefix, pa.options_values_price), 5) AS value
                                         FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                         LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po ON (po.products_options_id = pa.options_id)
                                         WHERE products_id = " . (int)$_POST['products_id'] . "
                                         AND attributes_display_only != 1
                                         AND attributes_price_base_included = 1
                                         ORDER BY pa.options_id, value");

// add attributes that are price dependent and in or not in the page's submission
      // Support price determination for product that are modified by attribute's price and are priced by attribute or just modified by the attribute's price.
      $process_price_attributes = (defined('DPU_PROCESS_ATTRIBUTES') && DPU_PROCESS_ATTRIBUTES === 'all') ? true : $product_check_result;
      if ($process_price_attributes and $product_att_query->RecordCount() >= 1) {
        $the_options_id = 'x';
        $new_attributes = array();
        $this->num_options = 0;
        foreach ($product_att_query as $item) {
          if ($the_options_id != $item['options_id']) {
            $the_options_id = $item['options_id'];
            $new_attributes[$the_options_id] = $item['options_values_id'];
            $this->num_options++;
          } elseif (array_key_exists($the_options_id, $attributes) && $attributes[$the_options_id] == $item['options_values_id']) {
            $new_attributes[$the_options_id] = $item['options_values_id'];
          }
        }

        // Need to now resort the attributes as one would have expected them to be presented which is to sort the option name(s)
        if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
          $options_order_by = ' ORDER BY LPAD(popt.products_options_sort_order,11,"0"), popt.products_options_name';
        } else {
          $options_order_by = ' ORDER BY popt.products_options_name';
        }

        $sql = "SELECT DISTINCT popt.products_options_id, popt.products_options_name, popt.products_options_sort_order, popt.products_options_type
                FROM " . TABLE_PRODUCTS_OPTIONS . " popt,
                     " . TABLE_PRODUCTS_ATTRIBUTES . " patrib
                WHERE patrib.products_id=" . (int)$_POST['products_id'] . "
                AND patrib.options_id = popt.products_options_id
                AND popt.language_id = " . (int)$_SESSION['languages_id'] . "
                " . $options_order_by;

        $products_options_names = $db->Execute($sql);

        $new_temp_attributes = array();
        $this->new_temp_attributes = array();
        $this->unused = 0;
        //  To appear in the cart, $new_temp_attributes[$options_id]
        // must contain either the selection or the lowest priced selection
        // if an "invalid" selection had been made.
        //  To get removed from the cart for display purposes
        // the $options_id must be added to $this->new_temp_attributes
        foreach ($products_options_names as $item) {
          $options_id = $item['products_options_id'];
          $options_type = $item['products_options_type'];

          // Taken from the expected format in includes/modules/attributes.
          switch ($options_type) {
            case (PRODUCTS_OPTIONS_TYPE_TEXT):
              $options_id = TEXT_PREFIX . $options_id;
              break;
            case (PRODUCTS_OPTIONS_TYPE_FILE):
              $options_id = TEXT_PREFIX . $options_id;
              break;
            default:
              $this->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_DEFAULT_INSERT_PRODUCT_TYPE', $options_type, $options_id);
              break;
          }

          if (array_key_exists($options_id, $attributes) && zen_get_attributes_valid($_POST['products_id'], $options_id, $attributes[$options_id])) {
            // If the options_id selected is a valid attribute then add it to be part of the calculation
            $new_temp_attributes[$options_id] = $attributes[$options_id];
          } elseif (array_key_exists($options_id, $attributes) && !zen_get_attributes_valid($_POST['products_id'], $options_id, $attributes[$options_id])) {
            // If the options_id selected is not a valid attribute, then add a valid attribute determined above and mark it
            //   to be deleted from the shopping cart after the price has been determined.
            $this->new_temp_attributes[$options_id] = $attributes[$options_id];
            $new_temp_attributes[$options_id] = $new_attributes[$options_id];
            $this->unused++;
          }
        }

        $attributes = $new_temp_attributes;
      }

      $products_id = zen_get_uprid((int)$_POST['products_id'], $attributes);

      $this->product_stock = zen_get_products_stock($_POST['products_id'], $attributes);

      $this->new_attributes[$products_id] = $this->new_temp_attributes;
      $this->shoppingCart->contents[$products_id] = array('qty' => (convertToFloat($_POST['cart_quantity']) <= 0 ? zen_get_buy_now_qty($products_id) : convertToFloat($_POST['cart_quantity'])));

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
            // -----
            // Check that the length of this TEXT attribute is less than or equal to its "Max Length" definition. While there
            // is some javascript on a product details' page that limits the number of characters entered, the customer
            // can choose to disable javascript entirely or circumvent that checking by performing a copy&paste action.
            // Disabling javascript would have also disabled operation of this plugin so primarily by copy&paste.
            //
            $check = $db->Execute("SELECT products_options_length
                                   FROM " . TABLE_PRODUCTS_OPTIONS . "
                                   WHERE products_options_id = " . (int)$option . "
                                   LIMIT 1");
            if (!$check->EOF) {
              if (strlen($attr_value) > $check->fields['products_options_length']) {
                $attr_value = zen_trunc_string($attr_value, $check->fields['products_options_length'], '');
              }
              $this->shoppingCart->contents[$products_id]['attributes_values'][$option] = $attr_value;
            }
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
    } else {
      $products_id = (int)$_POST['products_id'];
      $this->product_stock = zen_get_products_stock($products_id);
      $this->shoppingCart->contents[$products_id] = array('qty' => (convertToFloat($_POST['cart_quantity']) <= 0 ? zen_get_buy_now_qty($products_id) : convertToFloat($_POST['cart_quantity'])));
    }
  }

  /**
   * Prepares the output for the Updater's sidebox display
   *
   */
  protected function getSideboxContent()
  {
    global $currencies, $db;

    $out = array();
    $products = $this->shoppingCart->get_products();
    for ($i = 0, $n = count($products); $i < $n; $i++) {

      $product_check = $db->Execute("SELECT products_tax_class_id
                                     FROM " . TABLE_PRODUCTS . "
                                     WHERE products_id = " . (int)$products[$i]['id'] . "
                                     LIMIT 1");
      $product = $db->Execute("SELECT products_id, products_price, products_tax_class_id, products_weight,
                                      products_priced_by_attribute, product_is_always_free_shipping, products_discount_type, products_discount_type_from,
                                      products_virtual, products_model
                               FROM " . TABLE_PRODUCTS . "
                               WHERE products_id = " . (int)$products[$i]['id']);

      $prid = $product->fields['products_id'];
      $products_tax = zen_get_tax_rate(0);
      $products_price = $product->fields['products_price'];
      $qty = convertToFloat($products[$i]['quantity']);



      if (is_array($this->shoppingCart->contents[$products[$i]['id']]['attributes'])) {
//    while (isset($this->shoppingCart->contents[$_POST['products_id']]['attributes']) && list($option, $value) = each($this->shoppingCart->contents[$_POST['products_id']]['attributes'])) {
        foreach ($this->shoppingCart->contents[$products[$i]['id']]['attributes'] as $option => $value) {
          $adjust_downloads++;

          $attribute_price = $db->Execute("SELECT *
                                           FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
                                           WHERE products_id = " . (int)$prid . "
                                           AND options_id = " . (int)$option . "
                                           AND options_values_id = " . (int)$value);

          $data = $db->Execute("SELECT products_options_values_name
                                FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . "
                                WHERE products_options_values_id = " . (int)$value);
          $name = $data->fields['products_options_values_name'];

          $new_attributes_price = 0;
          $discount_type_id = '';
          $sale_maker_discount = '';
          $total = 0;

          if ($attribute_price->fields['product_attribute_is_free'] == '1' && zen_get_products_price_is_free((int)$prid)) {
            // no charge for attribute
          } else {
            // + or blank adds
            if ($attribute_price->fields['price_prefix'] == '-') {
              // appears to confuse products priced by attributes
              if ($product->fields['product_is_always_free_shipping'] == '1' || $product->fields['products_virtual'] == '1') {
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
              if ($product->fields['product_is_always_free_shipping'] == '1' || $product->fields['products_virtual'] == '1') {
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
          $qty2 = sprintf('<span class="DPUSideboxQuantity">' . DPU_SIDEBOX_QUANTITY_FRAME . '</span>', convertToFloat($_POST['cart_quantity']));
          if (defined('DPU_SHOW_SIDEBOX_CURRENCY_SYMBOLS') && DPU_SHOW_SIDEBOX_CURRENCY_SYMBOLS == 'false') {
            $decimal_places = $currencies->get_decimal_places($_SESSION['currency']);
            $decimal_point = $currencies->currencies[$_SESSION['currency']]['decimal_point'];
            $thousands_point = $currencies->currencies[$_SESSION['currency']]['thousands_point'];
            /* use of number_format is governed by the instruction from the php manual: 
             *  http://php.net/manual/en/function.number-format.php
             * By providing below all four values, they will be assigned/used as provided above.
             *  At time of this comment, if only one parameter is used below (remove/comment out the comma to the end of $thousands_point)
             *   then just the number will come back with a comma used at every thousands group (ie. 1,000).  
             *  With the first two parameters provided, a comma will be used at every thousands group and a decimal (.) for every part of the whole number.
             *  The only other option to use this function is to provide all four parameters with the third and fourth parameters identifying the
             *   decimal point and thousands group separater, respectively.
             */
            $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, number_format($this->shoppingCart->total, $decimal_places, $decimal_point, $thousands_point));
          } else {
            $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($total, 0 /* ?? Should this tax be applied? zen_get_tax_rate($product_check->fields['products_tax_class_id']) */));
          }
          $out[] = sprintf(DPU_SIDEBOX_FRAME, $name, $total, $qty2);
        }
      }
    } // EOF FOR loop of product

    if (defined('DPU_SHOW_SIDEBOX_CURRENCY_SYMBOLS') && DPU_SHOW_SIDEBOX_CURRENCY_SYMBOLS == 'false') {
      $decimal_places = $currencies->get_decimal_places($_SESSION['currency']);
      $decimal_point = $currencies->currencies[$_SESSION['currency']]['decimal_point'];
      $thousands_point = $currencies->currencies[$_SESSION['currency']]['thousands_point'];
      /* use of number_format is governed by the instruction from the php manual: 
       *  http://php.net/manual/en/function.number-format.php
       * By providing below all four values, they will be assigned/used as provided above.
       *  At time of this comment, if only one parameter is used below (remove/comment out the comma to the end of $thousands_point)
       *   then just the number will come back with a comma used at every thousands group (ie. 1,000).  
       *  With the first two parameters provided, a comma will be used at every thousands group and a decimal (.) for every part of the whole number.
       *  The only other option to use this function is to provide all four parameters with the third and fourth parameters identifying the
       *   decimal point and thousands group separater, respectively.
       */
      $out[] = sprintf('<hr />' . DPU_SIDEBOX_TOTAL_FRAME, number_format($this->shoppingCart->total, $decimal_places, $decimal_point, $thousands_point));
    } else {
      $out[] = sprintf('<hr />' . DPU_SIDEBOX_TOTAL_FRAME, $currencies->display_price($this->shoppingCart->total, 0));
    }

    $qty2 = sprintf('<span class="DPUSideboxQuantity">' . DPU_SIDEBOX_QUANTITY_FRAME . '</span>', convertToFloat($_POST['cart_quantity']));
    $total = sprintf(DPU_SIDEBOX_PRICE_FRAME, $currencies->display_price($this->shoppingCart->total - $global_total, 0));
    array_unshift($out, sprintf(DPU_SIDEBOX_FRAME, DPU_BASE_PRICE, $total, $qty2));

    $this->responseText['sideboxContent'] = implode('', $out);
  }

  /**
   * 
   * @global object $db
   * @global string $request_type
   */
  function setCurrentPage()
  {
    global $db, $request_type;

    if (isset($_SESSION['customer_id']) && $_SESSION['customer_id']) {
      $wo_customer_id = $_SESSION['customer_id'];

      $customer_query = "SELECT customers_firstname, customers_lastname
                         FROM " . TABLE_CUSTOMERS . "
                         WHERE customers_id = " . (int)$_SESSION['customer_id'];

      $customer = $db->Execute($customer_query);

      $wo_full_name = $customer->fields['customers_lastname'] . ', ' . $customer->fields['customers_firstname'];
    } else {
      $wo_customer_id = '';
      $wo_full_name = '&yen;' . 'Guest';
    }

    $wo_session_id = zen_session_id();
    $wo_ip_address = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown');
    $wo_user_agent = substr(zen_db_prepare_input($_SERVER['HTTP_USER_AGENT']), 0, 254);

    $page = zen_get_info_page((int)$_GET['products_id']);
    $uri = zen_href_link($page, zen_get_all_get_params(), $request_type);
    if (substr($uri, -1) == '?')
      $uri = substr($uri, 0, strlen($uri) - 1);
    $wo_last_page_url = (zen_not_null($uri) ? substr($uri, 0, 254) : 'Unknown');
    $current_time = time();
    $xx_mins_ago = ($current_time - 900);

    // remove entries that have expired
    $sql = "DELETE FROM " . TABLE_WHOS_ONLINE . "
            WHERE time_last_click < '" . $xx_mins_ago . "'";

    $db->Execute($sql);

    $stored_customer_query = "SELECT COUNT(*) AS count
                              FROM " . TABLE_WHOS_ONLINE . "
                              WHERE session_id = '" . zen_db_input($wo_session_id) . "'
                              AND ip_address='" . zen_db_input($wo_ip_address) . "'";

    $stored_customer = $db->Execute($stored_customer_query);

    if (empty($wo_session_id)) {
      $wo_full_name = '&yen;' . 'Spider';
    }

    if ($stored_customer->fields['count'] > 0) {
      $sql = "UPDATE " . TABLE_WHOS_ONLINE . "
              SET customer_id = " . (int)$wo_customer_id . ",
                  full_name = '" . zen_db_input($wo_full_name) . "',
                  ip_address = '" . zen_db_input($wo_ip_address) . "',
                  time_last_click = '" . zen_db_input($current_time) . "',
                  last_page_url = '" . zen_db_input($wo_last_page_url) . "',
                  host_address = '" . zen_db_input($_SESSION['customers_host_address']) . "',
                  user_agent = '" . zen_db_input($wo_user_agent) . "'
              WHERE session_id = '" . zen_db_input($wo_session_id) . "'
              AND ip_address='" . zen_db_input($wo_ip_address) . "'";

      $db->Execute($sql);
    } else {
      $sql = "INSERT INTO " . TABLE_WHOS_ONLINE . " (customer_id, full_name, session_id, ip_address, time_entry, time_last_click, last_page_url, host_address, user_agent)
              VALUES (" . (int)$wo_customer_id . ", '" . zen_db_input($wo_full_name) . "', '" . zen_db_input($wo_session_id) . "', '" . zen_db_input($wo_ip_address) . "', '" . zen_db_input($current_time) . "', '" . zen_db_input($current_time) . "', '" . zen_db_input($wo_last_page_url) . "', '" . zen_db_input($_SESSION['customers_host_address']) . "', '" . zen_db_input($wo_user_agent) . "')";
      $db->Execute($sql);
    }
  }

}

if (!function_exists('convertToFloat')) {

  function convertToFloat($input = 0)
  {
    if ($input === null) {
      return 0;
    }

    $val = preg_replace('/[^0-9,\.\-]/', '', $input);

    // do a non-strict compare here:
    if ($val == 0) {
      return 0;
    }

    return (float)$val;
  }

}

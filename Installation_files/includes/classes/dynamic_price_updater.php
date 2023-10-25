<?php

declare(strict_types=1);
/**
 * Dynamic Price Updater V5.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75) / mc12345678 / torvista
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: 2023 Oct 24
 */
define('DPU_DEBUG', 'false'); //TODO: relocate

/**
 * Dynamic Price Updater V5.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75) / mc12345678 / torvista
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: 2023 Mar 10
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class DPU extends base
{
    /**
     *
     * @param int $products_id
     * @return array
     * @global object $db
     */
    public function getOptionPricedIds(int $products_id): array
    {
        global $db;
        $attribute_price_query = 'SELECT *
                                  FROM ' . TABLE_PRODUCTS_ATTRIBUTES . '
                                  WHERE products_id = ' . $products_id . '
                                  ORDER BY options_id, options_values_price';
        $attribute_price = $db->Execute($attribute_price_query);

        $last_id = 'X';
        $options_id = [];

        //populate array $options_id with only the options_ids that may modify the price
        while (!$attribute_price->EOF) {

            // skip if this options_id has already been captured
            if ($last_id == $attribute_price->fields['options_id']) {
                $attribute_price->MoveNext();
                continue;
            }

            /* Identify an option name that could affect price by:
              having a price that is not zero,
              having quantity prices (though this is not (yet) deconstruct the prices and existing quantity),
              having a price factor that could affect the price,
              is a text field that has a word or letter price.
            */
            if (!(
                    $attribute_price->fields['options_values_price'] == 0 &&
                    !zen_not_null($attribute_price->fields['attributes_qty_prices']) &&
                    !zen_not_null($attribute_price->fields['attributes_qty_prices_onetime']) &&
                    $attribute_price->fields['attributes_price_onetime'] == 0 &&
                    (
                        $attribute_price->fields['attributes_price_factor'] ==
                        $attribute_price->fields['attributes_price_factor_offset']
                    ) &&
                    (
                        $attribute_price->fields['attributes_price_factor_onetime'] ==
                        $attribute_price->fields['attributes_price_factor_onetime_offset']
                    )
                ) ||
                (
                    zen_get_attributes_type($attribute_price->fields['products_attributes_id']) == PRODUCTS_OPTIONS_TYPE_TEXT &&
                    !($attribute_price->fields['attributes_price_words'] == 0 &&
                        $attribute_price->fields['attributes_price_letters'] == 0)
                )
            ) {
                $prefix_format = 'id[:option_id:]';
                $attribute_type = zen_get_attributes_type($attribute_price->fields['products_attributes_id']);

                switch ($attribute_type) {
                    case (PRODUCTS_OPTIONS_TYPE_FILE):
                    case (PRODUCTS_OPTIONS_TYPE_TEXT):
                        $prefix_format = $db->bindVars($prefix_format, ':option_id:', TEXT_PREFIX . ':option_id:', 'noquotestring');
                        break;
                    default:
                        $GLOBALS['zco_notifier']->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_ATTRIBUTE_ID_TEXT', $attribute_price->fields, $prefix_format, $options_id, $last_id);
                }

                $result = $db->bindVars($prefix_format, ':option_id:', $attribute_price->fields['options_id'], 'integer');
                $options_id[$attribute_price->fields['options_id']] = $result;
                $last_id = $attribute_price->fields['options_id'];

                $attribute_price->MoveNext();
                continue;
            }
            $attribute_price->MoveNext();
        }
        return $options_id;
    }
}

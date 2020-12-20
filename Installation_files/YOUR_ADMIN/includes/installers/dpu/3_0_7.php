<?php

/**
 * @package functions
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 thanks to bislewl 6/9/2015
 */
/* 
  V3.0.7, What changed:
    Corrected issue that price of product without attributes was not incorporated/considered because
      the dpu javascript code basically prevented overwriting the price until corrections were made
      in version 3.0.6 that would automatically rewrite the current price display based on what
      dpu determined based on the product.  (This additional feature was added to support display of
      the appropriate information when returning to the product from the shopping cart.)
    Because the price is automatically calculated based on arriving at a product, if a product is
      priced by attributes and not all of the "mandatory" attributes are selected then the price
      was only showing the price point of those that were selected.  The code now calculates the 
      lowest available price based on the selections already made.  The displayed text has not been
      edited to account for this "feature" yet, but could be modified to support the condition.
*/

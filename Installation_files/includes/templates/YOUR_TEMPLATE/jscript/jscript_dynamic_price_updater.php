<?php
//torvista: 158 branch, work in progress

declare(strict_types=1);
/**
 * Dynamic Price Updater V5.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75) / mc12345678 / torvista
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: 2023 Mar 10
 */

if (defined('DPU_STATUS') && DPU_STATUS === 'true') {
    $load = true; // if any of the PHP conditions fail, set to false and prevent any DPU processing
    $pid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
    if (!zen_products_id_valid($pid)) {
        $load = false;
    } elseif (STORE_STATUS > 0 || zen_get_products_price_is_call($pid) || (zen_get_products_price_is_free($pid))) {
        $load = false;
    } else {
        if (!class_exists('DPU')) {
            $dpu_classfile = DIR_FS_CATALOG . DIR_WS_CLASSES . 'dynamic_price_updater.php';
            if (is_file($dpu_classfile)) {
                require $dpu_classfile;
            } else {
                error_log('DPU class file not found: ' . $dpu_classfile);
                $load = false;
            }
        } else {
            $dpu = new DPU();
        }
// Check for conditions that use DPU
        // - quantity box in use
        $products_qty_box_status = zen_products_lookup($pid, 'products_qty_box_status');

        // - quantity not limited to 1
        $products_quantity_order_max = zen_products_lookup($pid, 'products_quantity_order_max');

        // - any attribute options that affect the price. Assign ONLY these option name ids to $optionIds, to subsequently attach events to ONLY these options.
        $optionIds = [];
        // getOptionPricedIds retrieves the attributes that affect price including text boxes.
        if ($load && !($optionIds = $dpu->getOptionPricedIds($pid)) && ($products_qty_box_status === 0 || $products_quantity_order_max === 1)) { // do not reorder this line or $optionIds will not be created
            // If there are none that affect price and the quantity box is not shown, then disable DPU as there is no reason to refresh the price display.
            $load = false;
        }
        /* example array $optionIds
        ([3] => id[3]
         [4] => id[4]) */
    }
    // get the display price html, which could be composed of various spans: $show_normal_price . $show_special_price . $show_sale_price . $show_sale_discount. $free_tag . $call_tag;
    $pidp = zen_get_products_display_price($pid);
    if (empty($pidp) && empty($optionIds)) {
        $load = false;
    }

    if ($load) {
        //TODO relocate this
        if (!defined('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY')) {
            define('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY', 'productDetailsList_product_info_quantity');
        }
        ?>

        <script title="DPU">
            if (typeof console === "undefined") { //if a console is not present, handle the calls, to not break code
                console = {
                    log: function () {
                    },
                    group: function () {
                    },
                    groupEnd: function () {
                    }
                };
            }
            const DPUdebug = ('<?php echo DPU_DEBUG; ?>' === 'true'); // DPU_DEBUG set in class dynamic_price_updater.php
            if (DPUdebug) {
                console.group('DPU_DEBUG on: jscript_dynamic_price_updater.php (set in class)');
            }

            // Set some global vars
            const theFormName = "<?php echo DPU_PRODUCT_FORM; // which form to watch? default: cart_quantity ?>";
            const optionIdsPriced = '"<?php echo implode('", "', $optionIds); ?>"';
            let theForm = false;
            let _secondPrice = "<?php echo(DPU_SECOND_PRICE !== '' ? DPU_SECOND_PRICE : 'false'); //default: cartAdd ?>";
            let objSP = false; // please don't adjust this
            // Updater sidebox settings
            let objSB = false;
            let tmp = ''; // debugging, for multiple reuse
            <?php
            // this holds the sidebox object
            // i.e. Left sidebox false should become document.getElementById('leftBoxContainer');
            // For right sidebox, this should equal document.getElementById('rightBoxContainer');
            // Perhaps this could be added as an additional admin configuration key. The result should end up being that a new SideBox is added
            // before whatever is described in this "search".  So this may actually need to be a div within the left or right boxes instead of the
            // left or right side box.
            // May also be that this it is entirely unnecessary to create a sidebox when one could already exist based on the file structure.

            if (DPU_SHOW_LOADING_IMAGE === 'true') { // create the JS object for the loading image
            ?>
            const imgLoc = 'replace'; // Options are 'replace' or '' (empty)

            let origPrice;
            let loadImg = document.createElement("img");
            loadImg.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
            loadImg.id = "DPULoaderImage";
            loadImg.alt = "<?php echo DPU_LOADING_IMAGE_ALT; ?>";
            // sidebox
            let loadImgSB = document.createElement("img");
            loadImgSB.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
            loadImgSB.id = "DPULoaderImageSB";
            loadImgSB.alt = "<?php echo DPU_LOADING_IMAGE_ALT; ?>";
            loadImgSB.style.margin = "auto";
            <?php } ?>

            // called on initial page load / change of quantity / change of a price-affecting-attribute
            function getPrice() {
                if (DPUdebug) {
                    console.group('<?= __LINE__; ?>: fn: getPrice');
                }

                let pspClass = false;

                <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
                //loadImg/loadImgSB object has already been created
                let psp = false;  // a product special price
                let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; // id of price block (normal/discount/sales...). default: productPrices ?>");
                if (DPUdebug) {
                    console.log('<?= __LINE__; ?>: thePrice (object)=');
                    console.log(thePrice);
                }

                let spanResults = false;

                if (thePrice) {
                    // get any price spans (modifiers/discounts) inside the productPrices block.
                    spanResults = thePrice.getElementsByTagName('span');
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: spanResults=');
                        console.log(spanResults);
                    }
                } else {
                    console.error('DPU_PRICE_ELEMENT_ID "' + '<?php echo DPU_PRICE_ELEMENT_ID; ?>' + '" not found!');
                    return;
                }

                let a;
                let spanCount = spanResults.length; // how many price spans are there ?
                // On initial page load, the default ZC span+text is inside productPrices. eg. <h2 id="productPrices" class="productGeneral">Starting at: <span class="productBasePrice">€329.99</span></h2>
                // so spanCount = 1. But when this span subsequently gets replaced by DPU, the length property of the "spanResults" live htmlcollection subsequently becomes 0. Confusing.
                // Changes of attribute selection result in spanCount = 0

                // parse spans
                for (a = 0; a < spanCount; a++) {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: parsing span ' + (a + 1) + '/' + spanCount);
                    }
                    if (spanResults[a].className === "productSpecialPrice" || spanResults[a].className === "productSalePrice" || spanResults[a].className === "productSpecialPriceSale") {
                        psp = spanResults[a];
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: discount span found, psp=');
                            console.log(psp);
                        }
                    } else {
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: no discount spans found');
                        }
                        //torvista TODO this clause may be necessary
                        /*
                        if (spanResults[a].className === "productBasePrice") {
                            psp = spanResults[a];
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: span "productBasePrice" found, psp=');
                                console.log(psp);
                            }
                        }*/
                    }
                }

                // psp at this point may have a discount span in it. But if not, psp is now loaded with the whole productPrices div containing the productBasePrice span...not the same thing....??
                /*info
                           Core: <h2 id="productPrices" class="productGeneral">Starting at: <span class="productBasePrice">€329.99</span></h2>
                 DPU changes to: <h2 id="productPrices" class="productGeneral">Your price: €329.99</h2>
                */

                // no discount spans were found
                if (!psp) {
                    psp = thePrice;
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: no discount spans found, psp=');
                        console.log(psp);
                    }
                } else {
                    pspClass = psp.className;
                    origPrice = psp.innerHTML; // origPrice could be the complete span of productBasePrice (no discounts) OR the discount price text inside the discount span. Not the same thing!
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: pspClass=' + pspClass + ', origPrice=' + origPrice);
                    }
                }

                if (psp && imgLoc === "replace") { // REPLACE price with loading image
                    loadImg.style.display = "inline"; //'block';
                    let pspStyle = window.getComputedStyle(psp);
                    loadImg.style.height = pspStyle.lineHeight; // Maintains the height so that there is not a vertical shift of the content.
                    /*if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: pspStyle.lineHeight=' + pspStyle.lineHeight + ', pspStyle=');
                            console.log(pspStyle);
                        }*/
                    origPrice = psp.innerHTML;
                    updateInnerHTML(loadImg.outerHTML, false, psp, true);//TODO these parameters correct?
                } else { // APPEND price with loading image
                    document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>").appendChild(loadImg);
                }

                //sidebox
                if (document.getElementById("dynamicpriceupdatersidebox")) {
                    let theSB;
                    theSB = document.getElementById("dynamicpriceupdatersideboxContent");
                    updateInnerHTML("", false, theSB, true); //TODO if first parameter is empty, function does nothing!
                    theSB.style.textAlign = "center";
                    theSB.appendChild(loadImgSB);
                }
                <?php } //eof DPU_SHOW_LOADING IMAGE ?>

                const n = theForm.elements.length;
                let attributes = '';
                let el;
                let i;
                let aName;
                let aValue; //TODO aValue never used

                // parse the elements in the form
                if (DPUdebug) {
                    console.log('<?= __LINE__; ?>: parse theForm="' + theForm.name + '"');
                }
                for (i = 0; i < n; i++) {
                    el = theForm.elements[i];
                    if (DPUdebug) {
                        tmp = $('label[for="' + theForm.elements[i].getAttribute('id') + '"]').html();
                        console.log('<?= __LINE__; ?>: parse element ' + (i + 1) + '/' + n + ' type="' + el.type + '", name="' + el.name + '", label="' + tmp + '"');
                    }
                    //best tested with A Bug's Life "Multi Pak" Special 2003 Collectors Edition for varied attributes
                    switch (el.type) {
                        /* TODO is this even needed as a switch? */
                        case "select": //dropdown
                        /* example for Matrox G200. 3 is Model (Value/Premium), 4 is Memory (4/8/16MB)
                        select name="id[3]", option value="5"
                        select Model Value/Memory 4MB:    attributes=id[3]~5|id[4]~1|
                        select Model Premium/Memory 4MB:  attributes=id[3]~6|id[4]~1|
                        select Model Premium/Memory 8MB:  attributes=id[3]~6|id[4]~2|
                        select Model Premium/Memory 16MB: attributes=id[3]~6|id[4]~3|
                        */
                        case "select-one":
                        case "textarea":
                        case "text": // e.g. "id[txt_10]"
                        case "number":
                        case "hidden":
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "select/select-one/textarea/text/number/hidden" for el.type="' + el.type + '"');
                            }
                            //torvista TODO mod to next line may be necessary
                            //if (el.name.startsWith("id[") && el.value !== '') {
                            if (el.name.startsWith("id[")) { // Ensure not to replace an existing value. i.e. drop a duplicate value.
                                aName = el.name;
                                attributes += aName + '~' + el.value + '|';
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: added attributes="' + attributes + '"');
                                }
                            } else {
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: skipped for name="' + el.name + '"');
                                }
                            }
                            break;

                        case "checkbox":
                        /* e.g.
                        form input for checkbox name="id[1][29]" value="[29]"
                        The code below produces these for checkbox selections:
                        option 1, attribute 29 selected:       attributes=id[1][29]~29|
                        option 1, attribute 29 + 30 selected:  attributes=id[1][29]~29|id[1][30]~30|
                        option 1, attribute 29 + 32 selected:  attributes=id[1][32]~32|id[1][29]~29|
                        option 1, attribute 30 + 32 selected:  attributes=id[1][32]~32|id[1][30]~30|
                        option 1, attribute 29+30+32 selected: attributes=id[1][32]~32|id[1][29]~29|id[1][30]~30|
                        */
                        //torvista TODO: this may be necessary
                        /*
                        if (el.checked === true) { // get the radio that has been selected steve reversed comparison
                            if (el.name.startsWith("id[") && el.value !== '') { // Ensure not to replace an existing value. i.e. drop a duplicate value.
                                aName = el.name; // name is the option name
                                aValue = el.value;
                                aName = aName.replace("[" + el.value + "]", "");
                                attributes += aName + '~' + el.value + '|'; // value is the option value
                            }
                        }
                        break;
                       */
                        //TODO when none selected, aName is undefined

                        case "radio":
                            /* e.g. form input for each radio name="id[1]" value="29"
                            The code below extracts these for radio:
                            option 1, attribute 29 selected: attributes=id[1]~29|
                            option 1, attribute 30 selected: attributes=id[1]~30|
                            option 1, attribute 32 selected: attributes=id[1]~32|
                            */
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "checkbox/radio" for el.type="' + el.type + '"');
                            }
                            if (el.checked === true) { // ensure this checkbox/radio is selected
                                if (el.name.startsWith("id[") && el.value !== '') { // Ensure not to replace an existing value. i.e. drop a duplicate value.
                                    aName = el.name; // name is the option name
                                    attributes += aName + '~' + el.value + '|'; // value is the option value
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: added attributes="' + attributes + '"');
                                    }
                                } else {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: skipped for name="' + el.name + '"');
                                    }
                                }
                            } else {
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: skipped (unchecked) for name="' + el.name + '", label="' + tmp + '"');
                                }
                            }
                            break;
                        default:
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: switch default, no match for el.type="' + el.type + '"');
                            }
                    }
                }
                if (DPUdebug) {
                    console.log('<?= __LINE__; ?>: final attributes="' + attributes + '"');
                }

                // If no default set, on first page load attributes ==='', so DPU call returns 0.
                // Too complex to deal with it in the ajax class, so just clause it here.
                // However, if loading graphic is used, it replaces/appends original before DPU call, then should be updated after DPU call...but this is not done now, so do not use the loading graphic.
                // This needs a rethink like use the loading graphic just prior to the DPU call, inside this clause.
                const products_id = <?php echo $pid; ?>;
                let cartQuantity = $('input[name="cart_quantity"]').val();
                // send data to DPU_Ajax, method=getDetails to process the change and return the new price data to handlePrice
                if (DPUdebug) {
                    console.log('<?= __LINE__; ?>: ajax DPU_Ajax&method=getDetails');
                }
                zcJS.ajax({
                    url: 'ajax.php?act=DPU_Ajax&method=getDetails',
                    data: {
                        products_id: products_id,
                        attributes: attributes,
                        pspClass: pspClass,
                        cart_quantity: cartQuantity
                    }
                }).done(function (resultArray) {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: ajax resultArray ' + JSON.stringify(resultArray));
                    }
                    handlePrice(resultArray);
                }).fail(function () {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: ajax call FAIL');
                    }

                    <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
                    const thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>");
                    let spanResults = thePrice.getElementsByTagName("span");
                    let psp = false;
                    let a;
                    let spanCount = spanResults.length;

                    for (a = 0; a < spanCount; a += 1) {
                        if (spanResults[a].className === "productSpecialPrice" || spanResults[a].className === "productSalePrice" || spanResults[a].className === "productSpecialPriceSale") {
                            psp = spanResults[a];
                        }
                    }
                    //TODO loadImg.parentNode.id exists?
                    if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
                        if (psp) {
                            psp.removeChild(loadImg);
                        } else {
                            thePrice.removeChild(loadImg);
                        }
                    } else if (typeof (loadImg) !== "undefined" && imgLoc === "replace") {
                        updateInnerHTML(origPrice, psp, thePrice);
                    }
                    if (_secondPrice !== false) {
                        updSP();
                    }
                    <?php } ?>
                });
                if (DPUdebug) {
                    console.groupEnd();
                }
            }

            function updateInnerHTML(storeVal, psp, obj, replace) {
                if (DPUdebug) {
                    console.group("<?= __LINE__; ?>: fn: updateInnerHtml");
                    console.log("storeVal=" + storeVal);
                }
                if (typeof (replace) === "undefined") {
                    replace = true;
                }
                if (storeVal !== "") {
                    if (psp) {
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: psp=');
                            console.log(psp);
                        }
                        if (replace) {
                            psp.innerHTML = storeVal;
                        } else {
                            psp.innerHTML += storeVal;
                        }
                    } else {
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: obj=');
                            console.log(obj);
                        }
                        if (replace) {
                            obj.innerHTML = storeVal;
                        } else {
                            obj.innerHTML += storeVal;
                        }
                    }

                    if (_secondPrice !== false) {
                        updSP();
                    }
                }
                if (DPUdebug) {
                    console.groupEnd();
                }
            }

            function handlePrice(results) {
                if (DPUdebug) {
                    console.group('<?= __LINE__; ?>: fn: handlePrice');
                }

                let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: id productPrices contains all price spans ?>");
                //TODO loadImg.parentNode.id exists?
                if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
                    thePrice.removeChild(loadImg);
                }

                // use the spans to see if there is a discount
                let spanResults = thePrice.getElementsByTagName("span");
                let psp = false;
                let a;
                let spanCount = spanResults.length;
                let pdpt = false;

                for (a = 0; a < spanCount; a += 1) {
                    if (spanResults[a].className === "normalprice") {//normalprice is a strikeout, shown when there is another span with a discount
                        pdpt = spanResults[a];
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: found normalprice, pdpt=');
                            console.log(pdpt);
                        }
                    }
                    if (spanResults[a].className === "productSpecialPrice" || spanResults[a].className === "productSalePrice" || spanResults[a].className === "productSpecialPriceSale") {
                        psp = spanResults[a];
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: found discounts, psp=');
                            console.log(psp);
                        }
                    }
                }

                let updateSidebox;
                let sbContent = "";
                let theSB;

                if (document.getElementById("dynamicpriceupdatersidebox")) {
                    theSB = document.getElementById("dynamicpriceupdatersideboxContent");
                    theSB.style.textAlign = "left";
                    updateSidebox = true;
                } else {
                    updateSidebox = false;
                }

                //example: ajax resultArray{
                // "responseType":"success",
                // "data":{
                //   "sideboxContent":"<span class=\"DPUSideBoxName\"></span><span class=\"DPUSideboxQuantity\">&nbsp;x&nbsp;2</span>&nbsp;(19,90&euro;)<br><hr><span class=\"DPUSideboxTotalText\">Total:</span><span class=\"DPUSideboxTotalDisplay\">19,90&euro;</span>",
                //   "priceTotal":"19,90&euro;",
                //   "preDiscPriceTotalText":"",
                //   "preDiscPriceTotal":"19,90&euro;",
                //   "stock_quantity":"4 Units in Stock",
                //   "weight":"0.5"
                //   }
                // }
                if (results.responseType === "error") {
                    showErrors();
                } else {
                    //results.responseType === "success"
                    let type;     // key of property
                    let storeVal; // value of property
                    let length;   // size of properties array
                    if (DPUdebug) {
                        length = Object.keys(results.data).length;
                        tmp = 1; // debugging iteration count
                    }
                    for (type of Object.keys(results.data)) {
                        storeVal = results.data[type];
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: parsing responseType.data ' + tmp + '/' + length + ', type="' + type + '"');
                            tmp++;
                        }

                        // The 'type' (array key) attribute defines what type of information is being returned
                        switch (type) {

                            case "preDiscPriceTotal":
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: case match "preDiscPriceTotal"' + "\n" + 'storeVal=' + storeVal);
                                }
                                if (pdpt) {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: pdpt=' + pdpt);
                                    }
                                    // a normalprice/strikeout span exists
                                    // TODO check is it replace or in front of?
                                    // this replaces the undiscounted/original price text inside the span normalprice strikeout
                                    // this replaces any TEXT in front of the span normalprice: use the observer NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_PSP_CLASS to maintain any custom texts
                                    updateInnerHTML(storeVal, pdpt, thePrice, true);
                                } else {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: pdpt="' + pdpt + '", no update');
                                    }
                                }
                                break;

                            case "preDiscPriceTotalText":
                                if (pdpt) {
                                    // a normalprice/strikeout span exists
                                    // TODO check is it replace or in front of?
                                    // this replaces the undiscounted/original price text inside the span normalprice strikeout
                                    // this replaces any TEXT in front of the span normalprice: use the observer NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_PSP_CLASS to maintain any custom texts
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: case match "preDiscPriceTotalText" (normalprice)' + "\n" + 'storeVal=' + storeVal + ', node replacement');
                                    }
                                    if (thePrice.firstChild.nodeType === 3) {//3 is text
                                        thePrice.firstChild.nodeValue = storeVal;
                                    }
                                }
                                break;

                            case "priceTotal": //the final/actual/total price located in span "productSpecialPrice"/ "productSalePrice"/ "productSpecialPriceSale"
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: case match "priceTotal"' + "\n" + 'storeVal=' + storeVal + "\npsp=");
                                    console.log(psp);
                                }
                                updateInnerHTML(storeVal, psp, thePrice, true);
                                break;

                            case "quantity":
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: case match "quantity"' + "\n" + 'storeVal=' + storeVal);
                                }
                                updateInnerHTML(storeVal, psp, thePrice, false);
                                break;

                            case "weight":
                                let theWeight = document.getElementById("<?php echo DPU_WEIGHT_ELEMENT_ID; ?>");
                                if (theWeight) {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: case match "weight"' + "\n" + 'storeVal=' + storeVal);
                                    }
                                    updateInnerHTML(storeVal, false, theWeight, true);
                                } else {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: no weight displayed, no update');
                                    }
                                }
                                break;

                            case "sideboxContent":
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: case match "sideboxContent"' + "\n" + 'storeVal=' + storeVal);
                                }
                                if (updateSidebox) {
                                    sbContent += storeVal;
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: updateSidebox=true, storeVal appended to sbContent');
                                    }
                                } else {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: updateSidebox=false, no action');
                                    }
                                }
                                break;

                            case "stock_quantity":
                                let theStockQuantity = document.getElementById("<?php echo DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY; ?>");
                                if (theStockQuantity) {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: case match "stock_quantity"' + "\n" + 'storeVal=' + storeVal);
                                    }
                                    updateInnerHTML(storeVal, false, theStockQuantity, true);
                                } else {
                                    if (DPUdebug) {
                                        console.log('<?= __LINE__; ?>: no stock quantity displayed, no update');
                                    }
                                }
                                break;

                            default:
                                if (DPUdebug) {
                                    console.log('<?= __LINE__; ?>: switch default, no match for type="' + type + '"');
                                }
                        }
                    }
                }
                //TODO why is this not in the test above?
                if (updateSidebox) {
                    updateInnerHTML(sbContent, false, theSB, true);
                }

                if (DPUdebug) {
                    console.log('<?= __LINE__; ?>: END fn handlePrice');
                    console.groupEnd();
                }
            }

            // adjust the second price display; create the div if necessary
            function updSP() {
                if (DPUdebug) {
                    console.group('<?= __LINE__; ?>: fn: updSP');
                }

                let flag = false; // error tracking flag
                if (_secondPrice !== false) { // second price is active
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: parsing secondPrice');
                    }
                    //TODO centre never used?
                    let centre = document.getElementById("productGeneral");
                    let temp = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>");
                    //TODO temp contains the loading image with id=DPULoaderImage: duplicate id. Removing the id completely does not appear to affect functionality.
                    let itemp = document.getElementById(_secondPrice);
                    flag = false;

                    if (objSP === false) { // create the second price object
                        if (!temp || !itemp) {
                            flag = true;
                        }
                        if (!flag) {
                            objSP = temp.cloneNode(true);
                            objSP.id = temp.id + "Second";
                            itemp.parentNode.insertBefore(objSP, itemp.nextSibling);
                        }
                    }
                    objSP.innerHTML = temp.innerHTML;
                } else {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: parsing secondPrice skipped');
                    }
                }
                if (DPUdebug) {
                    console.groupEnd();
                }
            }

            // create the sidebox for the attributes info display
            function createSB() {
                if (DPUdebug) {
                    console.group('<?= __LINE__; ?>: fn: createSB');
                }
                if (!(document.getElementById("dynamicpriceupdatersidebox")) && objSB) {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: creating sidebox');
                    }
                    let tempC = document.createElement("div");
                    tempC.id = "dynamicpriceupdatersideboxContent";
                    tempC.className = "sideBoxContent";
                    tempC.innerHTML = "If you can read this Chrome has broken something";
                    objSB.appendChild(tempC);
                    //TODO review temp/tempC error on next line
                    temp.parentNode.insertBefore(objSB, temp);
                } else {
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: sidebox creation skipped');
                    }
                }
                if (DPUdebug) {
                    console.groupEnd();
                }
            }

            function showErrors() {
                let alertText = '';
                let errVal;
                let errorText;
                let i;
                //TODO check responseJSON available?
                errorText = this.responseJSON.responseText;

                for (i in errorText) {
                    if (!(errorText.hasOwnProperty(i))) {
                        continue;
                    }
                    errVal = i;
                    alertText += "\n- " + errVal;
                }
                alert("Error! Message reads:\n\n" + alertText);
            }

            function init() { // called by on_load_dpu.js
                if (DPUdebug) {
                    console.group('<?= __LINE__; ?>: fn: init');
                    console.log('searching for form name "' + theFormName + '"');
                }
                let selectName;
                let n = document.forms.length; // get the number of forms on the page
                let i;
                for (i = 0; i < n; i++) { // parse the forms to find which one is cart_quantity
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: parsing form ' + (i + 1) + '/' + n + ', name "' + document.forms[i].name + '"');
                    }
                    // matching form name found
                    if (document.forms[i].name === theFormName) {
                        theForm = document.forms[i]; // get the cart_quantity form data
                        if (DPUdebug) {
                            console.log('<?= __LINE__; ?>: theFormName "' + theFormName + '" FOUND:');
                            console.log(theForm);
                        }
                        break;
                    }
                }
                // NO matching form name found
                if (theForm === false) {
                    console.error('<?= __LINE__; ?>: theFormName "' + theFormName + '" NOT FOUND')
                }

                n = theForm.elements.length;
                // parse the elements that the form contains, assign an appropriate event to the element to be triggered on a change of that element
                for (i = 0; i < n; i++) {
                    // TODO: identify and ignore attributes that do not affect the price. Currently ANY change triggers the ajax call and the ignoring is done in zcDPU_Ajax. It would be more performant to do the filtering here.
                    if (DPUdebug) {
                        console.log('<?= __LINE__; ?>: parsing form "' + theFormName + '", element ' + i + ', type="' + theForm.elements[i].type + '"');
                    }

                    switch (theForm.elements[i].type) {
                        //TODO: add type "select-multiple"?
                        case "select": // TODO does this type "select" exist?: https://developer.mozilla.org/en-US/docs/Web/API/HTMLSelectElement/type
                        case "select-one":
                            // Drop-down: only one value may be selected
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "select/select-one"');
                            }
                            selectName = theForm.elements[i].getAttribute('name');
                            if (optionIdsPriced.indexOf(selectName) !== -1) {
                                theForm.elements[i].addEventListener("change", function () {
                                    getPrice();
                                });
                                if (DPUdebug) {
                                    tmp = $('label[for="' + theForm.elements[i].getAttribute('id') + '"]').html();
                                    console.log('<?= __LINE__; ?>: added EventListener to name="' + selectName + '", label="' + tmp + '"');
                                }
                            }
                            break;

                        case "textarea":
                        case "text":
                            // Text-field e.g. quantity for Add to Cart
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "textarea/text"');
                            }
                            selectName = theForm.elements[i].getAttribute('name');
                            //TODO test this simplification against main branch...was a devious mixed php/js clause
                            if (optionIdsPriced.indexOf(selectName) !== -1 || selectName === theFormName) {
                                theForm.elements[i].addEventListener("input", function () {
                                    getPrice();
                                });
                                if (DPUdebug) {
                                    tmp = $('label[for="' + theForm.elements[i].getAttribute('id') + '"]').html();
                                    console.log('<?= __LINE__; ?>: added EventListener to name="' + selectName + '", label="' + tmp + '"');
                                }
                            }
                            break;

                        case "checkbox": // e.g. checkbox: name="id[1][15]"
                        case "radio":    // e.g.    radio: name="id[1]"
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "checkbox/radio"');
                            }
                            selectName = theForm.elements[i].getAttribute('name');
                            if (theForm.elements[i].type === "checkbox") {
                                selectName = selectName.substring(0, selectName.indexOf("]") + 1);
                            }
                            if (optionIdsPriced.indexOf(selectName) !== -1) { // e.g. if (["id[1]"].indexOf(selectName) !== -1
                                theForm.elements[i].addEventListener("click", function () {
                                    getPrice();
                                });
                                if (DPUdebug) {
                                    tmp = $('label[for="' + theForm.elements[i].getAttribute('id') + '"]').html();
                                    console.log('<?= __LINE__; ?>: added EventListener to name="' + selectName + '", label="' + tmp + '"');
                                }
                            }
                            break;

                        case "number":
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: case match "number"');
                            }
                            //TODO selectName assigment was missing: bugfix
                            selectName = theForm.elements[i].getAttribute('name');
                            if (optionIdsPriced.indexOf(selectName) !== -1) {
                                theForm.elements[i].addEventListener("change", function () {
                                    getPrice();
                                });
                                theForm.elements[i].addEventListener("keyup", function () {
                                    getPrice();
                                });
                                theForm.elements[i].addEventListener("input", function () {
                                    getPrice();
                                });
                                if (DPUdebug) {
                                    tmp = $('label[for="' + theForm.elements[i].getAttribute('id') + '"]').html();
                                    console.log('<?= __LINE__; ?>: added EventListener to name="' + selectName + '", label="' + tmp + '"');
                                }
                            }
                            break;

                        default:
                            if (DPUdebug) {
                                console.log('<?= __LINE__; ?>: switch default, no match for "' + theForm.elements[i].type + '"');
                            }
                    } //eof switch
                } //eof end of parse form elements
                //torvista TODO review (unnecessary?) sidebox creation
                //if (document.getElementById("dynamicpriceupdatersidebox") && typeof objSB === 'undefined') {
                createSB();
                //}
                // getPrice on initial page load
                getPrice();
                if (DPUdebug) {
                    console.groupEnd();
                }
            }
        </script>
        <?php
    }
}

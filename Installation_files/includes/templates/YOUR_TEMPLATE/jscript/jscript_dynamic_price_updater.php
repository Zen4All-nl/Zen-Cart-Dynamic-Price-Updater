<?php
declare(strict_types=1);
/**
 * @package Dynamic Price Updater
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @version 5.0
 * @licence This module is released under the GNU/GPL licence
 */

if (defined('DPU_STATUS') && DPU_STATUS === 'true') {
    $load = true; // if any of the PHP conditions fail, set to false and prevent any DPU processing
    $pid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
    if ($pid == 0) {
        $load = false;
    } elseif (zen_get_products_price_is_call($pid) || (zen_get_products_price_is_free($pid) && empty($optionIds)) || STORE_STATUS > 0) {
        $load = false;
    } else {
        if (!class_exists('DPU')) {
            if (is_file(DIR_FS_CATALOG . DIR_WS_CLASSES . 'dynamic_price_updater.php')) {
                require DIR_FS_CATALOG . DIR_WS_CLASSES . 'dynamic_price_updater.php';
            } else {
                $load = false;
            }
        }

        if (class_exists('DPU')) {
            $dpu = new DPU();
        }
// Check for conditions that use DPU.
        $optionIds = [];

        // - quantity box in use
        $products_qty_box_status = zen_products_lookup($pid, 'products_qty_box_status');
// - quantity not limited to 1
        $products_quantity_order_max = zen_products_lookup($pid, 'products_quantity_order_max');
// - any attribute options that affect the price. Assign ONLY these option name ids to $optionIds, to subsequently attach events to ONLY these options.
        if ($load && !($optionIds = $dpu->getOptionPricedIds($pid)) && ($products_qty_box_status == 0 || $products_quantity_order_max == 1)) {
            // Checks for attributes that affect price including if text boxes.  If there are none that affect price and the quantity
            //   box is not shown, then go ahead and disable DPU as there is nothing available to adjust/modify price.
            $load = false;
        }
        /* example $optionIds
        Array
        (
            [3] => id[3]
            [4] => id[4]
         )
       */
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

            // Set some global vars
            const theFormName = "<?php echo DPU_PRODUCT_FORM; ?>";
            let theForm = false;
            let _secondPrice = <?php echo(DPU_SECOND_PRICE !== '' ? '"' . DPU_SECOND_PRICE . '"' : 'false'); //default: cartAdd ?>;
            let objSP = false; // please don't adjust this
            // Updater sidebox settings
            let objSB = false;
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
            const imgLoc = "replace"; // Options are "replace" or , "" (empty)

            let origPrice;
            let loadImg = document.createElement("img");
            loadImg.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
            loadImg.id = "DPULoaderImage";

            let loadImgSB = document.createElement("img");
            loadImgSB.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
            loadImgSB.id = "DPULoaderImageSB";
            loadImgSB.style.margin = "auto";
            // loadImg.style.display = 'none';
            <?php } ?>
            // called on initial page load / change of quantity / change of price-affecting-attribute
            function getPrice() {

                let pspClass = false;

                <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
                //loadImg/loadImgSB object has already been created
                let psp = false;
                let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; // id of price block (normal/discount/sales...). default: productPrices ?>");

                let test = false;
                //TODO wrap all code in this initial test  
                if (thePrice) {
                     // get the price spans (modifiers/discounts) inside the productPrices block 
                    test = thePrice.getElementsByTagName("span");
                }

                let a;
                let b = test.length; // how many price spans are there ?
                // On initial page load, the default ZC span+text is inside productPrices. eg. "Starting at: <span class="productBasePrice">&euro;14.99</span>
                // so b = 1. But when this span subsequently gets replaced by DPU, the length property of the "test" live htmlcollection subsequently becomes 0. Confusing.
                // Changes of attribute selection result in b = 0

                // parse spans
                for (a = 0; a < b; a += 1) {
                    if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
                        psp = test[a];
                    }
                }

                //psp at this point may have a discount span in it. But if not, psp is loaded with the whole productPrices div containing the productBasePrice span...not the same thing....??

                // no discount spans were found
                if (!psp) {
                    psp = thePrice;
                }
                if (psp) {
                    pspClass = psp.className;
                    origPrice = psp.innerHTML; // origPrice could be the complete span of productBasePrice (no discounts) OR the discount price text inside the discount span. Not the same thing!
                }

                if (psp && imgLoc === "replace") { // REPLACE price with loading image
                    if (thePrice) {
                        loadImg.style.display = "inline"; //'block';
                        let pspStyle = psp.currentStyle || window.getComputedStyle(psp);
                        loadImg.style.height = pspStyle.lineHeight; // Maintains the height so that there is not a vertical shift of the content.
                        origPrice = psp.innerHTML;
                        updateInnerHTML(loadImg.outerHTML, false, psp, true);
                    }

                } else { // APPEND price with loading image
                    document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>").appendChild(loadImg);
                }
//sidebox
                let theSB;
                if (document.getElementById("dynamicpriceupdatersidebox")) {
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

                // parse the elements in the form 
                for (i = 0; i < n; i += 1) {
                    el = theForm.elements[i];
                    //best tested with A Bug's Life "Multi Pak" Special 2003 Collectors Edition for varied attributes
                    switch (el.type) {
                        /* I'm not sure this even needed as a switch; testing needed*/
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
                            if (el.name.startsWith("id[")) { // Ensure not to replace an existing value. I.e. drop a duplicate value.
                                aName = el.name;
                                attributes += aName + '~' + el.value + '|';
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

                        case "radio":
                            /* e.g. form input for each radio name="id[1]" value="29"
                            The code below extracts these for radio:
                            option 1, attribute 29 selected: attributes=id[1]~29|
                            option 1, attribute 30 selected: attributes=id[1]~30|
                            option 1, attribute 32 selected: attributes=id[1]~32|
                            */
                            if (true === el.checked) { // get the radio that has been selected
                                if (!(el.name in attributes) && el.name.startsWith("id[")) { // Ensure not to replace an existing value. I.e. drop a duplicate value.
                                    aName = el.name; // name is the option name
                                    attributes += aName + '~' + el.value + '|'; // value is the option value
                                }
                            }
                            break;
                    }
                }

                    // If no default set, on first page load attributes ==='', so DPU call returns 0.
                    // Too complex to deal with it in the ajax class, so just clause it here.
                    // However, if loading graphic is used, it replaces/appends original before DPU call, then should be updated after DPU call...but this is not done now, so do not use the loading graphic.
                    // This needs a rethink like use the loading graphic just prior to the DPU call, inside this clause.
                const products_id = <?php echo (int)$pid; ?>;
                let cartQuantity = $('input[name="cart_quantity"]').val();
                    // send data to DPU_Ajax, method=getDetails to process the change and return the new price data to handlePrice
                zcJS.ajax({
                    url: 'ajax.php?act=DPU_Ajax&method=getDetails',
                    data: {
                        products_id: products_id,
                        attributes: attributes,
                        pspClass: pspClass,
                        cart_quantity: cartQuantity
                    }
                }).done(function (resultArray) {
                    handlePrice(resultArray);
                }).fail(function () {
                    <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
                    const thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>");
                    let test = thePrice.getElementsByTagName("span");
                    let psp = false;
                    let a;
                    let b = test.length;

                    for (a = 0; a < b; a += 1) {
                        if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
                            psp = test[a];
                        }
                    }

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
                    //alert("Status returned - " + textStatus);
                });
            }

            function updateInnerHTML(storeVal, psp, obj, replace) {
                if (typeof (replace) === "undefined") {
                    replace = true;
                }
                if (storeVal !== "") {
                    if (psp) {
                        if (replace) {
                            psp.innerHTML = storeVal;
                        } else {
                            psp.innerHTML += storeVal;
                        }
                    } else {
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
            }

            function handlePrice(results) {

                var thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
                if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
                    thePrice.removeChild(loadImg);
                }

                // use the spans to see if there is a discount
                let test = thePrice.getElementsByTagName("span");
                let psp = false;
                let a;
                let b = test.length;
                let pdpt = false;

                for (a = 0; a < b; a += 1) {
                    if (test[a].className === "normalprice") {//normalprice is a strikeout, shown when there is another span with a discount
                        pdpt = test[a];
                    }
                    if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
                        psp = test[a];
                    }
                }

                let updateSidebox;
                let type = results.responseType;
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
                // "sideboxContent":"<span class=\"DPUSideBoxName\"></span><span class=\"DPUSideboxQuantity\">&nbsp;x&nbsp;2</span>&nbsp;(19,90&euro;)<br><hr><span class=\"DPUSideboxTotalText\">Total: </span><span class=\"DPUSideboxTotalDisplay\">19,90&euro;</span>",
                // "priceTotal":"19,90&euro;","preDiscPriceTotalText":"","preDiscPriceTotal":"19,90&euro;","stock_quantity":"4 Units in Stock","weight":"0.5"}
                // }
                if (type === "error") {
                    showErrors();
                } else {//results.responseType === "success"
                    let temp;
                    temp = results.data;

                    let storeVal;
                    let i;
                    for (i in temp) {
                        type = i;
                        storeVal = temp[i];
                        switch (type) {// the 'type' attribute defines what type of information is being provided

                            case "preDiscPriceTotal":
                                if (pdpt) {
                                    // a normalprice/strikeout span exists
                                    // this replaces any TEXT in front of the span normalprice: use the observer NOTIFY_DYNAMIC_PRICE_UPDATER_PREPARE_OUTPUT_PSP_CLASS to maintain any custom texts
                                    updateInnerHTML(storeVal, pdpt, thePrice, true);
                                }
                                break;

                            case "preDiscPriceTotalText":
                                if (pdpt) {
                                    // a normalprice/strikeout span exists
                                    // this replaces the undiscounted/original price text inside the span normalprice strikeout
                                    if (thePrice.firstChild.nodeType === 3) {
                                        thePrice.firstChild.nodeValue = storeVal;
                                    }
                                }
                                break;

                            case "priceTotal": //the final/actual/total price located in span "productSpecialPrice"/ "productSalePrice"/ "productSpecialPriceSale"
                                updateInnerHTML(storeVal, psp, thePrice, true);
                                break;

                            case "quantity":
                                updateInnerHTML(storeVal, psp, thePrice, false);
                                break;

                            case "weight":
                                var theWeight = document.getElementById("<?php echo DPU_WEIGHT_ELEMENT_ID; ?>");
                                if (theWeight) {
                                    updateInnerHTML(storeVal, false, theWeight, true);
                                }
                                break;


                            case "sideboxContent":
                                if (updateSidebox) {
                                    sbContent += storeVal;
                                }
                                break;
                            case "stock_quantity":
                                var theStockQuantity = document.getElementById("<?php echo DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY; ?>");
                                if (theStockQuantity) {
                                    updateInnerHTML(storeVal, false, theStockQuantity, true);
                                }
                                break;
                        }
                    }
                }
                if (updateSidebox) {//TODO why is this not in the switch above?
                    updateInnerHTML(sbContent, false, theSB, true);
                }
            }

            function updSP() {
                // adjust the second price display; create the div if necessary
                let flag = false; // error tracking flag

                if (_secondPrice !== false) { // second price is active
                    let centre = document.getElementById("productGeneral");
                    let temp = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>");
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
                }
            }
             // create the sidebox for the attributes info display
            function createSB() {
                if (!(document.getElementById("dynamicpriceupdatersidebox")) && objSB) {
                    let tempC = document.createElement("div");
                    tempC.id = "dynamicpriceupdatersideboxContent";
                    tempC.className = "sideBoxContent";
                    tempC.innerHTML = "If you can read this Chrome has broken something";
                    objSB.appendChild(tempC);

                    temp.parentNode.insertBefore(objSB, temp);
                }
            }

            function showErrors() {
                let alertText = "";
                let errVal;
                let errorText;
                let i;

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
                var selectName;
                let n = document.forms.length; // get the number of forms on the page
                let i;
                for (i = 0; i < n; i += 1) { // parse the forms to find which one is cart_quantity

                    if (document.forms[i].name === theFormName) { // matches
                        theForm = document.forms[i]; // get the cart_quantity form data
                    } // TODO what if form not found??
                }

                n = theForm.elements.length;
                //parse the elements that the form contains, and assign an appropriate event to be triggered on a change of the element
                for (i = 0; i < n; i += 1) {
                   // TODO: identify and ignore attributes that do not affect the price. Currently all changes trigger the ajax call and the ignoring is done in zcDPU_Ajax. 
                   // TODO: Here would be an area to potentially identify attribute related items to skip either combining PHP from top or some sort of script detect of the presented html.

                    switch (theForm.elements[i].type) {
                        case "select":
                        case "select-one":
                        <?php if (!empty($optionIds)) { ?>
                            selectName = theForm.elements[i].getAttribute('name');
                            if (["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1) {
                                theForm.elements[i].addEventListener("change", function () {
                                    getPrice();
                                });
                            }
                        <?php } ?>
                            break;
                        case "textarea":
                        case "text":
                            selectName = theForm.elements[i].getAttribute('name');
                            if (<?php if (!empty($optionIds)) { ?>["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1 || <?php } ?>selectName == "<?php echo DPU_PRODUCT_FORM; ?>") {
                                theForm.elements[i].addEventListener("input", function () {
                                    getPrice();
                                });
                            }
                            break;
                        case "checkbox": // e.g. checkbox: name="id[1][15]"
                        case "radio":    // e.g.    radio: name="id[1]"
                        <?php if (!empty($optionIds)) { ?>
                            if (theForm.elements[i].type == "radio") {
                                selectName = theForm.elements[i].getAttribute('name');
                            } else if (theForm.elements[i].type == "checkbox") {
                                selectName = theForm.elements[i].getAttribute('name');
                                selectName = selectName.substring(0, selectName.indexOf("]") + 1);
                            }

                            if (["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1) {//e.g. if (["id[1]"].indexOf(selectName) !== -1
                                theForm.elements[i].addEventListener("click", function () {
                                    getPrice();
                                });
                            }
                        <?php } ?>
                            break;
                        case "number":
                        <?php if (!empty($optionIds)) { ?>
                            if (["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1) {
                                theForm.elements[i].addEventListener("change", function () {
                                    getPrice();
                                });
                                theForm.elements[i].addEventListener("keyup", function () {
                                    getPrice();
                                });
                                theForm.elements[i].addEventListener("input", function () {
                                    getPrice();
                                });
                            }
                        <?php } ?>
                            break;
                    } //eof switch
                } //eof end of parse form elements

                createSB();

                getPrice();
            }
        </script>
        <?php
    }
}

<?php
/**
 * @package Dynamic Price Updater
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @version 4.0
 * @licence This module is released under the GNU/GPL licence
 */
if (defined('DPU_STATUS') && DPU_STATUS === 'true') {
  $load = true; // if any of the PHP conditions fail, set to false and prevent any DPU processing
  $pid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
  if ($pid === 0) {
    $load = false;
  } elseif (STORE_STATUS > 0 || zen_get_products_price_is_call($pid) || (zen_get_products_price_is_free($pid) && empty($optionIds)) ) {
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
// Check that there are conditions that need to trigger DPU:
//  - quantity box in use or quantity not limited to 1
//  - any attribute options that affect the price. Assign only these option name ids to $optionIds, to subsequently attach events to only these options.
// If all these conditions are false, disable DPU.

    $optionIds = [];
    $products_qty_box_status = (int)zen_products_lookup($pid, 'products_qty_box_status');
    $products_quantity_order_max = (int)zen_products_lookup($pid, 'products_quantity_order_max');
      if ($load && !($optionIds = $dpu->getOptionPricedIds($pid)) && ($products_qty_box_status === 0 || $products_quantity_order_max === 1)) {
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
// get the price displayed after modification (e.g. as a special or sales)
  $pidp = zen_get_products_display_price($pid);
  if (empty($pidp) && empty($optionIds)) {
    $load = false;
  }

  if ($load) {
    if (!defined('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY')) {
      define('DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY', 'productDetailsList_product_info_quantity');
    }
    ?>
    <script title="DPU">
 if (typeof console === "undefined") { //if a console is not present, handle the calls, to not break code
     console = {
         log: function() { },
         group: function() { },
         groupEnd: function() { }
     };
 }
      const DPUdebug = <?php echo DPU_DEBUG; ?> === !!'true';
      if (DPUdebug) console.group("DPUdebug: jscript_dynamic_price_updater.php");
      // Set some global vars
      const theFormName = "<?php echo DPU_PRODUCT_FORM; //default: cart_quantity ?>";
      let theForm = false;
      let _secondPrice = <?php echo (DPU_SECOND_PRICE !== '' ? '"' . DPU_SECOND_PRICE . '"' : 'false'); //default: cartAdd ?>;
      let objSP = false; // please don't adjust this
      // Updater sidebox settings
      let objSB = false;
    <?php
// this holds the sidebox object
// i.e. Left sidebox false should become document.getElementById('leftBoxContainer');
// For right sidebox, this should equal document.getElementById('rightBoxContainer');
// Perhaps this could be added as an additional admin configuration key.  The result should end up being that a new SideBox is added
// before whatever is described in this "search".  So this may actually need to be a div within the left or right boxes instead of the
// left or right side box.
//   May also be that this it is entirely unnecessary to create a sidebox when one could already exist based on the file structure.

    if (DPU_SHOW_LOADING_IMAGE === 'true') { // create the JS object for the loading image 
      ?>
        const imgLoc = "replace"; // Options are "replace" or , "" (empty)

        let origPrice;
        let loadImg = document.createElement("img");
        loadImg.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
        loadImg.id = "DPULoaderImage";
//todo is sidebox in use?
        let loadImgSB = document.createElement("img");
        loadImgSB.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
        loadImgSB.id = "DPULoaderImageSB";
        loadImgSB.style.margin = "auto";
        // loadImg.style.display = 'none';
    <?php } ?>

      function getPrice() { // called on initial page load, every attribute and quantity change
          if (DPUdebug){ console.group("fn: getPrice");}
        let pspClass = false;
    <?php if (DPU_SHOW_LOADING_IMAGE === 'true') { ?>
          let psp = false;
          let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; // id of the displayed price. default: productPrices ?>");

          if (DPUdebug){ console.log("113: thePrice="); console.log(thePrice);}

          let test = false;
          if (thePrice) {
            test = thePrice.getElementsByTagName("span"); // get the possible price modifiers inside the Product Price block
          }

          if (DPUdebug) {console.log("120: test=");console.log(test);}

          let a;
          let b = test.length;
          // On initial page load, the default ZC span+text is inside the Product Price block. eg. "Starting at: <span class="productBasePrice">&euro;14.99</span>
          // so b = 1. But as this span subsequently gets replaced by DPU, the length property of the "test" live htmlcollection subsequently becomes 0. Confusing.
          // Changes of attribute selection result in b = 0

          if (DPUdebug) {console.log("128: b="+b + ", " + "test.length="+test.length);}

          for (a = 0; a < b; a++) { // parse for price modifier spans
            if (test[a].className === "productSpecialPrice" || test[a].className === "productSalePrice" || test[a].className === "productSpecialPriceSale") {
              psp = test[a];
                if (DPUdebug) {console.group("133: loop psp=");console.log(psp);console.groupEnd()}
            }
          }
          if (!psp) { // no span price modifiers found...use the displayed price
            psp = thePrice;
              if (DPUdebug) {console.group("138: psp=");console.log(psp);console.groupEnd()}
          }
          if (psp) {
            pspClass = psp.className;
            origPrice = psp.innerHTML;
              if (DPUdebug) { console.log("143: pspClass=" + pspClass + ", origPrice=" + origPrice);}
          }
          if (psp && imgLoc === "replace") { // REPLACE price with loading image
            if (thePrice) {
              loadImg.style.display = "inline"; //'block';
              let pspStyle = psp.currentStyle || window.getComputedStyle(psp);
              loadImg.style.height = pspStyle.lineHeight; // Maintains the height so that there is not a vertical shift of the content.
              origPrice = psp.innerHTML;
              updateInnerHTML(loadImg.outerHTML, false, psp, true); // replace psp innerHtml with the loading image
            }

          } else {  // APPEND price with loading image
            document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>").appendChild(loadImg);
          }

//for sidebox
          let theSB;//todo theSB should be inside the clause?
          if (document.getElementById("dynamicpriceupdatersidebox")) {
            theSB = document.getElementById("dynamicpriceupdatersideboxContent");
            updateInnerHTML("", false, theSB, true); //todo if first parameter is empty, function does nothing!
            theSB.style.textAlign = "center";
            theSB.appendChild(loadImgSB);
          }

    <?php } //eof DPU_SHOW_LOADING IMAGE ?>

        const n = theForm.elements.length;
        let attributes = '';
        let el;
        let i;
        let aName;
        let aValue;

        for (i = 0; i < n; i++) { // parse the elements in the form
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
              if (el.name.startsWith("id[") && el.value !== '') { // Ensure not to replace an existing value. i.e. drop a duplicate value.
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
                if (true === el.checked) { // get the radio that has been selected
                    if (el.name.startsWith("id[") && el.value !== '') { // Ensure not to replace an existing value. i.e. drop a duplicate value.
                        aName = el.name; // name is the option name
                        aValue = el.value;
                        aName = aName.replace("["+el.value+"]", "");
                        attributes += aName + '~' + el.value + '|'; // value is the option value
                    }
                }
                break;
                  //todo when none selected, aName is undefined

            case "radio":
                /* e.g. form input for each radio name="id[1]" value="29"
                The code below extracts these for radio:
                option 1, attribute 29 selected: attributes=id[1]~29|
                option 1, attribute 30 selected: attributes=id[1]~30|
                option 1, attribute 32 selected: attributes=id[1]~32|
                */
              if (true === el.checked) { // get the radio that has been selected
                if (el.name.startsWith("id[") && el.value !== '') { // Ensure not to replace an existing value. i.e. drop a duplicate value.
                  aName = el.name; // name is the option name
                  attributes += aName + '~' + el.value + '|'; // value is the option value
                }
              }
              break;
          }
        }
          if (DPUdebug) {console.log("237: attributes="+attributes);}

        const products_id = <?php echo (int)$pid; ?>;
        let cartQuantity = $('input[name="cart_quantity"]').val();
        // send data to DPU_Ajax, method=getDetails to process the change and return the new price data to handlePrice
          if (DPUdebug) {console.log("242: ajax DPU_Ajax&method=getDetails");}
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
       if (DPUdebug) {console.log("254: ajax call FAIL");}
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
          if (DPUdebug){console.groupEnd();}
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
        let thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; //default: productPrices ?>");
        if (typeof (loadImg) !== "undefined" && loadImg.parentNode !== null && loadImg.parentNode.id === thePrice.id && imgLoc !== "replace") {
          thePrice.removeChild(loadImg);
        }

        // use the spans to see if there is a discount occuring up in this here house
        let test = thePrice.getElementsByTagName("span");
        let psp = false;
        let a;
        let b = test.length;
        let pdpt = false;

        for (a = 0; a < b; a += 1) {
          if (test[a].className === "normalprice") {
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
        if (type === "error") {
          showErrors();
        } else {
          let temp;
          temp = results.data;

          let storeVal;
          let i;
          for (i in temp) {
            type = i;
            storeVal = temp[i];
            switch (type) {
              // the 'type' attribute defines what type of information is being provided

              case "preDiscPriceTotal":
                if (pdpt) {
                  updateInnerHTML(storeVal, pdpt, thePrice, true);
                }
                break;
              case "preDiscPriceTotalText":
                if (pdpt) {
                  if (thePrice.firstChild.nodeType === 3) {
                    thePrice.firstChild.nodeValue = storeVal;
                  }
                }
                break;
              case "priceTotal":
                updateInnerHTML(storeVal, psp, thePrice, true);
                break;
              case "quantity":
                updateInnerHTML(storeVal, psp, thePrice, false);
                break;
              case "weight":
                let theWeight = document.getElementById("<?php echo DPU_WEIGHT_ELEMENT_ID; ?>");
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
                let theStockQuantity = document.getElementById("<?php echo DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY; ?>");
                if (theStockQuantity) {
                  updateInnerHTML(storeVal, false, theStockQuantity, true);
                }
                break;
            }
          }
        }
        if (updateSidebox) {
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

      function createSB() { // create the sidebox for the attributes info display
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

      function init() { //loaded by body tag
        if (DPUdebug){ console.group("fn: init");}
        let selectName;
        let n = document.forms.length;
        let i;
        for (i = 0; i < n; i++) {
            if (DPUdebug){ console.log('464: loop: form name='+document.forms[i].name);}
            if (document.forms[i].name === theFormName) { //locate the cart_quantity form
            theForm = document.forms[i];
            if (DPUdebug){ console.group('467: theFormName "'+theFormName+'" FOUND:');console.log(theForm);console.groupEnd();}
            break;
          }//todo if form not found??
            if (DPUdebug){ console.log('470: theFormName "'+theFormName+'" NOT FOUND')}
        }

        n = theForm.elements.length;
        for (i = 0; i < n; i++) { //parse the elements that the form may contain, and assign an appropriate event to be triggered on a change of the element
          //todo: identify and ignore attributes that do not affect the price. Currently all changes trigger the ajax call and the ignoring is done in zcDPU_Ajax.

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
              if (<?php if (!empty($optionIds)) { ?>["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1 || <?php } ?>selectName === "<?php echo DPU_PRODUCT_FORM; ?>") {
                theForm.elements[i].addEventListener("input", function () {
                  getPrice();
                });
              }
              break;
            case "checkbox": // e.g. checkbox: name="id[1][15]"
            case "radio":    // e.g.    radio: name="id[1]"
    <?php if (!empty($optionIds)) { ?>
                  selectName = theForm.elements[i].getAttribute('name');
                if (theForm.elements[i].type === "checkbox") {
                  selectName = selectName.substring(0, selectName.indexOf("]") + 1);
                }
                if (DPUdebug){ console.log("505: selectName=" + selectName);}
                if (["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1) {//e.g. if (["id[1]"].indexOf(selectName) !== -1
                  theForm.elements[i].addEventListener("click", function () {
                    getPrice();
                  });
                }
    <?php } ?>
              break;
            case "number":
    <?php if (!empty($optionIds)) { ?>
                if (["<?php echo implode('", "', $optionIds); ?>"].indexOf(selectName) !== -1) {//todo selectName is initialised?
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
          if (DPUdebug){ console.groupEnd();}
      }
 if (DPUdebug){console.groupEnd();}
    </script>
    <?php
  }
}

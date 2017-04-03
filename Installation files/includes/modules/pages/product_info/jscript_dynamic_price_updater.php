<?php
/**
 * @package Dynamic Price Updater
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @version 3.0
 * @licence This module is released under the GNU/GPL licence
 */

if (DPU_STATUS == 'true')
{
  $load = true; // if any of the PHP conditions fail this will be set to false and DPU won't be fired up
  $pid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
  if (0==$pid)
  {
    $load = false;
  }
  elseif (zen_get_products_price_is_call($pid) || zen_get_products_price_is_free($pid) || STORE_STATUS > 0)
  {
    $load = false;
  }
  $pidp = zen_get_products_display_price($pid);
  if (empty($pidp)) {
    $load = false;
  }

  if ($load)
  {
?>
<script language="javascript" type="text/javascript">
// <![CDATA[
// Set some global vars
var theFormName = "<?php echo DPU_PRODUCT_FORM; ?>";
var theForm = false;
var theURL = "<?php echo DIR_WS_CATALOG; ?>dpu_ajax.php";
var _secondPrice = <?php echo (DPU_SECOND_PRICE != '' ? '"' . DPU_SECOND_PRICE . '"' : 'false'); ?>;
var objSP = false; // please don't adjust this
var DPURequest = [];
// Updater sidebox settings
var objSB = false; // this holds the sidebox object // IE. Left sidebox false should become document.getElementById('leftBoxContainer');
// For right sidebox, this should equal document.getElementById('rightBoxContainer');
// Perhaps this could be added as an additional admin configuration key.  The result should end up being that a new SideBox is added
// before whatever is described in this "search".  So this may actually need to be a div within the left or right boxes instead of the
// left or right side box.
//   May also be that this it is entirely unnecessary to create a sidebox when one could already exist based on the file structure.

<?php if (DPU_SHOW_LOADING_IMAGE == 'true') { // create the JS object for the loading image ?>
var imgLoc = "replace"; // Options are "replace" or , "" (empty)

var loadImg = document.createElement("img");
loadImg.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
loadImg.id = "DPULoaderImage";

var loadImgSB = document.createElement("img");
loadImgSB.src = "<?php echo DIR_WS_IMAGES; ?>ajax-loader.gif";
loadImgSB.id = "DPULoaderImageSB";
loadImgSB.style.margin = "auto";
// loadImg.style.display = 'none';
<?php } ?>

function objXHR()
{ // scan the function clicked and act on it using the Ajax interthingy
  var url; // URL to send HTTP DPURequests to
  var timer; // timer for timing things
  var XHR; // XMLHttpDPURequest object
  var _responseXML; // holds XML formed responses from the server
  var _responseText; // holds any textual response from the server
  // var DPURequest = []; // associative array to hold DPURequests to be sent

  // DPURequest = new Array();
  this.createXHR();
}

objXHR.prototype.createXHR = function () { // this code has been modified from the Apple developers website
  this.XHR = false;

    // branch for native XMLHttpDPURequest object
    if(window.XMLHttpRequest) { // decent, normal, law abiding browsers
      try { // make sure the object can be created
      this.XHR = new XMLHttpRequest();
        } catch(e) { // it can't
      this.XHR = false;
        }
    // branch for IE/Windows ActiveX version
    } else if(window.ActiveXObject) { // this does stuff too
        var tryNext = false;
        try {
          this.XHR = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(f) {
          tryNext = true;
        }
        if (tryNext) {
          try {
              this.XHR = new ActiveXObject("Microsoft.XMLHTTP");
          } catch(g) {
              this.XHR = false;
          }
        }

    }
};

objXHR.prototype.getData = function(strMode, resFunc, _data) { // send a DPURequest to the server in either GET or POST
  strMode = (strMode.toLowerCase() == "post" ? "post" : "get");
  var _this = this; // scope resolution
  this.createXHR();

  if (this.XHR) {
    this.XHR.onreadystatechange = function () {
      if (_this.XHR.readyState == 4) {
      // only if "OK"
        if (_this.XHR.status == 200) {
          _this.responseXML = _this.XHR.responseXML;
          _this.responseText = _this.XHR.responseText;
          _this.responseHandler(resFunc);
        } else {
          alert("Status returned - " + _this.XHR.statusText);
        }
      }
    };
    this.XHR.open(strMode.toLowerCase(), this.url+(strMode.toLowerCase() == "get" ? "?" + this.compileRequest() : ""), true);
    if (strMode.toLowerCase() == "post") {
      this.XHR.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    }
    this.XHR.send(_data);
  } else {
    var mess = "I couldn't contact the server!\n\nIf you use IE please allow ActiveX objects to run";
    alert (mess);
  }
};

objXHR.prototype.compileRequest = function () {
  // parse the DPURequest array into a URL encoded string
  var ret = ""; // return DPURequest string

  for (var e in DPURequest) {
    ret += e + "=" + DPURequest[e] + "&";
  }

  return (ret.substr(0, ret.length - 1));
};

objXHR.prototype.responseHandler = function (theFunction) { // redirect responses from the server to the right function
  DPURequest = new Array();
  this[theFunction](); // Eliminates concern of improper evaluation; however, does limit the response value(s)
};

objXHR.prototype.getPrice = function () {
    <?php if (DPU_SHOW_LOADING_IMAGE == 'true') { ?>

    var psp = false;
    if (imgLoc == "replace") {
      var thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
      var test = thePrice.getElementsByTagName("span");

      for (var a=0,b=test.length; a<b; a++) {
        if (test[a].className == "productSpecialPrice" || test[a].className == "productSalePrice" || test[a].className == "productSpecialPriceSale") {
          psp = test[a];
        }
      }
    }
    if (psp && imgLoc == "replace") {
      if (thePrice) {
        loadImg.style.display = "inline"; //'block';
        loadImg.style.height = "23px"; // Maintains the height so that there is not a vertical shift of the content.
        psp.innerHTML = loadImg.outerHTML;
      }

    } else {
      document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>").appendChild(loadImg);
    }

    if (document.getElementById("dynamicpriceupdatersidebox")) {
        var theSB = document.getElementById("dynamicpriceupdatersideboxContent");
        theSB.innerHTML = "";
        theSB.style.textAlign = "center";
        theSB.appendChild(loadImgSB);
    }
    <?php } ?>
  this.url = theURL;
  var n=theForm.elements.length;
  var temp = "";
  for (var i=0; i<n; i++) {
    var el = theForm.elements[i];
    switch (el.type) { <?php // I'm not sure this even needed as a switch; testing needed ?>
      case "select":
      case "select-one":
      case "text":
      case "number":
      case "hidden":
        temp += el.name+"="+encodeURIComponent(el.value)+"&";

        break;
      case "checkbox":
      case "radio":
        if (true == el.checked) {
          temp += el.name+"="+encodeURIComponent(el.value)+"&";
        }
        break;
    }
  }
  temp = temp.substr(0, temp.length - 1)
  this.getData("post", "handlePrice", temp);
};

objXHR.prototype.handlePrice = function () {
  var thePrice = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
  if (loadImg !== undefined && loadImg.parentNode != null && loadImg.parentNode.id == thePrice.id && imgLoc != "replace") {
    thePrice.removeChild(loadImg);
  }

  // use the spans to see if there is a discount occuring up in this here house
  var test = thePrice.getElementsByTagName("span");
  var psp = false;

  for (var a=0,b=test.length; a<b; a++) {
    if (test[a].className == "productSpecialPrice" || test[a].className == "productSalePrice" || test[a].className == "productSpecialPriceSale") {
      psp = test[a];
    }
  }

  var type = this.responseXML.getElementsByTagName("responseType")[0].childNodes[0].nodeValue;
    if (document.getElementById("dynamicpriceupdatersidebox")) {
        var theSB = document.getElementById("dynamicpriceupdatersideboxContent");
        theSB.style.textAlign = "left";
        var sbContent = "";
        updateSidebox = true;
    } else {
        updateSidebox = false;
    }
  if (type == "error") {
    this.showErrors();
  } else {
    var temp = this.responseXML.getElementsByTagName("responseText");
    for(var i=0, n=temp.length; i<n; i++) {
      var type = temp[i].getAttribute("type");

      switch (type) {<?php // the 'type' attribute defines what type of information is being provided ?>
        case "priceTotal":
          if (psp) {
            psp.innerHTML = temp[i].childNodes[0].nodeValue;
          } else {
            thePrice.innerHTML = temp[i].childNodes[0].nodeValue;
          }
          if (_secondPrice !== false) {
            this.updSP();
          }
          break;
        case "quantity":
          with (temp[i].childNodes[0]) {
            if (nodeValue != "") {
              if (psp) {
                psp.innerHTML += nodeValue;
              } else {
                thePrice.innerHTML += nodeValue;
              }

              this.updSP();
            }
          }
          break;
        case "weight":
          var theWeight = document.getElementById("<?php echo DPU_WEIGHT_ELEMENT_ID; ?>");
          if (theWeight) {
            theWeight.innerHTML = temp[i].childNodes[0].nodeValue;
          }
          break;
        case "sideboxContent":
          if (updateSidebox) {
            sbContent += temp[i].childNodes[0].nodeValue;
          }
          break;
      }
    }
  }
  if (updateSidebox) {
    theSB.innerHTML = sbContent;
  }
};

objXHR.prototype.updSP = function () {
  // adjust the second price display; create the div if necessary
  var flag = false; // error tracking flag

  if (_secondPrice !== false) { // second price is active
    var centre = document.getElementById("productGeneral");
    var temp = document.getElementById("<?php echo DPU_PRICE_ELEMENT_ID; ?>");
    var itemp = document.getElementById(_secondPrice);

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
};
<?php
$show_dynamic_price_updater_sidebox = true;

  if ($show_dynamic_price_updater_sidebox == true)
  {
?>
    objXHR.prototype.createSB = function ()
    { // create the sidebox for the attributes info display
      if (!(document.getElementById("dynamicpriceupdatersidebox")) && objSB)
      {
        var tempC = document.createElement("div");
        tempC.id = "dynamicpriceupdatersideboxContent";
        tempC.className = "sideBoxContent";
        tempC.innerHTML = "If you can read this Chrome has broken something";
        objSB.appendChild(tempC);

        temp.parentNode.insertBefore(objSB, temp);
      }
    };
<?php
  }
?>
objXHR.prototype.showErrors = function () {
  var errorText = this.responseXML.getElementsByTagName("responseText");
  var alertText = "";
  var n=errorText.length;
  for (var i=0; i<n; i++) {
    alertText += "\n- "+errorText[i].childNodes[0].nodeValue;
  }
  alert ("Error! Message reads:\n\n"+alertText);
};

var xhr = new objXHR;

function init() {
  var n=document.forms.length;
  for (var i=0; i<n; i++) {
    if (document.forms[i].name == theFormName) {
      theForm = document.forms[i];
      continue;
    }
  }

  var n=theForm.elements.length;
  for (var i=0; i<n; i++) {
    switch (theForm.elements[i].type) {
      case "select":
      case "select-one":
        theForm.elements[i].addEventListener("change", function () { xhr.getPrice(); });
        break;
      case "text":
        theForm.elements[i].addEventListener("keyup", function () { xhr.getPrice(); });
        break;
      case "checkbox":
      case "radio":
        theForm.elements[i].addEventListener("click", function () { xhr.getPrice(); });
        break;
      case "number":
        theForm.elements[i].addEventListener("change", function () { xhr.getPrice(); });
        theForm.elements[i].addEventListener("keyup", function () { xhr.getPrice(); });
        theForm.elements[i].addEventListener("input", function () { xhr.getPrice(); });
        break;
    }
  }

<?php
  $show_dynamic_price_updater_sidebox = true;

    if ($show_dynamic_price_updater_sidebox == true)
    {
?>
    this.createSB();
<?php
    }
?>
  xhr.getPrice();
};

<?php
// the following statements should allow multiple onload handlers to be applied
// I know this type of event registration is technically deprecated but I decided to use it because I haven't before
// There shouldn't be any fallout from the downsides of this method as only a single function is registered (and in the bubbling phase of each model)
// For backwards compatibility I've included the traditional DOM registration method ?>
<?php /*try { // the IE event registration model
  window.attachEvent('onload', init);
} catch (e) { // W3C event registration model
  window.addEventListener('load', init, false);
} finally {
  window.onload = init;
}*/ ?>
// ]]></script>
<?php
}
}
?>

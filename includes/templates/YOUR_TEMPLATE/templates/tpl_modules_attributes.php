<?php
/**
 * Module Template
 *
 * Template used to render attribute display/input fields
 *
 * @package templateSystem
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Sat Oct 17 21:58:04 2015 -0400 Modified in v1.5.5 $
 * Modified to support products with attributes that are not tracked by SBA.
 * 
 * Stock by Attributes 1.5.4 : mc12345678 15-08-17
 */
?>
<div id="productAttributes">
<?php
    if ($is_SBA_product /*$stock->_isSBA*/ && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && defined('PRODINFO_ATTRIBUTE_DYNAMIC_STATUS') && PRODINFO_ATTRIBUTE_DYNAMIC_STATUS != '0'
        && ((isset($_SESSION['pwas_class2'])
            && method_exists($_SESSION['pwas_class2'], 'zen_sba_dd_allowed')
            && is_callable(array($_SESSION['pwas_class2'], 'zen_sba_dd_allowed')))
              ? $_SESSION['pwas_class2']->zen_sba_dd_allowed($products_options_names)
              : ((function_exists('zen_sba_dd_allowed')) 
                ? zen_sba_dd_allowed($products_options_names)
                : true)
            )
        ) {
      if (!defined('SBA_ZC_DEFAULT')) {
        define('SBA_ZC_DEFAULT','false'); // sets to use the ZC method of HTML tags around attributes.
      }
//      $inSBA_query = "select products_id from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " where products_id = :products_id:";
//      $inSBA_query = $db->bindVars($inSBA_query, ':products_id:', $_GET['products_id'], 'integer');

//      $inSBA = $db->Execute($inSBA_query); // Determine that product is tracked by SBA
//      $prodInSBA = (!$inSBA->EOF && $inSBA->RecordCount() > 0 ? true : false);
      $prodInSBA = (isset($_SESSION['pwas_class2'])
            && method_exists($_SESSION['pwas_class2'], 'zen_product_is_sba')
            && is_callable(array($_SESSION['pwas_class2'], 'zen_product_is_sba'))
              ? $_SESSION['pwas_class2']->zen_product_is_sba($_GET['products_id'])
              : (function_exists('zen_product_is_sba') 
                ? zen_product_is_sba($_GET['products_id'])
                : false)
            );
    } else { 
//      $inSBA = new queryFactoryResult($db->link);
//      $inSBA->EOF = true;
      $prodInSBA = false;
    }

    if ($zv_display_select_option > 0) {
      $products_attributes_query = "select count(distinct products_options_id) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id=:products_id: and patrib.options_id = popt.products_options_id and popt.language_id = :languages_id:";
      $products_attributes_query = $db->bindVars($products_attributes_query, ':products_id:', $_GET['products_id'], 'integer');
      $products_attributes_query = $db->bindVars($products_attributes_query, ':languages_id:', $_SESSION['languages_id'], 'integer');
      $products_attributes = $db->Execute($products_attributes_query);
      $products_attributes_noread_query = "SELECT count(distinct products_options_id) AS total 
         FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id=:products_id:
         AND patrib.options_id = popt.products_options_id
         AND popt.products_options_type != '" . PRODUCTS_OPTIONS_TYPE_READONLY . "'
         AND popt.language_id = :languages_id:"; 
      $products_attributes_noread_query = $db->bindVars($products_attributes_noread_query, ':products_id:', $_GET['products_id'], 'integer');
      $products_attributes_noread_query = $db->bindVars($products_attributes_noread_query, ':languages_id:', $_SESSION['languages_id'], 'integer');
      $products_attributes_noread = $db->Execute($products_attributes_noread_query);
      if ((
        ((defined('PRODINFO_ATTRIBUTE_PLUGIN_MULTI') && ($products_attributes->fields['total'] > 1) 
          && ($products_attributes->fields['total'] - $products_attributes_noread->fields['total'] !== $products_attributes->fields['total']/*0*/) 
          && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '2')) 
           ? file_exists(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI . '.php') 
           : ( (defined('PRODINFO_ATTRIBUTE_PLUGIN_SINGLE') 
              && ($products_attributes->fields['total'] == 1 || ($products_attributes->fields['total'] > 1 && $products_attributes_noread->fields['total'] == 1)) 
              && (PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '1' || PRODINFO_ATTRIBUTE_DYNAMIC_STATUS == '3')) 
               ? file_exists(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE . '.php') 
               : false )) 
        && defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && $prodInSBA/*!$inSBA->EOF*/)) {
        //$products_attributes = $db->Execute("select count(distinct products_options_id) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int) $_GET['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = " . (int) $_SESSION['languages_id'] . "");


        if ($products_attributes->fields['total'] > 1) {

          $products_id = (preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/", $_GET['products_id']) ? $_GET['products_id'] : (int) $_GET['products_id']);
          require(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI . '.php');
          $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_MULTI;

          $pad = new $class($products_id);

          echo $pad->draw();
          $prodInSBA = true;
        } /* END SBA Multi */ elseif ($products_attributes->fields['total'] > 0) {
          $products_id = (preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/", $_GET['products_id']) ? $_GET['products_id'] : (int) $_GET['products_id']);
          require(DIR_WS_CLASSES . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE . '.php');
          $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN_SINGLE;
          $pad = new $class($products_id);
          echo $pad->draw();
        } //End SBA SINGLE
      } //END SBA Specific
      else {
         //$inSBA = new queryFactoryResult($db->link);
         //$inSBA->EOF = true;
         $prodInSBA = false;
         ?>
<h3 id="attribsOptionsText"><?php echo TEXT_PRODUCT_OPTIONS; ?></h3>
<?php } // END NON-SBA SPECIFIC: show please select unless all are readonly ?>

<?php
     } // End display info 
     if (!$prodInSBA /*$inSBA->EOF && $inSBA->RecordCount() < 1*/) {
    for($i=0;$i<sizeof($options_name);$i++) {
?>
<?php
  if ($options_comment[$i] != '' and $options_comment_position[$i] == '0') {
?>
<h3 class="attributesComments"><?php echo $options_comment[$i]; ?></h3>
<?php
  } // END h3_attributes_comment
?>

<div class="wrapperAttribsOptions" id="<?php echo $options_html_id[$i]; ?>">
<h4 class="optionName back"><?php echo $options_name[$i]; ?></h4>
<div class="back"><?php echo "\n" . $options_menu[$i]; ?></div>
<br class="clearBoth" />
</div>


<?php if ($options_comment[$i] != '' and $options_comment_position[$i] == '1') { ?>
    <div class="ProductInfoComments"><?php echo $options_comment[$i]; ?></div>
<?php } // END if Div_options_Comment    
       } // End FOR options_name 
       ?>
       <?php
       // This displays ALL images regardless of attribute stock levels. Comment-out the "echo" if you want to skip images.
       for ($j = 0, $m = sizeof($options_name); $j < $m; $j++) {
if ($options_attributes_image[$j] != '') {
?>
<?php echo $options_attributes_image[$j]; ?>
<?php
} // End If(attributes images)
       } // End For images_Options_name
?>
<br class="clearBoth" />
<?php
    }
?>


<?php
  if ($show_onetime_charges_description == 'true') {
?>
    <div class="wrapperAttribsOneTime"><?php echo TEXT_ONETIME_CHARGE_SYMBOL . TEXT_ONETIME_CHARGE_DESCRIPTION; ?></div>
<?php } ?>


<?php
  if ($show_attributes_qty_prices_description == 'true') {
?>
    <div class="wrapperAttribsQtyPrices"><?php echo zen_image(DIR_WS_TEMPLATE_ICONS . 'icon_status_green.gif', TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK, 10, 10) . '&nbsp;' . '<a href="javascript:popupWindowPrice(\'' . zen_href_link(FILENAME_POPUP_ATTRIBUTES_QTY_PRICES, 'products_id=' . $_GET['products_id'] . '&products_tax_class_id=' . $products_tax_class_id) . '\')">' . TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK . '</a>'; ?></div>
<?php } ?>
</div>

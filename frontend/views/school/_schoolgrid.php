<?php

use yii\helpers\Html;
use yii\helpers\Url;

use common\components\helpers\ElementsHelper;

/**
 * @see school/index.php for @var used
 */

/* --- ONE SCHOOL BLOCK --- */
echo $this->render('_schoolblock', [
  'model' => $model,
  'page' => $page,
]);

/* This input is needed for smooth Packery init after each AJAX call */
echo Html::hiddenInput('page_height', 250, ['id' => 'page_height']);

/* This input is for sending pages */
echo Html::hiddenInput('page', $page, ['id' => 'page']);

/**
 * Filter block 500x250
 * Filter box (small tooltip-alike dropdown)
 * TODO: on that filter @ _newsgrid
 */
if ($categories) {
  echo ElementsHelper::filterBlock('geolocation', Yii::$app->controller->id, $categories);
  echo ElementsHelper::filterBox($categories, 'category[]', Url::toRoute('school/show'), $target_el = '#outstyle_school .school', $include = '#page_height,#school-filter-form');
}

/*
    JS stuff, that is related ONLY to this view
    Used:
    - echoJS for lazy load images:      https://www.npmjs.com/package/echo-js
    - packery for grid layout:          http://packery.metafizzy.co/
    - PreciseTextResize for text:       @frontend/web/js/misc/preciseTextResize.js
    - wayjs for two-way data-binding:   https://github.com/gwendall/way.js

    ---TODO---: Redo this using ic-scroll-offset: http://intercoolerjs.org/attributes/ic-scroll-offset.html
    I also need to mention, that we have some really big stuck with Packery+'scrolled-in-view' event for loading more news
    So the possible solution could be in destroying Packery instance every time after ajax event and setting it up again instead 'reloadItems'
    Otherwise we will get continious AJAX requests.

    jQuery "school" trigger fires after ajaxComplete request when certain Intercooler header was accepted.
    See 'X-IC-Trigger' in 'SchoolController'

    !!! IMPORTANT !!! DON'T FORGET to switch off active events to prevent event binding duplication! (make event .off().on())
*/
?>
<script>
jQuery(document).ready(function () {

  function init_school() {

    way.restore(); // https://github.com/gwendall/way.js - We need to restore our values for page TODO: Do we need to use it here?

    /* --- This stuff is needed for triggering 'scroll-on-view' element, so it could be on a user's viewport! --- TODO: scrolltrigger */
    jQuery('#outstyle_school .school__item--initial').css({'height':50,'width':50,'position':'static'}).hide();

    /* --- We need to initialize Packery at start --- */
    jQuery('#outstyle_school .school')
    .packery({
      itemSelector: '.block__item',
      gutter: 0
    })
    .off('layoutComplete').on('layoutComplete', function() {
      /* --- Fit text size for each Packery block --- */
      jQuery('.block__title').preciseTextResize({
        parent: '.overlay',
        widthOffset: 1,
        heightOffset: 1
      });

      jQuery('.block__item .overlay').show();
      jQuery('#outstyle_school, #school-filter-block--geolocation').css({
        'visibility':'visible'
      });

    })
    .packery('layout')
    .find('.overlay')
    .show();

    /* --- Now to init images lazy loading --- */
    echo.init({offset:1000});

    /* --- Bind some events to this page elements --- */
    jQuery(".school__filter-button").on("click", function() {
      jQuery(this).after(
        jQuery('#filter-box').slideDown('fast')
      );
    });
    jQuery('#school-filter-form input[type=checkbox]').on("change", function() {
      if(this.checked) {
        jQuery(this).next('i').removeClass('zmdi-circle-o').addClass('zmdi-circle');
      } else {
        jQuery(this).next('i').removeClass('zmdi-circle').addClass('zmdi-circle-o');
      }
    });

    /* --- Restoring from way.js storage after some timeout and modifying elements --- */
    var categories = way.get("school.filter.category");
    if (categories) {
      jQuery.each(categories, function(key, value) {
        jQuery("#school-filter-form input[type=checkbox][value="+value+"]").attr("checked", true);
      });
    }
    jQuery("#school-filter-form input[type=checkbox]").each(function() {
      if(this.checked) {
        jQuery(this).next('i').removeClass('zmdi-circle-o').addClass('zmdi-circle');
      }
    });

    /* --- Finally hiding the preloader --- */
    jQuery("#cool_loader").hide();

  }

  init_school();

  /* --- Some bindings to the school view page --- */
  /* off.on is necessary to prevent event duplicate, when getting from another page to this one and back and so on */
  jQuery("body").off("school").on("school", function(event, data) {
    jQuery('#outstyle_school').css({'min-height':data.page_height+'px'});
    if (data.page) {
      jQuery('#page').val(data.page);
    }

    setTimeout(function(){
      jQuery('#outstyle_school .school')
      .packery()
      .packery('destroy');

      init_school();
    },50);
  });

  /* --- Before sending our Intercooler AJAX request, we check for stored values from way.js and pass them too. Also sending our custom values TODO: opt --- */
  jQuery(document).off("beforeAjaxSend.ic").on("beforeAjaxSend.ic", function(event, settings) {

    var countryId = parseInt(jQuery('#geolocation_country').val()),
        schoolsId = jQuery('#geolocation_cities_query').val(),
        page_height = parseInt(jQuery('#content').height());

    settings.data = settings.data+
    '&countryId='+countryId+
    '&schoolsId='+schoolsId+
    '&page_height='+page_height;

    var categories = way.get("school.filter");
    if (categories) {
      categories = jQuery.param(categories);
      settings.data = settings.data+'&'+categories;
    }

    /* --- Also we need to prepend filter containter and filter blocl back to prevent it's disappearing after AJAX call --- */
    jQuery("#filter-box").prependTo("#outstyle_school").hide();
    jQuery("#school-filter-block--geolocation").insertAfter("#outstyle_school");

    /* --- Some neat loader for the news page, showing before each filtering event --- */
    if(jQuery('#cool_loader').length === 0) {
      jQuery("#outstyle_school").before('<img src="/frontend/web/images/images/breakdance_loader.gif" class="school__loader" id="cool_loader">');
    }
  });

});
</script>

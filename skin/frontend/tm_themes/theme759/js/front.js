/**
 * Magento
 *
 */

$j(document).ready(function (){

    // ==============================================
    // Header Switchers (language/currency)
    // ==============================================


    $j('.header-button, .switch-show').not('.top-login').on("click", function(e){
            var ul=$j(this).find('ul')
            if(ul.is(':hidden'))
             ul.slideDown()
             ,$j(this).addClass('active')
            else
             ul.slideUp()
             ,$j(this).removeClass('active')
             $j('.header-button, .switch-show').not(this).removeClass('active'),
             $j('.header-button, .switch-show').not(this).find('ul').slideUp()
             $j('.header-button ul li, .switch-show ul li').click(function(e){
                 e.stopPropagation(); 
                });
                return false
        });

    $j(document).on('click',function(){ 
        var selector = $j('.header-button, .switch-show');
        selector.removeClass('active');
        $j('ul', selector).slideUp();
    });

    $j('body, html, #header', document).on('click', function (event) {

        element = $j(event.target);
        if(element.parents('.skip-link').length == 1 || element.hasClass('skip-link'))
        {
            return;
        }

        var parent = $j('.skip-content');
        var link = parent.siblings('.skip-link');

        if (element.parents('.skip-content').length == 0) {
            parent.removeClass('skip-active');
            link.removeClass('skip-active');
        }

    });

    /* Call functions */
    theme_accordion();

    // ==============================================
    // Login form in Mobile
    // ==============================================
    if(isMobile){
        $j('.skip-links2 .skip-account2').on('click', function(){
            $j('#header-account2').addClass('abs');
            $j("html, body").animate({
                scrollTop: 0
            }, 800);
        });
    }



    // ==============================================
    // Back To Top
    // ==============================================
        $j(function () {
         $j(window).scroll(function () {
          if ($j(this).scrollTop() > 100) {
           $j('#back-top').fadeIn();
          } else {
           $j('#back-top').fadeOut();
          }
         });

         // scroll body to 0px on click
         $j('#back-top a').click(function () {
          $j('body,html').stop(false, false).animate({
           scrollTop: 0
          }, 800);
          return false;
         });
        });


    // ==============================================
    // Stick menu init
    // ==============================================

    /*if(!isMobile) {
        if($j('.main-menu #nav li').length){
            $j('.main-menu').tmStickUp() ;
        }
    };*/

    // ==============================================
    // Additional options
    // ==============================================

    if ($j('.add-options').length){
        $j('.showmore', '.add-options').on('click', function(){
            var wrap = $j(this).parent('.add-options');
            $j('.item-options.additional', wrap).slideToggle(500);
            $j(this).toggleClass('active');
        })
    };

    // ==============================================
    // Remove Cart link form TopLinks
    // ==============================================

    if(jQuery('.top-links-inline').length > 0){
        jQuery('li a.top-link-cart, li a.register-link', '.top-links-inline').parent().remove();

    }


    // ==============================================
    // Superfish Menu
    // ==============================================
    
    jQuery(".skip-nav").on("click", function(){
        jQuery(".sf-menu-phone").slideToggle();
        jQuery(this).toggleClass("active");
    });

    jQuery('li.parent', '.sf-menu-phone').append('<strong></strong>');
    jQuery('.sf-menu-phone li.parent strong').each(function(){
        jQuery(this).on("click", function(){
            if (jQuery(this).attr('class') == 'opened') { 
                jQuery(this).removeClass().parent('li.parent').find('> ul').slideToggle(); 
            } 
                else {
                    jQuery(this).addClass('opened').parent('li.parent').find('> ul').slideToggle();
                }
        });
    })


    // ==============================================
    // Custom Select
    // ==============================================
    $j('.toolbar select, .product-shop .product-options dd:not(.swatch-attr) select:not(.multiselect), #customer-reviews .review-heading .pager .count-container .limiter select, .advanced-search select:not([multiple]), .my-account .pager .limiter select').addClass('dropdown');


    // ==============================================
    // Replacement
    // ==============================================

    if($j('.checkout-onepage-index').length > 0){
        $j('.main-container', '.checkout-onepage-index').prepend($j('.page-title', '.checkout-onepage-index'));
    }

    // ==============================================
    // Equal Height Columns
    // ==============================================

    if(!isMobile){
        if(jQuery('.EqHeightDiv').length > 0){
            equalHeight($j(".EqHeightDiv"));
        }
    }
    

    // ==============================================
    // Animation Skills
    // ==============================================

    if(jQuery('.skills .number').length > 0){
        var number = $j('.skills .number');
        number.each(function(){
            var finish = $j(this).data('finish');
            jQuery(this).animateNumber({ number: finish }, 2000);
        })
    }



    // ==============================================
    // Material Design Click Animation
    // ==============================================

    jQuery(function(){
        var ink, d, x, y;
        jQuery("a.grid, a.list, .sort-by-switcher, .pages a, .button").click(function(e){
        if(jQuery(this).find(".ink").length === 0){
            jQuery(this).prepend("<b class='ink'></b>");
        }
             
        ink = jQuery(this).find(".ink");
        ink.removeClass("animate");
         
        if(!ink.height() && !ink.width()){
            d = Math.max(jQuery(this).outerWidth(), jQuery(this).outerHeight());
            ink.css({height: d, width: d});
        }
         
        x = e.pageX - jQuery(this).offset().left - ink.width()/2;
        y = e.pageY - jQuery(this).offset().top - ink.height()/2;
         
        ink.css({top: y+'px', left: x+'px'}).addClass("animate");
    });
    });


    // ==============================================
    // Product List Gallery Animation
    // ==============================================

    jQuery(function() {
        jQuery('.products-grid li').hover(function(){
            jQuery('.product-thumb', this).each(function(i){
                jQuery(this).delay((i++) * 100).fadeTo(500, 1); 
            })
        },
        function(){
            jQuery('.product-thumb', this).css({'display' : 'none'})
        })
    });
    



    // ==============================================
    // Carousels init
    // ==============================================

    /*testimonials*/

    $j(".owl-testimonials").owlCarousel({
          navigation : true, 
          slideSpeed : 600,
          paginationSpeed : 400,
          singleItem:true,
          pagination: false,
          autoHeight: false
      });
    /*-----------------------*/

    var screenSize = '';

    var upsellSlidesCount = '';
    var relatedSlidesCount = '';

    var moreSlidesCount = '';
    var moreSliderDirection = '';
    var moreSpaceBetween = '';

    var gallerytopSlidesCount = '';

    /*upsell*/
    $j(".up-sell-carousel").owlCarousel({
            items: 4,
            itemsDesktop : [bp.xlarge,4],
            itemsDesktopSmall : [bp.large,3],
            itemsTablet: [bp.medium,2],
            itemsTabletSmall: false,
            itemsMobile : [bp.xsmall,1],
            pagination: false,
            navigation: true,

    });

    /*related*/
    $j(".related-carousel").owlCarousel({
            items: 4,
            itemsDesktop : [bp.xlarge,4],
            itemsDesktopSmall : [bp.large,3],
            itemsTablet: [bp.medium,2],
            itemsTabletSmall: false,
            itemsMobile : [bp.xsmall,1],
            pagination: false,
            navigation: true,

    });

   /*sale*/
    $j(".sale-carousel").owlCarousel({
            items: 4,
            itemsDesktop : [bp.xlarge,4],
            itemsDesktopSmall : [bp.large,3],
            itemsTablet: [bp.medium,2],
            itemsTabletSmall: [bp.medium,2],
            itemsMobile : [bp.xsmall,1],
            pagination: false,
            navigation: true,

    });
            
    /*more-views*/
    if ($j(".more-views-carousel").length) {

        var moreSliderOptions = {
            screenSize: '',
            mode: 'vertical',
            pager: false,
            controls: true,
            slideMargin: 11, 
            minSlides: 5,
            moveSlides: 1,
            maxSlides: 5,
            infiniteLoop: false,
            nextText: '',
            prevText: '',
            slideWidth: 100
            
        }

        var moreViewsSlider = $j('.more-views-carousel').bxSlider(moreSliderOptions);
        
    }

    /*more-views of "center-image" mode*/
    if($j(".gallery-thumbs").length){ 
       
        var galleryThumbsOptions = {
            screenSize: '',
            pager: false,
            controls: true,
            slideMargin: 15, 
            minSlides: 4,
            moveSlides: 1,
            maxSlides: 4,
            infiniteLoop: false,
            nextText: '',
            prevText: '',
            slideWidth: 82
            }
        var galleryThumbsSlider = $j('.gallery-thumbs').bxSlider(galleryThumbsOptions);

    }

    $j(window).on('resize.resize_slider', function(){

        var windowWidth = $j(window).width();

        // More-view carousel

        if($j(".more-views-carousel").length){ 

            if (windowWidth > bp.xlarge){
                var thumbs = $j('.more-views-carousel');
                var imgMode = $j(thumbs).data('imgmode');
                var slidesCount = (imgMode == 'large-image') ? 6 : 4;
                var spaceSlides = (imgMode == 'large-image') ? 12 : 20;
                screenSize = 'xlarge';
                moreSlidesCount = slidesCount;
                moreSpaceBetween = spaceSlides;
            }
            if (windowWidth > bp.large && windowWidth <= bp.xlarge){
                screenSize = 'large';
                moreSlidesCount = 3;
                moreSpaceBetween = 22;
            }
            if (windowWidth > bp.medium && windowWidth <= bp.large){
                screenSize = 'medium';
                moreSlidesCount = 3;
                moreSpaceBetween = 23;
            }
            if (windowWidth > bp.small && windowWidth <= bp.medium){
                screenSize = 'small';
                moreSlidesCount = 4;
                moreSpaceBetween = 28;
            }
            if (windowWidth > bp.xsmall && windowWidth <= bp.small){
                screenSize = 'xsmall';
                moreSlidesCount = 4;
                moreSpaceBetween = 28;
            }
            if (windowWidth <= bp.xsmall){
                screenSize = 'xxsmall';
                moreSlidesCount = 3;
                moreSpaceBetween = 12;
            }

            if(moreSliderOptions['screenSize'] != screenSize){

               moreSliderOptions['screenSize']  = screenSize;
               moreSliderOptions['slideMargin'] = moreSpaceBetween;
               moreSliderOptions['minSlides']   = moreSlidesCount;
               moreSliderOptions['maxSlides']   = moreSlidesCount;

               moreViewsSlider.reloadSlider(moreSliderOptions);
            }
        }


          /*more-views of "center-image" mode*/

        if($j(".gallery-thumbs").length){ 

            if (windowWidth > bp.xlarge){
                screenSize = 'xlarge';
                moreSlidesCount = 4;
                moreSpaceBetween = 16;
            }
            if (windowWidth > bp.large && windowWidth <= bp.xlarge){
                screenSize = 'large';
                moreSlidesCount = 4;
                moreSpaceBetween = 10;
            }
            if (windowWidth > bp.medium && windowWidth <= bp.large){
                screenSize = 'medium';
                moreSlidesCount = 4;
                moreSpaceBetween = 21;
            }
            if (windowWidth > bp.small && windowWidth <= bp.medium){
                screenSize = 'small';
                moreSlidesCount = 4;
                moreSpaceBetween = 20;
            }
            if (windowWidth > bp.xsmall && windowWidth <= bp.small){
                screenSize = 'xsmall';
                moreSlidesCount = 3;
                moreSpaceBetween = 20;
            }
            if (windowWidth <= bp.xsmall){
                screenSize = 'xxsmall';
                moreSlidesCount = 3;
                moreSpaceBetween = 12;
            }
      

             if(galleryThumbsOptions['screenSize'] != screenSize){

                   galleryThumbsOptions['screenSize']  = screenSize;
                   galleryThumbsOptions['slideMargin'] = moreSpaceBetween;
                   galleryThumbsOptions['minSlides']   = moreSlidesCount;
                   galleryThumbsOptions['maxSlides']   = moreSlidesCount;

                   galleryThumbsSlider.reloadSlider(galleryThumbsOptions);

            }

        }


    }).trigger('resize.resize_slider');


    // ==============================================
    // Active item of more-views-carousel 
    // ==============================================
    
    if($j('.product-image-thumbs').length){
       
            var slider = $j('.product-image-thumbs');
            $j('li:first-child a', slider).addClass('active');
            $j('li', slider).click(function(){
                $j('li a', slider).removeClass('active');    
                $j('a', this).addClass('active');
            })
        
    }

});




/************************** functions & plugins ************************************/

var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(navigator.userAgent), $flag;

function equalHeight(group) {
    tallest = 0;
    group.each(function() {
        thisHeight = $j(this).height();
        if(thisHeight > tallest) {
            tallest = thisHeight;
        }
    });
    group.height(tallest);
}


function theme_accordion() {
    idClick = '.id-click';
    idSlide = '.id-block';
    idClass = 'id-active';

    $j(idClick).on('click', function (e) {
        e.stopPropagation();
        var subUl = $j(this).next(idSlide);
        if (subUl.is(':hidden')) {
            subUl.slideDown();
            $j(this).addClass(idClass);
        }
        else {
            subUl.slideUp();
            $j(this).removeClass(idClass);
        }
        $j(idClick).not(this).next(idSlide).slideUp();
        $j(idClick).not(this).removeClass(idClass);
        e.preventDefault();
    });

    $j(idSlide).on('click', function (e) {
        e.stopPropagation();
    });

    $j(document).on('click', function (e) {
        e.stopPropagation();
        var elementHide = $j(idClick).next(idSlide);
        $j(elementHide).slideUp();
        $j(idClick).removeClass('id-active');
    }).trigger('click');
}


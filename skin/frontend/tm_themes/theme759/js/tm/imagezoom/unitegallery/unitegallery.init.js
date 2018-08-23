// Unite Gallery, Version: 1.6.6, released 05 Sep 2015 

jQuery(document).ready(function(){

	var gallery  = jQuery("#gallery");
	var settings = gallery.data('imagezoom');
		
	tmZoom = gallery.unitegallery({
		theme_panel_position: 					settings.unite_panel_position,
		gallery_skin: 							settings.unite_skin, 
		thumb_width: 							settings.unite_thumb_width,
		thumb_height: 							settings.unite_thumb_height,
		slider_enable_arrows:   				Number(settings.unite_arrows),
		slider_enable_fullscreen_button:		Number(settings.unite_fullscreen),
		/***/
		slider_enable_zoom_panel: 				Number(settings.unite_zoom_panel),
		slider_control_zoom: 					Number(settings.unite_zoom_panel),
		/***/
		slider_enable_play_button:   			Number(settings.unite_play),
		gallery_play_interval: 					settings.unite_play_interval,
		gallery_pause_on_mouseover: 			Number(settings.unite_pause_on_mouseover),
		slider_controls_always_on: 				settings.unite_controls_always_on,
		strippanel_enable_handle: 				settings.unite_strippanel_enable,
		//

		slider_textpanel_enable_description: 	false,
		slider_textpanel_enable_bg: 			false,
		slider_loader_type:   					1,
		gallery_width: 							"100%",
		gallery_height: 						660,
		slider_zoom_max_ratio: 					2,
		thumb_show_loader: 						true,

		slider_zoompanel_offset_vert: 			40,
		slider_progress_indicator_offset_vert:  50,
		slider_play_button_offset_vert: 		9,
		slider_fullscreen_button_offset_hor: 	15,
		slider_play_button_offset_hor: 			50,
		slider_progress_indicator_offset_hor:   50,

		strippanel_enable_buttons: 				true,
		
		thumb_fixed_size: 						true,
		thumb_color_overlay_effect: 			false,
		thumb_image_overlay_effect:  			false,
		strip_space_between_thumbs: 			22,
		thumb_border_effect: 					false,
		
		slider_scale_mode: 						"fit",

		strip_control_avia: 					false,
		strippanel_padding_buttons: 			5,
		strippanel_padding_top: 				0,
		strippanel_padding_left: 				10,
		strippanel_padding_right: 				10,
		strippanel_padding_bottom: 				0,

	});
	
	var bp = {
	    xsmall: 479,
	    small: 599,
	    medium: 767,
	    large: 991,
	    xlarge: 1199
	}
	var gallery_height = '';
	var gallery_width  = '';

	jQuery(window).on('resize.resize_zoom', function(){
		if(jQuery('body').is(':not(.body-gallery-fixed)')){
			var windowWidth = jQuery(window).width();
			tmZoom.resetZoom();
			
			if (windowWidth > bp.xlarge){
				screenSize = 'xlarge';
				gallery_height = 660;
				gallery_width = "100%";
				zoomResize(gallery_width, gallery_height);
			}
			if (windowWidth > bp.large && windowWidth <= bp.xlarge){
	            screenSize = 'xlarge';
				gallery_height = 360;
				gallery_width = "100%";
				zoomResize(gallery_width, gallery_height);
	        }
	        if (windowWidth > bp.medium && windowWidth <= bp.large){
	             screenSize = 'medium';
				gallery_height = 260;
				gallery_width = "100%";
				zoomResize(gallery_width, gallery_height);
	        }
	        if (windowWidth > bp.small && windowWidth <= bp.medium){
	            screenSize = 'small';
				gallery_height = 460;
				gallery_width = "100%";
				zoomResize(gallery_width, gallery_height);
	        }
	        if (windowWidth > bp.xsmall && windowWidth <= bp.small){
	            screenSize = 'xsmall';
				gallery_height = 460;
				gallery_width = "100%";
				zoomResize(gallery_width, gallery_height);
	        }
	        if (windowWidth <= bp.xsmall){
	            screenSize = 'xxsmall';
				gallery_height = 300;
				gallery_width = "100%"; 
				zoomResize(gallery_width, gallery_height);
	        }
		}

	}).trigger('resize.resize_zoom');

	
	if(!isMobile){
		if(Number(settings.unite_fullwindow) == 1){

			jQuery('.ug-slider-wrapper', gallery).append("<span class='gallery-zoom'></span>");
			jQuery('body').append("<div class='fullwindow-loader'></div>");
			var loader = jQuery('.fullwindow-loader', 'body');

			jQuery('.gallery-zoom', gallery).on('click', function(e){



				if(jQuery('body').hasClass('body-gallery-fixed')){
					escPress();
			    	return false;
				}

				jQuery(loader).fadeIn( "fast", function() {
				    gallery.toggleClass('gallery-fixed');
				    jQuery('.gallery-zoom', gallery).toggleClass('active');
				    jQuery('body').toggleClass('body-gallery-fixed');
				    if(jQuery('body').hasClass('body-gallery-fixed')){
				    	OffScroll();
				    	jQuery(window).on('resize.gallery_fixed', function(){
							var windowWidth  = jQuery(window).width();
							var windowHeight = jQuery(window).height();
					    	zoomResize(windowWidth, windowHeight);
						}).trigger('resize.gallery_fixed');
				    }else{
				    	modeExit();
				    }

				});
				setTimeout(function(){
					jQuery(loader).fadeOut("fast");
				}, 300);

			});

			jQuery(document).keyup(function(e) {
			    if (e.keyCode == 27) { 
			    	escPress();
			    }
			});

			function modeExit(){
				jQuery(window).unbind('resize.gallery_fixed').unbind('scroll').trigger('resize.resize_zoom');
			}

			function escPress(){
				gallery.removeClass('gallery-fixed');
			    jQuery('.gallery-zoom', gallery).removeClass('active');
			    jQuery('body').removeClass('body-gallery-fixed');
		    	modeExit();
			}
		}
	}

});

var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(navigator.userAgent), $flag;

function zoomResize(gallery_width, gallery_height){
	tmZoom.resize(gallery_width, gallery_height);
	tmZoom.zoomIn();
	tmZoom.resetZoom();
}

function OffScroll() {
	var winScrollTop = jQuery(window).scrollTop();
	jQuery(window).bind('scroll',function () {
	  jQuery(window).scrollTop(winScrollTop);
	});
}



(function($){
	$.fn.productListGalleryThumbs = function(){
		return this.each(function(){

			var thumbs 			= $('.product-thumbs', this);
			var thumb_link 		= $('.product-thumb a', this);

			$('.product-thumb:first-child a', thumbs).addClass('active');

			thumb_link.click(function(e){
				e.preventDefault();

				var url				= $(this).attr('href');
				var container 		= $(this).closest('.product-image-container');
				var product_image 	= $('.product-image img', container);

				product_image.stop().fadeOut(200, function(){
					$(this).attr('src', url);
					$(this).fadeIn(200);
				});

				$(this).addClass('active');
				$(this).parent().siblings().find('a').removeClass('active');
			})

		})
	}
})(jQuery);

jQuery(document).ready(function() {
	jQuery('.products-grid').productListGalleryThumbs();
	jQuery('.products-list').productListGalleryThumbs();
});
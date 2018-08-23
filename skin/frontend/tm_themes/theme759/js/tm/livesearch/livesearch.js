;(function ( $, window, document, undefined ) {
    "use strict";

    var pluginName = "tmLiveSearch",
        defaults = {
            suggestDelay:               500,
            suggestResultsContainer:    '#search_autocomplete'
        };

    // The actual plugin constructor
    function Plugin ( element, options ) {
        this.element    = element;

        this.settings   = $.extend( {
            formAction:    $(element).attr('action')
        }, defaults, options );

        this._defaults  = defaults;
        this._name      = pluginName;
        this.init();
    }

    // Avoid Plugin.prototype conflicts
    $.extend(Plugin.prototype, {
        init: function () {
            this.showSuggestResults(this.settings, this.element);

            // console.log("LiveSearch initialized.");
        },

        /**
         * Show suggest result after 'suggestDelay' seconds
         * @param  array  settings  plugin settings
         * @param  object element   live search main element
         */
        showSuggestResults: function(settings, element){
            var plugin      = this;
            var input       = $(element).find('#search');
            var inputBox    = input.parent('.input-box');
            var typingTimer;
            var action      = settings.formAction;
            var url         = action.replace("catalogsearch/result", "livesearch/ajax/suggest");
            var form        = this.element;

            //Start getSuggest actions on keypress
            input.on('keypress select input', function(e){
                clearTimeout(typingTimer);

                var data    = form.serialize();

                if (data.length > 3) { // if data is longer than q=#
                    typingTimer = setTimeout(function(){
                        plugin.ajaxGetResults(settings, url, data, inputBox, plugin);
                        inputBox.addClass('processing');
                    }, settings.suggestDelay)
                }
            });


            // Close suggest window in click outside
            $(document).on('click', function(e){
                if($(settings.suggestResultsContainer).hasClass('show')){
                    if ($(e.target).closest(settings.suggestResultsContainer).length <= 0) {
                        //$(settings.suggestResultsContainer).removeClass('show');
                        plugin.resultsBehaviour(settings);
                    }
                }
            })
        },

        /**
         * AJAX get results request
         * @param  array  settings  plugin settings
         * @param  string data result request url params
         */
        ajaxGetResults: function(settings, url, data, inputBox, plugin){

            if (typeof this.xhr !== 'undefined') {
                this.xhr.abort();
            }

            this.xhr = $.ajax({
                url: url,
                dataType: 'html',
                type: 'get',
                data: data,
                success: function(data){
                    $(settings.suggestResultsContainer).html(data);
                    inputBox.removeClass('processing');
                },
                error: function(xhr, ajaxOptions, thrownError){
                    //console.log(xhr.status + ": " + thrownError);
                },
                complete: function(xhr, status){
                    //$(settings.suggestResultsContainer).addClass('show');
                    plugin.resultsBehaviour(settings, true);
                }
            })
        },

        resultsBehaviour: function(settings, action){
            var container = settings.suggestResultsContainer;

            if(action){
                $(container).addClass('show');
            } else {
                $(container).removeClass('show');
            }
        }
    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $[pluginName] = $.fn[pluginName] = function (options) {
        if(!(this instanceof $)) { $.extend(defaults, options) }
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin(this, options));
            }
        });
    };
})( jQuery, window, document );


jQuery(document).ready(function(){
    jQuery('#search_mini_form').tmLiveSearch();
})
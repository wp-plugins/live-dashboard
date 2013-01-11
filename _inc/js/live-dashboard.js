(function($) {
    $(document).ready (function() {
        // Original code only loads widget contents when
        // they're visible, we're loading them always
        //
        // From: dashboard.js

        // These widgets are sometimes populated via ajax
        ajaxWidgets = [
            'dashboard_incoming_links',
            'dashboard_primary',
            'dashboard_secondary',
            'dashboard_plugins'
        ];

        ajaxPopulateWidgets = function(el) {
            function show(i, id) {
                var p, e = $('#' + id + ' div.inside').find('.widget-loading');
                if ( e.length ) {
                    p = e.parent();
                    setTimeout( function(){
                        p.load( ajaxurl + '?action=dashboard-widgets&widget=' + id, '', function() {
                            p.hide().slideDown('normal', function(){
                                $(this).css('display', '');
                            });
                        });
                    }, i * 500 );
                }
            }

            if ( el ) {
                el = el.toString();
                if ( $.inArray(el, ajaxWidgets) != -1 )
                    show(0, el);
            } else {
                $.each( ajaxWidgets, show );
            }
        };
        ajaxPopulateWidgets();

        $('.customize-section-title .postbox-title-action a').click( function(e) {
            var link = liveAdmin_addCurrentPageParam( $(this).attr('href') );
            $(this).attr('href', link );
        });

    });

})(jQuery);
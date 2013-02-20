(function($) {
    $(document).ready( function() {
        var site_url_length = liveDashboardLinks.site_url.length;

        $('a[href^="' + liveDashboardLinks.site_url + '"]').each( function(k,v) {
            var link = $(v).attr('href');
            if ( link.indexOf(liveDashboardLinks.admin_url) !== -1 ) return true;

            link = link.substr( site_url_length );
            $(v).attr('href', liveDashboardLinks.admin_url + "?current-page=" + encodeURIComponent(link) );

        });
    });
})(jQuery);
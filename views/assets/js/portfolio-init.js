jQuery(document).ready(function($) {

    if ($('.ABp_latest_portfolio').length) {
        $('.ABp_latest_portfolio').each(function (){
            var $prev = $(this).find('.portfolio_prev');
            var $next = $(this).find('.portfolio_next');
            $(this).find('ul').carouFredSel({
                prev: $prev,
                next: $next,
                auto: false,
                width: '100%',
                scroll: 1,
            });
        });
    }

     /* WP Media Uploader */

    $(document).on('click', '#manage_gallery', upload_gallery_button);

    function upload_gallery_button(e) {
        e.preventDefault();
        var $input_field = $('#portfolio_gallery_input');
        var ids = $input_field.val();
        var gallerysc = '[gallery ids="' + ids + '"]';
        wp.media.gallery.edit(gallerysc).on('update', function(g) {
            var id_array = [];
            var url_array = [];
            $.each(g.models, function(id, img){
                url_array.push(img.attributes.url);
                id_array.push(img.id);
            });
            var ids = id_array.join(",");
            ids = ids.replace(/,\s*$/, "");
            var urls = url_array.join(",");
            urls = urls.replace(/,\s*$/, "");
            $input_field.val(ids);
            var html = '';
            for(var i = 0 ; i < url_array.length; i++){
                html += '<div class="gallery-item"><img src="'+url_array[i]+'"></div>';
            }

            $('.gallery_images').html('').append(html);
        });

    }

    $(document).on('click', '#empty_gallery', empty_gallery_button);

    function empty_gallery_button(e){
        e.preventDefault();
        var $input_field = $('#portfolio_gallery_input');
        $input_field.val('');
        $('.gallery_images').html('');
    }

});
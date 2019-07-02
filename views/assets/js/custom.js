
/******************************************
    -   PREPARE PLACEHOLDER FOR SLIDER  -
******************************************/
var tpj=jQuery;
tpj.noConflict();
var revapi1;
tpj(document).ready(function() {
    if(tpj("#rev_slider_1_1").revolution == undefined){
        revslider_showDoubleJqueryError("#rev_slider_1_1");
    }else{
        revapi1 = tpj("#rev_slider_1_1").show().revolution({
            sliderType:"standard",
            jsFileLocation:"rs-plugin/js/",
            sliderLayout:"fullscreen",
            dottedOverlay:"none",
            delay:7000,
            navigation: {
                keyboardNavigation:"off",
                keyboard_direction: "horizontal",
                mouseScrollNavigation:"off",
                onHoverStop:"on",
                touch:{
                    touchenabled:"on",
                    swipe_threshold: 75,
                    swipe_min_touches: 1,
                    swipe_direction: "horizontal",
                    drag_block_vertical: false
                }
                ,
                arrows: {
                    style:"custom",
                    enable:true,
                    hide_onmobile:false,
                    hide_onleave:false,
                    tmp:'',
                    left: {
                        h_align:"left",
                        v_align:"center",
                        h_offset:20,
                        v_offset:0
                    },
                    right: {
                        h_align:"right",
                        v_align:"center",
                        h_offset:20,
                        v_offset:0
                    }
                }
            },
            visibilityLevels:[1240,1024,778,480],
            gridwidth:1170,
            gridheight:960,
            lazyType:"none",
            shadow:0,
            spinner:"spinner4",
            stopLoop:"off",
            stopAfterLoops:-1,
            stopAtSlide:-1,
            shuffle:"off",
            autoHeight:"off",
            fullScreenAutoWidth:"off",
            fullScreenAlignForce:"off",
            fullScreenOffsetContainer: "",
            fullScreenOffset: "",
            disableProgressBar:"on",
            hideThumbsOnMobile:"off",
            hideSliderAtLimit:0,
            hideCaptionAtLimit:0,
            hideAllCaptionAtLilmit:611,
            debugMode:false,
            fallbacks: {
                simplifyAll:"off",
                nextSlideOnWindowFocus:"off",
                disableFocusListener:false,
            }
        });
}
}); /*ready*/


var tpj=jQuery;
tpj.noConflict();
var revapi4;
tpj(document).ready(function() {
    if(tpj("#rev_slider_4_1").revolution == undefined){
        revslider_showDoubleJqueryError("#rev_slider_4_1");
    }else{
        revapi4 = tpj("#rev_slider_4_1").show().revolution({
            sliderType:"standard",
            jsFileLocation:"rs-plugin/js/",
            sliderLayout:"auto",
            dottedOverlay:"none",
            delay:7000,
            navigation: {
                keyboardNavigation:"off",
                keyboard_direction: "horizontal",
                mouseScrollNavigation:"off",
                onHoverStop:"on",
                touch:{
                    touchenabled:"on",
                    swipe_threshold: 75,
                    swipe_min_touches: 1,
                    swipe_direction: "horizontal",
                    drag_block_vertical: false
                }
                ,
                arrows: {
                    style:"inspirado",
                    enable:true,
                    hide_onmobile:false,
                    hide_onleave:false,
                    tmp:'',
                    left: {
                        h_align:"right",
                        v_align:"bottom",
                        h_offset:62,
                        v_offset:0
                    },
                    right: {
                        h_align:"right",
                        v_align:"bottom",
                        h_offset:0,
                        v_offset:0
                    }
                }
            },
            visibilityLevels:[1240,1024,778,480],
            gridwidth:755,
            gridheight:542,
            lazyType:"none",
            shadow:0,
            spinner:"spinner4",
            stopLoop:"off",
            stopAfterLoops:-1,
            stopAtSlide:-1,
            shuffle:"off",
            autoHeight:"off",
            disableProgressBar:"on",
            hideThumbsOnMobile:"off",
            hideSliderAtLimit:0,
            hideCaptionAtLimit:0,
            hideAllCaptionAtLilmit:0,
            debugMode:false,
            fallbacks: {
                simplifyAll:"off",
                nextSlideOnWindowFocus:"off",
                disableFocusListener:false,
            }
        });
}
}); /*ready*/


/* Portfolio slider */

var tpj=jQuery;
var revapi5;
tpj(document).ready(function() {
    if(tpj("#rev_slider_5_1").revolution == undefined){
        revslider_showDoubleJqueryError("#rev_slider_5_1");
    }else{
        revapi5 = tpj("#rev_slider_5_1").show().revolution({
            sliderType:"standard",
            jsFileLocation:"rs-plugin/js/",
            sliderLayout:"fullwidth",
            dottedOverlay:"none",
            delay:9000,
            navigation: {
                keyboardNavigation:"off",
                keyboard_direction: "horizontal",
                mouseScrollNavigation:"off",
                    mouseScrollReverse:"default",
                onHoverStop:"off",
                arrows: {
                    style:"inspirado",
                    enable:true,
                    hide_onmobile:false,
                    hide_onleave:false,
                    tmp:'',
                    left: {
                        h_align:"right",
                        v_align:"center",
                        h_offset:0,
                        v_offset:29
                    },
                    right: {
                        h_align:"right",
                        v_align:"center",
                        h_offset:0,
                        v_offset:-27
                    }
                }
            },
            visibilityLevels:[1240,1024,778,480],
            gridwidth:1240,
            gridheight:650,
            lazyType:"none",
            shadow:0,
            spinner:"spinner4",
            stopLoop:"off",
            stopAfterLoops:-1,
            stopAtSlide:-1,
            shuffle:"off",
            autoHeight:"off",
            disableProgressBar:"on",
            hideThumbsOnMobile:"off",
            hideSliderAtLimit:0,
            hideCaptionAtLimit:0,
            hideAllCaptionAtLilmit:0,
            debugMode:false,
            fallbacks: {
                simplifyAll:"off",
                nextSlideOnWindowFocus:"off",
                disableFocusListener:false,
            }
        });
    }
}); /*ready*/


jQuery(document).ready(function($) {
    "use strict";

    $('body.preloader').jpreLoader({
        showSplash : false,
        loaderVPos : '50%',
    }).css('visibility','visible');


    $(".scroll").on('click', function(e){
        e.preventDefault();
        var href = $(this).attr('href');
        var hash = href.split('#');
        var url_hash = '#' + hash[1];
        if ($(url_hash).length > 0) {
            var offset = ($(window).width()<968) ? 20 : 100;
            $('html, body').animate({
                scrollTop: $(url_hash).offset().top-offset
            }, 1000);
        }
        else{
            location.href = href;
        }
        if($(window).width()<968){
            var $menu_responsive = $('#ABdev_main_header nav');
            $menu_responsive.animate({width:'toggle'},350);
        }
    });


    //Back to top
    $("#back_to_top").on('click', function(e){
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 900);
        return false;
    });


    $('body.home.page').find('.tcvpb_section_tc').each(function(){
        var $section = $(this);
        var section_id = $(this).attr("id");
        var $menu_item_left;
        var $menu_item_right;
        var $menu_item;
        $section.waypoint({
            handler: function(direction){
                if(section_id!==undefined){
                    $('.current-menu-item').removeClass('current-menu-item').removeClass('current-menu-ancestor');
                    if(direction==='down'){
                        $menu_item_left = $('#main_menu_left a[href=#'+section_id+']').parent();
                        $menu_item_right = $('#main_menu_right a[href=#'+section_id+']').parent();
                        $menu_item = $('#main_menu a[href=#'+section_id+']').parent();
                        if($menu_item_left.length > 0 || $menu_item_right.length > 0 || $menu_item.length > 0){
                            $menu_item_left.addClass('current-menu-item');
                            $menu_item_right.addClass('current-menu-item');
                            $menu_item.addClass('current-menu-item');
                        }
                        else{
                            $('#main_menu_left .current_page_item').addClass('current-menu-item');
                            $('#main_menu_right .current_page_item').addClass('current-menu-item');
                            $('#main_menu .current_page_item').addClass('current-menu-item');
                        }
                    }
                    else if(direction==='up'){
                        var previous_section_id = $section.prevAll('[id]:first').attr('id');
                        $menu_item_left = $('#main_menu_left a[href=#'+previous_section_id+']').parent();
                        $menu_item_right = $('#main_menu_right a[href=#'+previous_section_id+']').parent();
                        $menu_item = $('#main_menu a[href=#'+previous_section_id+']').parent();
                        if($menu_item_left.length > 0 || $menu_item_right.length > 0 || $menu_item.length > 0){
                            $menu_item_left.addClass('current-menu-item');
                            $menu_item_right.addClass('current-menu-item');
                            $menu_item.addClass('current-menu-item');
                        }
                        else{
                            $('#main_menu_left .current_page_item').addClass('current-menu-item');
                            $('#main_menu_right .current_page_item').addClass('current-menu-item');
                            $('#main_menu .current_page_item').addClass('current-menu-item');
                        }
                    }
                }
            },
            offset: 150
        });
    });


    var $main_header = $('#ABdev_main_header');
    var $main_slider = $('#ABdev_main_slider');
    $main_slider.height('auto');
    var $header_spacer = $('#ABdev_header_spacer');
    var $header_spacer_shop = $('#ABdev_header_spacer_shop');

    var header_height = $main_header.outerHeight();

    $header_spacer.height(header_height);
    $header_spacer_shop.height(header_height);
    var admin_toolbar_height = parseInt($('html').css('marginTop'), 10);


    // Add class if menu has icon
    var $menu_icon = $('nav > ul > li a i');
    var $navigation = $('nav > ul');
    if($menu_icon.length>0 && !$main_header.hasClass('header_layout_right')){
        $navigation.addClass('with_icon');
    }

    // Header Layout Sidebar
    var $left_header = $('#ABdev_main_header.header_layout_right');
    $left_header.find('nav > ul > li').each(function() {
        if($(this).find('> ul').length) {
            $(this).find('> a').append('<i class="ci_icon-angle-up"></i>');
        }
    });

    var $menu_with_children = $('#ABdev_main_header.header_layout_right nav > ul > li.menu-item-has-children > a');
    $menu_with_children.on('click', function(e){
        e.preventDefault();
        var $this = $(this);
        if (!$this.parent().find('> ul').hasClass('visible')) {
            $this.parent().find('> ul').addClass('visible').slideDown('slow');
            $this.find('.ci_icon-angle-up').css('transform', 'rotate(0)');
        } else{
            $this.parent().find('> ul').removeClass('visible').slideUp('fast');
            $this.find('.ci_icon-angle-up').css('transform', 'rotate(180deg)');
        }
    });

    var $menu_toggle = $('.header_sidebar_toggle');
    var $right_sidebar = $('#ABdev_main_header.header_layout_right');
    var $menu_responsive = $('#ABdev_main_header nav');

    $menu_toggle.on('click', function(e){
        e.preventDefault();
        var $this = $(this);
        $this.toggleClass('active');
        $right_sidebar.toggleClass('visible');

        if ($(window).width()<977 && !$main_header.hasClass('header_layout_right')) {
            $menu_responsive.animate({width:'toggle'},350);
        }

        if ($main_header.hasClass('header_layout_right') && $this.hasClass('active')) {
            $right_sidebar.find('nav').css('display', 'block');
        } else{
            $right_sidebar.find('nav').css('display', 'none');
        }
    });

    function menu_switch(){
        if($(window).width()>767 && $main_slider.length > 0 && !$main_header.hasClass('static_header') && !$main_header.hasClass('header_layout_right') && !$('body').hasClass('single-portfolio')){
            $header_spacer.hide();
            if($(document).scrollTop() < $main_slider.height() - $main_header.outerHeight() ){
                $main_header.removeClass('full_background');
            } else{
                $main_header.addClass('full_background');
            }
            $(document).scroll(function(){
                var scrollTop = parseInt($(document).scrollTop() ,10);
                if(scrollTop > 20){
                    $main_header.addClass('full_background');
                }
                else{
                    $main_header.removeClass('full_background');
                }
            });
        } else{
            $main_header.addClass('full_background');
            $header_spacer.show();
        }
    }

    if (!$main_header.hasClass('header_layout_shop')) {
        menu_switch();
    }


    // Insert<->Remove Right Menu
    function header_right_menu(){
        var $left_menu_nav = $('#ABdev_main_header .first_menu nav');
        var $left_menu_ul = $('#ABdev_main_header .first_menu #main_menu_left');
        var $right_menu_ul = $('#ABdev_main_header .second_menu #main_menu_right');
        var $left_menu_on_right = $('#ABdev_main_header .second_menu #main_menu_left');

        if($(window).width()<977){
            $right_menu_ul.before($left_menu_ul);
        } else{
            $left_menu_on_right.appendTo($left_menu_nav);
            $('#ABdev_main_header nav').show();
        }
    }

    header_right_menu();

    var $sf = $('#main_menu_left, #main_menu_right, #main_menu');
    if($('.header_sidebar_toggle').css('display') === 'none') {
        // enable superfish when the page first loads if we're on desktop
        $sf.superfish({
            delay:          300,
            animation:      {opacity:'show',height:'show'},
            animationOut:   {height:'hide'},
            speed:          'fast',
            speedOut:       'fast',
            cssArrows:      false,
            disableHI:      true /* load hoverIntent.js in header to use this option */,
            onBeforeShow:   function(){
                var ww = $(window).width();
                if (this.parent().offset() !== undefined) {
                    var locUL = this.parent().offset().left + this.width();
                    var locsubUL = this.parent().offset().left + this.parent().width() + this.width();
                    var par = this.parent();
                    if (par.parent().is('#main_menu, #main_menu_left, #main_menu_right') && (locUL > ww)) {
                        this.css('marginLeft', '-' + (locUL - ww + 20) + 'px');
                    } else if (!par.parent().is('#main_menu, #main_menu_left, #main_menu_right') && (locsubUL > ww)) {
                        this.css('left', '-' + (this.width()) + 'px');
                    }
                }
            }
        });
    }

    $('.tcvpb-tabs-timeline').each(function(){
        var $this = $(this);
        var $tabs = $this.find('.tcvpb-tabs-ul > li');
        var tabsCount = $tabs.length;
        $tabs.addClass('tab_par_'+tabsCount);
    });


    $('.submit').on('click', function(){
        $(this).closest("form").submit();
    });

    $('input, textarea').placeholder();


    //Portfolio ajax load

    var $content_latest = $('.portfolio_full_width, .portfolio_container');
    var $loader = $('.load_more_portfolio');
    var itemSelector = ('.portfolio_single_column_item, .portfolio_item');
    var pageNumber = 1;

    var cat_latest = $loader.data('cat');
    var style = $loader.data('style');

    $loader.on('click', function () {
        load_portfolio_posts();
    });

    function load_portfolio_posts() {
        if (!($loader.hasClass('portfolio_posts_loading') || $loader.hasClass('no_more_portfolio_posts'))) {
            pageNumber++;
            var str = '&portfolio-category=' + cat_latest + '&style=' + style + '&pageNumber=' + pageNumber + '&action=inspirado_portfolio_load_posts';
            $.ajax({
                type: 'GET',
                dataType   : 'html',
                url: inspirado_ajax_posts.ajaxurl,
                data: str,
                success: function (data) {
                    var $data = $(data);
                    if ($data.length) {
                        var $newElements = $data.css({ opacity: 0 });
                        $content_latest.append($newElements);
                        $content_latest.imagesLoaded(function () {
                            $content_latest.isotope('insert', $newElements);
                            $newElements.animate({ opacity: 1 });
                        });
                        $loader.removeClass('portfolio_posts_loading').find('.loader').remove();
                    } else {
                        $loader.addClass('no_more_portfolio_posts').html(inspirado_ajax_posts.noposts);
                    }
                },
                beforeSend : function () {
                    $loader.addClass('portfolio_posts_loading').append('<div class="loader"></div>');
                },
                error : function (jqXHR, textStatus, errorThrown) {
                    $loader.html(jqXHR + ' :: ' + textStatus + ' :: ' + errorThrown);
                },
                complete : function () {
                    $loader.removeClass('portfolio_posts_loading').find('.loader').remove();
                }
            });
        }
        return false;
    }

    /* simple subscribe button */
    $("footer .ABss_form_wrapper .ABss_inline_form .ABss_subscriber_email").parent().append('<a href="#" class="ABss_subscriber_widget_submit submit"><i class="icon-chevron-right"></i></a>');

    $('input, textarea').placeholder();

    /*  isotope portfolio  */
    var sortBy = 'original-order';
    var columnWidth = '.portfolio_item';
    var gutter = 30;

    if ($('.ABdev_latest_portfolio').length) {
        gutter = 0;
    }

    $('.ABdev_latest_portfolio, .portfolio_full_width, .portfolio_container').each(function(){
        var $current_portfolio = $(this);
        $current_portfolio.imagesLoaded( function() {
            $current_portfolio.isotope({
                percentPosition: true,
                masonry: {
                  columnWidth: columnWidth,
                  gutter: gutter
                },
                itemSelector : '.portfolio_item',
                sortBy: sortBy
            });
        });
    });

    $('.portfolio_filter_button').on('click', function(){
        var $portfolio_filter_clicked_button = $(this);
        if ( $portfolio_filter_clicked_button.hasClass('selected') ) {
            return false;
        }
        var $portfolio_filter = $portfolio_filter_clicked_button.parents('.portfolio_filter');
        $portfolio_filter.find('.selected').removeClass('selected');
        $portfolio_filter_clicked_button.addClass('selected');
        var options = {},
            key = $portfolio_filter.attr('data-option-key'),
            value = $portfolio_filter_clicked_button.attr('data-option-value');
        value = value === 'false' ? false : value;
        options[ key ] = value;
        if ( key === 'layoutMode' && typeof changeLayoutMode === 'function' ) {
            changeLayoutMode( $portfolio_filter_clicked_button, options );
        } else {
            $portfolio_filter.next('.ABdev_latest_portfolio, .portfolio_full_width, .portfolio_container').isotope( options );
        }
        return false;
    });

    $(window).load(function() {

        //Nivo Slider
        $('#portfolio_gallery_slider').nivoSlider({
            effect:'fade', // Specify sets like: 'fold,fade,sliceDown'
            pauseTime:3000, // How long each slide will show
            directionNav:false, // Next & Prev navigation
            controlNavThumbs:true,
            controlNavThumbsFromRel:false,
            manualAdvance: false,
        });

        /****** Image Masonry *****/
            var $masonry_container = $('.tcvpb-image-masonry');
            $masonry_container.masonry({
              itemSelector: '.image-masonry',
            });

        // Portfolio AJAX

        /* carouFredSel */

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


    });


    $(window).on('resize', function() {

        $('.ABdev_latest_portfolio').isotope('layout');

        if ($(window).width()>977) {
            if ($menu_toggle.hasClass('active')) {
                $menu_toggle.removeClass('active');
                if ($('#ABdev_main_header.header_layout_right').hasClass('visible')) {
                    $('#ABdev_main_header.header_layout_right').removeClass('visible');
                }
            }
        } else{
            $('#ABdev_main_header.regular_header nav').css('display', 'none');
        }

        if($('.header_sidebar_toggle').css('display') === 'none' && !$sf.hasClass('sf-js-enabled')) {
            // you only want SuperFish to be re-enabled once ($sf.hasClass)
            $menu_responsive.show();
            $sf.superfish({
                delay:          300,
                animation:      {opacity:'show',height:'show'},
                animationOut:   {height:'hide'},
                speed:          'fast',
                speedOut:       'fast',
                cssArrows:      false,
                disableHI:      true /* load hoverIntent.js in header to use this option */,
                onBeforeShow:   function(){
                    var ww = $(window).width();
                    if (this.parent().offset() !== undefined) {
                        var locUL = this.parent().offset().left + this.width();
                        var locsubUL = this.parent().offset().left + this.parent().width() + this.width();
                        var par = this.parent();
                        if (par.parent().is('#main_menu, #main_menu_left, #main_menu_right') && (locUL > ww)) {
                            this.css('marginLeft', '-' + (locUL - ww + 20) + 'px');
                        } else if (!par.parent().is('#main_menu, #main_menu_left, #main_menu_right') && (locsubUL > ww)) {
                            this.css('left', '-' + (this.width()) + 'px');
                        }
                    }
                }
            });
        } else if($('.header_sidebar_toggle').css('display') != 'none' && $sf.hasClass('sf-js-enabled')) {
            // smaller screen, disable SuperFish
            $sf.superfish('destroy');
            $menu_responsive.hide();
            $menu_responsive.find('.sf-mega').css('marginLeft','0');
        }

        menu_switch();
        header_right_menu();

    });

});



/**
 * Created by josafa on 08/09/15.
 */

jQuery("document").ready(function ($) {
    var menutop = $('.menu-topo-mc');
    var position = menutop.offset().top;

    scrollToHash();

    if ("onhashchange" in window) {
        window.onhashchange = scrollToHash;
    }

    $(window).scroll(function () {
        var fixing = $(this).scrollTop() > position;
        menutop.toggleClass("fixed-top-mc", fixing);
    });

    $("select.form-control").change(function () {
        var value = this.value;
        scrollTo($("#" + value));
    });

    $("#btn-search-texto-em-debate").click(function () {
        highlightText();
    });

    $("#prev-highlight").click(function () {
        prevHighlight()
    });

    $("#next-highlight").click(function () {
        nextHighlight()
    });

    $("span.clear-input-btn").click(function () {
        $('#txt-texto-em-debate').val("").trigger('change');
    });

    $('#txt-texto-em-debate').change(function () {
        if ($(this).val().length > 0) {
            $("span.clear-input-btn").removeClass("hidden");
        } else {
            $("span.clear-input-btn").addClass("hidden");
        }
    });

    $('#txt-texto-em-debate').keyup(function (e) {
        if ($(this).val().length > 0) {
            $("span.clear-input-btn").removeClass("hidden");
        } else {
            $("span.clear-input-btn").addClass("hidden");
        }

        if (e.keyCode == 13) {
            highlightText();
        }
    });

    function highlightText() {
        var text = $("#txt-texto-em-debate").val();
        var searchable = $("span.searchable-content");

        searchable.removeHighlight();
        $(".total-highlight").html(0);
        $(".current-highlight").html(0);

        if (text.length > 0) {
            searchable.highlight(text);

            var highlights = searchable.find(".highlight");
            if (highlights.length > 0) {
                $(".total-highlight").html(highlights.length);
                $(".current-highlight").html(1);

                setCurrentHighlight(highlights.first());
            }
        }
    }

    function prevHighlight() {
        var highlights = $(".commentable-container").find(".highlight");
        var currentHighlight = $(".commentable-container").find(".highlight.current");
        var currentIndex = highlights.index(currentHighlight);

        if (currentIndex <= 0) {
            setCurrentHighlight(highlights.last());
            $(".current-highlight").html(highlights.length);
        } else {
            setCurrentHighlight(highlights.get(--currentIndex));
            $(".current-highlight").html(currentIndex + 1);
        }
    }

    function nextHighlight() {
        var highlights = $(".commentable-container").find(".highlight");
        var currentHighlight = $(".commentable-container").find(".highlight.current");
        var currentIndex = highlights.index(currentHighlight);

        if (currentIndex >= highlights.length - 1) {
            setCurrentHighlight(highlights.first());
            $(".current-highlight").html(1);
        } else {
            setCurrentHighlight(highlights.get(++currentIndex));
            $(".current-highlight").html(currentIndex + 1);
        }
    }

    function setCurrentHighlight(element) {
        var currentHighlight = $(".commentable-container").find(".highlight.current");
        currentHighlight.removeClass("current");
        $(element).addClass("current");
        scrollTo(element);
    }

    function scrollToHash() {
        var urlHash = window.location.href.split("#")[1];
        if (urlHash && $('#' + urlHash).length) {
            scrollTo($('#' + urlHash));
        }
    }

    function scrollTo(element) {
        var finalScroll = $(element).offset().top;
        if ($(menutop).hasClass('fixed-top-mc')) {
            finalScroll -= $(menutop).outerHeight(true);
        } else {
            finalScroll -= $(menutop).outerHeight(true) * 2;
        }

        $('body,html').animate({
            scrollTop: finalScroll
        }, 500);
    }

    function back_to_login($) {
        var offset = 220;
        var duration = 500;
        $('.back-to-login').click(function (event) {
            event.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, duration);
            return false;
        });
    }

    //Chama o back to login
    back_to_login($);
});

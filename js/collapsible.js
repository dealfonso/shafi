$(function($) {
    $('div.collapsible').each(function() {
        // var toggler = $(this).find('a.toggler').first();
        let toggler = $('<a href="#" class="toggler"></a>');
        let collapsible = $(this);
        collapsible.prepend(toggler);

        let closed_text = collapsible.attr('closed-text');
        if (closed_text == undefined) closed_text = "open...";

        let opened_text = collapsible.attr('opened-text');
        if (opened_text == undefined) opened_text = "close...";

        toggler.update_text = function() {        
            if (collapsible.hasClass('closed'))
                this.html(closed_text);
            else
                this.html(opened_text);
        }

        toggler.update_text();

        toggler.on('click', function(e) {
            e.preventDefault();
            var div = collapsible.children('div.content');
            if (collapsible.hasClass('closed'))
                div.slideDown();
            else
                div.slideUp();

            collapsible.toggleClass('closed');
            toggler.update_text();
        })
    })


    $('div.collapsible-old').each(function() {
        var toggler = $(this).find('a.toggler').first();

        let initial = $(this).attr('initial');
        if (initial === "closed") {
            toggler.addClass('closed');
            let div = $(this).children('div.content');
            div.hide();
        }

        toggler.update_text = function() {        
            if (this.hasClass('closed')) {
                var text = this.attr('closed-text');
                if (text == undefined) text = "open...";
            } else {
                var text = this.attr('opened-text');
                if (text == undefined) text = "close...";
            }
            this.html(text);
        }

        toggler.update_text();

        toggler.on('click', function(e) {
            e.preventDefault();
            var div = toggler.parent('div.collapsible').children('div.content');
            if (toggler.hasClass('closed'))
                div.slideDown();
            else
                div.slideUp();

            toggler.toggleClass('closed');
            toggler.update_text();
        })
      })
});
$(document).ready(function() {
    // Get the config values
    var cfg = $('#thumber-config');
    if (!cfg.length || cfg.length == 0) return;
    else cfg = cfg.data();
    // Set the default dimensions to either the user defined dimensions or the minimum dimensions
    $('#thumber-admin, #thumber-img').width(cfg.thumbResizeWidth).height(cfg.thumbResizeHeight);
    // Set the correct dimensions and position for our frame
    $('#thumber-drag').width(cfg.thumbWidth).height(cfg.thumbHeight).css('left', cfg.thumbX + 'px').css('top', cfg.thumbY + 'px');
    // Make our image resizable
    $('#thumber-img').resizable($.extend({}, cfg, {
        alsoResize: '#thumber-admin',
        aspectRatio: true,
        ghost: true,
        resize: function(e, ui) {
            // Check if our draggable frame is not out of bounds
            var img = $('#thumber-img');
            var frame = $('#thumber-drag');
            var pos = frame.position();
            if (pos.left > ui.size.width - frame.width()) {
                var left = (ui.size.width - frame.width());
                frame.css('left', left + 'px')
            }
            if (pos.top > ui.size.height - frame.height()) {
                var top = (ui.size.height - frame.height());
                frame.css('top', top + 'px')
            }
            // Update the on-screen configuration
            $('#thumber-user-config').find('span').eq(-3).text(Math.round(ui.size.height));
            $('#thumber-user-config').find('span').eq(-4).text(Math.round(ui.size.width));
        },
        stop: function(e, ui) {
            // Put our data in the config input element
            var frame = $('#thumber-drag');
            var pos = frame.position();
            var val = [Math.round(ui.size.width), Math.round(ui.size.height), pos.left, pos.top].join(',');
            $('input[name$="_config"]').val(val);
        }
    }));
    // Make our frame draggable
    $('#thumber-drag').draggable({
        containment: 'parent',
        drag: function(e, ui) {
            // Update the on-screen configuration
            var img = $('#thumber-img');
            $('#thumber-user-config').find('span').eq(-1).text(ui.position.top);
            $('#thumber-user-config').find('span').eq(-2).text(ui.position.left);
        },
        stop: function(e, ui) {
            // Put our data in the config input element
            var img = $('#thumber-img');
            var val = [Math.round(img.width()), Math.round(img.height()), ui.position.left, ui.position.top].join(',');
            $('input[name$="_config"]').val(val);
        }
    });
    $('#thumber-drag').dblclick(function() {
        $('button[name$="_apply"]').click();
    });
});
(function ($, undefined) {
  const slicknavInit = function () {
    if (!menus_slicknav || typeof menus_slicknav !== 'object') {
      return;
    }
    if (!menus_slicknav.hasOwnProperty('menu')) {
      return;
    }
    const elem = $('#nav-' + menus_slicknav.menu + ' > ul').first();
    if (!elem.length) {
      return;
    }
    const options = {
      removeClasses: true,
      removeStyles: true
    };
    if (menus_slicknav.hasOwnProperty('options')) {
      $.extend(options, menus_slicknav.options);
    }
    if (slicknav && typeof slicknav !== 'undefined') {
      elem.slicknav(options);
    } else if ($.fn['slicknav'] && typeof $.fn['slicknav'] === 'function') {
      elem.slicknav(options);
    } else {
      return;
    }
    // Add a class to the parent element.
    let parent = $('body');
    if (options.hasOwnProperty('appendTo')) {
      if (options.appendTo !== '') {
        if ($(options.appendTo).length) {
          parent = $(options.appendTo).first();
        }
      }
    } else if (options.hasOwnProperty('prependTo')) {
      if (options.prependTo !== '') {
        if ($(options.prependTo).length) {
          parent = $(options.prependTo).first();
        }
      }
    }
    parent.addClass('has-slicknav');
  };
  $(document).ready(function () {
    slicknavInit();
  });
})(jQuery);

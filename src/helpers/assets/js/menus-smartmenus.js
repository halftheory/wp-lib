(function ($, undefined) {
  const smartmenusInit = function () {
    if (!SmartMenus || typeof SmartMenus === 'undefined') {
      return;
    }
    if (!menus_smartmenus || typeof menus_smartmenus !== 'object') {
      return;
    }
    const selector = 'nav.sm-navbar';
    if (!$(selector).length) {
      return;
    }
    const options = {};
    if (menus_smartmenus.hasOwnProperty('options')) {
      $.extend(options, menus_smartmenus.options);
    }
    const smartmenus = new SmartMenus(document.querySelector(selector), options);
    // Extra buttons outside the selector should trigger the master buttons inside the selector.
    const masterShow = $(selector + ' div.sm-toggler a.sm-toggler-anchor--show').first();
    if (masterShow.length) {
      $('a.sm-toggler-anchor--show').on('click', function (e) {
        if ($(this)[0] === masterShow[0]) {
          return;
        } else if ($(this).closest(selector).length) {
          return;
        }
        e.preventDefault();
        masterShow[0].click();
      });
    }
    const masterHide = $(selector + ' div.sm-toggler a.sm-toggler-anchor--hide').first();
    if (masterHide.length) {
      $('a.sm-toggler-anchor--hide').on('click', function (e) {
        if ($(this)[0] === masterHide[0]) {
          return;
        } else if ($(this).closest(selector).length) {
          return;
        }
        e.preventDefault();
        masterHide[0].click();
      });
      const offcanvas = $(selector + ' .sm-offcanvas').first();
      if (offcanvas.length) {
        // Escape close.
        $(document).on('keyup', function (e) {
          if (e.key === 'Escape') {
            if (offcanvas.hasClass('sm-show')) {
              masterHide[0].click();
            }
          }
        });
        // Clicking anchor links closes menu.
        offcanvas.find('a.sm-nav-link').each(function (i) {
          const href = $(this).attr('href');
          if (href.substr(0, 1) === '#') {
            if ($(href).length) {
              if ($.fn.smoothScroll && typeof $.fn.smoothScroll === 'function') {
                $(this).smoothScroll({ speed: 300 });
              }
              $(this).on('click', function (e) {
                masterHide[0].click();
              });
            }
          }
        });
      }
    }
  };
  $(document).ready(function () {
    smartmenusInit();
  });
})(jQuery);

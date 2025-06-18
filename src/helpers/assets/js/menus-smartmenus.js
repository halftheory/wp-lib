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
        }
        if ($(this).closest(selector).length) {
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
        }
        if ($(this).closest(selector).length) {
          return;
        }
        e.preventDefault();
        masterHide[0].click();
      });
    }
  };
  $(document).ready(function () {
    smartmenusInit();
  });
})(jQuery);

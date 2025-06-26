(function ($, undefined) {
  const galleryPackery = function () {
    if (!$.fn.packery || typeof $.fn.packery !== 'function') {
      return;
    }
    if (!gallery_packery || typeof gallery_packery !== 'object') {
      return;
    }
    const defaults = {
      itemSelector: '.gallery-item',
      percentPosition: true
    };
    for (let selector of Object.keys(gallery_packery)) {
      const options = $.extend(true, {}, defaults);
      if (typeof gallery_packery[selector] === 'object') {
        $.extend(options, gallery_packery[selector]);
      }
      if (!$(selector + ' ' + options.itemSelector).length) {
        continue;
      }
      // Remove gutter selector if not found.
      if (options.hasOwnProperty('gutter')) {
        if (!Number.isInteger(options.gutter)) {
          if (!$(selector + ' ' + options.gutter).length) {
            options.gutter = 0;
          }
        }
      }
      $(selector).first().packery(options).addClass('has-packery');
    }
  };
  $(document).ready(function () {
    galleryPackery();
  });
})(jQuery);

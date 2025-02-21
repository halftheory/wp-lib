(function ($, undefined) {
  const galleryCarousel = function () {
    if (!$.fn.slick || typeof $.fn.slick !== 'function') {
      return;
    }
    if (!gallery_carousel || typeof gallery_carousel !== 'object') {
      return;
    }
    for (let selector of Object.keys(gallery_carousel)) {
      if (typeof gallery_carousel[selector] !== 'object') {
        continue;
      }
      if (!$('#' + selector).length) {
        continue;
      }
      $('#' + selector).first().slick(gallery_carousel[selector]);
    }
  };
  $(document).ready(function () {
    galleryCarousel();
  });
})(jQuery);

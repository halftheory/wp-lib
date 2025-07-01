(function ($, undefined) {
  const imageHash = function (href) {
    href = !href ? window.location.href : href;
    const id = href.split('#')[1];
    if (!id || !id.length) {
      return false;
    }
    const elem = $('a#' + id + ':has(img)').first();
    if (!elem.length) {
      return false;
    }
    elem.click();
    const pos = parseInt(elem.parent().offset().top, 10);
    $('body, html').animate({scrollTop: pos});
    return true;
  };
  $(document).ready(function () {
    imageHash();
  });
})(jQuery);

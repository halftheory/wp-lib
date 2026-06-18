(function ($, undefined) {
  function removeQueryStringFromUrl (url) {
    const u = new URL(url);
    u.hash = '';
    u.search = '';
    return u.toString();
  }

  const audioWavesurfer = function () {
    if (!$('audio').length || typeof WaveSurfer !== 'function') {
      return;
    }
    if (!audio_wavesurfer || typeof audio_wavesurfer !== 'object') {
      return;
    }
    const defaults = {
      mediaControls: true
    };
    $.extend(defaults, audio_wavesurfer);

    let index = 0;
    const items = [];

    // Scan WP shortcodes.
    $('div.wp-audio-shortcode').each(function (i) {
      const src = $(this).find('audio > source').first().attr('src');
      if (src === undefined) {
        return;
      }
      const id = 'wavesurfer-' + index;
      items[index++] = { container: '#' + id, url: removeQueryStringFromUrl(src) };
      $('<div id="' + id + '"></div>').insertBefore($(this)).addClass('wavesurfer');
      $(this).remove();
    });
    // Scan remaining audio.
    $('audio > source').each(function (i) {
      const src = $(this).attr('src');
      if (src === undefined) {
        return;
      }
      const id = 'wavesurfer-' + index;
      items[index++] = { container: '#' + id, url: removeQueryStringFromUrl(src) };
      const parentElem = $(this).parent();
      $('<div id="' + id + '"></div>').insertBefore(parentElem).addClass('wavesurfer');
      parentElem.remove();
    });

    if (!items.length) {
      return;
    }
    index = 0;
    const wavesurfers = [];
    for (let i of Object.keys(items)) {
      if (typeof items[i] !== 'object') {
        continue;
      }
      if (!items[i].hasOwnProperty('container')) {
        continue;
      }
      if (!$(items[i].container).length) {
        continue;
      }
      wavesurfers[index++] = WaveSurfer.create($.extend(items[i], defaults));
    }
  };
  $(document).ready(function () {
    audioWavesurfer();
  });
})(jQuery);

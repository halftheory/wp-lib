(function ($, undefined) {
  const fuzzysortInit = function (selector, jsonUrl, searchUrl, options, __) {
    if (!fuzzysort || typeof fuzzysort === 'undefined') {
      return;
    }
    if (!jsonUrl) {
      return;
    }

    // Get input element.
    selector = !selector ? 'input[type=search]' : selector;
    const inputElem = $(selector).first();
    if (!inputElem.length) {
      return;
    }
    if (inputElem.prop('tagName') !== 'INPUT') {
      return;
    }

    let targets = [];
    let results = [];
    let resultsElem = {};

    let _setupFuzzysort = null;
    const setupFuzzysort = function () {
      if (_setupFuzzysort === null) {
        _setupFuzzysort = false;
        // Setup targets.
        $.ajaxSetup({ async: false, cache: false });
        $.getJSON(jsonUrl, function (data) {
        }).done(function (data) {
          targets = data;
        });
        if (!targets.length) {
          return;
        }
        // Setup options.
        if (!options || typeof options !== 'object') {
          options = {};
        }
        const defaults = {
          all: false,
          key: 'title',
          limit: 10,
          threshold: .5
        };
        options = $.extend(defaults, options);
        _setupFuzzysort = true;
      }
      return _setupFuzzysort;
    }

    const viewAllResults = (__ && typeof __ === 'object' && __.hasOwnProperty('viewAllResults')) ? __.viewAllResults : 'View all results';

    const htmlUpdate = function (results, searchString) {
      // Hide.
      if (!results || !results.length) {
        if (resultsElem.length) {
          resultsElem.hide().html('');
        }
        return;
      }
      // Setup html container.
      if (!resultsElem.length) {
        inputElem.parent().append('<div class="search-fuzzysort search-results"></div>');
        resultsElem = inputElem.parent().find('div.search-fuzzysort.search-results').last();
      }
      // Show results.
      let result = '<ul>';
      $.each(results, function (key, value) {
        result += '<li><a href="' + value.obj.url + '" data-types="' + value.obj.types.join(' ').toLowerCase() + '">' + value.obj.title + '</a></li>';
      });
      if (searchUrl && searchString && searchUrl.length && searchString.length) {
        result += '<li class="view-all-results"><a href="' + searchUrl + encodeURIComponent(searchString) + '">' + viewAllResults + '</a></li>';
      }
      result += '</ul>';
      resultsElem.html(result).show();
    };

    // Setup search listener.
    inputElem.on('focusout', function (e) {
      if ($(document.activeElement).prop('tagName') !== 'BODY') {
        htmlUpdate();
      }
    }).on('keyup', function (e) {
      if (e.key === 'Escape') {
        $(this).val('');
        htmlUpdate();
      }
    }).on('keydown', function (e) {
      $(this).focus();
    }).on('input', function (e) {
      if (!$(this).val().length) {
        htmlUpdate();
        return;
      }
      if (setupFuzzysort()) {
        results = fuzzysort.go($(this).val(), targets, options);
        htmlUpdate(results, $(this).val());
      }
    }).focus();
  };

  $(document).ready(function () {
    if (search_fuzzysort && typeof search_fuzzysort === 'object') {
      const selector = search_fuzzysort.hasOwnProperty('selector') ? search_fuzzysort.selector : null;
      const jsonUrl = search_fuzzysort.hasOwnProperty('jsonUrl') ? search_fuzzysort.jsonUrl : null;
      const searchUrl = search_fuzzysort.hasOwnProperty('searchUrl') ? search_fuzzysort.searchUrl : null;
      const options = search_fuzzysort.hasOwnProperty('options') ? search_fuzzysort.options : null;
      const __ = search_fuzzysort.hasOwnProperty('__') ? search_fuzzysort.__ : null;
      fuzzysortInit(selector, jsonUrl, searchUrl, options, __);
    }
  });
})(jQuery);

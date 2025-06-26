(function ($, _) {
  /**
   * @namespace wp.media.backgroundImage
   * @memberOf wp.media
   */
  wp.media.backgroundImage = {
    /**
     * Get the featured image post ID
     *
     * @return {wp.media.view.settings.post.backgroundImageId|number}
     */
    get: function () {
      return wp.media.view.settings.post.backgroundImageId;
    },
    /**
     * Sets the featured image ID property and sets the HTML in the post meta box to the new featured image.
     *
     * @param {number} id The post ID of the featured image, or -1 to unset it.
     */
    set: function (id) {
      var settings = wp.media.view.settings;

      settings.post.backgroundImageId = id;

      wp.media.post('get_post_background_image_html', {
        post_id: settings.post.id,
        background_image_id: settings.post.backgroundImageId,
        _wpnonce: settings.post.nonce
      }).done(function (html) {
        if (html === '0') {
          window.alert(wp.i18n.__('Could not set that as the background. Try a different attachment.'));
          return;
        }
        $('.inside', '#postbackgroundimagediv').html(html);
      });
    },
    /**
     * Remove the featured image id, save the post thumbnail data and
     * set the HTML in the post meta box to no featured image.
     */
    remove: function () {
      wp.media.backgroundImage.set(-1);
    },
    /**
     * The Featured Image workflow
     *
     * @this wp.media.backgroundImage
     *
     * @return {wp.media.view.MediaFrame.Select} A media workflow.
     */
    frame: function () {
      if (this._frame) {
        wp.media.frame = this._frame;
        return this._frame;
      }

      this._frame = frame = wp.media({
        frame: 'select',
        id: 'background-image',
        title: 'Background image',
        button: {
          text: 'Set background image'
        },
        library: {
          type: ['image']
        },
        multiple: false
      });

      this._frame.on('open', function () {
        var selection = frame.state().get('selection');
        if (selection.length === 0) {
          var selected = wp.media.backgroundImage.get();
          if (selected) {
            selection.add(wp.media.attachment(selected));
          }
        }
      });

      this._frame.on('select', function () {
        var selection = frame.state().get('selection').single();
        if (typeof selection === 'undefined') {
          return;
        }
        wp.media.backgroundImage.set(selection ? selection.id : -1);
      });

      return this._frame;
    },
    /**
     * Open the content media manager to the 'featured image' tab when
     * the post thumbnail is clicked.
     *
     * Update the featured image id when the 'remove' link is clicked.
     */
    init: function () {
      $('#postbackgroundimagediv').on('click', '#set-background-image', function (event) {
        event.preventDefault();
        // Stop propagation to prevent thickbox from activating.
        event.stopPropagation();

        wp.media.backgroundImage.frame().open();
      }).on('click', '#remove-background-image', function () {
        wp.media.backgroundImage.remove();
        return false;
      });
    }
  };
  $(wp.media.backgroundImage.init);
}(jQuery, _));

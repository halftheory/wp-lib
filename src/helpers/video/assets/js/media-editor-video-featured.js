(function ($, _) {
  /**
   * @namespace wp.media.videoFeatured
   * @memberOf wp.media
   */
  wp.media.videoFeatured = {
    /**
     * Get the featured image post ID
     *
     * @return {wp.media.view.settings.post.videoFeaturedId|number}
     */
    get: function () {
      return wp.media.view.settings.post.videoFeaturedId;
    },
    /**
     * Sets the featured image ID property and sets the HTML in the post meta box to the new featured image.
     *
     * @param {number} id The post ID of the featured image, or -1 to unset it.
     */
    set: function (id) {
      var settings = wp.media.view.settings;

      settings.post.videoFeaturedId = id;

      wp.media.post('get_post_video_html', {
        post_id: settings.post.id,
        video_id: settings.post.videoFeaturedId,
        _wpnonce: settings.post.nonce
      }).done(function (html) {
        if (html === '0') {
          window.alert(wp.i18n.__('Could not set that as the video. Try a different attachment.'));
          return;
        }
        $('.inside', '#postvideodiv').html(html);
      });
    },
    /**
     * Remove the featured image id, save the post thumbnail data and
     * set the HTML in the post meta box to no featured image.
     */
    remove: function () {
      wp.media.videoFeatured.set(-1);
    },
    /**
     * The Featured Image workflow
     *
     * @this wp.media.videoFeatured
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
        id: 'featured-video',
        title: 'Featured video',
        button: {
          text: 'Set featured video'
        },
        library: {
          type: ['video']
        },
        multiple: false
      });

      this._frame.on('open', function () {
        var selection = frame.state().get('selection');
        if (selection.length === 0) {
          var selected = wp.media.videoFeatured.get();
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
        wp.media.videoFeatured.set(selection ? selection.id : -1);
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
      $('#postvideodiv').on('click', '#set-post-video', function (event) {
        event.preventDefault();
        // Stop propagation to prevent thickbox from activating.
        event.stopPropagation();

        wp.media.videoFeatured.frame().open();
      }).on('click', '#remove-post-video', function () {
        wp.media.videoFeatured.remove();
        return false;
      });
    }
  };
  $(wp.media.videoFeatured.init);
}(jQuery, _));

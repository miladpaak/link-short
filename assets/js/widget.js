(function ($) {
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      var textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();

      try {
        var successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        if (successful) {
          resolve();
        } else {
          reject(new Error('Copy command failed'));
        }
      } catch (err) {
        document.body.removeChild(textArea);
        reject(err);
      }
    });
  }

  $(document).on('click', '.elsb-short-btn', function () {
    var $btn = $(this);
    var $widgetRoot = $btn.closest('.elsb-wrap');
    var $msg = $widgetRoot.find('.elsb-message');
    var pageUrl = window.location.href;

    $btn.prop('disabled', true);
    $msg.text(elsbData.messages.shortening);

    $.post(elsbData.ajaxUrl, {
      action: 'elsb_shorten_url',
      nonce: elsbData.nonce,
      url: pageUrl
    })
      .done(function (res) {
        if (!res || !res.success || !res.data || !res.data.shortUrl) {
          $msg.text(elsbData.messages.failed);
          return;
        }

        copyToClipboard(res.data.shortUrl)
          .then(function () {
            $msg.text(elsbData.messages.copied + ' (' + res.data.shortUrl + ')');
          })
          .catch(function () {
            $msg.text(elsbData.messages.copyFailed + ' ' + res.data.shortUrl);
          });
      })
      .fail(function (xhr) {
        var apiMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
          ? xhr.responseJSON.data.message
          : elsbData.messages.failed;
        $msg.text(apiMessage);
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
})(jQuery);

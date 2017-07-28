/* global jQuery:false, List:false, wpApiSettings:false */


jQuery(document).ready(function($) {
  if (! $("#my-contacts").length || ! $("#my-contacts .list").length) {
    return;
  }
  var myContacts = new List('my-contacts', {
    valueNames: [
      'post_title',
      'assigned_name',
      { name: 'permalink', attr: 'href' },
    ],
    page: 30,
    pagination: true,
  });

  $.ajax({
    url: wpApiSettings.root + "dt-hooks/v1/user/1/contacts",
    beforeSend: function(xhr) {
      xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
    },
    success: function(data) {
      myContacts.clear();
      myContacts.add(data);
    },
    error: function() {
      $(".js-list-contacts-loading").text(wpApiSettings.txt_error);
    },
    complete: function() {
      $("#my-contacts .js-search-tools")
        .removeClass("faded-out")
        .find("button.sort[data-sort]")
          .removeAttr("disabled")
        .end()
        .find("input.search")
          .removeAttr("disabled");
    },
  });

});

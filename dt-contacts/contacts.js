let post_id        = window.detailsSettings.post_id
let post_type      = window.detailsSettings.post_type
let post           = window.detailsSettings.post_fields
jQuery(document).ready(function($) {

  /**
   * User-select
   */
  if ( !post.corresponds_to_user && $('.js-typeahead-user-select').length) {
    $.typeahead({
      input: '.js-typeahead-user-select',
      minLength: 0,
      accent: true,
      searchOnFocus: true,
      source: TYPEAHEADS.typeaheadUserSource(),
      templateValue: "{{name}}",
      template: function (query, item) {
        return `<span class="row">
          <span class="avatar"><img src="{{avatar}}"/> </span>
          <span>${window.lodash.escape( item.name )}</span>
        </span>`
      },
      dynamic: true,
      hint: true,
      emptyTemplate: window.lodash.escape(window.wpApiShare.translations.no_records_found),
      callback: {
        onClick: function (node, a, item) {
          jQuery.ajax({
            type: "GET",
            data: {"user_id": item.ID},
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            url: window.wpApiShare.root + 'dt/v1/users/contact-id',
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-WP-Nonce', window.wpApiShare.nonce);
            }
          }).then(user_contact_id => {
            $('.confirm-merge-with-user').show()
            $('#confirm-merge-with-user-dupe-id').val(user_contact_id)
          })
        },
        onResult: function (node, query, result, resultCount) {
          let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
          $('#user-select-result-container').html(text);
        },
        onHideLayout: function () {
          $('.user-select-result-container').html("");
        },
      },
    });
    let user_select_input = $(`.js-typeahead-user-select`)
    $('.search_user-select').on('click', function () {
      user_select_input.val("")
      user_select_input.trigger('input.typeahead')
      user_select_input.focus()
    })
  }

  $("#create-user-return").on("click", function (e) {
    e.preventDefault();
    $(this).toggleClass("loading")
    let $inputs = $('#create-user-form :input');
    let values = {};
    $inputs.each(function() {
        values[this.name] = $(this).val();
    });
    values["corresponds_to_contact"] = post_id;
    window.API.create_user(values).then(()=>{
      $(this).removeClass("loading")
      $(`#make-user-from-contact-modal`).foundation('close')
      location.reload();
    }).catch(err=>{
      $(this).removeClass("loading")
      $('#create-user-errors').html(window.lodash.get(err, "responseJSON.message", "Something went wrong"))
    })
    return false;
  })


  /**
   * Duplicates
   */
  window.makeRequestOnPosts( "GET", `${post_type}/${post_id}/duplicates` ).then(response => {
    if ( response.ids && response.ids.length > 0 ){
      $('.details-title-section').html(`
        <button class="button hollow center-items duplicates-detected-button" id="duplicates-detected-notice">
          <img style="height:20px" src="${window.lodash.escape( window.wpApiShare.template_dir )}/dt-assets/images/broken.svg"/>
          <strong>${window.lodash.escape(window.detailsSettings.translations.duplicates_detected)}</strong>
        </button>
      `)
    }
  })
  let merge_dupe_edit_modal =  $('#merge-dupe-edit-modal')
  $(document).on( 'click', '#duplicates-detected-notice', function(){
    merge_dupe_edit_modal.foundation('open');
  })

  let possible_duplicates = [];
  let openedOnce = false
  merge_dupe_edit_modal.on("open.zf.reveal", function () {
    if ( !openedOnce ){

      let original_contact_html = `<div class="merge-modal-contact-row">
        <h5>
        <a href="${window.wpApiShare.site_url}/${post_type}/${window.lodash.escape(post_id)}" class="merge-modal-contact-name" target=_blank>
        ${ window.lodash.escape(post.name) }
        <span class="merge-modal-contact-info"> #${post_id} (${window.lodash.get(post, "overall_status.label") ||""}) </span>
        </a>
        </h5>`
      window.lodash.forOwn(window.detailsSettings.post_settings.fields, (field_settings, field_key)=>{
        if ( field_settings.type === "communication_channel" && post[field_key] ){
          post[field_key].forEach( contact_info=>{
            if ( contact_info.value !== '' ){
              original_contact_html +=`<img src='${window.lodash.escape(field_settings.icon)}'><span style="margin-right: 15px;">&nbsp;${window.lodash.escape(contact_info.value)}</span>`
            }
          })
        }
      })
      original_contact_html += `</div>`
      $('#original-contact').append(original_contact_html);

      window.API.get_duplicates_on_post("contacts", post_id).done(dups_with_data=> {
        possible_duplicates = dups_with_data
        $("#duplicates-spinner").removeClass("active")
        loadDuplicates();
      })

      openedOnce = true;
    }
  })
  function loadDuplicates() {
    let dups_with_data = possible_duplicates
    if (dups_with_data) {
      let $duplicates = $('#duplicates_list');
      $duplicates.html("");

      let already_dismissed = window.lodash.get(post, 'duplicate_data.override', []).map(id=>parseInt(id))

      let html = ``
      dups_with_data.sort((a, b) => a.points > b.points ? -1:1).forEach((dupe) => {
        if (!already_dismissed.includes(parseInt(dupe.ID))) {
          html += dup_row(dupe)
        }
      })
      if ( html ){
        $duplicates.append(html);
      } else {
        $('#no_dups_message').show()
      }
      let dismissed_html = ``;
      dups_with_data.sort((a, b) => a.points > b.points ? -1:1).forEach((dupe) => {
        if (already_dismissed.includes(parseInt(dupe.ID))) {
          dismissed_html += dup_row(dupe, true)
        }
      })
      if (dismissed_html) {
        dismissed_html = `<h4 class="merge-modal-subheading">${window.lodash.escape(window.detailsSettings.translations.dismissed_duplicates)}</h4>`
          + dismissed_html
        $duplicates.append(dismissed_html);
      }
    }
  }
  let dup_row = (dupe, dismissed_row = false)=>{
    let html = ``;
    let dups_on_fields = window.lodash.uniq(dupe.fields.map(field=>{
      return window.lodash.get(window.detailsSettings.post_settings, `fields[${field.field}].name`)
    }))
    let matched_values = dupe.fields.map(f=>f.value)
    html += `<div class="merge-modal-contact-row">
      <h5>
      <a href="${window.wpApiShare.site_url}/${post_type}/${window.lodash.escape(dupe.ID)}" class="merge-modal-contact-name" target=_blank>
      ${ window.lodash.escape(dupe.post.name) }
      <span class="merge-modal-contact-info"> #${dupe.ID} (${window.lodash.get(dupe.post, "overall_status.label") ||""}) </span>
      </a>
    </h5>`
    html += `${window.lodash.escape(window.detailsSettings.translations.duplicates_on).replace('%s', '<strong>' + window.lodash.escape(dups_on_fields.join( ', ')) + '</strong>' )}<br />`

    window.lodash.forOwn(window.detailsSettings.post_settings.fields, (field_settings, field_key)=>{
      if ( field_settings.type === "communication_channel" && dupe.post[field_key] ){
        dupe.post[field_key].forEach( contact_info=>{
          if ( contact_info.value !== '' ){
            html +=`<img src='${window.lodash.escape(field_settings.icon)}'><span style="margin-right: 15px; ${matched_values.includes(contact_info.value) ? 'font-weight:bold;' : ''}">&nbsp;${window.lodash.escape(contact_info.value)}</span>`
          }
        })
      }
    })
    html += `<br>`
    if (dupe.post.overall_status?.key === 'closed' && dupe.post.reason_closed && window.detailsSettings.post_settings.fields.reason_closed) {
      html += `${window.lodash.escape(window.detailsSettings.post_settings.fields.reason_closed?.name)}: <strong>${window.lodash.escape(dupe.post.reason_closed.label)}</strong>`
      html += `<br>`
    }
    if ( !dismissed_row ){
      html += `<button class='mergelinks dismiss-duplicate merge-modal-button' data-id='${window.lodash.escape(dupe.ID)}'><a>${window.lodash.escape(window.detailsSettings.translations.dismiss)}</a></button>`
    }
    html += `
       <button type='submit' class="merge-post merge-modal-button" data-dup-id="${window.lodash.escape(dupe.ID)}">
          <a>${window.lodash.escape(window.detailsSettings.translations.merge)}</a>
      </button>
    `

    html += `</div>`
    return html;
  }

  $(document).on( "click", ".merge-post", function () {
    let dup_id = $(this).data('dup-id')
    window.location = `${window.wpApiShare.site_url}/${post_type}/mergedetails?dupeid=${dup_id}&currentid=${post_id}`
  })

  $(document).on( "click", ".dismiss-duplicate", function () {
    let id = $(this).data('id');
    makeRequestOnPosts('POST', `${post_type}/${post_id}/dismiss-duplicates`, {'id':id}).then(resp=>{
      post.duplicate_data = resp;
      loadDuplicates();
      adjust_duplicates_detected_notice_display(post.ID);
    })
  })
  $('#dismiss_all_duplicates').on( 'click', function () {
    makeRequestOnPosts('POST', `${post_type}/${post.ID}/dismiss-duplicates`, {'id':'all'}).then(resp=> {
      post.duplicate_data = resp;
      loadDuplicates();
      adjust_duplicates_detected_notice_display(post.ID);
    })
  })

  function adjust_duplicates_detected_notice_display(orig_post_id) {
    window.makeRequestOnPosts("GET", `contacts/${orig_post_id}/duplicates`).then(response => {
      if (response.ids && response.ids.length === 0) {
        $('#duplicates-detected-notice').hide();
      }
    });
  }

  //open duplicates modal if 'open-duplicates' param is is url
  let open_duplicates = window.SHAREDFUNCTIONS.get_url_param("open-duplicates")
  if ( open_duplicates === '1' ){
    merge_dupe_edit_modal.foundation('open');
  }

  /**
   * Transfer Contact
   */
  $('#transfer_confirm_button').on('click',function() {
    $(this).addClass('loading')
    let siteId = $('#transfer_contact').val()
    if ( ! siteId ) {
      return;
    }
    API.transfer_contact( post_id, siteId )
    .then(data=>{
      if ( data ) {
        location.reload();
      }
    }).catch(err=>{
      console.error(err)
      // try a second time.
      API.transfer_contact( post_id, siteId )
      .then(data=>{
        if ( data ) {
          location.reload();
        }
      }).catch(err=> {
        $(this).removeClass('loading')
        jQuery('#transfer_spinner').empty().append(err.responseJSON.message).append('&nbsp;' + window.detailsSettings.translations.transfer_error)
        console.error(err)
      })
    })
  });

  /**
   * Transfer Contact Summary Update
   */
  $('#transfer_contact_summary_update_button').on('click', function () {
    $(this).addClass('loading');
    let comments = $('#transfer_contact_summary_update_comment');

    let update = comments.val().trim();
    if (!update) {
      $(this).removeClass('loading');
      return;
    }

    API.transfer_contact_summary_update(post_id, update)
      .then(data => {
        $(this).removeClass('loading');
        transfer_contact_summary_update_results(data);

      }).catch(err => {
      console.error(err)
      // try a second time.
      API.transfer_contact_summary_update(post_id, update)
        .then(data => {
          $(this).removeClass('loading');
          transfer_contact_summary_update_results(data);

        }).catch(err => {
        console.error(err);
        $(this).removeClass('loading');
        $('#transfer_contact_summary_update_message').fadeOut('fast').html(window.detailsSettings.translations.transfer_update_error).fadeIn('fast');

      });
    });

    // Clear comments textarea
    comments.val('');
  });

  function transfer_contact_summary_update_results(data) {
    let message = $('#transfer_contact_summary_update_message');

    if (data['success']) {
      message.fadeOut('fast').html(window.detailsSettings.translations.transfer_update_success).fadeIn('fast');
    } else {
      message.fadeOut('fast').html(window.detailsSettings.translations.transfer_update_error).fadeIn('fast');
    }
  }

})

/**
 * DMM contacts section
 */
jQuery(document).ready(function($) {
  $('.quick-action-menu').on("click", function () {
    let fieldKey = $(this).data("id")

    let data = {}
    let numberIndicator = $(`span.${fieldKey}`)
    let newNumber = parseInt(numberIndicator.first().text() || "0" ) + 1
    data[fieldKey] = newNumber
    API.update_post('contacts', post_id, data).then(()=>{
      record_updated(false)
    })
    .catch(err=>{
      console.log("error")
      console.log(err)
    })

    if (fieldKey.indexOf("quick_button")>-1){
      numberIndicator.text(newNumber)
    }
  })

  // Baptism date
  let modalBaptismDatePicker = $('input#modal-baptism-date-picker');
  modalBaptismDatePicker.datepicker({
    constrainInput: false,
    dateFormat: 'yy-mm-dd',
    onSelect: function (date) {
      API.update_post('contacts', post_id, { baptism_date: date }).then((resp)=>{
        if (this.value) {
          this.value = window.SHAREDFUNCTIONS.formatDate(resp["baptism_date"]["timestamp"]);
        }
      }).catch(handleAjaxError)
    },
    changeMonth: true,
    changeYear: true,
    yearRange: "-20:+10",
  })
  let openBaptismModal = function( newContact ){
    if ( !post.baptism_date || !(post.milestones || []).includes('milestone_baptized') || (post.baptized_by || []).length === 0 ){
      $('#baptism-modal').foundation('open');
      if (!window.Typeahead['.js-typeahead-modal_baptized_by']) {
        $.typeahead({
          input: '.js-typeahead-modal_baptized_by',
          minLength: 0,
          accent: true,
          searchOnFocus: true,
          source: TYPEAHEADS.typeaheadContactsSource(),
          templateValue: "{{name}}",
          template: window.TYPEAHEADS.contactListRowTemplate,
          matcher: function (item) {
            return parseInt(item.ID) !== parseInt(post.ID)
          },
          dynamic: true,
          hint: true,
          emptyTemplate: window.lodash.escape(window.wpApiShare.translations.no_records_found),
          multiselect: {
            matchOn: ["ID"],
            data: function () {
              return (post["baptized_by"] || [] ).map(g=>{
                return {ID:g.ID, name:g.post_title}
              })
            }, callback: {
              onCancel: function (node, item) {
                API.update_post('contacts', post_id, {"baptized_by": {values:[{value:item.ID, delete:true}]}})
                .catch(err => { console.error(err) })
              }
            },
            href: window.lodash.escape( window.wpApiShare.site_url ) + "/contacts/{{ID}}"
          },
          callback: {
            onClick: function (node, a, item) {
              API.update_post('contacts', post_id, {"baptized_by": {values:[{"value":item.ID}]}})
              .catch(err => { console.error(err) })
              this.addMultiselectItemLayout(item)
              event.preventDefault()
              this.hideLayout();
              this.resetInput();
            },
            onResult: function (node, query, result, resultCount) {
              let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
              $('#modal_baptized_by-result-container').html(text);
            },
            onHideLayout: function () {
              $('.modal_baptized_by-result-container').html("");
            },
          },
        });
      }
      if ( window.lodash.get(newContact, "baptism_date.timestamp", 0) > 0){
        modalBaptismDatePicker.datepicker('setDate', moment.unix(newContact['baptism_date']["timestamp"]).format("YYYY-MM-DD"));
        modalBaptismDatePicker.val(window.SHAREDFUNCTIONS.formatDate(newContact['baptism_date']["timestamp"]) )
      }
    }
    post = newContact
  }
  $('#close-baptism-modal').on('click', function () {
    location.reload()
  })

  /**
   * detect if an update is made on the baptized_by field.
   */
  $( document ).on( 'dt_record_updated', function (e, response, request ){
    if ( window.lodash.get(request, "baptized_by" ) && window.lodash.get( response, "baptized_by[0]" ) ) {
      openBaptismModal( response )
    }
  })

  /**
   * detect if an update is made on the milestone field for baptized.
   */
  $( document ).on( 'dt_multi_select-updated', function (e, newContact, fieldKey, optionKey, action) {
    if ( optionKey === 'milestone_baptized' && action === 'add' ){
      openBaptismModal(newContact)
    }
  })
  /**
   * If a baptism date is added
   */
  $( document ).on( 'dt_date_picker-updated', function (e, newContact, id, date){
    if (id === 'baptism_date' && newContact.baptism_date && newContact.baptism_date.timestamp) {
      openBaptismModal(newContact)
    }
  })
})

/**
 * Access contacts section
 */

function setStatus(contact, openModal) {
  let statusSelect = $('#overall_status')
  let status = window.lodash.get(contact, "overall_status.key")
  let reasonLabel = window.lodash.get(contact, `reason_${status}.label`)
  let statusColor = window.lodash.get(window.detailsSettings,
    `post_settings.fields.overall_status.default.${status}.color`
  )
  statusSelect.val(status)

  if (openModal){
    if (status === "paused"){
      $('#paused-contact-modal').foundation('open');
    } else if (status === "closed"){
      $('#closed-contact-modal').foundation('open');
    } else if (status === 'unassignable'){
      $('#unassignable-contact-modal').foundation('open');
    }
  }

  if (statusColor){
    statusSelect.css("background-color", statusColor)
  } else {
    statusSelect.css("background-color", "#366184")
  }

  if (["paused", "closed", "unassignable"].includes(status)){
    $('#reason').text(`(${reasonLabel})`)
    $(`#edit-reason`).show()
  } else {
    $('#reason').text(``)
    $(`#edit-reason`).hide()
  }
}

function updateCriticalPath(key) {
  $('#seeker_path').val(key)
  let seekerPathKeys = window.lodash.keys(post.seeker_path.default)
  let percentage = (window.lodash.indexOf(seekerPathKeys, key) || 0) / (seekerPathKeys.length-1) * 100
  $('#seeker-progress').css("width", `${percentage}%`)
}


jQuery(document).ready(function($) {
  $( document ).on( 'dt_record_updated', function (e, response, request ){
    post = response
    window.lodash.forOwn(request, (val, key)=>{
      if (key.indexOf("quick_button")>-1){
        if (window.lodash.get(response, "seeker_path.key")){
          updateCriticalPath(response.seeker_path.key)
        }
      }
      if (key === "overall_status" || key === "assigned_to"){
        setStatus(response)
      }
    })
  })
  $('#content')[0].addEventListener('comment_posted', function (e) {
    if ( $('.update-needed').prop("checked") === true ){
      API.get_post("contacts",  post_id ).then(resp=>{
        post = resp
        record_updated(window.lodash.get(resp, "requires_update") === true )
      }).catch(err => { console.error(err) })
    }
  }, false);

  $( document ).on( 'select-field-updated', function (e, newContact, id, val) {
    if (id === 'seeker_path') {
      // updateCriticalPath(newContact.seeker_path.key)
      // refresh_quick_action_buttons(newContact)
    } else if (id === 'reason_unassignable') {
      setStatus(newContact)
    } else if (id === 'overall_status') {
      setStatus(newContact, true)
    }
  })
  //confirm setting a reason for a status.
  let confirmButton = $(".confirm-reason-button")
  confirmButton.on("click", function () {
    let field = $(this).data('field')
    let select = $(`#reason-${field}-options`)
    $(this).toggleClass('loading')
    let data = {overall_status:field}
    data[`reason_${field}`] = select.val()
    API.update_post('contacts', post_id, data).then(contactData=>{
      $(this).toggleClass('loading')
      $(`#${field}-contact-modal`).foundation('close')
      setStatus(contactData)
    }).catch(err => { console.error(err) })
  })

  $('#edit-reason').on('click', function () {
    setStatus(post, true)
  })
  /**
   * Accept or decline a contact
   */
  $('.accept-decline').on('click', function () {
    let action = $(this).data("action")
    let data = {accept:action === "accept"}
    makeRequestOnPosts( "POST", `contacts/${post_id}/accept`, data)
    .then(function (resp) {
      setStatus(resp)
      jQuery('#accept-contact').hide()
    }).catch(err=>{
      console.log('error')
      console.log(err.responseText)
    })
  })

  let dispatch_users = [];
  let selected_role = "multiplier";
  let dispatch_users_promise = null
  let list_filters = $('#user-list-filters')
  let defined_list_section = $('#defined-lists')
  let populated_list = $('.populated-list')

  jQuery('.advanced_user_select').on('click', function (){
    $('#assigned_to_user_modal').foundation('open');
    if ( dispatch_users_promise === null ){
      $('#assigned_to_user_modal #dispatch-tile-loader').addClass('active')
      dispatch_users_promise = window.makeRequest( 'GET', 'assignment-list', {location_ids: (post.location_grid||[]).map(l=>l.id), 'post_id': post_id, 'post_type': post_type}, 'dt-posts/v2/contacts' )
      dispatch_users_promise.then(response=>{
        $('#assigned_to_user_modal #dispatch-tile-loader').removeClass('active')
        dispatch_users = response
        $('.users-select-panel').show()
        show_assignment_tab( selected_role )
      })
    } else {
      $('.users-select-panel').show()
      show_assignment_tab( selected_role )
    }
  })

  //change tab
  $('#assign-role-tabs a').on('click', function () {
    selected_role = $(this).data('field')
    $('#search-users-filtered').attr("placeholder", $(this).text().trim())
    show_assignment_tab( selected_role )
  })

  function show_assignment_tab( tab = 'multiplier' ){
    const contact_languages = (window.lodash.get(window.detailsSettings, "post_fields.languages"))
      ? window.detailsSettings.post_fields.languages
      : []
    const contact_gender = (window.lodash.get(window.detailsSettings, "post_fields.gender"))
      ? window.detailsSettings.post_fields.gender
      : { key: null, label: "" }

    let filters = `<a data-id="all" style="color: black; font-weight: bold">${window.lodash.escape(window.dt_contacts.translations.all)}</a> | `

    defined_list_section.show()
    let users_with_role = dispatch_users.filter(u => u.roles.includes(tab))
    let filter_options = {
      all: users_with_role.sort((a, b) => {
        if (a.weight && b.weight) {
          if (a.weight === b.weight) {
            return 0;
          } else {
            return (a.weight > b.weight) ? -1 : 1;
          }
        } else {
          return a.name.localeCompare(b.name);
        }
      }),
      ready: users_with_role.filter(m=>m.status==='active'),
      recent: users_with_role.concat().sort((a,b)=>b.last_assignment-a.last_assignment),
      language: users_with_role.filter(({ languages }) => languages.some(language => contact_languages.includes(language))),
      gender: users_with_role.filter(m => contact_gender.label !== "" && m.gender === contact_gender.key),
      location: users_with_role.concat().filter(m=>m.location!==null).sort((a,b)=>a.location-b.location)
    }
    populate_users_list( users_with_role )
    filters += `<a data-id="ready">${window.lodash.escape(window.dt_contacts.translations.ready)}</a> | `
    filters += `<a data-id="recent">${window.lodash.escape(window.dt_contacts.translations.recent)}</a> | `
    filters += `<a data-id="language">${window.lodash.escape(window.dt_contacts.translations.language)}</a> | `
    filters += `<a data-id="gender">${window.lodash.escape(window.dt_contacts.translations.gender)}</a> | `
    filters += `<a data-id="location">${window.lodash.escape(window.dt_contacts.translations.location)}</a>`
    list_filters.html(filters)


    $('#user-list-filters a').on('click', function () {
      $( '#user-list-filters a' ).css("color","").css("font-weight","")
      $(this).css("color", "black").css("font-weight", "bold")
      let key = $(this).data('id')
      populate_users_list( filter_options[key] || [], key )
    })
  }

  function populate_users_list(users, tab = 'all') {
    let user_rows = '';
    users.forEach( m => {
      user_rows += `<div class="assigned-to-row" dir="auto">
        <span>
          <span class="avatar"><img style="vertical-align: text-bottom" src="${window.lodash.escape( m.avatar )}"/></span>
          ${window.lodash.escape(m.name)}
        </span>
        ${ m.status_color ? `<span class="status-square" style="background-color: ${ window.lodash.escape(m.status_color) }">&nbsp;</span>` : '' }
        ${ m.update_needed ? `
          <span>
            <img style="height: 12px;" src="${window.lodash.escape(window.wpApiShare.template_dir)}/dt-assets/images/broken.svg"/>
            <span style="font-size: 14px">${ window.lodash.escape(m.update_needed) }</span>
          </span>` : ''
      }
        ${ m.best_location_match ? `<span>(${ window.lodash.escape(m.best_location_match) })</span>` : ''

      }
        <div style="flex-grow: 1"></div>
        <button class="button hollow tiny trigger-assignment" data-id="${ window.lodash.escape(m.ID) }" style="margin-bottom: 3px">
           ${window.lodash.escape(window.dt_contacts.translations.assign)}
        </button>
      </div>
      `
    })
    if ( user_rows.length === 0 ){
      user_rows = `<p style="padding:1rem">${window.lodash.escape(window.wpApiShare.translations.no_records_found.replace("{{query}}", window.dt_contacts.translations[tab]))}</p>`
    }
    populated_list.html(user_rows)

  }

  $(document).on('click', '.trigger-assignment', function () {
    let user_id = $(this).data('id')
    $('#dispatch-tile-loader').addClass('active')
    let status = selected_role === "dispatcher" ? "unassigned" : "assigned"
    API.update_post(
      'contacts',
      window.detailsSettings.post_fields.ID,
      {
        assigned_to: 'user-' + user_id,
        overall_status: status
      }
    ).then(function (response) {
      $('#dispatch-tile-loader').removeClass('active')
      setStatus(response)
      $(`.js-typeahead-assigned_to`).val(window.lodash.escape(response.assigned_to.display)).blur()
      $('#assigned_to_user_modal').foundation('close');
    })
  })

  /**
   * search name in list
   */
  $('#search-users-filtered').on('input', function () {
    $( '#user-list-filters a' ).css("color","").css("font-weight","")
    let search_text = $(this).val().normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase()
    let users_with_role = dispatch_users.filter(u => u.roles.includes(selected_role) )
    let match_name = users_with_role.filter(u =>
      u.name.normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase().includes( search_text )
    )
    populate_users_list(match_name)
  })
})

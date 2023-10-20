<?php

add_filter( 'dt_custom_fields_settings', function ( $fields, $post_type ){
    if ( $post_type !== 'contacts' ){
        return $fields;
    }

    $fields['nickname'] = [
        'name' => __( 'Nickname', 'disciple_tools' ),
        'type' => 'text',
        'tile' => 'details',
        'icon' => get_template_directory_uri() . '/dt-assets/images/nametag.svg?v=2',
        'hidden' => true,
    ];
    $contact_preferences = get_option( 'dt_contact_preferences', [] );
    $fields['type'] = [
        'name'        => __( 'Contact Type', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'user' => [
                'label' => __( 'User', 'disciple_tools' ),
                'description' => __( 'Representing a User in the system', 'disciple_tools' ),
                'color' => '#3F729B',
                'hidden' => true,
                'in_create_form' => false,
            ],
            'personal' => [
                'label' => __( 'Private Contact', 'disciple_tools' ),
                'color' => '#9b379b',
                'description' => __( 'A friend, family member or acquaintance', 'disciple_tools' ),
                'visibility' => __( 'Only me', 'disciple_tools' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/locked.svg?v=2',
                'order' => 50,
                'hidden' => !empty( $contact_preferences['hide_personal_contact_type'] ),
                'default' => false
            ],
            'placeholder' => [
                'label' => __( 'Private Connection', 'disciple_tools' ),
                'color' => '#FF9800',
                'description' => __( 'Connected to a contact, or generational fruit', 'disciple_tools' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/locked.svg?v=2',
                'order' => 40,
                'visibility' => __( 'Only me', 'disciple_tools' ),
                'in_create_form' => false,
                'hidden' => !empty( $contact_preferences['hide_personal_contact_type'] ),
            ],
            'access' => [
                'label' => __( 'Standard Contact', 'disciple_tools' ),
                'color' => '#2196F3',
                'description' => __( 'A contact to collaborate on', 'disciple_tools' ),
                'visibility' => __( 'Me and project leadership', 'disciple_tools' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/share.svg?v=2',
                'order' => 20,
                'default' => true,
            ],
            'access_placeholder' => [
                'label' => __( 'Connection', 'disciple_tools' ),
                'color' => '#FF9800',
                'description' => __( 'Connected to a contact, or generational fruit', 'disciple_tools' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/share.svg?v=2',
                'order' => 40,
                'visibility' => __( 'Collaborators', 'disciple_tools' ),
                'in_create_form' => false,
            ],
        ],
        'description' => 'See full documentation here: https://disciple.tools/user-docs/getting-started-info/contacts/contact-types',
        'icon' => get_template_directory_uri() . '/dt-assets/images/circle-square-triangle.svg?v=2',
        'customizable' => false
    ];
    $fields['duplicate_data'] = [
        'name' => 'Duplicates', //system string does not need translation
        'type' => 'array',
        'default' => [],
        'hidden' => true,
        'customizable' => false,
    ];
    $fields['duplicate_of'] = [
        'name' => 'Duplicate of', //system string does not need translation
        'type' => 'text',
        'hidden' => true,
        'customizable' => false,
    ];

    $fields['languages'] = [
        'name' => __( 'Languages', 'disciple_tools' ),
        'type' => 'multi_select',
        'default' => dt_get_option( 'dt_working_languages' ) ?: [],
        'icon' => get_template_directory_uri() . '/dt-assets/images/languages.svg?v=2',
        'tile' => 'no_tile'
    ];

    //add communication channels
    $fields['contact_phone'] = [
        'name' => __( 'Phone', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/phone.svg?v=2',
        'type' => 'communication_channel',
        'tile' => 'details',
        'customizable' => false,
        'in_create_form' => true,
        'messagingServices' => [
            'Signal' => [
                'name' => __( 'Signal', 'disciple_tools' ),
                'link' => 'https://signal.me/#p/PHONE_NUMBER',
                'icon' => get_template_directory_uri() . '/dt-assets/images/signal.svg'
            ],
            'Viber' => [
                'name' => __( 'Viber', 'disciple_tools' ),
                'link' => 'viber://chat?number=PHONE_NUMBER',
                'icon' => get_template_directory_uri() . '/dt-assets/images/viber.svg'
            ],
            'Whatsapp' => [
                'name' => __( 'WhatsApp', 'disciple_tools' ),
                'link' => 'https://api.whatsapp.com/send?phone=PHONE_NUMBER_NO_PLUS',
                'icon' => get_template_directory_uri() . '/dt-assets/images/signal.svg'
            ],
        ]
    ];
    $fields['contact_email'] = [
        'name' => __( 'Email', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/email.svg?v=2',
        'type' => 'communication_channel',
        'tile' => 'details',
        'customizable' => false,
        'in_create_form' => true,
    ];

    // add location fields
    $fields['location_grid'] = [
        'name'        => __( 'Locations', 'disciple_tools' ),
        'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
        'type'        => 'location',
        'mapbox'    => false,
        'in_create_form' => true,
        'tile' => 'details',
        'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg?v=2',
    ];
    $fields['location_grid_meta'] = [
        'name'        => __( 'Locations or Address', 'disciple_tools' ),
        'type'        => 'location_meta',
        'tile'      => 'details',
        'mapbox'    => false,
        'hidden' => true,
        'in_create_form' => true,
        'icon' => get_template_directory_uri() . '/dt-assets/images/map-marker-multiple.svg?v=2',
    ];
    $fields['contact_address'] = [
        'name' => __( 'Address', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/house.svg?v=2',
        'type' => 'communication_channel',
        'tile' => 'details',
        'mapbox'    => false,
        'customizable' => false,
        'in_create_form' => true,
    ];
    if ( DT_Mapbox_API::get_key() ){
        $fields['contact_address']['custom_display'] = true;
        $fields['contact_address']['mapbox'] = true;
        $fields['contact_address']['hidden'] = true;
        unset( $fields['contact_address']['tile'] );
        $fields['location_grid']['mapbox'] = true;
        $fields['location_grid']['hidden'] = true;
        $fields['location_grid_meta']['mapbox'] = true;
        $fields['location_grid_meta']['hidden'] = false;
    }

    // add social media
    $fields['contact_facebook'] = [
        'name' => __( 'Facebook', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/facebook.svg?v=2',
        'hide_domain' => true,
        'type' => 'communication_channel',
        'tile' => 'details',
        'customizable' => false,
        'hidden' => true,
    ];
    $fields['contact_twitter'] = [
        'name' => __( 'Twitter', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/twitter.svg?v=2',
        'hide_domain' => true,
        'type' => 'communication_channel',
        'tile' => 'details',
        'customizable' => false,
        'hidden' => true,
    ];
    $fields['contact_other'] = [
        'name' => __( 'Other Social Links', 'disciple_tools' ),
        'icon' => get_template_directory_uri() . '/dt-assets/images/chat.svg?v=2',
        'hide_domain' => false,
        'type' => 'communication_channel',
        'tile' => 'details',
        'customizable' => false,
        'hidden' => true,
    ];

    $fields['relation'] = [
        'name' => sprintf( _x( 'Connections to other %s', 'connections to other records', 'disciple_tools' ), __( 'Contacts', 'disciple_tools' ) ),
        'description' => _x( 'Relationship this contact has with another contact in the system.', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'any',
        'p2p_key' => 'contacts_to_relation',
        'tile' => 'other',
        'in_create_form' => [ 'placeholder' ],
        'icon' => get_template_directory_uri() . '/dt-assets/images/connection-people.svg?v=2',
    ];

    $fields['gender'] = [
        'name'        => __( 'Gender', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'male'    => [ 'label' => __( 'Male', 'disciple_tools' ) ],
            'female'  => [ 'label' => __( 'Female', 'disciple_tools' ) ],
        ],
        'tile'     => 'details',
        'icon' => get_template_directory_uri() . '/dt-assets/images/gender-male-female.svg',
        'hidden' => true,
    ];

    $fields['age'] = [
        'name'        => __( 'Age', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'not-set' => [ 'label' => '' ],
            '<19'     => [ 'label' => __( 'Under 18 years old', 'disciple_tools' ) ],
            '<26'     => [ 'label' => __( '18-25 years old', 'disciple_tools' ) ],
            '<41'     => [ 'label' => __( '26-40 years old', 'disciple_tools' ) ],
            '>41'     => [ 'label' => __( 'Over 40 years old', 'disciple_tools' ) ],
        ],
        'tile'     => 'details',
        'icon' => get_template_directory_uri() . '/dt-assets/images/contact-age.svg?v=2',
        'select_cannot_be_empty' => true, //backwards compatible since we already have an "none" value
        'hidden' => true,
    ];

    $fields['requires_update'] = [
        'name'        => __( 'Requires Update', 'disciple_tools' ),
        'type'        => 'boolean',
        'default'     => false,
        'customizable' => false,
    ];

    $fields['overall_status'] = [
        'name' => __( 'Contact Status', 'disciple_tools' ),
        'description' => _x( 'The Contact Status describes the progress in communicating with the contact.', 'Contact Status field description', 'disciple_tools' ),
        'type' => 'key_select',
        'default' => [
            'new'   => [
                'label' => __( 'New Contact', 'disciple_tools' ),
                'description' => _x( 'The contact is new in the system.', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#F43636',
            ],
            'unassignable' => [
                'label' => __( 'Not Ready', 'disciple_tools' ),
                'description' => _x( 'There is not enough information to move forward with the contact at this time.', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#FF9800',
                'hidden' => true,
            ],
            'unassigned'   => [
                'label' => __( 'Dispatch Needed', 'disciple_tools' ),
                'description' => _x( 'This contact needs to be assigned to a multiplier.', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#F43636',
                'hidden' => true,
            ],
            'assigned'     => [
                'label' => __( 'Waiting to be accepted', 'disciple_tools' ),
                'description' => _x( 'The contact has been assigned to someone, but has not yet been accepted by that person.', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#FF9800',
                'hidden' => true,
            ],
            'active'       => [
                'label' => __( 'Active', 'disciple_tools' ),
                'description' => _x( 'The contact is progressing and/or continually being updated.', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#4CAF50',
            ],
            'paused'       => [
                'label' => __( 'Paused', 'disciple_tools' ),
                'description' => _x( 'This contact is currently on hold (i.e. on vacation or not responding).', 'Contact Status field description', 'disciple_tools' ),
                'color' => '#FF9800',
                'hidden' => true,
            ],
            'closed' => [
                'label' => __( 'Archived', 'disciple_tools' ),
                'color' => '#808080',
                'description' => _x( 'This contact has made it known that they no longer want to continue or you have decided not to continue with him/her.', 'Contact Status field description', 'disciple_tools' ),
            ]
        ],
        'default_color' => '#366184',
        'tile'     => 'status',
        'customizable' => 'add_only',
        'custom_display' => true, //for reason x fields
        'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg?v=2',
        'show_in_table' => 10,
        'select_cannot_be_empty' => true,
    ];


    /**
     * DMM fields
     */
    $fields['milestones'] = [
        'name'    => __( 'Faith Milestones', 'disciple_tools' ),
        'description' => _x( 'Assign which milestones the contact has reached in their faith journey. These are points in a contact’s spiritual journey worth celebrating but can happen in any order.', 'Optional Documentation', 'disciple_tools' ),
        'type'    => 'multi_select',
        'default' => [
            'milestone_has_bible'     => [
                'label' => __( 'Has Bible', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/bible.svg?v=2',
            ],
            'milestone_reading_bible' => [
                'label' => __( 'Reading Bible', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/reading.svg?v=2',
            ],
            'milestone_belief'        => [
                'label' => __( 'States Belief', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/speak.svg?v=2',
            ],
            'milestone_can_share'     => [
                'label' => __( 'Can Share Gospel/Testimony', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/hand-heart.svg?v=2',
            ],
            'milestone_sharing'       => [
                'label' => __( 'Sharing Gospel/Testimony', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/account-voice.svg?v=2',
            ],
            'milestone_baptized'      => [
                'label' => __( 'Baptized', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/baptism.svg?v=2',
            ],
            'milestone_baptizing'     => [
                'label' => __( 'Baptizing', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/child.svg?v=2',
            ],
            'milestone_in_group'      => [
                'label' => __( 'In Church/Group', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg?v=2',
            ],
            'milestone_planting'      => [
                'label' => __( 'Starting Churches', 'disciple_tools' ),
                'description' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/stream.svg?v=2',
            ],
        ],
        'customizable' => 'add_only',
        'tile' => 'faith',
        'show_in_table' => 20,
        'icon' => get_template_directory_uri() . '/dt-assets/images/bible.svg?v=2',
        'hidden' => true,
    ];
    $fields['faith_status'] =[
        'name' => __( 'Faith Status', 'disciple_tools' ),
        'description' => '',
        'type' => 'key_select',
        'default' => [
            'seeker'     => [
                'label' => __( 'Seeker', 'disciple_tools' ),
            ],
            'believer'     => [
                'label' => __( 'Believer', 'disciple_tools' ),
            ],
            'leader'     => [
                'label' => __( 'Leader', 'disciple_tools' ),
            ],
        ],
        'tile' => 'status',
        'icon' => get_template_directory_uri() . '/dt-assets/images/cross.svg?v=2',
        'in_create_form' => true,
        'hidden' => true,
    ];
    $fields['subassigned'] = [
        'name' => __( 'Sub-assigned to', 'disciple_tools' ),
        'description' => __( 'Contact or User assisting the Assigned To user to follow up with the contact.', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'to',
        'p2p_key' => 'contacts_to_subassigned',
        'tile' => 'status',
        'custom_display' => false,
        'icon' => get_template_directory_uri() . '/dt-assets/images/subassigned.svg?v=2',
        'hidden' => true,
    ];
    $fields['subassigned_on'] = [
        'name' => __( 'Sub-assigned on other Contacts', 'disciple_tools' ),
        'description' => __( 'Contacts this contacts is subassigned on', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'from',
        'p2p_key' => 'contacts_to_subassigned',
        'tile' => 'no_tile',
        'custom_display' => false,
        'icon' => get_template_directory_uri() . '/dt-assets/images/subassigned.svg?v=2',
        'hidden' => true,
    ];
    $fields['coaching'] = [
        'name' => __( 'Is Coaching', 'disciple_tools' ),
        'description' => _x( 'Who is this contact coaching', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'to',
        'p2p_key' => 'contacts_to_contacts',
        'tile' => 'other',
        'icon' => get_template_directory_uri() . '/dt-assets/images/coaching.svg?v=2',
        'hidden' => true,
    ];
    $fields['baptism_date'] = [
        'name' => __( 'Baptism Date', 'disciple_tools' ),
        'description' => '',
        'type' => 'date',
        'icon' => get_template_directory_uri() . '/dt-assets/images/calendar-heart.svg?v=2',
        'tile' => 'details',
        'hidden' => true,
    ];
    $fields['baptism_generation'] = [
        'name'        => __( 'Baptism Generation', 'disciple_tools' ),
        'type'        => 'number',
        'default'     => '',
        'hidden' => true,
    ];
    $fields['coached_by'] = [
        'name' => __( 'Coached by', 'disciple_tools' ),
        'description' => _x( 'Who is coaching this contact', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'from',
        'p2p_key' => 'contacts_to_contacts',
        'tile' => 'status',
        'icon' => get_template_directory_uri() . '/dt-assets/images/coach.svg?v=2',
        'hidden' => true,
    ];
    $fields['baptized_by'] = [
        'name' => __( 'Baptized by', 'disciple_tools' ),
        'description' => _x( 'Who baptized this contact', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'from',
        'p2p_key' => 'baptizer_to_baptized',
        'tile'     => 'faith',
        'icon' => get_template_directory_uri() . '/dt-assets/images/baptism.svg?v=2',
        'hidden' => true,
    ];
    $fields['baptized'] = [
        'name' => __( 'Baptized', 'disciple_tools' ),
        'description' => _x( 'Who this contact has baptized', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'contacts',
        'p2p_direction' => 'to',
        'p2p_key' => 'baptizer_to_baptized',
        'tile'     => 'faith',
        'icon' => get_template_directory_uri() . '/dt-assets/images/child.svg?v=2',
        'hidden' => true,
    ];
    $fields['people_groups'] = [
        'name' => __( 'People Groups', 'disciple_tools' ),
        'description' => _x( 'The people groups represented by this contact.', 'Optional Documentation', 'disciple_tools' ),
        'type' => 'connection',
        'post_type' => 'peoplegroups',
        'p2p_direction' => 'from',
        'p2p_key' => 'contacts_to_peoplegroups',
        'tile'     => 'details',
        'icon' => get_template_directory_uri() . '/dt-assets/images/people-group.svg?v=2',
        'connection_count_field' => [ 'post_type' => 'peoplegroups', 'field_key' => 'contact_count', 'connection_field' => 'contacts' ],
        'hidden' => true,
    ];
    $fields['quick_button_no_answer'] = [
        'name'        => __( 'No Answer', 'disciple_tools' ),
        'description' => '',
        'type'        => 'number',
        'default'     => 0,
        'section'     => 'quick_buttons',
        'icon'        => get_template_directory_uri() . '/dt-assets/images/account-voice-off.svg?v=2',
        'customizable' => false,
        'hidden' => true,
    ];
    $fields['quick_button_contact_established'] = [
        'name'        => __( 'Contact Established', 'disciple_tools' ),
        'description' => '',
        'type'        => 'number',
        'default'     => 0,
        'section'     => 'quick_buttons',
        'icon'        => get_template_directory_uri() . '/dt-assets/images/account-voice.svg?v=2',
        'customizable' => false
    ];
    $fields['quick_button_meeting_scheduled'] = [
        'name'        => __( 'Meeting Scheduled', 'disciple_tools' ),
        'description' => '',
        'type'        => 'number',
        'default'     => 0,
        'section'     => 'quick_buttons',
        'icon'        => get_template_directory_uri() . '/dt-assets/images/calendar-plus.svg?v=2',
        'customizable' => false
    ];
    $fields['quick_button_meeting_complete'] = [
        'name'        => __( 'Meeting Complete', 'disciple_tools' ),
        'description' => '',
        'type'        => 'number',
        'default'     => 0,
        'section'     => 'quick_buttons',
        'icon'        => get_template_directory_uri() . '/dt-assets/images/calendar-check.svg?v=2',
        'customizable' => false
    ];
    $fields['quick_button_no_show'] = [
        'name'        => __( 'Meeting No-show', 'disciple_tools' ),
        'description' => '',
        'type'        => 'number',
        'default'     => 0,
        'section'     => 'quick_buttons',
        'icon'        => get_template_directory_uri() . '/dt-assets/images/calendar-remove.svg?v=2',
        'customizable' => false
    ];

    /**
     * Access Fields
     */
    $fields['assigned_to'] = [
        'name'        => __( 'Assigned To', 'disciple_tools' ),
        'description' => __( 'Select the main person who is responsible for reporting on this contact.', 'disciple_tools' ),
        'type'        => 'user_select',
        'default'     => '',
        'tile'        => 'status',
        'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg?v=2',
        'show_in_table' => 25,
        'only_for_types' => [ 'access', 'user' ],
        'custom_display' => true
    ];
    $fields['seeker_path'] = [
        'name'        => __( 'Seeker Path', 'disciple_tools' ),
        'description' => _x( 'Set the status of your progression with the contact. These are the steps that happen in a specific order to help a contact move forward.', 'Seeker Path field description', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'none'        => [
                'label' => __( 'Contact Attempt Needed', 'disciple_tools' ),
                'description' => ''
            ],
            'attempted'   => [
                'label' => __( 'Contact Attempted', 'disciple_tools' ),
                'description' => ''
            ],
            'established' => [
                'label' => __( 'Contact Established', 'disciple_tools' ),
                'description' => ''
            ],
            'scheduled'   => [
                'label' => __( 'First Meeting Scheduled', 'disciple_tools' ),
                'description' => ''
            ],
            'met'         => [
                'label' => __( 'First Meeting Complete', 'disciple_tools' ),
                'description' => ''
            ],
            'ongoing'     => [
                'label' => __( 'Ongoing Meetings', 'disciple_tools' ),
                'description' => ''
            ],
            'coaching'    => [
                'label' => __( 'Being Coached', 'disciple_tools' ),
                'description' => ''
            ],
        ],
        'customizable' => 'add_only',
        'tile' => 'followup',
        'show_in_table' => 15,
        'only_for_types' => [ 'access' ],
        'icon' => get_template_directory_uri() . '/dt-assets/images/sign-post.svg?v=2',
        'hidden' => true,
    ];

    $fields['reason_unassignable'] = [
        'name'        => __( 'Reason Not Ready', 'disciple_tools' ),
        'description' => _x( 'The main reason the contact is not ready to be assigned to a user.', 'Optional Documentation', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'none'         => [
                'label' => '',
            ],
            'insufficient' => [
                'label' => __( 'Insufficient Contact Information', 'disciple_tools' )
            ],
            'location'     => [
                'label' => __( 'Unknown Location', 'disciple_tools' )
            ],
            'media'        => [
                'label' => __( 'Only wants media', 'disciple_tools' )
            ],
            'outside_area' => [
                'label' => __( 'Outside Area', 'disciple_tools' )
            ],
            'needs_review' => [
                'label' => __( 'Needs Review', 'disciple_tools' )
            ],
            'awaiting_confirmation' => [
                'label' => __( 'Waiting for Confirmation', 'disciple_tools' )
            ],
        ],
        'customizable' => 'all',
        'only_for_types' => [ 'access' ],
        'hidden' => true,
    ];

    $fields['reason_paused'] = [
        'name'        => __( 'Reason Paused', 'disciple_tools' ),
        'description' => _x( 'A paused contact is one you are not currently interacting with but expect to in the future.', 'Optional Documentation', 'disciple_tools' ),
        'type'        => 'key_select',
        'default' => [
            'none'                 => [ 'label' => '' ],
            'vacation'             => [ 'label' => _x( 'Contact on vacation', 'Reason Paused label', 'disciple_tools' ) ],
            'not_responding'       => [ 'label' => _x( 'Contact not responding', 'Reason Paused label', 'disciple_tools' ) ],
            'not_available'        => [ 'label' => _x( 'Contact not available', 'Reason Paused label', 'disciple_tools' ) ],
            'little_interest'      => [ 'label' => _x( 'Contact has little interest/hunger', 'Reason Paused label', 'disciple_tools' ) ],
            'no_initiative'        => [ 'label' => _x( 'Contact shows no initiative', 'Reason Paused label', 'disciple_tools' ) ],
            'questionable_motives' => [ 'label' => _x( 'Contact has questionable motives', 'Reason Paused label', 'disciple_tools' ) ],
            'ball_in_their_court'  => [ 'label' => _x( 'Ball is in the contact\'s court', 'Reason Paused label', 'disciple_tools' ) ],
            'wait_and_see'         => [ 'label' => _x( 'We want to see if/how the contact responds to automated text messages', 'Reason Paused label', 'disciple_tools' ) ],
        ],
        'customizable' => 'all',
        'only_for_types' => [ 'access' ],
        'hidden' => true,
    ];

    $fields['reason_closed'] = [
        'name'        => __( 'Reason Archived', 'disciple_tools' ),
        'description' => _x( "A closed contact is one you can't or don't wish to interact with.", 'Optional Documentation', 'disciple_tools' ),
        'type'        => 'key_select',
        'default'     => [
            'none'                 => [ 'label' => '' ],
            'duplicate'            => [ 'label' => _x( 'Duplicate', 'Reason Closed label', 'disciple_tools' ) ],
            'insufficient'         => [ 'label' => _x( 'Insufficient contact info', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'denies_submission'    => [ 'label' => _x( 'Denies submitting contact request', 'Reason Closed label', 'disciple_tools' ) ],
            'hostile_self_gain'    => [ 'label' => _x( 'Hostile, playing games or self gain', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'apologetics'          => [ 'label' => _x( 'Only wants to argue or debate', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'media_only'           => [ 'label' => _x( 'Just wanted media or book', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'no_longer_interested' => [ 'label' => _x( 'No longer interested', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'no_longer_responding' => [ 'label' => _x( 'No longer responding', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'already_connected'    => [ 'label' => _x( 'Already in church or connected with others', 'Reason Closed label', 'disciple_tools' ) ],
            'transfer'             => [ 'label' => _x( 'Transferred contact to partner', 'Reason Closed label', 'disciple_tools' ) ],
            'martyred'             => [ 'label' => _x( 'Martyred', 'Reason Closed label', 'disciple_tools' ), 'hidden' => true ],
            'moved'                => [ 'label' => _x( 'Moved or relocated', 'Reason Closed label', 'disciple_tools' ) ],
            'gdpr'                 => [ 'label' => _x( 'GDPR request', 'Reason Closed label', 'disciple_tools' ) ],
            'spam'                 => [ 'label' => _x( 'Spam', 'Reason Closed label', 'disciple_tools' ) ],
            'unknown'              => [ 'label' => _x( 'Unknown', 'Reason Closed label', 'disciple_tools' ) ]
        ],
        'customizable' => 'all',
        'only_for_types' => [ 'access' ],
        'hidden' => true,
    ];

    $fields['accepted'] = [
        'name'        => __( 'Accepted', 'disciple_tools' ),
        'type'        => 'boolean',
        'default'     => false,
        'hidden'      => true,
        'only_for_types' => [ 'access' ]
    ];
    $sources_default = [
        'personal'           => [
            'label'       => __( 'Personal', 'disciple_tools' ),
            'key'         => 'personal',
        ],
        'web'           => [
            'label'       => __( 'Web', 'disciple_tools' ),
            'key'         => 'web',
        ],
        'facebook'      => [
            'label'       => __( 'Facebook', 'disciple_tools' ),
            'key'         => 'facebook',
        ],
        'twitter'       => [
            'label'       => __( 'Twitter', 'disciple_tools' ),
            'key'         => 'twitter',
        ],
        'transfer' => [
            'label'       => __( 'Transfer', 'disciple_tools' ),
            'key'         => 'transfer',
            'description' => __( 'Contacts transferred from a partnership with another Disciple.Tools site.', 'disciple_tools' ),
        ]
    ];
    foreach ( dt_get_option( 'dt_site_custom_lists' )['sources'] as $key => $value ) {
        if ( !isset( $sources_default[$key] ) ) {
            if ( isset( $value['enabled'] ) && $value['enabled'] === false ) {
                $value['deleted'] = true;
            }
            $sources_default[ $key ] = $value;
        }
    }

    $fields['sources'] = [
        'name'        => __( 'Sources', 'disciple_tools' ),
        'description' => _x( 'The website, event or location this contact came from.', 'Optional Documentation', 'disciple_tools' ),
        'type'        => 'multi_select',
        'default'     => $sources_default,
        'tile'     => 'details',
        'customizable' => 'all',
        'display' => 'typeahead',
        'icon' => get_template_directory_uri() . '/dt-assets/images/arrow-collapse-all.svg?v=2',
        'in_create_form' => [ 'access' ]
    ];

    $fields['campaigns'] = [
        'name' => __( 'Campaigns', 'disciple_tools' ),
        'description' => _x( 'Marketing campaigns or access activities that this contact interacted with.', 'Optional Documentation', 'disciple_tools' ),
        'tile' => 'details',
        'type'        => 'tags',
        'default'     => [],
        'icon' => get_template_directory_uri() . '/dt-assets/images/megaphone.svg?v=2',
        'only_for_types' => [ 'access' ],
        'hidden' => true,
    ];

    return $fields;
}, 10, 2 );

add_filter( 'dt_details_additional_tiles', function ( $tiles, $post_type ){
    if ( $post_type === 'contacts' ){
        $tiles['faith'] = [
            'label' => __( 'Faith', 'disciple_tools' ),
            'hidden' => true,
        ];
        $tiles['followup'] = [
            'label' => __( 'Follow Up', 'disciple_tools' ),
            'display_for' => [
                'type' => [ 'access' ],
            ],
            'hidden' => true,
        ];
    }
    return $tiles;


}, 10, 2 );

add_filter( 'dt_filter_access_permissions', function ( $permissions, $post_type ){
    if ( $post_type === 'contacts' ){
        if ( DT_Posts::can_view_all( $post_type ) ){
            $permissions['type'] = [ 'access', 'user', 'access_placeholder' ];
        } else if ( current_user_can( 'dt_all_access_contacts' ) ){
            //give user permission to all contacts af type 'access'
            $permissions[] = [ 'type' => [ 'access', 'user', 'access_placeholder' ] ];
        } else if ( current_user_can( 'access_specific_sources' ) ){
            //give user permission to all 'access' that also have a source the user can view.
            $allowed_sources = get_user_option( 'allowed_sources', get_current_user_id() ) ?: [];
            if ( empty( $allowed_sources ) || in_array( 'all', $allowed_sources, true ) ){
                $permissions['type'] = [ 'access', 'access_placeholder' ];
            } elseif ( !in_array( 'restrict_all_sources', $allowed_sources ) ){
                $permissions[] = [ 'type' => [ 'access' ], 'sources' => $allowed_sources];
            }
        }
    }
    return $permissions;
}, 10, 2  );


add_filter( 'dt_can_view_permission', function ( $has_permission, $post_id, $post_type ){
    if ( $post_type === 'contacts' ){
        if ( current_user_can( 'dt_all_access_contacts' ) ){
            $contact_type = get_post_meta( $post_id, 'type', true );
            if ( $contact_type === 'access' || $contact_type === 'user' || $contact_type === 'access_placeholder' ){
                return true;
            }
        }
        //check if the user has access to all posts of a specific source
        if ( current_user_can( 'access_specific_sources' ) ){
            $contact_type = get_post_meta( $post_id, 'type', true );
            if ( $contact_type === 'access' || $contact_type === 'access_placeholder' ){
                $sources = get_user_option( 'allowed_sources', get_current_user_id() ) ?: [];
                if ( empty( $sources ) || in_array( 'all', $sources ) ) {
                    return true;
                }
                $post_sources = get_post_meta( $post_id, 'sources' );
                foreach ( $post_sources as $s ){
                    if ( in_array( $s, $sources ) ){
                        return true;
                    }
                }
            }
        }
    }
    return $has_permission;
}, 10, 3 );

add_filter( 'dt_can_update_permission', function ( $has_permission, $post_id, $post_type ){
    if ( $post_type === 'contacts' ){
        if ( current_user_can( 'dt_all_access_contacts' ) ){
            $contact_type = get_post_meta( $post_id, 'type', true );
            if ( $contact_type === 'access' || $contact_type === 'user' || $contact_type === 'access_placeholder' ){
                return true;
            }
        }
        //check if the user has access to all posts of a specific source
        if ( current_user_can( 'access_specific_sources' ) ){
            $contact_type = get_post_meta( $post_id, 'type', true );
            if ( $contact_type === 'access' || $contact_type === 'access_placeholder' ){
                $sources = get_user_option( 'allowed_sources', get_current_user_id() ) ?: [];
                if ( empty( $sources ) || in_array( 'all', $sources ) ){
                    return true;
                }
                $post_sources = get_post_meta( $post_id, 'sources' );
                foreach ( $post_sources as $s ){
                    if ( in_array( $s, $sources ) ){
                        return true;
                    }
                }
            }
        }
    }
    return $has_permission;
}, 10, 3 );
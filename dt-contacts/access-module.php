<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Contacts_Access extends DT_Module_Base {
    public $post_type = 'contacts';
    public $module = 'access_module';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }
        //permissions
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 10, 1 );

        //setup fields
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        //display tiles and fields
        add_action( 'dt_record_top_above_details', [ $this, 'dt_record_top_above_details' ], 20, 2 );
        add_action( 'dt_render_field_for_display_template', [ $this, 'dt_render_field_for_display_template' ], 20, 6 );

//        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        //list
        add_filter( 'dt_user_list_filters', [ $this, 'dt_user_list_filters' ], 20, 2 );

        //api
        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 10, 4 );
        add_action( 'dt_comment_created', [ $this, 'dt_comment_created' ], 20, 4 );
        add_action( 'dt_post_created', [ $this, 'dt_post_created' ], 10, 3 );
        add_filter( 'dt_post_create_fields', [ $this, 'dt_post_create_fields' ], 5, 2 );
        add_action( 'dt_post_updated', [ $this, 'dt_post_updated' ], 10, 5 );

        add_filter( 'dt_filter_users_receiving_comment_notification', [ $this, 'dt_filter_users_receiving_comment_notification' ], 10, 4 );

    }

    public function dt_set_roles_and_permissions( $expected_roles ){
        $expected_roles['marketer']['permissions']['access_specific_sources'] = true;
        $expected_roles['marketer']['permissions']['assign_any_contacts'] = true;
        $expected_roles['partner']['permissions']['access_specific_sources'] = true;

        $expected_roles['dispatcher']['permissions']['dt_all_access_contacts'] = true;
        $expected_roles['dispatcher']['permissions']['assign_any_contacts'] = true;
        $expected_roles['dispatcher']['permissions']['list_users'] = true;
        $expected_roles['dispatcher']['permissions']['dt_list_users'] = true;

        $expected_roles['administrator']['permissions']['dt_all_access_contacts'] = true;
        $expected_roles['administrator']['permissions']['assign_any_contacts'] = true;
        $expected_roles['dt_admin']['permissions']['dt_all_access_contacts'] = true;
        $expected_roles['dt_admin']['permissions']['assign_any_contacts'] = true;

        return $expected_roles;
    }

    public function add_api_routes(){
        $namespace = 'dt-posts/v2';
        register_rest_route(
            $namespace, '/contacts/(?P<id>\d+)/accept', [
                'methods'  => 'POST',
                'callback' => [ $this, 'accept_contact' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            $namespace, '/contacts/assignment-list', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_dispatch_list' ],
                'permission_callback' => '__return_true',
            ]
        );

    }

    public function dt_render_field_for_display_template( $post, $field_type, $field_key, $required_tag, $display_field_id, $custom_display = false ){
        $contact_fields = DT_Posts::get_post_field_settings( 'contacts' );
        if ( isset( $post['post_type'] ) && isset( $post['ID'] ) ) {
            $can_update = DT_Posts::can_update( $post['post_type'], $post['ID'] );
        } else {
            $can_update = true;
        }
        $disabled = 'disabled';
        if ( $can_update || isset( $post['assigned_to']['id'] ) && $post['assigned_to']['id'] == get_current_user_id() ) {
            $disabled = '';
        }
        if ( isset( $post['post_type'] ) && $post['post_type'] === 'contacts' && $field_key === 'overall_status'
            && isset( $contact_fields[$field_key] ) && $custom_display
            && empty( $contact_fields[$field_key]['hidden'] )
            ){
            $contact = $post;
            if ( !dt_field_enabled_for_record_type( $contact_fields[$field_key], $post ) ){
                return;
            }
            ?>
                <div class="section-subheader">
                    <?php dt_render_field_icon( $contact_fields[$field_key] ) ?>
                    <?php echo esc_html( $contact_fields[$field_key]['name'] ) ?>
                </div>
                <?php
                $active_color = '#366184';
                $current_key = $contact['overall_status']['key'] ?? '';
                if ( isset( $contact_fields['overall_status']['default'][ $current_key ]['color'] ) ){
                    $active_color = $contact_fields['overall_status']['default'][ $current_key ]['color'];
                }
                ?>
                <select id="overall_status" class="select-field color-select" style="margin-bottom:0; background-color: <?php echo esc_html( $active_color ) ?>" <?php echo esc_html( $disabled ); ?>>
                    <?php foreach ( $contact_fields['overall_status']['default'] as $key => $option ){
                        if ( isset( $option['hidden'] ) && $option['hidden'] === true ){
                            continue;
                        }
                        $selected = isset( $post[$field_key]['key'] ) && $post[$field_key]['key'] === strval( $key ); ?>
                        <option value="<?php echo esc_html( $key )?>" <?php echo esc_html( $selected ? 'selected' : '' )?>>
                            <?php echo esc_html( $option['label'] ) ?>
                        </option>
                    <?php } ?>
                </select>
                <p>
                    <span id="reason">
                        <?php
                        $hide_edit_button = false;
                        $status_key = isset( $contact['overall_status']['key'] ) ? $contact['overall_status']['key'] : '';
                        if ( $status_key === 'paused' &&
                            isset( $contact['reason_paused']['label'] ) ){
                            echo '(' . esc_html( $contact['reason_paused']['label'] ) . ')';
                        } else if ( $status_key === 'closed' &&
                            isset( $contact['reason_closed']['label'] ) ){
                            echo '(' . esc_html( $contact['reason_closed']['label'] ) . ')';
                        } else if ( $status_key === 'unassignable' &&
                            isset( $contact['reason_unassignable']['label'] ) ){
                            echo '(' . esc_html( $contact['reason_unassignable']['label'] ) . ')';
                        } else {
                            if ( !in_array( $status_key, [ 'paused', 'closed', 'unassignable' ] ) ){
                                $hide_edit_button = true;
                            }
                        }
                        ?>
                    </span>
                    <button id="edit-reason" <?php if ( $hide_edit_button ) : ?> style="display: none"<?php endif; ?> ><i class="fi-pencil"></i></button>
                </p>
                <div class="reveal" id="paused-contact-modal" data-reveal>
                    <h3><?php echo esc_html( $contact_fields['reason_paused']['name'] ?? '' )?></h3>
                    <p><?php echo esc_html( $contact_fields['reason_paused']['description'] ?? '' )?></p>
                    <p><?php esc_html_e( 'Choose an option:', 'disciple_tools' )?></p>

                    <select id="reason-paused-options">
                        <?php
                        foreach ( $contact_fields['reason_paused']['default'] as $reason_key => $option ) {
                            if ( $option['label'] && empty( $option['hidden'] ) ) {
                                ?>
                                <option value="<?php echo esc_attr( $reason_key ) ?>"
                                    <?php if ( ( $contact['reason_paused']['key'] ?? '' ) === $reason_key ) {
                                        echo 'selected';
                                    } ?>>
                                    <?php echo esc_html( $option['label'] ?? '' ) ?>
                                </option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                    <button class="button loader confirm-reason-button" type="button" id="confirm-pause" data-field="paused">
                        <?php echo esc_html__( 'Confirm', 'disciple_tools' )?>
                    </button>
                    <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close', 'disciple_tools' ); ?>" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="reveal" id="unassignable-contact-modal" data-reveal>
                    <h3><?php echo esc_html( $contact_fields['reason_unassignable']['name'] ?? '' )?></h3>
                    <p><?php echo esc_html( $contact_fields['reason_unassignable']['description'] ?? '' )?></p>
                    <p><?php esc_html_e( 'Choose an option:', 'disciple_tools' )?></p>

                    <select id="reason-unassignable-options">
                        <?php
                        foreach ( $contact_fields['reason_unassignable']['default'] as $reason_key => $option ) {
                            if ( isset( $option['label'] ) && empty( $option['hidden'] ) ) {
                                ?>
                                <option value="<?php echo esc_attr( $reason_key ) ?>"
                                    <?php if ( ( $contact['unassignable_paused']['key'] ?? '' ) === $reason_key ) {
                                        echo 'selected';
                                    } ?>>
                                    <?php echo esc_html( $option['label'] ?? '' ) ?>
                                </option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                    <button class="button loader confirm-reason-button" type="button" id="confirm-unassignable" data-field="unassignable">
                        <?php echo esc_html__( 'Confirm', 'disciple_tools' )?>
                    </button>
                    <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close', 'disciple_tools' ); ?>" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="reveal" id="closed-contact-modal" data-reveal>
                    <h3><?php echo esc_html( $contact_fields['reason_closed']['name'] ?? '' )?></h3>
                    <p><?php echo esc_html( $contact_fields['reason_closed']['description'] ?? '' )?></p>
                    <p><?php esc_html_e( 'Choose an option:', 'disciple_tools' )?></p>

                    <select id="reason-closed-options">
                        <?php
                        foreach ( $contact_fields['reason_closed']['default'] as $reason_key => $option ) {
                            if ( !empty( $option['label'] ) && empty( $option['hidden'] ) ) {
                                $selected = ( $reason_key === ( $contact['reason_closed']['key'] ?? '' ) ) ? 'selected' : '';
                                ?>
                                <option
                                    value="<?php echo esc_attr( $reason_key ) ?>" <?php echo esc_html( $selected ) ?>> <?php echo esc_html( $option['label'] ?? '' ) ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                    <button class="button loader confirm-reason-button" type="button" id="confirm-close" data-field="closed">
                        <?php echo esc_html__( 'Confirm', 'disciple_tools' )?>
                    </button>
                    <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close', 'disciple_tools' ); ?>" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php
        }


        if ( isset( $post['post_type'] ) && $post['post_type'] === 'contacts' && $field_key === 'assigned_to'
            && isset( $contact_fields[$field_key] ) && !empty( $contact_fields[$field_key]['custom_display'] )
            && empty( $contact_fields[$field_key]['hidden'] ) ){
            $button_class =( current_user_can( 'dt_all_access_contacts' ) || current_user_can( 'list_users' ) ) ? 'advanced_user_select' : 'search_assigned_to'
            ?>
            <div class="section-subheader">
                <img src="<?php echo esc_url( $contact_fields[$field_key]['icon'] ) ?>">
                <?php echo esc_html( $contact_fields[$field_key]['name'] ) ?>
            </div>
            <div id="<?php echo esc_html( $field_key ); ?>" class="<?php echo esc_html( $display_field_id ); ?> dt_user_select">
                <var id="<?php echo esc_html( $display_field_id ); ?>-result-container" class="result-container <?php echo esc_html( $display_field_id ); ?>-result-container"></var>
                <div id="<?php echo esc_html( $display_field_id ); ?>_t" name="form-<?php echo esc_html( $display_field_id ); ?>" class="scrollable-typeahead">
                    <div class="typeahead__container" style="margin-bottom: 0">
                        <div class="typeahead__field">
                            <span class="typeahead__query">
                                <input class="js-typeahead-<?php echo esc_html( $display_field_id ); ?> input-height" dir="auto"
                                       name="<?php echo esc_html( $display_field_id ); ?>[query]" placeholder="<?php echo esc_html_x( 'Search Users', 'input field placeholder', 'disciple_tools' ) ?>"
                                       data-field_type="user_select"
                                       data-field="<?php echo esc_html( $field_key ); ?> <?php echo esc_html( $disabled ); ?>"
                                       autocomplete="off" <?php echo esc_html( $disabled ); ?>>
                            </span>
                            <span class="typeahead__button">
                                <button type="button" class="<?php echo esc_html( $button_class ); ?> typeahead__image_button input-height" data-id="<?php echo esc_html( $field_key ); ?>" <?php echo esc_html( $disabled ); ?>>
                                    <i class="fi-magnifying-glass"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $is_dispatcher = dt_current_user_has_role( 'dispatcher' ) || current_user_can( 'dt_all_access_contacts' );
            $roles = [
                'multiplier' => [
                    'label' => __( 'Multipliers', 'disciple_tools' )
                ],
                'dispatcher' => [
                    'label' => __( 'Dispatchers', 'disciple_tools' )
                ],
                'marketer' => [
                    'label' => __( 'Digital Responders', 'disciple_tools' )
                ],
            ];
            if ( $is_dispatcher ) { ?>
            <div class="reveal" id="assigned_to_user_modal" data-reveal>
                <section class="small-12 grid-y grid-margin-y cell dispatcher-tile">
                    <div class="cell dt-filter-tabs">
                        <h4 class="section-header"><?php esc_html_e( 'Assign To', 'disciple_tools' ); ?> <span id="dispatch-tile-loader" style="display: inline-block; margin-left: 10px" class="loading-spinner"></span></h4>
                        <div class="section-body">
                            <ul class="horizontal tabs" data-tabs id="assign-role-tabs">
                                <?php foreach ( $roles as $key => $value ) : ?>
                                    <li class="tabs-title <?php echo esc_html( $key === 'multiplier' ? 'is-active' : '' ); ?>">
                                        <a href="#<?php echo esc_html( $key ); ?>" data-field="<?php echo esc_html( $key ); ?>">
                                            <?php echo esc_html( $value['label'] ); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="tabs-column-right users-select-panel" style="margin-top:20px; display: none">
                                <div id="defined-lists" style="padding-top:0">
                                    <div class="grid-x grid-margin-x" style="margin-top:5px">
                                        <div class="medium-4 cell">
                                            <div class="input-group">
                                                <input id="search-users-filtered" class="input-group-field" type="text" placeholder="Multipliers" <?php echo esc_html( $disabled ); ?>>
                                                <div class="input-group-button">
                                                    <button type="button" class="button hollow"><i class="fi-magnifying-glass"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="medium-8 cell">
                                            <div id="user-list-filters" style="margin-bottom:3px">
                                                <!--filters is filled out by js-->
                                            </div>
                                            <div class="populated-list">
                                                <!--users list is filled out by js-->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close', 'disciple_tools' ); ?>" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php }
        }
    }


    public function dt_record_top_above_details( $post_type, $contact ){
        if ( $post_type === 'contacts' && isset( $contact['type'] ) && $contact['type']['key'] === 'access' ) {
            $current_user = wp_get_current_user();

            /**
             * Add the accept contact banner
             */
            if ( isset( $contact['overall_status'] ) && $contact['overall_status']['key'] == 'assigned' &&
                isset( $contact['assigned_to'] ) && $contact['assigned_to']['id'] == $current_user->ID ) { ?>
                <section class="cell accept-contact" id="accept-contact">
                    <div class="bordered-box detail-notification-box">
                        <h4><?php esc_html_e( 'This contact has been assigned to you.', 'disciple_tools' )?></h4>
                        <button class="accept-button button small accept-decline" data-action="accept"><?php esc_html_e( 'Accept', 'disciple_tools' )?></button>
                        <button class="decline-button button small accept-decline" data-action="decline"><?php esc_html_e( 'Decline', 'disciple_tools' )?></button>
                    </div>
                </section>
                <?php
            }
        }
    }


    public function dt_post_update_fields( $fields, $post_type, $post_id, $existing_post ){
        if ( $post_type === 'contacts' ){
            if ( ( !isset( $existing_post['type']['key'] ) || $existing_post['type']['key'] !== 'access' ) && ( !isset( $fields['type'] ) || $fields['type'] !== 'access' ) ){
                return $fields;
            }
            //make sure and access contact is assigned to a user
            if ( isset( $fields['assigned_to'] ) ) {
                if ( !isset( $existing_post['assigned_to'] ) || $fields['assigned_to'] !== $existing_post['assigned_to']['assigned-to'] ){
                    $user_id = dt_get_user_id_from_assigned_to( $fields['assigned_to'] );
                    if ( !isset( $fields['overall_status'] ) ){
                        if ( $user_id != get_current_user_id() ){
                            if ( current_user_can( 'assign_any_contacts' ) ) {
                                $fields['overall_status'] = 'assigned';
                            }
                            $fields['accepted'] = false;
                        } elseif ( isset( $existing_post['overall_status']['key'] ) && $existing_post['overall_status']['key'] === 'assigned' ) {
                            $fields['overall_status'] = 'active';
                        }
                    }
                    if ( $user_id ){
                        DT_Posts::add_shared( 'contacts', $post_id, $user_id, null, false, true, false );
                    }
                }
            }
            if ( isset( $fields['seeker_path'] ) ){
                self::update_quick_action_buttons( $post_id, $fields['seeker_path'] );
            }
            foreach ( $fields as $field_key => $value ){
                if ( strpos( $field_key, 'quick_button' ) !== false ){
                    self::handle_quick_action_button_event( $post_id, [ $field_key => $value ] );
                }
            }
            if ( isset( $fields['overall_status'], $fields['reason_paused'] ) && $fields['overall_status'] === 'paused' ){
                $fields['requires_update'] = false;
            }
            if ( isset( $fields['overall_status'], $fields['reason_closed'] ) && $fields['overall_status'] === 'closed' ){
                $fields['requires_update'] = false;
            }
            //if a contact type is changed to access
            if ( isset( $fields['type'] ) && $fields['type'] === 'access' ){
                //set the status to active if there is no status
                if ( !isset( $existing_post['overall_status'] ) && !isset( $fields['overall_status'] ) ){
                    $fields['overall_status'] = 'active';
                }
                //assign the contact to the user
                if ( !isset( $existing_post['assigned_to'] ) && !isset( $fields['assigned_to'] ) && get_current_user_id() ){
                    $fields['assigned_to'] = get_current_user_id();
                }
            }
        }
        return $fields;
    }

    public function dt_comment_created( $post_type, $post_id, $created_comment_id, $comment_type ){
        if ( $post_type === 'contacts' ){
            if ( $comment_type === 'comment' ){
                self::check_requires_update( $post_id );
            }
        }
    }

    // Runs after post is created and fields are processed.
    public function dt_post_created( $post_type, $post_id, $initial_request_fields ){
        if ( $post_type === 'contacts' ){
            $post = DT_Posts::get_post( $post_type, $post_id, true, false );
            if ( !isset( $post['type']['key'] ) || $post['type']['key'] !== 'access' ){
                return;
            }
            //check for duplicate along other access contacts
            $this->check_for_duplicates( $post_type, $post_id );
        }
    }

    // Add, remove or modify fields before the fields are processed on post create.
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type !== 'contacts' ){
            return $fields;
        }
        if ( isset( $fields['additional_meta']['created_from'] ) ){
            $from_post = DT_Posts::get_post( 'contacts', $fields['additional_meta']['created_from'], true, false );
            if ( !is_wp_error( $from_post ) && isset( $from_post['type']['key'] ) && $from_post['type']['key'] === 'access' ){
                $fields['type'] = 'access_placeholder';
            }
        }
        if ( !isset( $fields['type'] ) && isset( $fields['sources'] ) ){
            if ( !empty( $fields['sources'] ) ){
                $fields['type'] = 'access';
            }
        }
        //If a contact is created via site link or externally without a source, make sure the contact is accessible
        if ( !isset( $fields['type'] ) && get_current_user_id() === 0 ){
            $fields['type'] = 'access';
        }
        if ( !isset( $fields['type'] ) || $fields['type'] !== 'access' ){
            return $fields;
        }
        if ( !isset( $fields['seeker_path'] ) ){
            $fields['seeker_path'] = 'none';
        }
        if ( !isset( $fields['assigned_to'] ) ){
            if ( get_current_user_id() ) {
                $fields['assigned_to'] = sprintf( 'user-%d', get_current_user_id() );
            } else {
                $base_id = dt_get_base_user( true );
                if ( is_wp_error( $base_id ) ) { // if default editor does not exist, get available administrator
                    $users = get_users( [ 'role' => 'administrator' ] );
                    if ( count( $users ) > 0 ) {
                        foreach ( $users as $user ) {
                            $base_id = $user->ID;
                        }
                    }
                }
                if ( !empty( $base_id ) ){
                    $fields['assigned_to'] = sprintf( 'user-%d', $base_id );
                }
            }
        }
        if ( !isset( $fields['overall_status'] ) ){
            if ( get_current_user_id() ){
                $fields['overall_status'] = 'active';
            } else {
                $fields['overall_status'] = 'new';
            }
        }
        if ( !isset( $fields['sources'] ) ) {
            $fields['sources'] = [ 'values' => [ [ 'value' => 'personal' ] ] ];
        }
        return $fields;
    }

    //Runs after fields are processed on update
    public function dt_post_updated( $post_type, $post_id, $initial_request_fields, $post_fields_before_update, $contact ){
        if ( $post_type === 'contacts' ){
            if ( !isset( $contact['type']['key'] ) || $contact['type']['key'] !== 'access' ){
                return;
            }
            self::check_seeker_path( $post_id, $contact, $post_fields_before_update );
        }
    }



    /**
     * Make sure activity is created for all the steps before the current seeker path
     *
     * @param $contact_id
     * @param $contact
     * @param $previous_values
     */
    public function check_seeker_path( $contact_id, $contact, $previous_values ){
        if ( isset( $contact['seeker_path']['key'] ) && $contact['seeker_path']['key'] != 'none' ){
            $current_key = $contact['seeker_path']['key'];
            $prev_key = isset( $previous_values['seeker_path']['key'] ) ? $previous_values['seeker_path']['key'] : 'none';
            $field_settings = DT_Posts::get_post_field_settings( 'contacts' );
            $seeker_path_options = $field_settings['seeker_path']['default'];
            $option_keys = array_keys( $seeker_path_options );
            $current_index = array_search( $current_key, $option_keys );
            $prev_option_key = $option_keys[ $current_index - 1 ];

            if ( $prev_option_key != $prev_key && $current_index > array_search( $prev_key, $option_keys ) ){
                global $wpdb;
                $seeker_path_activity = $wpdb->get_results( $wpdb->prepare( "
                    SELECT meta_value, hist_time, meta_id
                    FROM $wpdb->dt_activity_log
                    WHERE object_id = %s
                    AND meta_key = 'seeker_path'
                ", $contact_id), ARRAY_A );
                $existing_keys = [];
                $most_recent = 0;
                $meta_id = 0;
                foreach ( $seeker_path_activity as $activity ){
                    $existing_keys[] = $activity['meta_value'];
                    if ( $activity['hist_time'] > $most_recent ){
                        $most_recent = $activity['hist_time'];
                    }
                    $meta_id = $activity['meta_id'];
                }
                $activity_to_create = [];
                for ( $i = $current_index; $i > 0; $i-- ){
                    if ( !in_array( $option_keys[$i], $existing_keys ) ){
                        $activity_to_create[] = $option_keys[$i];
                    }
                }
                foreach ( $activity_to_create as $missing_key ){
                    $wpdb->query( $wpdb->prepare("
                        INSERT INTO $wpdb->dt_activity_log
                        ( action, object_type, object_subtype, object_id, user_id, hist_time, meta_id, meta_key, meta_value, field_type )
                        VALUES ( 'field_update', 'contacts', 'seeker_path', %s, %d, %d, %d, 'seeker_path', %s, 'key_select' )",
                        $contact_id, get_current_user_id(), $most_recent - 1, $meta_id, $missing_key
                    ));
                }
            }
        }
    }

    //list page filters function
    public static function dt_user_list_filters( $filters, $post_type ){
        if ( $post_type === 'contacts' ){
            $counts = self::get_my_contacts_status_seeker_path();
            $fields = DT_Posts::get_post_field_settings( $post_type );

            /**
             * Setup my contacts filters
             */
            $active_counts = [];
            $update_needed = 0;
            $status_counts = [];
            $total_my = 0;
            foreach ( $counts as $count ){
                $total_my += $count['count'];
                dt_increment( $status_counts[$count['overall_status']], $count['count'] );
                if ( $count['overall_status'] === 'active' ){
                    if ( isset( $count['update_needed'] ) ) {
                        $update_needed += (int) $count['update_needed'];
                    }
                    dt_increment( $active_counts[$count['seeker_path']], $count['count'] );
                }
            }
            if ( !isset( $status_counts['closed'] ) ) {
                $status_counts['closed'] = '';
            }

            // add assigned to me filters
            $filters['filters'][] = [
                'ID' => 'my_all',
                'tab' => 'default',
                'name' => __( 'My Follow-Up', 'disciple_tools' ),
                'query' => [
                    'assigned_to' => [ 'me' ],
                    'subassigned' => [ 'me' ],
                    'combine' => [ 'subassigned' ],
                    'overall_status' => [ '-closed' ],
                    'type' => [ 'access' ],
                    'sort' => 'overall_status',
                ],
                'labels' => [
                    [ 'name' => __( 'My Follow-Up', 'disciple_tools' ), 'field' => 'combine', 'id' => 'subassigned' ],
                    [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
                    [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
                ],
                'count' => $total_my,
            ];
            foreach ( $fields['overall_status']['default'] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ) {
                    $filters['filters'][] = [
                        'ID' => 'my_' . $status_key,
                        'tab' => 'default',
                        'name' => $status_value['label'],
                        'query' => [
                            'assigned_to' => [ 'me' ],
                            'subassigned' => [ 'me' ],
                            'combine' => [ 'subassigned' ],
                            'type' => [ 'access' ],
                            'overall_status' => [ $status_key ],
                            'sort' => 'seeker_path'
                        ],
                        'labels' => [
                            [ 'name' => $status_value['label'] ],
                            [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
                            [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
                        ],
                        'count' => $status_counts[$status_key],
                        'subfilter' => 1
                    ];
                    if ( $status_key === 'active' ){
                        if ( $update_needed > 0 ){
                            $filters['filters'][] = [
                                'ID' => 'my_update_needed',
                                'tab' => 'default',
                                'name' => $fields['requires_update']['name'],
                                'query' => [
                                    'assigned_to' => [ 'me' ],
                                    'subassigned' => [ 'me' ],
                                    'combine' => [ 'subassigned' ],
                                    'overall_status' => [ 'active' ],
                                    'requires_update' => [ true ],
                                    'type' => [ 'access' ],
                                    'sort' => 'seeker_path'
                                ],
                                'labels' => [
                                    [ 'name' => $fields['requires_update']['name'] ],
                                    [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
                                    [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
                                ],
                                'count' => $update_needed,
                                'subfilter' => 2
                            ];
                        }
                        if ( isset( $fields['seeker_path']['default'] ) && is_array( $fields['seeker_path']['default'] ) ){
                            foreach ( $fields['seeker_path']['default'] as $seeker_path_key => $seeker_path_value ){
                                if ( isset( $active_counts[$seeker_path_key] ) ){
                                    $filters['filters'][] = [
                                        'ID' => 'my_' . $seeker_path_key,
                                        'tab' => 'default',
                                        'name' => $seeker_path_value['label'],
                                        'query' => [
                                            'assigned_to' => [ 'me' ],
                                            'subassigned' => [ 'me' ],
                                            'combine' => [ 'subassigned' ],
                                            'overall_status' => [ 'active' ],
                                            'seeker_path' => [ $seeker_path_key ],
                                            'type' => [ 'access' ],
                                            'sort' => 'name'
                                        ],
                                        'labels' => [
                                            [ 'name' => $seeker_path_value['label'] ],
                                            [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
                                            [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
                                        ],
                                        'count' => $active_counts[$seeker_path_key],
                                        'subfilter' => 2
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Setup dispatcher filters
             */
            if ( current_user_can( 'dt_all_access_contacts' ) || current_user_can( 'access_specific_sources' ) ) {
                $counts = self::get_all_contacts_status_seeker_path();
                $all_active_counts = [];
                $all_update_needed = 0;
                $all_status_counts = [];
                $total_all = 0;
                foreach ( $counts as $count ){
                    $total_all += $count['count'];
                    dt_increment( $all_status_counts[$count['overall_status']], $count['count'] );
                    if ( $count['overall_status'] === 'active' ){
                        if ( isset( $count['update_needed'] ) ) {
                            $all_update_needed += (int) $count['update_needed'];
                        }
                        dt_increment( $all_active_counts[$count['seeker_path']], $count['count'] );
                    }
                }
                if ( !isset( $all_status_counts['closed'] ) ) {
                    $all_status_counts['closed'] = '';
                }
                $filters['tabs'][] = [
                    'key' => 'all_dispatch',
//                    "label" => __( "Follow-Up", 'disciple_tools' ),
                    'label' => sprintf( _x( 'Follow-Up %s', 'All records', 'disciple_tools' ), DT_Posts::get_post_settings( $post_type )['label_plural'] ),
                    'count' => $total_all,
                    'order' => 10
                ];
                // add assigned to me filters
                $filters['filters'][] = [
                    'ID' => 'all_dispatch',
                    'tab' => 'all_dispatch',
                    'name' => __( 'All Follow-Up', 'disciple_tools' ),
                    'query' => [
                        'overall_status' => [ '-closed' ],
                        'type' => [ 'access' ],
                        'sort' => 'overall_status'
                    ],
                    'count' => $total_all,
                ];

                foreach ( $fields['overall_status']['default'] as $status_key => $status_value ) {
                    if ( isset( $all_status_counts[$status_key] ) ) {
                        $filters['filters'][] = [
                            'ID' => 'all_' . $status_key,
                            'tab' => 'all_dispatch',
                            'name' => $status_value['label'],
                            'query' => [
                                'overall_status' => [ $status_key ],
                                'type' => [ 'access' ],
                                'sort' => 'seeker_path'
                            ],
                            'count' => $all_status_counts[$status_key]
                        ];
                        if ( $status_key === 'active' ){
                            if ( $all_update_needed > 0 ){
                                $filters['filters'][] = [
                                    'ID' => 'all_update_needed',
                                    'tab' => 'all_dispatch',
                                    'name' => $fields['requires_update']['name'],
                                    'query' => [
                                        'overall_status' => [ 'active' ],
                                        'requires_update' => [ true ],
                                        'type' => [ 'access' ],
                                        'sort' => 'seeker_path'
                                    ],
                                    'count' => $all_update_needed,
                                    'subfilter' => true
                                ];
                            }
                            if ( isset( $fields['seeker_path']['default'] ) && is_array( $fields['seeker_path']['default'] ) ) {
                                foreach ( $fields['seeker_path']['default'] as $seeker_path_key => $seeker_path_value ) {
                                    if ( isset( $all_active_counts[$seeker_path_key] ) ) {
                                        $filters['filters'][] = [
                                            'ID' => 'all_' . $seeker_path_key,
                                            'tab' => 'all_dispatch',
                                            'name' => $seeker_path_value['label'],
                                            'query' => [
                                                'overall_status' => [ 'active' ],
                                                'seeker_path' => [ $seeker_path_key ],
                                                'type' => [ 'access' ],
                                                'sort' => 'name'
                                            ],
                                            'count' => $all_active_counts[$seeker_path_key],
                                            'subfilter' => true
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $filters['filters'] = self::add_default_custom_list_filters( $filters['filters'] );
        }
        return $filters;
    }

    //list page filters function
    private static function add_default_custom_list_filters( $filters ){
        if ( empty( $filters ) ){
            $filters = [];
        }
        $default_filters = [
            [
                'ID' => 'my_subassigned',
                'visible' => '1',
                'type' => 'default',
                'tab' => 'custom',
                'name' => 'Subassigned to me',
                'query' => [
                    'subassigned' => [ 'me' ],
                    'sort' => 'overall_status',
                ],
                'labels' => [
                    [
                        'id' => 'me',
                        'name' => 'Subassigned to me',
                        'field' => 'subassigned',
                    ],
                ],
            ],
        ];
        $contact_filter_ids = array_map( function ( $a ){
            return $a['ID'];
        }, $filters );
        foreach ( $default_filters as $filter ) {
            if ( !in_array( $filter['ID'], $contact_filter_ids ) ){
                array_unshift( $filters, $filter );
            }
        }
        //translation for default fields
        foreach ( $filters as $index => $filter ) {
            if ( $filter['name'] === 'Subassigned to me' ) {
                $filters[$index]['name'] = __( 'Subassigned only', 'disciple_tools' );
                $filters[$index]['labels'][0]['name'] = __( 'Subassigned only', 'disciple_tools' );
            }
        }
        return $filters;
    }

    //list page filters function
    private static function get_all_contacts_status_seeker_path(){
        global $wpdb;
        $results = [];

        $can_view_all = false;
        if ( current_user_can( 'access_specific_sources' ) ) {
            $sources = get_user_option( 'allowed_sources', get_current_user_id() ) ?: [];
            if ( empty( $sources ) || in_array( 'all', $sources ) ) {
                $can_view_all = true;
            }
        }

        if ( current_user_can( 'dt_all_access_contacts' ) || $can_view_all ) {
            $results = $wpdb->get_results("
                SELECT status.meta_value as overall_status, pm.meta_value as seeker_path, count(pm.post_id) as count, count(un.post_id) as update_needed
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'overall_status' AND status.meta_value != 'closed' )
                INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'contacts' and a.post_status = 'publish' )
                INNER JOIN $wpdb->postmeta type ON ( type.post_id = pm.post_id AND type.meta_key = 'type' AND type.meta_value = 'access' )
                LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
                WHERE pm.meta_key = 'seeker_path'
                GROUP BY status.meta_value, pm.meta_value
            ", ARRAY_A);
        } else if ( current_user_can( 'access_specific_sources' ) ) {
            $sources = get_user_option( 'allowed_sources', get_current_user_id() ) ?: [];
            $sources_sql = dt_array_to_sql( $sources );
            // phpcs:disable
            $results = $wpdb->get_results( $wpdb->prepare( "
                SELECT status.meta_value as overall_status, pm.meta_value as seeker_path, count(pm.post_id) as count, count(un.post_id) as update_needed
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'overall_status' AND status.meta_value != 'closed' )
                INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'contacts' and a.post_status = 'publish' )
                INNER JOIN $wpdb->postmeta type ON ( type.post_id = pm.post_id AND type.meta_key = 'type'  AND type.meta_value = 'access'  )
                LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
                WHERE pm.meta_key = 'seeker_path'
                AND (
                    pm.post_id IN ( SELECT post_id from $wpdb->postmeta as source where source.meta_value IN ( $sources_sql ) )
                    OR pm.post_id IN ( SELECT post_id FROM $wpdb->dt_share AS shares where shares.user_id = %s )
                )
                GROUP BY status.meta_value, pm.meta_value
            ", esc_sql( get_current_user_id() ) ) , ARRAY_A );
            // phpcs:enable
        }
        return $results;
    }

    //list page filters function
    private static function get_my_contacts_status_seeker_path(){
        global $wpdb;
        $user_post = Disciple_Tools_Users::get_contact_for_user( get_current_user_id() ) ?? 0;
        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT status.meta_value as overall_status, pm.meta_value as seeker_path, count(pm.post_id) as count, count(un.post_id) as update_needed
            FROM $wpdb->postmeta pm
            INNER JOIN $wpdb->postmeta status ON( status.post_id = pm.post_id AND status.meta_key = 'overall_status' AND status.meta_value != 'closed')
            INNER JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = 'contacts' and a.post_status = 'publish' )
            LEFT JOIN $wpdb->postmeta un ON ( un.post_id = pm.post_id AND un.meta_key = 'requires_update' AND un.meta_value = '1' )
            INNER JOIN $wpdb->postmeta type ON ( type.post_id = pm.post_id AND type.meta_key = 'type' AND type.meta_value = 'access' )
            WHERE pm.meta_key = 'seeker_path'
            AND (
                pm.post_id IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'assigned_to' AND meta_value = CONCAT( 'user-', %s ) )
                OR pm.post_id IN ( SELECT p2p_to from $wpdb->p2p WHERE p2p_from = %s AND p2p_type = 'contacts_to_subassigned' )
            )
            GROUP BY status.meta_value, pm.meta_value
        ", get_current_user_id(), $user_post ), ARRAY_A);
        return $results;
    }

    private static function handle_quick_action_button_event( int $contact_id, array $field, bool $check_permissions = true ) {
        $update = [];
        $key = key( $field );

        if ( $key == 'quick_button_no_answer' ) {
            $update['seeker_path'] = 'attempted';
        } elseif ( $key == 'quick_button_phone_off' ) {
            $update['seeker_path'] = 'attempted';
        } elseif ( $key == 'quick_button_contact_established' ) {
            $update['seeker_path'] = 'established';
        } elseif ( $key == 'quick_button_meeting_scheduled' ) {
            $update['seeker_path'] = 'scheduled';
        } elseif ( $key == 'quick_button_meeting_complete' ) {
            $update['seeker_path'] = 'met';
        }

        if ( isset( $update['seeker_path'] ) ) {
            self::check_requires_update( $contact_id );
            return self::update_seeker_path( $contact_id, $update['seeker_path'], $check_permissions );
        } else {
            return $contact_id;
        }
    }

    public static function update_quick_action_buttons( $contact_id, $seeker_path ){
        if ( $seeker_path === 'established' ){
            $quick_button = get_post_meta( $contact_id, 'quick_button_contact_established', true );
            if ( empty( $quick_button ) || $quick_button == '0' ){
                update_post_meta( $contact_id, 'quick_button_contact_established', '1' );
            }
        }
        if ( $seeker_path === 'scheduled' ){
            $quick_button = get_post_meta( $contact_id, 'quick_button_meeting_scheduled', true );
            if ( empty( $quick_button ) || $quick_button == '0' ){
                update_post_meta( $contact_id, 'quick_button_meeting_scheduled', '1' );
            }
        }
        if ( $seeker_path === 'met' ){
            $quick_button = get_post_meta( $contact_id, 'quick_button_meeting_complete', true );
            if ( empty( $quick_button ) || $quick_button == '0' ){
                update_post_meta( $contact_id, 'quick_button_meeting_complete', '1' );
            }
        }
        self::check_requires_update( $contact_id );
    }

    private static function update_seeker_path( int $contact_id, string $path_option, $check_permissions = true ) {
        $contact_fields = DT_Posts::get_post_field_settings( 'contacts' );
        $seeker_path_options = $contact_fields['seeker_path']['default'];
        $option_keys = array_keys( $seeker_path_options );
        $current_seeker_path = get_post_meta( $contact_id, 'seeker_path', true );
        $current_index = array_search( $current_seeker_path, $option_keys );
        $new_index = array_search( $path_option, $option_keys );
        if ( $new_index > $current_index ) {
            $current_index = $new_index;
            $update = DT_Posts::update_post( 'contacts', $contact_id, [ 'seeker_path' => $path_option ], $check_permissions );
            if ( is_wp_error( $update ) ) {
                return $update;
            }
            $current_seeker_path = $path_option;
        }

        return [
            'currentKey' => $current_seeker_path,
            'current' => $seeker_path_options[ $option_keys[ $current_index ] ],
            'next'    => isset( $option_keys[ $current_index + 1 ] ) ? $seeker_path_options[ $option_keys[ $current_index + 1 ] ] : '',
        ];
    }

    //check to see if the contact is marked as needing an update
    //if yes: mark as updated
    private static function check_requires_update( $contact_id ){
        if ( get_current_user_id() ){
            $requires_update = get_post_meta( $contact_id, 'requires_update', true );
            if ( $requires_update == 'yes' || $requires_update == true || $requires_update == '1' ){
                //don't remove update needed if the user is a dispatcher (and not assigned to the contacts.)
                if ( current_user_can( 'dt_all_access_contacts' ) ){
                    if ( dt_get_user_id_from_assigned_to( get_post_meta( $contact_id, 'assigned_to', true ) ) === get_current_user_id() ){
                        update_post_meta( $contact_id, 'requires_update', false );
                    }
                } else {
                    update_post_meta( $contact_id, 'requires_update', false );
                }
            }
        }
    }

    public static function accept_contact( WP_REST_Request $request ){
        $params = $request->get_params();
        $body = $request->get_json_params() ?? $request->get_body_params();
        if ( !isset( $params['id'] ) ) {
            return new WP_Error( 'accept_contact', 'Missing a valid contact id', [ 'status' => 400 ] );
        } else {
            $contact_id = $params['id'];
            $accepted = $body['accept'];
            if ( !DT_Posts::can_update( 'contacts', $contact_id ) ) {
                return new WP_Error( __FUNCTION__, 'You do not have permission for this', [ 'status' => 403 ] );
            }

            if ( $accepted ) {
                $update = [
                    'overall_status' => 'active',
                    'accepted' => true
                ];
                dt_activity_insert(
                    [
                        'action'         => 'assignment_accepted',
                        'object_type'    => get_post_type( $contact_id ),
                        'object_subtype' => '',
                        'object_name'    => get_the_title( $contact_id ),
                        'object_id'      => $contact_id,
                        'meta_id'        => '', // id of the comment
                        'meta_key'       => '',
                        'meta_value'     => '',
                        'meta_parent'    => '',
                        'object_note'    => '',
                    ]
                );
                return DT_Posts::update_post( 'contacts', $contact_id, $update, true );
            } else {
                $assign_to_id = 0;
                $last_activity = DT_Posts::get_most_recent_activity_for_field( $contact_id, 'assigned_to' );
                if ( isset( $last_activity->user_id ) ){
                    $assign_to_id = $last_activity->user_id;
                } else {
                    $base_user = dt_get_base_user( true );
                    if ( $base_user ){
                        $assign_to_id = $base_user;
                    }
                }

                $update = [
                    'assigned_to' => $assign_to_id,
                    'overall_status' => 'unassigned'
                ];
                $contact = DT_Posts::update_post( 'contacts', $contact_id, $update, true );
                $current_user = wp_get_current_user();
                dt_activity_insert(
                    [
                        'action'         => 'assignment_decline',
                        'object_type'    => get_post_type( $contact_id ),
                        'object_subtype' => 'decline',
                        'object_name'    => get_the_title( $contact_id ),
                        'object_id'      => $contact_id,
                        'meta_id'        => '', // id of the comment
                        'meta_key'       => '',
                        'meta_value'     => '',
                        'meta_parent'    => '',
                        'object_note'    => ''
                    ]
                );
                Disciple_Tools_Notifications::insert_notification_for_assignment_declined( $current_user->ID, $assign_to_id, $contact_id );
                return $contact;
            }
        }
    }

    /*
     * Check other access contacts for possible duplicates
     */
    private function check_for_duplicates( $post_type, $post_id ){
        if ( get_current_user_id() === 0 ){
            $current_user = wp_get_current_user();
            $had_cap = current_user_can( 'dt_all_access_contacts' );
            $current_user->add_cap( 'dt_all_access_contacts' );
            $dup_ids = DT_Duplicate_Checker_And_Merging::ids_of_non_dismissed_duplicates( $post_type, $post_id, true );
            if ( ! is_wp_error( $dup_ids ) && sizeof( $dup_ids['ids'] ) < 10 ){
                $comment = __( 'This record might be a duplicate of: ', 'disciple_tools' );
                foreach ( $dup_ids['ids'] as $id_of_duplicate ){
                    $comment .= " \n -  [$id_of_duplicate]($id_of_duplicate)";
                }
                $args = [
                    'user_id' => 0,
                    'comment_author' => __( 'Duplicate Checker', 'disciple_tools' )
                ];
                DT_Posts::add_post_comment( $post_type, $post_id, $comment, 'duplicate', $args, false, true );
            }
            if ( !$had_cap ){
                $current_user->remove_cap( 'dt_all_access_contacts' );
            }
        }
    }

    public function dt_filter_users_receiving_comment_notification( $users_to_notify, $post_type, $post_id, $comment ){
        if ( $post_type === 'contacts' ){
            $post = DT_Posts::get_post( $post_type, $post_id );
            if ( !is_wp_error( $post ) && isset( $post['type']['key'] ) && $post['type']['key'] === 'access' ){
                $following_all = get_users( [
                    'meta_key' => 'dt_follow_all',
                    'meta_value' => true
                ] );
                foreach ( $following_all as $user ){
                    if ( !in_array( $user->ID, $users_to_notify ) ){
                        $users_to_notify[] = $user->ID;
                    }
                }
            }
        }
        return $users_to_notify;
    }


    public function get_dispatch_list( WP_REST_Request $request ) {
        if ( !current_user_can( 'dt_all_access_contacts' ) || !current_user_can( 'list_users' ) ){
            return new WP_Error( __FUNCTION__, __( 'No permission' ), [ 'status' => 403 ] );
        }
        $params = $request->get_query_params();

        $user_data = DT_User_Management::get_users( true );

        $last_assignments = $this->get_assignments();
        $location_data = $this->get_location_data( $params['location_ids'] );
        $gender_data = $this->get_gender_data();

        $list = [];
        $workload_status_options = dt_get_site_custom_lists()['user_workload_status'] ?? [];
        foreach ( $user_data as $user ) {
            $roles = maybe_unserialize( $user['roles'] );
            if ( isset( $roles['multiplier'] ) || isset( $roles['dt_admin'] ) || isset( $roles['dispatcher'] ) || isset( $roles['marketer'] ) ) {
                $u = [
                    'name' => wp_specialchars_decode( $user['display_name'] ),
                    'ID' => $user['ID'],
                    'avatar' => get_avatar_url( $user['ID'], [ 'size' => '16' ] ),
                    'last_assignment' => $last_assignments[$user['ID']] ?? null,
                    'roles' => array_keys( $roles ),
                    'location' => null,
                    'languages' => [],
                    'gender' => null,
                ];
                $user_languages = get_user_option( 'user_languages', $user['ID'] );
                if ( $user_languages ) {
                    $u['languages'] = $user_languages;
                }
                //extra information for the dispatcher
                $workload_status = $user['workload_status'] ?? null;
                if ( $workload_status && isset( $workload_status_options[$workload_status]['color'] ) ) {
                    $u['status'] = $workload_status;
                    $u['status_color'] = $workload_status_options[$workload_status]['color'];
                }
                if ( isset( $location_data[$user['ID']] ) ){
                    $u['location'] = $location_data[$user['ID']]['level'];
                    $u['best_location_match'] = $location_data[$user['ID']]['match_name'];
                }
                if ( isset( $gender_data[$user['ID']] ) ) {
                    $u['gender'] = $gender_data[$user['ID']];
                }

                $u['update_needed'] = (int) $user['number_update'] ?? 0;

                $list[] = $u;
            }
        }

        return apply_filters( 'dt_get_dispatch_list', $list, $params['post_type'], $params['post_id'] );
    }

    private function get_assignments() {
        global $wpdb;
        $last_assignment_query = $wpdb->get_results( "
            SELECT meta_value as user, MAX(hist_time) as assignment_date
            from $wpdb->dt_activity_log as log
            WHERE meta_key = 'assigned_to'
            GROUP by meta_value",
        ARRAY_A );
        $last_assignments =[];
        foreach ( $last_assignment_query as $assignment ){
            $user_id = str_replace( 'user-', '', $assignment['user'] );
            $last_assignments[$user_id] = $assignment['assignment_date'];
        }

        return $last_assignments;
    }

    private function get_location_data( $location_ids ) {
        global $wpdb;

        $location_data = [];
        if ( isset( $location_ids ) ) {
            foreach ( $location_ids as $grid_id ){
                $location = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->dt_location_grid WHERE grid_id = %s", esc_sql( $grid_id ) ), ARRAY_A );
                $levels = [];

                if ( $grid_id === '1' ){
                    $match_location_ids = '( 1 )';
                } else {
                    $match_location_ids = '( ';
                    for ( $i = 0; $i <= ( (int) $location['level'] ); $i++ ) {
                        $levels[ $location['admin'. $i . '_grid_id']] = [ 'level' => $i ];
                        $match_location_ids .= $location['admin'. $i . '_grid_id'] . ', ';
                    }
                    $match_location_ids .= ')';

                }

                $match_location_ids = str_replace( ', )', ' )', $match_location_ids );
                //phpcs:disable
                //already sanitized IN value
                $location_names = $wpdb->get_results( "
                    SELECT alt_name, grid_id
                    FROM $wpdb->dt_location_grid
                    WHERE grid_id IN $match_location_ids
                ", ARRAY_A);

                //get users with the same location grid.
                $users_in_location = $wpdb->get_results( $wpdb->prepare("
                    SELECT user_id, meta_value as grid_id
                    FROM $wpdb->usermeta um
                    WHERE um.meta_key = %s
                    AND um.meta_value IN $match_location_ids
                ", "{$wpdb->prefix}location_grid"), ARRAY_A );
                //phpcs:enable

                foreach ( $location_names as $l ){
                    if ( isset( $levels[$l['grid_id']] ) ) {
                        $levels[$l['grid_id']]['name'] = $l['alt_name'];
                    }
                }

                //0 if the location is exact match. 1 if the matched location is the parent etc
                foreach ( $users_in_location as $l ){
                    $level = (int) $location['level'] - $levels[$l['grid_id']]['level'];
                    if ( !isset( $location_data[$l['user_id']] ) || $location_data[$l['user_id']]['level'] > $level ){
                        $location_data[$l['user_id']] = [
                            'level' => $level,
                            'match_name' => $levels[$l['grid_id']]['name']
                        ];
                    }
                }
            }
        }
        return $location_data;
    }

    private function get_gender_data() {
        global $wpdb;
        $gender_data = [];

        $gender_query = $wpdb->get_results( $wpdb->prepare("
            SELECT user_id, meta_value as gender
            from $wpdb->usermeta
            WHERE meta_key = %s", "{$wpdb->prefix}user_gender"),
        ARRAY_A );

        foreach ( $gender_query as $data ){
            $gender_data[$data['user_id']] = $data['gender'];
        }

        return $gender_data;
    }
}

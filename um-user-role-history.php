<?php
/**
 * Plugin Name:     Ultimate Member - User Role History
 * Description:     Extension to Ultimate Member for display of User Role History of Role Changes and User Registration Date and Last Login Date.
 * Version:         3.1.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

Class UM_User_Role_History {

    public $max_saved_roles = 100;                      // Max saved Role changes
    public $username        = 'user_login';             // Performed by username
    public $table_row_color = 'rgb(59,161,218,0.3)';    // Background RGB color and opacity ( UM Default blue )

    function __construct() {

        add_action( 'um_profile_content_user_role_history_default', array( $this, 'um_profile_content_user_role_history_default' ), 10, 1 );
        add_action( 'set_user_role',                                array( $this, 'custom_role_is_changed_user_role_history' ), 10, 3 );
        add_filter( 'um_set_user_role',                             array( $this, 'um_after_user_role_is_updated_user_role_history' ), 10, 3 );
        add_filter( 'um_profile_tabs',                              array( $this, 'um_user_role_history_add_tab' ), 1000, 1 );
        add_filter( 'um_settings_structure',                        array( $this, 'um_settings_structure_user_role_history' ), 10, 1 );
    }

    public function um_user_role_history_add_tab( $tabs ) {

        $tabs['user_role_history'] = array(
              'name'   => __( 'User History', 'ultmate-member' ),
              'icon'   => 'um-faicon-pencil',
              'custom' => true
        );

        return $tabs;
    }

    public function um_after_user_role_is_updated_user_role_history( $new_role, $user_id, $user ) {

        $this->user_role_history( $new_role, $user_id, 'A' );
        return $new_role;
    }

    public function custom_role_is_changed_user_role_history( $user_id, $new_role, $old_roles ) {

        if ( ! empty( $old_roles ) && is_array( $old_roles )) {

            $old_role = array_shift( $old_roles );
            $this->user_role_history( $new_role, $user_id, 'B', $old_role );

        } else {

            $this->user_role_history( $new_role, $user_id, 'C' );
        }
    }

    public function get_date_format() {

        $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $um_user_role_history_date_format = UM()->options()->get( 'um_user_role_history_date_format' );

        if ( ! empty( $um_user_role_history_date_format )) {
            $date_format = $um_user_role_history_date_format;
        }

        return $date_format;
    }

    public function max_changes_user_role_history( $user_role_history, $current_addition = false) {

        if ( ! empty( $current_addition )) {
            $user_role_history = array_merge( $user_role_history, $current_addition );
        }

        $um_user_role_history_max_changes = intval( UM()->options()->get( 'um_user_role_history_max_changes' ));

        if ( $um_user_role_history_max_changes == 0 ) {
            $um_user_role_history_max_changes = $this->max_saved_roles;
        }

        if ( count( $user_role_history ) > $um_user_role_history_max_changes ) {
            $user_role_history = array_slice( $user_role_history, -$um_user_role_history_max_changes );
        }

        return $user_role_history;
    }

    public function user_role_history_date( $date_type, $default = false ) {

        switch( $date_type ) {
            case 'user_registered': $date = um_user( 'user_registered' );  break;
            case '_um_last_login':  $date = um_user( '_um_last_login' );   break;
            case 'timestamp':       $date = current_time( 'mysql', true ); break;
            default:                $date = $date_type; break;
        }
 
        $timestamp = $date;
        if ( ! is_numeric( $date )) {
            $timestamp = strtotime( $date );
        }

        $format = 'Y-m-d H:i:s';
        if ( ! $default ) {
            $format = $this->get_date_format();
        }

        switch( $date_type ) {
            case 'user_registered': $date = wp_date( $format, $timestamp ); break;
            case '_um_last_login':  $date = wp_date( $format, $timestamp ); break;
            case 'timestamp':       $date =    date( $format, $timestamp ); break;
            default:                $date = wp_date( $format, $timestamp ); break;
        }
        
        if ( empty( $date )) {
            $date = __( 'Invalid date', 'ultimate-member' );
        }

        return $date;
    }

    public function update_user_role_history( $user_id, $user_role_history ) {

        update_user_meta( $user_id, 'user_role_history', $user_role_history );
        UM()->user()->remove_cache( $user_id );
        um_fetch_user( $user_id );
    }

    public function setup_user_role_history( $date, $role, $type ) {

        global $current_user;

        $user_id = '';
        if ( ! empty( $current_user ) && isset( $current_user->ID ) && ! empty( $current_user->ID )) {
            $user_id = $current_user->ID;
        }

        $array = array( 'date'  => $this->user_role_history_date( $date, true ),
                        'role'  => $role,
                        'admin' => $user_id,
                        'type'  => $type
                    );

        return $array;
    }

    public function user_role_history( $new_role, $user_id, $type, $old_role = false ) {

        if ( ! empty( $new_role ) && ! empty( $user_id )) {

            um_fetch_user( $user_id );
            $user_role_history = um_user( 'user_role_history' );
            $current_addition = array();

            if ( empty( $user_role_history )) {

                if ( ! empty( $old_role )) {
                    $current_addition[] = $this->setup_user_role_history( 'user_registered', $old_role, $type );
                }

                $current_addition[] = $this->setup_user_role_history( 'timestamp', $new_role, $type );
                $this->update_user_role_history( $user_id, $current_addition );

            } else {

                if ( is_array( $user_role_history )) {

                    if ( isset( $user_role_history['date'])) unset( $user_role_history['date'] );
                    if ( isset( $user_role_history['role'])) unset( $user_role_history['role'] );

                    $last_key = array_key_last( $user_role_history );
                    if ( $user_role_history[$last_key]['role'] != $new_role ) {

                        $current_addition[] = $this->setup_user_role_history( 'timestamp', $new_role, $type );
                        $user_role_history  = $this->max_changes_user_role_history( $user_role_history, $current_addition );
                        $this->update_user_role_history( $user_id, $user_role_history );
                    }
                }
            }
        }
    }

    public function um_profile_content_user_role_history_default( $args ) {

        $user_last_login = um_user( '_um_last_login' );
        if ( empty( $user_last_login )) {
            $date  = '';
            $login = __( 'No login', 'ultimate-member' );

        } else {

            $date  = $this->user_role_history_date( '_um_last_login' );
            $login = __( 'Last login', 'ultimate-member' );

            if ( defined( 'um_online_url' ) ) {
                $online_users = UM()->Online()->get_users();
                if ( ! empty( $online_users[um_profile_id()] ) ) {
                    $login = __( 'Online', 'ultimate-member' );
                }
            }
        }

        echo '<h4>' . __( 'User Role History', 'ultimate-member' ) . '</h4>
              <div><table style="border:none !important;";>
                <tr>
                    <th>' . __( 'Date and Time', 'ultmate-member' ) . '</th>
                    <th>' . __( 'Role Change',   'ultmate-member' ) . '</th>
                    <th>' . __( 'Performed by',  'ultmate-member' ) . '</th>
                    <th>' . __( 'Source',        'ultmate-member' ) . '</th>
                </tr>
                <tr><td style="border:none !important;">' . esc_attr( $this->user_role_history_date( 'user_registered' )) . '</td>
                    <td style="border:none !important;">' . __( 'User Registration', 'ultimate-member' ) . '</td>
                </tr>
                <tr>
                    <td style="border:none !important;">' . esc_attr( $date ) . '</td>
                    <td style="border:none !important;">' . $login . '</td>
                </tr>';

        $user_role_history = um_user( 'user_role_history' );
        if ( empty( $user_role_history )) {

            $role = UM()->roles()->get_priority_user_role( um_profile_id() );
            $role_name = UM()->roles()->get_role_name( $role );

            echo '<tr><td style="border:none !important;">' . esc_attr( $this->user_role_history_date( 'user_registered' )) . '</td>
                      <td style="border:none !important;">' . esc_attr( $role_name ) . '</td>
                  </tr>';

        } else {

            if ( is_array( $user_role_history )) {

                $user_role_history = $this->max_changes_user_role_history( $user_role_history );

                $type_translations = array();
                $types = array_map( 'sanitize_text_field', explode( ',', UM()->options()->get( 'um_user_role_history_types' )));

                if ( ! empty( $types ) && is_array( $types )) {
                    foreach( $types as $type ) {
                        $type = array_map( 'trim', explode( ':', $type ));
                        if ( is_array( $type ) && isset( $type[0] ) && isset( $type[1] )) {
                            $type_translations[$type[0]] = $type[1];
                        }
                    }
                }

                $lines = 1;

                foreach( $user_role_history as $old_role ) {

                    if ( is_array( $old_role ) && isset( $old_role['role'] ) && isset( $old_role['date'] )) {

                        $role_name = UM()->roles()->get_role_name( $old_role['role'] );
                        if ( ! empty( $role_name )) {

                            $admin_login = 'UM';
                            if ( isset( $old_role['admin'] ) && ! empty( $old_role['admin'] )) {
                                $admin_login = sprintf( __( 'User ID %s', 'ultimate-member' ), $old_role['admin'] );

                                $admin_user = new WP_User( $old_role['admin'] );
                                if ( $admin_user->has_prop( $this->username )) {
                                    $admin_login = $admin_user->get( $this->username );
                                }
                            }

                            $type = '';
                            if ( isset( $old_role['type'] )) {
                                $type = $old_role['type'];
                                if( isset( $type_translations[$old_role['type']] )) {
                                    $type = $type_translations[$old_role['type']];
                                }
                            }

                            $role_date = $this->user_role_history_date( $old_role['date'] );
                            $background_color = ( $lines % 2 != 0 ) ?  '<tr style="background-color:' . esc_attr( $this->table_row_color) . ' !important;">' : '<tr>';

                            echo $background_color .
                                '<td style="border:none !important;">' . esc_attr( $role_date ) . '</td>
                                 <td style="border:none !important;">' . esc_attr( $role_name ) . '</td>
                                 <td style="border:none !important;">' . esc_attr( $admin_login ) . '</td>
                                 <td style="border:none !important;">' . esc_attr( $type ) . '</td>
                            </tr>';
                            $lines++;
                        }
                    }
                }
            }
        }

        echo '</table></div>';
    }

    public function um_settings_structure_user_role_history( $settings_structure ) {

        $settings_structure['']['sections']['users']['fields'][] = array(
                    'id'            => 'um_user_role_history_date_format',
                    'type'          => 'text',
                    'size'          => 'small',
                    'label'         => __( 'User Role History - Date/Time format', 'ultimate-member' ),
                    'tooltip'       => sprintf( __( 'Default is the WP date format "%s %s". Both PHP date and time local formats can be used.', 'ultimate-member' ), get_option( 'date_format' ), get_option( 'time_format' )),
                );

        $settings_structure['']['sections']['users']['fields'][] = array(
                    'id'            => 'um_user_role_history_max_changes',
                    'type'          => 'text',
                    'size'          => 'small',
                    'label'         => __( 'User Role History - Max number of Role changes saved', 'ultimate-member' ),
                    'tooltip'       => sprintf( __( 'Default is %d saved Role changes per user.', 'ultimate-member' ), $this->max_saved_roles ),
                );

        $settings_structure['']['sections']['users']['fields'][] = array(
                    'id'            => 'um_user_role_history_types',
                    'type'          => 'text',
                    'size'          => 'medium',
                    'label'         => __( 'User Role History - Source type Translations', 'ultimate-member' ),
                    'tooltip'       => __( 'Enter Source type translations comma separated like A:UM,B:Backend,C:Registration', 'ultimate-member' ),
                );

        return $settings_structure;
    }
}

new UM_User_Role_History();


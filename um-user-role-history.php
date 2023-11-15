<?php
/**
 * Plugin Name:     Ultimate Member - User Role History
 * Description:     Extension to Ultimate Member for display of User Role History of Role Changes and User Registration Date and Last Login Date.
 * Version:         2.3.1
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

Class UM_User_Role_History {

    public $duplicate_stop = false;

    function __construct() {

        add_action( 'um_profile_content_user_role_history_default', array( $this, 'um_profile_content_user_role_history_default' ), 10, 1 );
        add_action( 'set_user_role',                                array( $this, 'custom_role_is_changed_user_role_history' ), 10, 3 );
        add_filter( 'um_set_user_role',                             array( $this, 'um_after_user_role_is_updated_user_role_history' ), 10, 3 );
        add_filter( 'um_profile_tabs',                              array( $this, 'um_user_role_history_add_tab' ), 1000, 1 );
        add_filter( 'um_settings_structure',                        array( $this, 'um_settings_structure_user_role_history' ), 10, 1 );
    }

    public function um_user_role_history_add_tab( $tabs ) {

        $tabs['user_role_history'] = array(
              'name'   => 'User History',
              'icon'   => 'um-faicon-pencil',
              'custom' => true
        );

        return $tabs;
    }

    public function get_date_format() {

        $date_format = get_option( 'date_format' );
        $um_user_role_history_date_format = UM()->options()->get( 'um_user_role_history_date_format' );

        if ( ! empty( $um_user_role_history_date_format )) {
            $date_format = $um_user_role_history_date_format;
        }
        return $date_format;
    }

    public function um_after_user_role_is_updated_user_role_history( $new_role, $user_id, $user ) {

        if ( $this->duplicate_stop != $new_role ) {
            if ( UM()->roles()->get_priority_user_role( $user_id ) != $new_role ) {

                um_fetch_user( $user_id );
                $user_role_history = um_user( 'user_role_history' );

                if ( empty( $user_role_history )) {
                    $user_role_history = array();
                }

                if ( ! empty( $new_role ) && is_array( $user_role_history )) {

                    if ( isset( $user_role_history['date'])) unset( $user_role_history['date'] );
                    if ( isset( $user_role_history['role'])) unset( $user_role_history['role'] );

                    $array = array();
                    $array[] = array( 'date' => date_i18n( $this->get_date_format(), current_time( 'timestamp' )), 'role' => $new_role );
                    $user_role_history = array_merge( $user_role_history, $array );

                    update_user_meta( $user_id, 'user_role_history', $user_role_history );
                    UM()->user()->remove_cache( $user_id );
                    um_fetch_user( $user_id );

                    $this->duplicate_stop = $new_role;
                }
            }
        }

        return $new_role;
    }

    public function custom_role_is_changed_user_role_history( $user_id, $role, $old_roles ) {

        if ( $this->duplicate_stop != $role ) {
            if ( UM()->roles()->get_priority_user_role( $user_id ) != $role ) {

                um_fetch_user( $user_id );
                $user_role_history = um_user( 'user_role_history' );

                if ( empty( $user_role_history )) {

                    $user_role_history = array();

                    if ( ! empty( $old_roles ) && is_array( $old_roles )) {

                        $old_role = array_shift( $old_roles );

                        $array = array();
                        $array[] = array( 'date' => date_i18n( $this->get_date_format(), strtotime( um_user( 'user_registered' ))), 'role' => $old_role );
                        $user_role_history = array_merge( $user_role_history, $array );
                    }
                }

                if ( is_array( $user_role_history )) {

                    if ( isset( $user_role_history['date'])) unset( $user_role_history['date'] );
                    if ( isset( $user_role_history['role'])) unset( $user_role_history['role'] );

                    $array = array();
                    $array[] = array( 'date' => date_i18n( $this->get_date_format(), current_time( 'timestamp' )), 'role' => $role );
                    $user_role_history = array_merge( $user_role_history, $array );

                    update_user_meta( $user_id, 'user_role_history', $user_role_history );
                    UM()->user()->remove_cache( $user_id );

                    $this->duplicate_stop = $role;
                }
            }
        }
    }

    public function um_profile_content_user_role_history_default( $args ) {

        echo '<h4>' . __( 'User Role History', 'ultimate-member' ) . '</h4>
              <div><table>';

        echo '<tr><td style="border-bottom:none !important;">' . date_i18n( $this->get_date_format(), strtotime( um_user( 'user_registered' ))) . '</td>
                  <td style="border-bottom:none !important;">' . __( 'User Registration', 'ultimate-member' ) . '</td>
              </tr>';

        $user_last_login = um_user( '_um_last_login' );
        if ( empty( $user_last_login )) {

            echo '<tr><td style="border-bottom:none !important;"></td>
                      <td style="border-bottom:none !important;">' . __( 'No login', 'ultimate-member' ) . '</td>
                  </tr>';

        } else {

            echo '<tr><td style="border-bottom:none !important;">' . esc_attr( date_i18n( $this->get_date_format(), $user_last_login ) ) . '</td>
                      <td style="border-bottom:none !important;">' . __( 'Last login', 'ultimate-member' ) . '</td>
                  </tr>';
        }

        $user_role_history = um_user( 'user_role_history' );
        if ( empty( $user_role_history )) {

            $role = UM()->roles()->get_priority_user_role( um_profile_id() );
            $role_name = UM()->roles()->get_role_name( $role );

            echo '<tr><td style="border-bottom:none !important;">' . date_i18n( $this->get_date_format(), strtotime( um_user( 'user_registered' ))) . '</td>
                      <td style="border-bottom:none !important;">' . esc_attr( $role_name ) . '</td>
                  </tr>';

        } else {

            if ( is_array( $user_role_history )) {

                foreach( $user_role_history as $role_step ) {

                    if ( is_array( $role_step ) && isset( $role_step['role'] ) && isset( $role_step['date'] )) {

                        $role_name = UM()->roles()->get_role_name( $role_step['role'] );

                        if ( ! empty( $role_name )) {

                            $step_date = date_i18n( $this->get_date_format(), strtotime( $role_step['date'] ));
                            echo '<tr><td style="border-bottom:none !important;">' . esc_attr( $step_date ) . '</td>
                                      <td style="border-bottom:none !important;">' . esc_attr( $role_name ) . '</td>
                                  </tr>';
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
                    'label'         => __( 'User Role History - Date format', 'ultimate-member' ),
                    'tooltip'       => sprintf( __( 'Default is the WP date format "%s"', 'ultimate-member' ), get_option( 'date_format' ) ),
                );

        $settings_structure['']['sections']['users']['fields'][] = array(
                    'id'            => 'um_user_role_history_max_changes',
                    'type'          => 'text',
                    'size'          => 'small',
                    'label'         => __( 'User Role History - Max number of changes saved', 'ultimate-member' ),
                    'tooltip'       => sprintf( __( 'Default is 100 Role saved changes per user.', 'ultimate-member' ), get_option( 'date_format' ) ),
                );

        return $settings_structure;
    }
}

new UM_User_Role_History();



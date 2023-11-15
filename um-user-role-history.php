<?php
/**
 * Plugin Name:     Ultimate Member - User Role History
 * Description:     Extension to Ultimate Member for display of User Role History of Role Changes and User Registration Date and Last Login Date.
 * Version:         1.4.0
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

    function __construct() {

        add_action( 'um_profile_content_user_role_history_default', array( $this, 'um_profile_content_user_role_history_default' ), 10, 1 );
        add_action( 'set_user_role',                                array( $this, 'custom_role_is_changed_user_role_history' ), 10, 3 );
        add_filter( 'um_set_user_role',                             array( $this, 'um_after_user_role_is_updated_user_role_history' ), 1000, 3 );
        add_filter( 'um_profile_tabs',                              array( $this, 'um_user_role_history_add_tab' ), 1000, 1 );
    }

    public function um_user_role_history_add_tab( $tabs ) {

        $tabs['user_role_history'] = array(
              'name'   => 'User History',
              'icon'   => 'um-faicon-pencil',
              'custom' => true
        );

        UM()->options()->options['profile_tab_user_role_history'] = true;

        return $tabs;
    }

    public function um_after_user_role_is_updated_user_role_history( $new_role, $user_id, $user ) {

        um_fetch_user( $user_id );
        $user_role_history = um_user( 'user_role_history' );

        if ( empty( $user_role_history )) {
            $user_role_history = array();
        }

        if ( ! empty( $new_role )) {

            $user_role_history = array_merge( $user_role_history, array( 'date' => date_i18n( 'm/d/Y', current_time( 'timestamp' )), 'role' => $new_role ));

            update_user_meta( $user_id, 'user_role_history', $user_role_history );
            UM()->user()->remove_cache( $user_id );
            um_fetch_user( $user_id );
        }

        return $new_role;
    }

    public function custom_role_is_changed_user_role_history( $user_id, $role, $old_roles ) {

        um_fetch_user( $user_id );
        $user_role_history = um_user( 'user_role_history' );

        if ( empty( $user_role_history )) {

            $user_role_history = array();

            if ( ! empty( $old_roles ) && is_array( $old_roles )) {
                $old_role = array_shift( $old_roles );
                $user_role_history = array_merge( $user_role_history, array( 'date' => date_i18n( 'm/d/Y', strtotime( um_user( 'user_registered' )) ), 'role' => $old_role ));
            }
        }

        $user_role_history[] = array( 'date' => date_i18n( 'm/d/Y', current_time( 'timestamp' )), 'role' => $role );

        update_user_meta( $user_id, 'user_role_history', $user_role_history );
        UM()->user()->remove_cache( $user_id );
    }

    public function um_profile_content_user_role_history_default( $args ) {

        echo '<h4>' . __( 'User Role History', 'ultimate-member' ) . '</h4>
              <div><table style="width:100%">';

        echo '<tr><td style="border-bottom:none !important;">' . date_i18n( 'm/d/Y', strtotime( um_user( 'user_registered' ))) . '</td>
                  <td style="border-bottom:none !important;">' . __( 'User Registration', 'ultimate-member' ) . '</td>
              </tr>';

        $user_last_login = um_user( '_um_last_login' );
        if ( empty( $user_last_login )) {

            echo '<tr><td style="border-bottom:none !important;"></td>
                      <td style="border-bottom:none !important;">' . __( 'No login', 'ultimate-member' ) . '</td>
                  </tr>';

        } else {

            echo '<tr><td style="border-bottom:none !important;">' . esc_attr( date_i18n( 'm/d/Y', $user_last_login ) ) . '</td>
                      <td style="border-bottom:none !important;">' . __( 'Last login', 'ultimate-member' ) . '</td>
                  </tr>';
        }

        $user_role_history = um_user( 'user_role_history' );

        if ( empty( $user_role_history )) {

            $role = UM()->roles()->get_priority_user_role( um_profile_id() );
            $role_name = UM()->roles()->get_role_name( $role );

            echo '<tr><td style="border-bottom:none !important;">' . date_i18n( 'm/d/Y', strtotime( um_user( 'user_registered' ))) . '</td>
                      <td style="border-bottom:none !important;">' . esc_attr( $role_name ) . '</td>
                  </tr>';

        } else {

            foreach( $user_role_history as $role_step ) {

                $role_name = UM()->roles()->get_role_name( $role_step['role']);
                if ( ! empty( $role_name )) {
                    echo '<tr><td style="border-bottom:none !important;">' . esc_attr( $role_step['date'] ) . '</td>
                              <td style="border-bottom:none !important;">' . esc_attr( $role_name ) . '</td>
                          </tr>';
                }
            }
        }

        echo '</table></div>';
    }

}

new UM_User_Role_History();

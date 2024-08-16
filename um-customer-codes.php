<?php
/**
 * Plugin Name:         Ultimate Member - Customer Codes
 * Description:         Extension to Ultimate Member for Registrationb Customer Codes Validation.
 * Version:             1.2.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-customer-codes
 * Update URI:          https://github.com/MissVeronica/um-customer-codes
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.3
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Customer_Codes {

    function __construct() {

        add_action( 'um_custom_field_validation_customer_codes', array( $this, 'um_customer_code_validation' ), 100, 3 );
        add_filter( 'um_settings_structure',                     array( $this, 'um_settings_structure_customer_codes' ), 10, 1 );
        add_filter( 'um_predefined_fields_hook',                 array( $this, 'um_predefined_fields_customer_codes' ), 10, 1 );
        add_filter( 'um_user_profile_restricted_edit_fields',    array( $this, 'um_user_profile_restricted_edit_fields' ), 10, 2 );

        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'customer_code_settings_link' ), 10 );

        if ( UM()->options()->get( 'customer_codes_account' ) == 1 ) {

            add_filter( 'um_account_tab_general_fields',         array( $this, 'um_account_custom_fields' ), 10, 1 );
            add_filter( 'um_get_field__customer_code',           array( $this, 'um_get_field__customer_code' ), 10, 1 );
            add_filter( 'um_edit_label_all_fields',              array( $this, 'um_edit_label_customer_code' ), 100, 2 );
        }
    }

    public function um_customer_code_validation( $key, $array, $args ) {

        if ( $key == 'customer_code' && isset( $args[$key] ) && ! empty( $args[$key] ) ) {

            $code = sanitize_text_field( $args[$key] );

            if ( UM()->options()->get( 'customer_codes_single' ) == 1 ) {

                $args_unique_meta = array(
                                            'meta_key'      => $key,
                                            'meta_value'    => $code,
                                            'compare'       => '=',
                                        );

                $meta_key_exists = get_users( $args_unique_meta );

                if ( ! empty( $meta_key_exists ) && is_array( $meta_key_exists ) && count( $meta_key_exists ) > 0 ) {

                    UM()->form()->add_error( $key , __( 'Invalid Old Customer Code', 'ultimate-member' ) );
                }
            }

            $code_options = UM()->options()->get( 'customer_codes' );

            if ( ! empty( $code_options )) {
                $customer_codes = array_map( 'trim', explode( ',', $code_options));

                if ( ! in_array( $code, $customer_codes )) {
                    UM()->form()->add_error( $key, __( 'Invalid Customer Code', 'ultimate-member' ) );
                }

            } else {

                UM()->form()->add_error( $key, __( 'No Customer Codes available', 'ultimate-member' ) );
            }
        }
    }

    public function um_account_custom_fields( $args ) {

        if ( ! strpos( $args, ',customer_code' )) {
            $args = str_replace( ',single_user_password', ',customer_code,single_user_password', $args );
        }

        return $args;
    }

    public function um_user_profile_restricted_edit_fields( $arr_restricted_fields, $_um_profile_id ){

        $arr_restricted_fields[] = 'customer_code';

        return $arr_restricted_fields;
    }

    public function um_get_field__customer_code( $fields ) {

        if ( um_is_core_page( 'account' ) ) {
            $fields['disabled'] = 'disabled="disabled"';
        }

        return $fields;
    }

    public function um_edit_label_customer_code( $label, $data ) {

        if ( isset( $data['metakey'] ) && $data['metakey'] == 'customer_code' && um_is_core_page( 'account' ) ) {
            $label = str_replace( '*', '', $label );
        }

        return $label;
    }

    function customer_code_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=um_options&tab=appearance&section=registration_form';
        $links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>';

        return $links;
    }

    public function um_settings_structure_customer_codes( $settings_structure ) {

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'appearance' ) {
                if ( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'registration_form' ) {

                    if ( ! isset( $settings_structure['appearance']['sections']['']['form_sections']['customer_codes']['fields'] )) {

                        $plugin_data = get_plugin_data( __FILE__ );

                        $link = sprintf( '<a href="%s" target="_blank" title="%s">%s</a>',
                                                    esc_url( $plugin_data['PluginURI'] ),
                                                    __( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                                    __( 'Plugin', 'ultimate-member' )
                                        );
    
                        $header = array(
                                            'title'       => __( 'Registration Customer Codes', 'ultimate-member' ),
                                            'description' => sprintf( __( '%s version %s - tested with UM 2.8.6', 'ultimate-member' ),
                                                                                $link, esc_attr( $plugin_data['Version'] )),
                                        );

                        $description = '';
                        $tooltip = __( 'All the valid customer codes (comma separated) for the 
                                        Registration Form.', 'ultimate-member' );

                        if ( UM()->options()->get( 'customer_codes_single' ) == 1 ) {

                            $tooltip .= '<br />' . __( 'Old used customer codes found in the current textbox are listed below the textbox.', 'ultimate-member' );

                            if ( ! empty( UM()->options()->get( 'customer_codes' ))) {

                                $customer_codes = array_map( 'trim', explode( ',', UM()->options()->get( 'customer_codes' )));
                                $customer_codes = array_unique( $customer_codes );
                                $invalid_codes = array();

                                $args_unique_meta = array(
                                                            'meta_key'      => 'customer_code',
                                                            'meta_value'    => $customer_codes,
                                                            'compare'       => '=',
                                                        );

                                $meta_key_exists = get_users( $args_unique_meta );

                                if ( is_array( $meta_key_exists ) && count( $meta_key_exists ) > 0 ) {

                                    foreach( $meta_key_exists as $user ) {
                                        $invalid_codes[] = $user->customer_code;
                                    }
                                }

                                $invalid_codes = array_unique( $invalid_codes );
                                sort( $invalid_codes );

                                if ( count( $invalid_codes ) > 0 ) {

                                    if ( count( $invalid_codes ) == 1 ) {
                                        $description = sprintf( __( 'Old used code: %s', 'ultimate-member' ), implode( ',', $invalid_codes ));

                                    } else {
                                        $description = sprintf( __( 'Old used codes: %s', 'ultimate-member' ), implode( ',', $invalid_codes ));
                                    }

                                } else {

                                    if ( count( $customer_codes ) == 1 ) {
                                        $description = __( 'Code is valid', 'ultimate-member' );

                                    } else {
                                        $description = __( 'All codes are valid', 'ultimate-member' );
                                    }
                                }
                            }
                        }

                        $prefix = '&nbsp; * &nbsp;';
                        $section_fields = array();

                        $section_fields[] = array(
                                                    'id'          => 'customer_codes',
                                                    'type'        => 'text',
                                                    'label'       => $prefix . __( 'Valid codes', 'ultimate-member' ),
                                                    'description' => $tooltip . '<br />' . $description,
                                                );

                        $section_fields[] = array(
                                                    'id'             => 'customer_codes_single',
                                                    'type'           => 'checkbox',
                                                    'label'          => $prefix . __( 'Single usage codes', 'ultimate-member' ),
                                                    'checkbox_label' => __( 'Click checkbox if the Customer Codes only can be used one time.', 'ultimate-member' ),
                                                );

                        $section_fields[] = array(
                                                    'id'             => 'customer_codes_account',
                                                    'type'           => 'checkbox',
                                                    'label'          => $prefix . __( 'Account page', 'ultimate-member' ),
                                                    'checkbox_label' => __( 'Click checkbox for Customer Code display in view mode on the user Account page.', 'ultimate-member' ),
                                                );

                        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes'] = $header;
                        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['fields'] = $section_fields;

                    }
                }
            }
        }

        return $settings_structure;
    }

    public function um_predefined_fields_customer_codes( $predefined_fields ) {

        $predefined_fields['customer_code'] = array(

                        'title'           => __( 'Customer code', 'ultimate-member' ),
                        'metakey'         => 'customer_code',
                        'type'            => 'text',
                        'label'           => __( 'Customer code', 'ultimate-member' ),
                        'required'        => 1,
                        'public'          => -1,
                        'editable'        => 0,
                        'placeholder'     => __( 'Code', 'ultimate-member' ),
                        'validate'        => 'custom',
                        'custom_validate' => 'customer_codes',
        );

        return $predefined_fields;
    }
}

new UM_Customer_Codes();



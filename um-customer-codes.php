<?php
/**
 * Plugin Name:         Ultimate Member - Customer Codes
 * Description:         Extension to Ultimate Member for Registrationb Customer Codes Validation.
 * Version:             1.1.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
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

        if ( UM()->options()->get( 'customer_codes_account' ) == 1 ) {

            add_filter( 'um_account_tab_general_fields',         array( $this, 'um_account_custom_fields' ), 10, 1 );
            add_filter( 'um_get_field__customer_code',           array( $this, 'um_get_field__customer_code' ), 10, 1 );
            add_filter( 'um_edit_label_all_fields',              array( $this, 'um_edit_label_customer_code' ), 100, 2 );
        }
    }

    public function um_customer_code_validation( $key, $array, $args ) {

        if ( $key == 'customer_code' && isset( $args[$key] ) && $args[$key] != '' ) {

            $code = sanitize_text_field( $args[$key] );

            if ( UM()->options()->get( 'customer_codes_single' ) == 1 ) {

                $args_unique_meta = array(
                    'meta_key'      => $key,
                    'meta_value'    => $code,
                    'compare'       => '=',
                );

                $meta_key_exists = get_users( $args_unique_meta );

                if ( is_array( $meta_key_exists ) && count( $meta_key_exists ) > 0 ) {

                    UM()->form()->add_error( $key , __( 'Invalid Old Customer Code', 'ultimate-member' ) );
                }
            }

            if ( ! empty( UM()->options()->get( 'customer_codes' ) )) {
                $customer_codes = array_map( 'trim', explode( ',', UM()->options()->get( 'customer_codes' )));

                if ( ! in_array( $code, $customer_codes )) {
                    UM()->form()->add_error( $key, __( 'Invalid Customer Code', 'ultimate-member' ) );
                }
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

    public function um_settings_structure_customer_codes( $settings_structure ) {

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

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['title']       = __( 'Registration Customer Codes', 'ultimate-member' );
        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['description'] = __( 'Plugin version 1.1.0 - tested with UM 2.8.3', 'ultimate-member' );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['fields'][] = array(
            'id'          => 'customer_codes',
            'type'        => 'text',
            'label'       => __( 'Valid codes', 'ultimate-member' ),
            //'tooltip'     => $tooltip,
            'description' => $tooltip . '<br />' . $description,
        ); 

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['fields'][] = array(
            'id'          => 'customer_codes_single',
            'type'        => 'checkbox',
            'label'       => __( 'Single usage codes', 'ultimate-member' ),
            'description' => __( 'Click checkbox if the Customer Codes only can be used one time.', 'ultimate-member' ),
        );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['customer_codes']['fields'][] = array(
            'id'          => 'customer_codes_account',
            'type'        => 'checkbox',
            'label'       => __( 'Account page', 'ultimate-member' ),
            'description' => __( 'Click checkbox for Customer Code display in view mode on the user Account page.', 'ultimate-member' ),
        );

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


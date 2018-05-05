<?php

namespace Cryptocurrency\Settings;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Cryptocurrency\Request\Mapper;

class Global_Settings {


    public function init() {
        $this->attach_hooks();
    }

    public function attach_hooks() {


        add_action( 'carbon_fields_register_fields', array( $this, 'setup_options' ) );
        add_action( 'after_setup_theme', array( $this, 'cf_boot' ) );


    }

    public function cf_boot() {

        Carbon_Fields::boot();

    }

    public function setup_options() {

        $labels = array(
            'plural_name'   => __( 'Coins', 'cryptocurrency' ),
            'singular_name' => __( 'Coin', 'cryptocurrency' ),
        );


        Container::make( 'theme_options', __( 'Cryptocurrency', 'cryptocurrency' ) )
                 ->set_page_parent( 'options-general.php' )
                 ->add_fields( array(
                     Field::make( 'complex', 'crypto_items', __( 'Coin Purchases', 'cryptocurrency' ) )
                          ->set_layout( 'tabbed-horizontal' )
                          ->setup_labels( $labels )
                          ->add_fields( array(
                              Field::make( 'select', 'coin_type', __( 'Coin Type', 'cryptocurrency' ) )
                                   ->add_options( self::choices() ),
                              Field::make( 'text', 'quantity', __( 'Quantity Purchased', 'cryptocurrency' ) ),
                              Field::make( 'number', 'purchased_price',
                                  __( 'Total Spend in fiat currency', 'cryptocurrency' ) ),
                          ) ),
                     Field::make( 'select', 'crypto_fiat_currency', __( 'Fiat currency', 'cryptocurrency' ) )
                          ->add_options( array(
                              'usd'  => 'USD ($)',
                              'eur' => 'EUR (€)',
                          ) )
                 ) );
    }


    private static function choices() {


        $mapper  = new Mapper();
        $choices = [];
        foreach ( $mapper->get() as $key => $value ) {
            $choices[ $key ] = $value['nice_name'];
        }

        return $choices;

    }

    public static function get_fiat() {
        $fiat_slug = carbon_get_theme_option( 'crypto_fiat_currency' ) ? carbon_get_theme_option( 'crypto_fiat_currency' ) : 'USD';

        return self::get_fiat_data( $fiat_slug );
    }

    private static function get_fiat_data( $fiat = 'usd' ) {
        $return_array = array();
        switch ( strtoupper( $fiat ) ):

            case 'EUR':
                $return_array = array(
                    'symbol_after'  => ' €',
                    'price_field'   => 'price_eur',
                    'add_query_arg' => array( 'convert' => 'eur' ),
                );
                break;
            case 'USD':
            default:
                $return_array = array(
                    'symbol_before' => '$',
                    'price_field'   => 'price_usd',
                );

                break;

        endswitch;

        $defaults = array(
            'symbol_before' => '',
            'symbol_after'  => '',
        );

        $return_array = array_merge( $defaults, $return_array );

        $return_array['symbol_before'] = apply_filters( 'cryptocurrency/currency_symbol_before', $return_array['symbol_before'] );
        $return_array['symbol_after']  = apply_filters( 'cryptocurrency/currency_symbol_after', $return_array['symbol_after'] );

        return $return_array;

    }


}

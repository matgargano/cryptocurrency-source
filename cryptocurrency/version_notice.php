<?php

namespace Cryptocurrency;

class Version_Notice extends Thing {


    public function attach_hooks() {
        add_action( 'admin_notices', array( $this, 'notice' ) );
    }

    public function notice() {

        

            $class   = 'notice notice-error';
            $message = __( sprintf( 'This plugin requires PHP version 5.5 or newer, you currently are using %s',
                phpversion() ), 'cryptocurrency' );

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        
    }


}
<?php




$json_file = file_get_contents(__DIR__ . '/config.json');

$config = json_decode($json_file);


if ( ! $config ) {
    echo 'Bad json' . PHP_EOL . PHP_EOL;
    die();
}




$files                           = $config->files;


$place     = null;
$decrement = false;

if ( in_array( '--major', $_SERVER['argv'] ) || in_array( '-m', $_SERVER['argv'] ) ) {
    $place = 0;
}

if ( in_array( '--minor', $_SERVER['argv'] ) || in_array( '-i', $_SERVER['argv'] ) ) {
    $place = 1;
}

if ( in_array( '--patch', $_SERVER['argv'] ) || in_array( '-p', $_SERVER['argv'] ) ) {
    $place = 2;
}

if ( in_array( '--decrement', $_SERVER['argv'] ) || in_array( '-d', $_SERVER['argv'] ) ) {
    $decrement = true;
}


if ( is_null( $place ) ) {
    echo 'Set either --major --minor or --patch' . PHP_EOL;
    die();
}


foreach ( $files as $file ) {

    $file_name         = __DIR__ . $file->file_name;
    $string_identifier = $file->string_identifier;
    $regex             = str_replace( $config->string_identifier_replace_token, $string_identifier, $file->regex );

    $file = fopen( $file_name, "r" ) or die( "Unable to open main file file" );
    $file_content = fread( $file, filesize( $file_name ) );


    preg_match( sprintf( $regex, $string_identifier ), $file_content, $match );
    $old_version = $match[1];

    $version_array = explode( '.', $old_version );

    if ( $decrement ) {
        $version_array[ $place ] = (int) $version_array[ $place ] - 1;
    } else {
        $version_array[ $place ] = (int) $version_array[ $place ] + 1;
    }

    $new_string_identifier = sprintf( '%s.%s.%s', (int) $version_array[0], (int) $version_array[1],
        (int) $version_array[2] );


    $new_file_contents = preg_replace( sprintf( $regex, $string_identifier ),
        sprintf( "%s $new_string_identifier", $string_identifier ), $file_content );
    file_put_contents( $file_name, $new_file_contents );

    echo sprintf( "Success for %s --- %s is now %s" . PHP_EOL . PHP_EOL, $file_name, $old_version,
        $new_string_identifier );


}








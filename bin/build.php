<?php


$json_file = file_get_contents( __DIR__ . '/config.json' );

$config = json_decode( $json_file );


if ( ! $config ) {
    echo 'Bad json' . PHP_EOL . PHP_EOL;
    die();
}

$new_version = null;

$branch = isset( $config->branch ) ? $config->branch : 'master';

$files = $config->files;


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

    $new_version = $new_version ? $new_version : sprintf( '%s.%s.%s', (int) $version_array[0], (int) $version_array[1],
        (int) $version_array[2] );


    $new_file_contents = preg_replace( sprintf( $regex, $string_identifier ),
        sprintf( "%s $new_version", $string_identifier ), $file_content );
    file_put_contents( $file_name, $new_file_contents );

    echo sprintf( "Success for %s --- %s is now %s" . PHP_EOL . PHP_EOL, $file_name, $old_version,
        $new_version );


}

echo '---- done with file updating ---';

$exclude = $config->exclude;

$exclude[] = '.git';
$exclude[] = '.svn';
$exclude[] = '.build';
$exclude[] = '.idea ';

$exclude = array_unique( $exclude );


$exclude_parameters = array_map( function ( $exclude ) {

    return ' --exclude=' . $exclude . ' ';

}, $exclude );

$exclude_string = implode( ' ', $exclude_parameters );

echo $exclude_string;

shell_exec( 'rm -rf .build' );
shell_exec( 'git clone ' . $config->destination_repo . ' .build' );


shell_exec( sprintf( 'rsync -aP %s ./ ./.build', $exclude_string ) );
shell_exec( 'cd .build' );
shell_exec( 'git add .' );
shell_exec( sprintf( 'git commit -am "updating for %s"', $new_version ) );
shell_exec( sprintf( 'git push origin %s', $branch ) );







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

if ( in_array( '--version', $_SERVER['argv'] ) || in_array( '-v', $_SERVER['argv'] ) ) {
    $new_version = 0;
}


foreach ( (array) $_SERVER['argv'] as $argument ) {
    $version_string = "--version=";


    if ( strpos( $argument, $version_string ) > - 1 ) {

        $new_version = str_replace( $version_string, '', $argument );
    }
}


if ( ! $new_version && is_null( $place ) ) {
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

echo '---- done with file updating ---' . PHP_EOL;

$exclude = $config->exclude;

$exclude[] = '.git';
$exclude[] = '.svn';
$exclude[] = '.buildgit';
$exclude[] = '.idea ';
$exclude[] = '.buildsvn ';

$exclude = array_unique( $exclude );


$exclude_parameters = array_map( function ( $exclude ) {

    return ' --exclude=' . $exclude . ' ';

}, $exclude );

$exclude_string = implode( ' ', $exclude_parameters );

shell_exec( 'rm -rf .buildgit' );
shell_exec( 'rm -rf .buildsvn' );


shell_exec( 'git clone ' . $config->dist_git_repo . ' .buildgit' );


shell_exec( sprintf( 'rsync -aP %s ./ ./.buildgit', $exclude_string ) );
shell_exec( 'cd .buildgit && git add .' );
shell_exec( sprintf( 'cd .buildgit && git commit -am "updating for %s"', $new_version ) );
shell_exec( sprintf( 'cd .buildgit && git push origin %s', $branch ) );
shell_exec( 'rm -rf .buildgit' );
echo 'done with git, starting svn' . PHP_EOL;
shell_exec( sprintf( 'svn co %s .buildsvn', $config->dist_svn_repo ) );
shell_exec('rm -rf .buildsvn/trunk');
shell_exec( sprintf( 'rsync -aP %s ./ ./.buildsvn/trunk', $exclude_string ) );
shell_exec( sprintf( 'rsync -aP %s ./ ./.buildsvn/tags/%s', $exclude_string, $new_version ) );
shell_exec('cd .buildsvn && svn add trunk/*');
shell_exec('cd .buildsvn && svn add tags/*');

shell_exec(sprintf('cd .buildsvn && svn ci -m "updated to %s"', $new_version) );
shell_exec('rm -rf .buildsvn --');
echo 'done with svn' . PHP_EOL;







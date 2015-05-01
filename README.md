# YAK Pro - Php Obfuscator

**YAK Pro** stands for **Y**et **A**nother **K**iller **Pro**duct.

This tool makes use of [PHP-Parser](https://github.com/nikic/PHP-Parser) for parsing php.  
You just have to download the zip archive and uncompress it under the PHP-Parser subdirectory.  
or make a git clone ...

The yakpro-po.cnf self-documented file contains many configuration options!  
Take a look at it!

Prerequisites:  php 5.3 or higher, [PHP-Parser](https://github.com/nikic/PHP-Parser).

Published under the MIT License

## Setup:
    put the downloaded files where you want...
        chmod a+x yakpro-po.php     would be helpfull...

    It would be a good idea to create a symbolic link named yakpro-po in /usr/local/bin,
    pointing to the yakpro-po.php file.

    put the PHP-Parser directory at the same level that the yakpro-po.php file.

    modify a copy of the yakpro-po.cnf to fit your needs...
    read the "Configuration file loading algorithm" section of this document
    to choose the best location suiting your needs!

    that's it! you're done!

####

## Usage:

`yakpro-po`  
Obfuscates according configuration file!  
(See configuration file loading algorithm)

`yakpro-po source_filename`  
Obfuscates code to stdout  

`yakpro-po source_filename -o target_filename`  
Obfuscates code to target_filename  

`yakpro-po source_directory -o target_directory`  
Recursivly obfuscates code to target_directory/yakpro-po (creates it if not already exists).

`yakpro-po --config-file config_file_path`  
According to config_file_path.

`yakpro-po --clean`  
Requires target_directory to be present in your config file!  
Recursivly removes target_directory/yakpro-po


## Configuration file loading algorithm:
(the first found is used)

    --config-file argument value
    YAKPRO_PO_CONFIG_FILE environnement variable value if exists and not empty.

    filename selection:
           YAKPRO_PO_CONFIG_FILENAME environnement variable value if exists and not empty,
           yakpro-po.cnf otherwise.

     file is then searched in the following directories:
            YAKPRO_PO_CONFIG_DIRECTORY  environnement variable value if exists and not empty.
            current_working_directory
            current_working_directory/config
            home_directory
            home_directory/config
            /usr/local/YAK/yakpro-po
            source_code_directory/default_conf_filename

      if no config file is found, default values are used.

      You can find the default config file as an example in the yakpro-po.cnf file of the
      repository.
      Do not modify it directly because it will be overwritten at each update!
      Use your own yakpro-po.cnf file (for example in the root directory of your project)

      When working on directories,
      context is saved in order to reuse the same obfuscation translation table.
      When you make some changes in one or several source files,
      yakpro-po uses timestamps to only reobfuscate files that where changed
      since last obfuscation.
      This can saves you a lot of time.

      caveats: does not delete files that are no more present...
               use --clean  command line parameter, and then re-obfuscate all!

## Other command line options:
    --silent                do not display Information level messages.
    --debug                 (internal debugging use) displays the syntax tree.
    -s or
    --no-strip-indentation  force output not to be on a single line
    --scramble-mode identifier (or hexa or numeric) force scramble mode
    -h or
    --help                  displays help.

####

## YOU MUST BE AWARE OF:
    If your obfuscated software makes use of external libraries
    that you do not obfuscate along with your software:

    if the library consist of functions:
            set the $conf->obfuscate_function_name to false in your yakpro-po.cnf config file,
            or declare all the functions names you are using in $conf->t_ignore_functions
            example : $conf->t_ignore_functions = array('my_func1','my_func2');

    if the library consist of classes :
            set the $conf->obfuscate_class_name,
                    $conf->obfuscate_property_name,
                    $conf->obfuscate_method_name
            to false in your yakpro-po.cnf config file...
            ... or declare all the classes, properties, methods names you are using in
                    $conf->t_ignore_classes,
                    $conf->t_ignore_properties,
                    $conf->t_ignore_methods.

    This is also true for PDO::FETCH_OBJ that retrieves properties from external source
    (i.e. database columns).

## Hints for preparing your Software to be run obfuscated

    At first you can test obfuscating only variable names...


    If you obfuscate functions, do not use:
        if (!function_exists('my_function'))
        {
            function my_function () { ... }
        }
    Instead use:
        require_once "my_functions.php";
    and put your functions in the my_function.php file.
    If you do not do this way, the function name my_function will be obfuscated,
    but not the 'my_function' string within the function_exists call...


    If you obfuscate functions, do not use indirect function calls like
        $my_var = 'my_function';
        $my_var();
    or put all the function names you call indirectly in the $conf->t_ignore_functions array!


    Do not use indirect variable names!
        $$my_var = something;
    or put all the variable names you use indirectly in the $conf->t_ignore_variables array!


    Do not use PDO::FETCH_OBJ  but use PDO::FETCH_ASSOC instead!
    or disable properties obfuscation in the config file.


    If you use the define function for defining constants, the only allowed form is when
    define function has exactly 2 arguments, and the first one is a litteral string!
    You MUST disable constants obfuscation in the config file,
    if you use other forms of the define function!
    There is no probleme with the const MY_CONT = something form!



## TODO
    obfuscation of namespaces, interfaces, traits is not yet supported!

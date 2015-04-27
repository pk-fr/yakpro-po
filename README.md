# YAK Pro - Php Obfuscator

YAK Pro stands for Yet Another Killer Product.

This tool makes use of [PHP-Parser](https://github.com/nikic/PHP-Parser) for parsing php.
You just have to download the zip archive and uncompress it under the PHP-Parser subdirectory.

coming soon....

## Usage:
`yakpro-po`  
Obfuscates according configuration file!  
(see Configuration file loading algorythm)

`yakpro-po source_filename`  
Obfuscates code to stdout

`yakpro-po source_filename -o target_filename`  
Obfuscates code to target_filename

`yakpro-po source_directory -o target_directory`  
Recursivly obfuscates code to target_directory/yakpro-po (creates it if not already exists).

`yakpro-po --config-file config_file_path`  
According to config_file_path.

`yakpro --clean`  
Requires target_directory to be present in your config file!  
Recursivly removes target_directory/yakpro-po


## Configuration file loading algorythm:

(the first found is used)  

    --config-file argument value
    YAKPRO_PO_CONFIG_FILE environnement variable value if exist and not empty.
    
    filename selection:
 	       YAKPRO_PO_CONFIG_FILENAME environnement variable value if exist and not empty.
	       yakpro-po.cnf
	 
	 file is then searched in the following directories:
		    YAKPRO_PO_CONFIG_DIRECTORY  environnement variable value if exist and not empty
		    current_working_directory
		    current_working_directory/config
		    home_directory
		    home_directory/config
		    /usr/local/YAK/yakpro-po
		    source_code_directory/default_conf_filename

	  if no config file is found, default values are used.

 	  You can find the default config file as an example in the yakpro-po.cnf file of the repository.
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

   
## YOU MUST BE AWARE OF:
	If your obfuscated software makes use of external libraries
	that you do not obfuscate along with your software:
	
	if the library consist of functions:
			set the $conf->obfuscate_function_name to false in your yakpro-po.cnf config file,
			or declare all the functions names you are using in $conf->t_ignore_functions
			
	if the library consist of classes :
			set the $conf->obfuscate_class_name, $conf->obfuscate_property_name, $conf->obfuscate_method_name
			to false in your yakpro-po.cnf config file, or declare all the classes, properties, methods names
			you are using in		t_ignore_classes,t_ignore_properties,t_ignore_methods.
			
	This is also true for PDO::FETCH_OBJ that retrieves properties from external source (i.e. database columns).
	
	

# YAK Pro - Php Obfuscator

**YAK Pro** vient de  **Y**et **A**nother **K**iller **Pro**duct.

Ce programme utilise [PHP-Parser](https://github.com/nikic/PHP-Parser) pour analyser le php.  
Télécharger l'archive zip et décompressez la dans le sous-répertoire PHP-Parser .
ou alors utilisez git clone.

Le fichier de configuration yakpro-po.cnf est auto-documenté et contient de
nombreuses options de configuration !  
Un petit coup d'oeil vaut le détour.

Pré-requis:  php 5.3 ou supérieur, [PHP-Parser](https://github.com/nikic/PHP-Parser).

Publié sous les termes de la licence MIT.

## Installation :
    Placez l'arborescence téléchargée (ou faites un git clone ...)  ou vous voulez ...

        chmod a+x yakpro-po.php     vous aidera grandement!

    Créer un lien symbolique yakpro-po dans /usr/local/bin pointant sur le fichier yakpro-po.php
    serait une bonne idée !

    Placez le répertoire PHP-Parser (obtenu par téléchargement ou git clone)
    au même niveau que le fichier yakpro-po.php.

    Créez une copie du fichier yakpro-po.cnf
    Lire la section "Algorithme de chargement du fichier de configuration"
    pour savoir où le placer, et modifiez le selon vos besoins.

    C'est tout! vous n'avez plus qu'à tester !

####

## Utilisation :

`yakpro-po`  
L'obfuscation se fait selon le fichier de configuration!  
Veuillez consulter la section "Algorithme de chargement du fichier de configuration" de  
cette documentation.

`yakpro-po fichier_source`  
L'obfuscation est dirigée vers la sortie standard (stdout)

`yakpro-po fichier_source -o fichier_cible`  
fichier_cible contiendra le résultat de l'obfuscation.

`yakpro-po répertoire_source -o répertoire_cible`  
Exécutera une obfuscation récursive du code vers le répertoire répertoire_cible/yakpro-po  
(le répertoire cible est automatiquement créé si il n'existe pas déjà !)

`yakpro-po --config-file chemin_du_fichier_de_config`  
Permet de spécifier un fichier de config.

`yakpro-po --clean`  
Le répertoire cible doit être renseigné dans le fichier de configuration!  
Supprime récursivement le répertoire répertoire_cible/yakpro-po


## Algorithme de chargement du fichier de configuration :
(Le premier trouvé sera utilisé)

    --config-file répertoire_cible
    La valeur de la variable d'environnement YAKPRO_PO_CONFIG_FILE
    si elle existe et est non vide.

    détermination du nom de fichier :
           La valeur de la variable d'environnement YAKPRO_PO_CONFIG_FILENAME
           si elle existe et est non vide,
           yakpro-po.cnf sinon.

    Le fichier est ensuite recherché dans les répertoires suivants :
            La valeur de la variable d'environnement YAKPRO_PO_CONFIG_DIRECTORY
                                                si elle existe et est non vide.
            répertoire_de_travail_courant
            répertoire_de_travail_courant/config
            home_directory
            home_directory/config
            /usr/local/YAK/yakpro-po
            source_code_directory/default_conf_filename

      Si aucun fichier de configuration n'est trouvé, les valeurs par défaut sont utilisées.

      Le fichier de configuration par défaut est le fichier yakpro-po.cnf situé à la racine du dépot.
      Ne modifiez pas directement ce fichier, car il sera ré-écrasé à chaque mise à jour !
      Utilisez votre propre fichier yakpro-po.cnf ( par exemple à la racine de votre projet )

      Lorsque vous travaillez sur des répertoires,
      le contexte est sauvegardé afin de ré-utiliser la même table de traduction.
      Lorsque vous modifiez un ou plusieurs fichier, yapro-po utilise l'horodatage des fichiers
      pour ne ré-obfusquer que les fichiers modifiés depuis l'obfuscation précédente.
      Celà vous permettra de gagner un temps précieux !

      Attention: les fichiers qui ne sont plus présents dans le source ne sont pas retirés de la cible !...
                 utilisez l'option  --clean  et ré-obfusquez l'ensemble du projet.

## Autre options de la ligne de commande :
    --silent                   ommet l'affichage des messages de niveau Information.
    --debug                    (utilisation interne pour le debug) affichage de l'arbre syntaxique.
    -s ou
    --no-strip-indentation     force la sortie à ne pas être sur une seule ligne.
    --scramble-mode identifier (ou hexa ou numeric) force le scramble mode.
    -h ou
    --help                     affiche l'aide.

####

## VOUS DEVEZ ETRE CONSCIENT QUE:
    Si votre projet utilise des bibliothèques externes,
    que vous n'obfusquez pas en même temps que votre projet :

    Si la bibliothèque est faite de fonctions :
            renseignez $conf->obfuscate_function_name à false dans votre fichier de configuration,
            ou alors déclarez tous les noms de fonction que vous utilisez dans
            $conf->t_ignore_functions
            Par example : $conf->t_ignore_functions = array('my_func1','my_func2');

    Si la bibliothèque est faite de classes :
            renseignez  $conf->obfuscate_class_name,
                        $conf->obfuscate_property_name,
                        $conf->obfuscate_method_name
            à false dans votre fichier de configuration yakpro-po.cnf ...
            ... ou alors renseignez tous les noms de classes, attributs, et methodes
                que vous utilisez dans
                    $conf->t_ignore_classes,
                    $conf->t_ignore_properties,
                    $conf->t_ignore_methods.

    Celà vaut aussi pour PDO::FETCH_OBJ qui récupère les noms d'attributs à partir de
    sources externes (i.e. colonnes de bases de données).

## Conseils pour préparer votre Logiciel à s'exécuter correctement lorsqu'il est obfusqué

    Commencez par tester en n'obfusquant que les variables.


    Si vous obfusquez des fonctions, n'utilisez pas :
        if (!function_exists('ma_function'))
        {
            function ma_function () { ... }
        }
    Utilisez à la place :
        require_once "mes_fonctions.php";
    et mettez vos fonctions dans le fichier mes_fonctions.php.
    Si non, le nom de fonction ma_fonction sera obfusquée, mais pas la chaine 'ma_fonction'
    testée par l'appel à function_exists ...


    Si vous obfusquez des fonctions, n'utilisez pas d'appels indirects tels que:
        $ma_var = 'ma_function';
        $ma_var();
    ou alors, renseignez tous les noms de fonctions que vous appellez de façon indirecte
    dans le tableau $conf->t_ignore_functions !


    N'utilisez pas les variables indirectes.
        $$ma_var = qqe_chose;
    ou alors, renseignez tous les noms de variables que vous utilisez de façon indirecte
    dans le tableau $conf->t_ignore_variables !


    N'utilisez pas PDO::FETCH_OBJ  mais utilisez PDO::FETCH_ASSOC à la place !
    ou alors désactivez l'obfuscation des attributs dans le fichier de configuration.



## TODO
    L'obfuscation des  namespaces, interfaces, traits n'est pas encore implémentée!

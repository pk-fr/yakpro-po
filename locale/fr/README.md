[This page in English](https://github.com/pk-fr/yakpro-po)

# YAK Pro - Php Obfuscator

**YAK Pro** vient de  **Y**et **A**nother **K**iller **Pro**duct.

Gratuit, Open Source, Publié sous les termes de la licence MIT.  

Ce programme utilise [PHP-Parser 4.x](https://github.com/nikic/PHP-Parser/tree/4.x/) pour analyser le php.
[PHP-Parser 4.x](https://github.com/nikic/PHP-Parser/tree/4.x/) est une remarquable bibliothèque développée par [nikic](https://github.com/nikic).

Télécharger l'archive zip et décompressez la dans le sous-répertoire PHP-Parser .
ou alors utilisez git clone.

### Attention :  
    yakpro-po 2.x utilise PhpParser 4.x   
    il fonctionne avec php >= 7.0, et obfusque le code pour php 5.2 à php 8.1
    
    Si vous utilisez php 5.3 ou supérieur,  
    Veuillez utiliser [yakpro-po 1.x](https://github.com/pk-fr/yakpro-po/tree/1.x) (non supporté) qui fonctionne avec la branche 1.x de PhpParser.   




Le fichier de configuration yakpro-po.cnf est auto-documenté et contient de
nombreuses options de configuration !  
Un petit coup d'oeil vaut le détour.  

Démo : [yakpro-po demo](https://www.php-obfuscator.com/?demo).

Pré-requis:  php 7.0 ou supérieur, [PHP-Parser 4.x](https://github.com/nikic/PHP-Parser/tree/4.x/).

Remarque : Cet outil à été conçu dans le but d'obfusquer des sources en php pur.  
Il n'a pas été conçu pour être utilisé avec du html avec des bouts de code php à l'intérieur.  
(utilisation à vos risques et périls)  
Par contre, vous pouvez intégrer du html dans votre php en utilisant la syntaxe echo <<<END ... END;



## Qu'est-ce qu'un Obfuscateur Php ?

Lorsque vous désirez distribuer un projet écrit en php, comme php est un langage interprété,
vous distribuez aussi tous les sources de votre logiciel !

Il se peut que vous ne désiriez pas, quelle qu'en soit la raison, que d'autres personnes comprennent le
fonctionnement, modifient ou bien adaptent votre logiciel.

Comme votre programme doit pouvoir être compris par **l'interprète php**, mais doit rester
incompréhensible (ou bien très difficilement compréhensible) par les personnes humaines,
l'obfuscation est un très bon moyen de parvenir à vos fins.

### Principales fonctionnalités de YAK Pro - Php Obfuscator :

- Supprime tous les commentaires, les indentations et génère un programme sur une seule ligne.
- Obfusque les  instructions **if, else, elseif, for, while, do while** en les remplaçant par des instructions **if goto**.
- Obfusque les chaines de caractères.
- Génère des noms aléatoires pour :
  - Les Variables, les Fonctions, les Constantes.
  - Les Classes, les Interfaces, les Traits.
  - Les Attributs, les Méthodes.
  - Les Espaces de Noms.
  - Les étiquettes.
- Mélange les instructions.

- Obfusque récursivement le répertoire d'un projet.
- Un mécanisme de type Makefile, basé sur l'horodatage des fichiers, permet de ne re-obfusquer que les fichiers
ayant été modifiés depuis la dernière obfuscation.
- De nombreuses options de configuration vous permettent d'avoir un **contrôle total** sur ce qui est
obfusqué dans votre projet !


### Pourquoi un Obfuscateur php de plus ?
J'ai commencé par tester quelques outils d'obfuscation php, mais je n'en ai trouvé aucun qui répondait
à tous mes besoins.  
Je voulais un outil sous la forme d'une **simple** ligne de commande, basé sur un fichier de configuration
**personnalisable à l'extrème**, qui :
- Serait rapide, et ne re-obfusquerait que les fichiers ayant été modifiés depuis la dernière obfuscation.
- Permettrait de ne pas obfusquer certains fichiers et/ou répertoires.
- Permettrait de ne pas inclure dans le résultat de l'obfuscation, certains fichiers et/ou répertoires
qui existent dans le source du projet.
- Accepterait des listes de noms ou de préfixes de noms à ne pas obfusquer.

J'ai donc commencé à écrire cet outil.  
La version 1.0 a été écrite en quelques jours...   


## Installation :
    Note: cette procédure est aussi valide pour Windows 10 Anniversary avec bash installé...  
    1. Pré-requis : commande git installée, ainsi que php-cli (command line interface). 
       sous ubuntu : (adaptez selon votre distribution) 
       # apt install git 
       # apt install php-cli
       N'oubliez pas d'installer tous les modules php dont vous vous servez dans votre logiciel :
       par exemple: apt install php-mysql  si vous utilisez mysql... 

    2. Placez-vous dans le répertoire ou vous voulez installer yakpro-po (par exemple dans /usr/local ) : 
       # cd /usr/local 
    3. Puis récupérez à partir de GitHub : 
       # git clone https://github.com/pk-fr/yakpro-po.git 
    4. Placez-vous dans le répertoire de yakpro-po : 
       # cd yakpro-po 
    5. Puis récupérez à partir de GitHub : 
       # git clone https://github.com/nikic/PHP-Parser.git --branch 4.x
    6. Verifiez que yakpro-po.php possède bien les droits d'exécution, sinon :
                                                    # chmod a+x yakpro-po.php 
    7. Créer un lien symbolique dans /usr/local/bin 
       # cd /usr/local/bin 
       # ln -s /usr/local/yakpro-po/yakpro-po.php yakpro-po 
    8. Vous pouvez maintenant exécuter yakpro-po 
       # yakpro-po --help 
       # yakpro-po test.php 

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

    --config-file chemin_du_fichier_de_config
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
( modifient les options du fichier de configuration )

    --silent                            ommettre l'affichage des messages de niveau Information.
    --debug                             (utilisation interne pour le debug) afficher l'arbre syntaxique.

    -s or
    --no-strip-indentation              génèrer le code sur plusieurs lignes indentées
    --strip-indentation                 génèrer tout sur une seule ligne

    --no-shuffle-statements             ne pas mélanger les instructions
    --shuffle-statements                       mélanger les instructions

    --no-obfuscate-string-literal       ne pas obfusquer les chaines de caractères
    --obfuscate-string-literal                 obfusquer les chaines de caractères

    --no-obfuscate-loop-statement       ne pas obfusquer les boucles
    --obfuscate-loop-statement                 obfusquer les boucles

    --no-obfuscate-if-statement         ne pas obfusquer les "if"
    --obfuscate-if-statement                   obfusquer les "if"

    --no-obfuscate-constant-name        ne pas obfusquer les noms de constantes
    --obfuscate-constant-name                  obfusquer les noms de constantes

    --no-obfuscate-variable-name        ne pas obfusquer les noms de variables
    --obfuscate-variable-name                  obfusquer les noms de variables

    --no-obfuscate-function-name        ne pas obfusquer les noms de functions
    --obfuscate-function-name                  obfusquer les noms de functions

    --no-obfuscate-class_constant-name  ne pas obfusquer les noms de constantes de classes
    --obfuscate-class_constant-name            obfusquer les noms de constantes de classes

    --no-obfuscate-class-name           ne pas obfusquer les noms de classes
    --obfuscate-class-name                     obfusquer les noms de classes

    --no-obfuscate-interface-name       ne pas obfusquer les noms d'interfaces
    --obfuscate-interface-name                 obfusquer les noms d'interfaces

    --no-obfuscate-trait-name           ne pas obfusquer les noms de traits
    --obfuscate-trait-name                     obfusquer les noms de traits

    --no-obfuscate-property-name        ne pas obfusquer les noms d'attributs
    --obfuscate-property-name                  obfusquer les noms d'attributs

    --no-obfuscate-method-name          ne pas obfusquer les noms de méthodes
    --obfuscate-method-name                    obfusquer les noms de méthodes

    --no-obfuscate-namespace-name       ne pas obfusquer les noms de namespaces
    --obfuscate-namespace-name                 obfusquer les noms de namespaces

    --no-obfuscate-label-name           ne pas obfusquer les étiquettes
    --obfuscate-label-name                     obfusquer les étiquettes

    --scramble-mode     identifier|hexa|numeric         forcer le scramble mode
    --scramble-length   longueur ( min=2; max = 16 pour scramble_mode=identifier,
                                          max = 32 pour scramble_mode = hexa ou numeric)

    --whatis scrambled_name             retrouve le nom d'origine à partir du contexte d'obfuscation.
                                        (utile pour debugger votre code lorsque vous délivrez du code
                                        obfusqué, et que vous avez gardé le même contexte d'obfuscation).
                                        Conseil : n'utilisez pas le symbole $, ou faites le précéder du 
                                        caractère \ car $ est interprété par le shell.

    -h ou
    --help                              afficher l'aide.

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

    Cela vaut aussi pour PDO::FETCH_OBJ qui récupère les noms d'attributs à partir de
    sources externes (i.e. colonnes de bases de données).

## Conseils pour préparer votre Logiciel à s'exécuter correctement lorsqu'il est obfusqué

    Commencez par tester en n'obfusquant que les variables.


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


    Si vous utilisez la fonction define pour définir des constantes, la seule forme autorisée
    est lorsque la fonction define est utilisée avec exactement 2 arguments,
    et que le premier argument est une chaine de caractères !
    Vous DEVEZ désactiver l'obfuscation des constantes dans le fichier de configuration
    si vous utilisez la fonction define autrement !
    Il n'y a aucune restriction si vous utilisez la construction :
        const MA_CONSTANTE = quelque_chose;




## Considérations sur les Performances

    Excepté pour l'option d'obfuscation concernant le mélange des instructions,  
    la vitesse du programme obfusqué est équivalent à celle du programme original.

    $conf->shuffle_stmts    est positionné à  true par défaut.  

    Si vous rencontrez des problèmes de performance, vous pouvez soit désactiver l'option,  
    soit paramétrer finement les options de mélange...  

    Plus la taille du chunk est petite, meilleure sera l'obfuscation,  
    ... et plus le coût sur les performance de votre logiciel sera élevé...
    
    (lors de mes propres tests, avec l'obfuscation maximale, la dégradation est de l'ordre de 13%)
    
    Vous avez tous les paramètres nécessaires à votre disposition pour parvenir à votre meilleur compromis.



## Problèmes connus

[segmentation fault](https://github.com/php/php-src/issues/8193)

    opcache plante sur Ubuntu 21.10 - php 8.0.8 (segfault) que se soit en mode cli ou à travers apache2
    lorsque l'option shuffle-statements est activée sur des gros fichiers.
    
    fonctionne parfaitement sur des versions plus récentes de php ( 8.0.16 et 8.1 )



[sedimentation-fault](https://github.com/sedimentation-fault) a rapporté sur le problème [#75](https://github.com/pk-fr/yakpro-po/issues/75) que un segmentation fault pouvait survenir dans le 'garbage collector' de php lors de l'obfuscation de beaucoup de gros fichiers dans un projet :

    Trying to obfuscate ~5000 PHP files of ~1000 lines each, yakpro-po stopped after processing ~1600 files 
    with a simple (and frustrating) Segmentation fault

    Workaround:
    
    There is a stack overflow in garbage collector. The solution is to increase limit for stack.
    To see your current limit, type

    ulimit -s

    I had 8192 - for a task of this size obviously totally undersized...
    Change this to something more appropriate, say

    ulimit -s 102400

    and retry - the segmentation fault is gone! :-)
    
    

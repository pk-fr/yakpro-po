<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

$t_pre_defined_classes          = array_flip(array_map('strtolower', get_declared_classes()));
$t_pre_defined_interfaces       = array_flip(array_map('strtolower', get_declared_interfaces()));
$t_pre_defined_traits           = function_exists('get_declared_traits') ? array_flip(array_map('strtolower', get_declared_traits())) : array(); 
$t_pre_defined_classes          = array_merge($t_pre_defined_classes,$t_pre_defined_interfaces,$t_pre_defined_traits);

$t_pre_defined_class_methods    = array();  $t_pre_defined_class_methods_by_class       = array();
$t_pre_defined_class_properties = array();  $t_pre_defined_class_properties_by_class    = array();
$t_pre_defined_class_constants  = array();  $t_pre_defined_class_constants_by_class     = array();

foreach($t_pre_defined_classes as $pre_defined_class_name => $dummy)
{
    $t = array_flip(array_map('strtolower', get_class_methods($pre_defined_class_name)));
    if (count($t)) $t_pre_defined_class_methods_by_class[$pre_defined_class_name] = $t;
    $t_pre_defined_class_methods = array_merge($t_pre_defined_class_methods, $t);
    
    $t = get_class_vars($pre_defined_class_name);
    if (count($t)) $t_pre_defined_class_properties_by_class[$pre_defined_class_name] = $t;
    $t_pre_defined_class_properties = array_merge($t_pre_defined_class_properties, $t);

    
    $r = new ReflectionClass($pre_defined_class_name);
    $t = $r->getConstants();
    if (count($t)) $t_pre_defined_class_constants_by_class[$pre_defined_class_name] = $t;
    $t_pre_defined_class_constants = array_merge($t_pre_defined_class_constants, $t);
}

?>

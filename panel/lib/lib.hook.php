<?php

$___HOOKS = array();
$___HOOK_GLOBALS = array();

function hooks_add ( $instance , $function_name ) {
	global $___HOOKS;
	if ( !isset ( $___HOOKS[ $instance ] ) ) {
		$___HOOKS[ $instance ] = array();
	}
	$___HOOKS[ $instance ][] = $function_name;

}

function hooks_globals ( $dizi = array() ) {
	global $___HOOK_GLOBALS;
	$___HOOK_GLOBALS = $dizi;

}

function hooks_run ( $instance ) {
	global $___HOOKS , $___HOOK_GLOBALS;
	foreach ( $___HOOK_GLOBALS as $key ) {
		global $$key;
	}
	mysql_query ( sprintf ( 'insert into aaa_hooks (tx) values("%1$s")' , guvenlik ( $instance ) ) );
	if ( isset ( $___HOOKS[ $instance ] ) ) {
		foreach ( $___HOOKS[ $instance ] as $func_name ) {
			if ( function_exists ( $func_name ) ) {
				$args = func_get_args ();
				unset ( $args[ 0 ] );
				call_user_func_array ( $func_name , $args );
			} else {
				throw new Exception ( 'hooks_run can not reach / find function `' . $func_name . '`' );
			}
		}
	}

}

<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		5 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

$options = array();

if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_staff' )->first() )
{
	$options[] = new \IPS\Helpers\Form\Custom( '40000_nexus_staff', null, FALSE, array( 'getHtml' => function( $element ) {
		return "";
	} ), function( $val ) {}, NULL, NULL, '40000_nexus_staff' );
}

if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages', "p_module<>'' AND p_module IS NOT NULL" )->first() )
{
	$options[] = new \IPS\Helpers\Form\Custom( '40000_nexus_modules', null, FALSE, array( 'getHtml' => function( $element ) {
		return "";
	} ), function( $val ) {}, NULL, NULL, '40000_nexus_modules' );
}
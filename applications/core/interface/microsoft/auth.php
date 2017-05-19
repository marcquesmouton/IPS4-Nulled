<?php
/**
 * @brief		Microsoft Account Login Handler Redirect URI Handler
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		20 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/microsoft/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
if ( \IPS\Request::i()->state == 'ucp' )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service=Microsoft&loginProcess=microsoft&code=" . urlencode( \IPS\Request::i()->code ), 'front', 'settings_Microsoft' ) );
}
else
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=login&loginProcess=live&code=" . urlencode( \IPS\Request::i()->code ), \IPS\Request::i()->state, 'login', NULL, \IPS\Settings::i()->logins_over_https ) );
}
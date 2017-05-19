<?php
/**
 * @brief		LinkedIn Account Login Handler Redirect URI Handler
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		20 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/linkedin/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

if ( isset( \IPS\Request::i()->error ) and \IPS\Request::i()->error )
{
	\IPS\Dispatcher\Front::i();
	\IPS\Output::i()->error( \IPS\Request::i()->error_description, '4C271/1', 403 );
}

if ( \IPS\Request::i()->state == 'ucp' )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service=Linkedin&loginProcess=linkedin&code=" . urlencode( \IPS\Request::i()->code ), 'front', 'settings_LinkedIn' ) );
}
else
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=login&loginProcess=linkedin&code=" . urlencode( \IPS\Request::i()->code ), \IPS\Request::i()->state, 'login', NULL, \IPS\Settings::i()->logins_over_https ) );
}
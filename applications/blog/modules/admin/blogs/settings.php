<?php
/**
 * @brief		Blog Settings
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		03 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\modules\admin\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'blog_settings_manage' );
		parent::execute();
	}

	/**
	 * Manage Blog Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form;
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'blog_enable_rating', \IPS\Settings::i()->blog_enable_rating ) );
		
		$form->addHeader('blog_settings_rss');
		$form->add( new \IPS\Helpers\Form\YesNo( 'blog_allow_rssimport', \IPS\Settings::i()->blog_allow_rssimport ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'blog_allow_rss', \IPS\Settings::i()->blog_allow_rss ) );
		
		if ( $form->values() )
		{
			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__blog_settings' );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('settings');
		\IPS\Output::i()->output = $form;
	}
}
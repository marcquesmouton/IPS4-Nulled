<?php
/**
 * @brief		licensekey
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		17 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * licensekey
 */
class _licensekey extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief Data about the license key from the store
	 */
	protected $licenseData = array();

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'licensekey_manage' );
		parent::execute();
	}

	/**
	 * License key overview screen
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Get license info.  If license info is empty, refresh it. */
		$licenseData = \IPS\IPS::licenseKey();
		
		/* If no license key has been supplied yet just show the form */
		if( !isset( $licenseData['key'] ) )
		{
			return $this->settings();
		}
		
		/* Output */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('license_settings');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'licensekey', 'core' )->overview( $licenseData );
		\IPS\Output::i()->sidebar['actions'] = array(
			'refresh'	=> array(
				'icon'	=> 'refresh',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=licensekey&do=refresh' ),
				'title'	=> 'license_refresh',
			),
			'remove'	=> array(
				'icon'	=> 'pencil',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=licensekey&do=settings' ),
				'title'	=> 'license_change',
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('license_change') )
			),
		);
	}

	/**
	 * Refresh the license key data stored locally
	 *
	 * @return	void
	 */
	protected function refresh()
	{
		/* Fetch the license key data and update our local storage */
		unset( \IPS\Data\Store::i()->license_data );

		/* Return the overview screen afterwards */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=licensekey' ), 'license_key_refreshed' );
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		//IV
		$l_key = \IPS\Http\Url::iv( 'license.php' )->setQueryString( 'get', 'lkey' )->request()->get();
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'ipb_reg_number', $l_key, TRUE, array(), function( $val ){
			\IPS\IPS::checkLicenseKey( $val, \IPS\Settings::i()->base_url );
		} ) );

		if ( $values = $form->values() )
		{
			$values['ipb_reg_number'] = trim( $values['ipb_reg_number'] );

			if ( mb_substr( $values['ipb_reg_number'], -12 ) === '-TESTINSTALL' )
			{
				$values['ipb_reg_number'] = mb_substr( $values['ipb_reg_number'], 0, -12 );
			}
			
			$form->saveAsSettings( $values );
			\IPS\Session::i()->log( 'acplogs__license_settings' );

			/* Refresh the locally stored license info */
			unset( \IPS\Data\Store::i()->license_data );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=licensekey' ), 'saved' );
		}

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('license_settings');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_licensekey', $form );
	}
}
<?php
/**
 * @brief		Upgrader: License
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		25 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: License
 */
class _license extends \IPS\Dispatcher\Controller
{

	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Check license key */
		if( \IPS\Db::i()->checkForTable( 'core_store' ) )
		{
			$licenseData = \IPS\IPS::licenseKey();
		}
		else
		{
			$licenseData	= NULL;

			try
			{
				$license	= \IPS\Db::i()->select( '*', 'cache_store', array( 'cs_key=?', 'licenseData' ) )->first();
				$licenseData	= unserialize( $license['cs_value'] );
			}
			catch( \Exception $e ){}
		}

		if( isset( $licenseData['key'] ) AND !isset( $licenseData['expires'] ) )
		{
			$licenseData	= $this->getLicenseData();
		}

		if ( !$licenseData or !isset( $licenseData['expires'] ) )
		{
			//IV
			$l_key = \IPS\Http\Url::iv( 'license.php' )->setQueryString( 'get', 'lkey' )->request()->get();
			$response	= NULL;
			$active		= NULL;
			$form		= new \IPS\Helpers\Form( 'licensekey', 'continue' );
			$form->add( new \IPS\Helpers\Form\Text( 'ipb_reg_number', $l_key, TRUE, array(), function( $val ){
				\IPS\IPS::checkLicenseKey( $val, \IPS\Settings::i()->base_url );
			} ) );

			if( $values = $form->values() )
			{
				$values['ipb_reg_number'] = trim( $values['ipb_reg_number'] );
				
				if ( mb_substr( $values['ipb_reg_number'], -12 ) === '-TESTINSTALL' )
				{
					$values['ipb_reg_number'] = mb_substr( $values['ipb_reg_number'], 0, -12 );
				}
	
				/* Save */
				$form->saveAsSettings( $values );

				/* Refresh the locally stored license info */
				if( \IPS\Db::i()->checkForTable( 'core_store' ) )
				{
					unset( \IPS\Data\Store::i()->license_data );
					$licenseData = \IPS\IPS::licenseKey();
				}
				else
				{
					/* Call the main server */
					$licenseData	= $this->getLicenseData();
				}

				/* Reset some vars now */
				$form	= NULL;
				$active = isset( $licenseData['expires'] ) ? strtotime( $licenseData['expires'] ) > time() : NULL;
			}
		}
		else
		{
			$active = isset( $licenseData['expires'] ) ? strtotime( $licenseData['expires'] ) > time() : NULL;
		}
		
		if( $active )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=applications" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('license');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->license( $form, $active );
	}

	/**
	 * Retrieve license data from license server
	 *
	 * @return mixed
	 */
	protected function getLicenseData()
	{
		/* Call the main server */
		try
		{
			$response = \IPS\Http\Url::iv( 'license.php' )->request()->post( array(
				'url'	=> \IPS\Settings::i()->base_url,
				'lkey'	=> \IPS\Settings::i()->ipb_reg_number
			) );
			if ( $response->httpResponseCode == 404 )
			{
				$licenseData	= NULL;
			}
			else
			{
				$licenseData	= $response->decodeJson();
			}

			\IPS\Db::i()->replace( 'cache_store', array( 'cs_key' => 'licenseData', 'cs_array' => 1, 'cs_value' => serialize( $licenseData ) ) );
		}
		catch ( \Exception $e )
		{
			$licenseData	= NULL;
		}

		return $licenseData;
	}
}
<?php
/**
 * @brief		Custom Fields
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		08 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Fields
 */
class _info extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X242/1', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_info') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_info');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}
	
	/**
	 * Edit Info
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form;
		foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
		{
			$column = $field->column;
			$form->add( $field->buildHelper( \IPS\nexus\Customer::loggedIn()->$column ) );
		}
		
		if ( $values = $form->values( TRUE ) )
		{
			$changes = array();
			foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
			{
				$column = $field->column;
				if ( \IPS\nexus\Customer::loggedIn()->$column != $values["nexus_ccfield_{$field->id}"] )
				{
					$changes[] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $values["nexus_ccfield_{$field->id}"] ), 'old' => \IPS\nexus\Customer::loggedIn()->$column );
					\IPS\nexus\Customer::loggedIn()->$column = $values["nexus_ccfield_{$field->id}"];
				}
				
				if ( $field->type === 'Editor' )
				{
					$field->claimAttachments( \IPS\nexus\Customer::loggedIn()->member_id );
				}
			}
			if ( !empty( $changes ) )
			{
				\IPS\nexus\Customer::loggedIn()->log( 'info', array( 'other' => $changes ) );
			}
			\IPS\nexus\Customer::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=info', 'front', 'clientsinfo', array(), \IPS\Settings::i()->nexus_https ) );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->info( $form );
	}
}
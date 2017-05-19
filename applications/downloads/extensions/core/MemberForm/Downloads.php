<?php
/**
 * @brief		Admin CP Member Form
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	
 * @since		13 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\extensions\core\MemberForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Member Form
 */
class _Downloads
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{		
		$form->addMessage( "<a href='" . \IPS\Http\Url::internal( 'app=downloads&module=stats&controller=member&do=downloads&id=' . $member->member_id ) . "'>" . \IPS\Member::loggedIn()->language()->addToStack('downloads_stats') . '</a>', '', FALSE );
		$form->add( new \IPS\Helpers\Form\YesNo( 'idm_block_submissions', !$member->idm_block_submissions ) );
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member			$member	The member
	 * @return	void
	 */
	public function save( $values, &$member )
	{
		$member->idm_block_submissions = !$values['idm_block_submissions'];	
	}
}
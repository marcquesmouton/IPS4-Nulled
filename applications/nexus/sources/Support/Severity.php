<?php
/**
 * @brief		Support Severity Model
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		9 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Severity Model
 */
class _Severity extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_support_severities';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'sev_';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
			
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'severities';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'sev_default' );
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_severity_';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'support',
		'all' 		=> 'severities_manage'
	);

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->addHeader( 'severity_settings' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'sev_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => ( $this->id ? "nexus_severity_{$this->id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'sev_default', $this->default ) );
		$form->addHeader( 'severity_submissions' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'sev_public', $this->public, FALSE, array( 'togglesOn' => array( 'sev_departments', 'sev_desc' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'sev_departments', ( $this->departments and $this->departments !== '*' ) ? explode( ',', $this->departments ) : 0, FALSE, array( 'class' => 'IPS\nexus\Support\Department', 'multiple' => TRUE, 'zeroVal' => 'all' ), NULL, NULL, NULL, 'sev_departments' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'sev_desc', NULL, FALSE, array(
			'app'		=> 'nexus',
			'key'		=> ( $this->id ? "nexus_severity_{$this->id}_desc" : NULL ),
			'textArea'	=> TRUE
		), NULL, NULL, NULL, 'sev_desc' ) );
		$form->addHeader( 'severity_acp_list' );
		$form->add( new \IPS\Helpers\Form\Upload( 'sev_icon', $this->icon ? \IPS\File::get( 'nexus_Support', $this->icon ) : NULL, FALSE, array( 'storageExtension' => 'nexus_Support', 'image' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Color( 'sev_color', $this->color ?: '000' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{	
		if ( !$this->id )
		{
			$this->save();
		}
		
		if( isset( $values['sev_name'] ) )
		{
			\IPS\Lang::saveCustom( 'nexus', "nexus_severity_{$this->id}", $values['sev_name'] );
			unset( $values['sev_name'] );
		}

		if( isset( $values['sev_desc'] ) )
		{
			\IPS\Lang::saveCustom( 'nexus', "nexus_severity_{$this->id}_desc", $values['sev_desc'] );
			unset( $values['sev_desc'] );
		}
		
		if ( isset( $values['sev_default'] ) AND $values['sev_default'] )
		{
			\IPS\Db::i()->update( 'nexus_support_severities', array( 'sev_default' => 0 ) );
		}
		
		if( isset( $values['sev_color'] ) )
		{
			$values['sev_color'] = ltrim( $values['sev_color'], '#' );
		}

		if( isset( $values['sev_departments'] ) )
		{
			$values['sev_departments'] = $values['sev_departments'] == 0 ? '*' : implode( ',', array_keys( $values['sev_departments'] ) );
		}
				
		return $values;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		if ( isset( $buttons['delete'] ) and \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( 'r_severity=?', $this->id ) ) )
		{
			$buttons['delete']['data'] = array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('delete') );
		}
		
		return $buttons;
	}
}
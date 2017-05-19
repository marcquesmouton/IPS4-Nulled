<?php
/**
 * @brief		Content Discovery Stream
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		1 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Discovery Stream
 */
class _Stream extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_streams';
			
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'streams';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'stream_title_';
	
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
	 * @encode
	 */
	protected static $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'discovery',
		'all'	 	=> 'streams_manage',
	);
	
	/**
	 * @brief	The default stream either set by member or admin
	 */
	protected static $defaultStream = NULL;
	
	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function roots( $permissionCheck='view', $member=NULL, $where=array() )
	{
		$where[] = array( 'member IS NULL' );
		return parent::roots( $permissionCheck, $member, $where );
	}
	
	/**
	 * Fetch the default stream, or NULL
	 *
	 * @return \IPS\core\Stream|null
	 */
	public static function defaultStream()
	{
		/* Check the member first */
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$default = \IPS\Member::loggedIn()->defaultStream;

			if ( $default !== NULL )
			{
				try
				{
					if ( $default )
					{
						return static::load( $default );
					}
					else
					{
						return static::allActivityStream();
					}
				}
				catch( \Exception $e )
				{
					return NULL;
				}
			}
		}

		/* Still here? Check menu */
		try
		{
			$stream = static::load( \IPS\Db::i()->select( 'id', 'core_streams', array( array( '`default`=?', 1 ) ) )->first() );
			
			/* Suitable for guests? */
			if ( ! \IPS\Member::loggedIn()->member_id )
			{
				if ( ! ( ( $stream->ownership == 'all' and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) ) )
				{
					return static::allActivityStream();
				}
			}

			return $stream;
		}
		catch( \Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * "All Activity" Stream
	 *
	 * @return	\IPS\core\Stream
	 */
	public static function allActivityStream()
	{
		$stream = new static;
		$stream->id = 0;
		$stream->include_comments = TRUE;
		return $stream;
	}
	
	/**
	 * [Node] Get Title
	 *
	 * @return	string|null
	 */
	protected function get__title()
	{
		if ( $this->id )
		{
			return $this->title ?: parent::get__title();
		}
		else
		{
			return \IPS\Member::loggedIn()->language()->addToStack('all_activity');
		}
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form			The form
	 * @param	string				$titleType		'Text' or 'Translatable
	 * @pram	bool				$titleRequired	Is the title field required?
	 * @return	void
	 */
	public function form( &$form, $titleType='Translatable', $titleRequired=TRUE )
	{
		/* Title */
		if ( $titleType )
		{
			$titleClass = '\IPS\Helpers\Form\\' . $titleType;
			$form->add( new $titleClass( 'stream_title', ( $this->id and $titleType === 'Text' ) ? $this->_title : NULL, $titleRequired, array( 'app' => 'core', 'key' => ( $this->id ? "stream_title_{$this->id}" : NULL ) ) ) );
		}
		
		/* All content or specific content? */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_include_comments', $this->include_comments, TRUE, array( 'options' => array(
			1	=> 'stream_include_comments_1',
			0	=> 'stream_include_comments_0'
		) ) ) );
		
		/* All content or specific content? */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_classes_type', $this->classes ? 1 : 0, TRUE, array(
			'options'	=> array( 0 => 'stream_classes_type_all', 1 => 'stream_classes_type_custom' ),
			'toggles'	=> array( 1 => array( 'stream_classes' ) )
		) ) );
		
		/* Work out all the different classes */
		$classes = array();
		$classContainers = array();
		$classToggles = array();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			$classes[ $class ] = $class::$title . '_pl';
			if ( isset( $class::$containerNodeClass ) )
			{
				$classContainers[] = $class;
				$classToggles[ $class ][] = 'stream_containers_' . $class::$title;
			}
		}
						
		/* Add the fields for them */
		$currentContainers = $this->containers ? json_decode( $this->containers, TRUE ) : array();
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'stream_classes', $this->classes ? explode( ',', $this->classes ) : array(), NULL, array( 'options' => $classes, 'toggles' => $classToggles ), NULL, NULL, NULL, 'stream_classes' ) );
		foreach ( $classContainers as $class )
		{
			$field = new \IPS\Helpers\Form\Node( 'stream_containers_' . $class::$title, isset( $currentContainers[ $class ] ) ? $currentContainers[ $class ] : array(), NULL, array( 'class' => $class::$containerNodeClass, 'multiple' => TRUE, 'permissionCheck' => 'view' ), NULL, NULL, NULL, 'stream_containers_' . $class::$title );
			$containerClass = $class::$containerNodeClass;
			$field->label = \IPS\Member::loggedIn()->language()->addToStack( $containerClass::$nodeTitle );
			$form->add( $field );
		}
		
		/* Tags */
		if ( \IPS\Settings::i()->tags_enabled )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'stream_tags_type', $this->tags ? 'custom' : 'all', TRUE, array(
				'options' 	=> array(
					'all'		=> 'stream_tags_all',
					'custom'	=> 'stream_tags_custom'
				),
				'toggles'	=> array(
					'custom'	=> array( 'stream_tags' )
				)
			) ) );
			$form->add( new \IPS\Helpers\Form\Text( 'stream_tags', $this->tags ? explode( ',', $this->tags ) : NULL, NULL, array( 'autocomplete' => array() ), NULL, NULL, NULL, 'stream_tags' ) );
		}
		
		/* Ownership */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_ownership', $this->ownership, TRUE, array(
			'options' => array(
				'all'				=> 'stream_ownership_all',
				'started'			=> 'stream_ownership_started',
				'postedin'			=> 'stream_ownership_postedin',
				'custom'			=> 'stream_ownership_custom',
			),
			'toggles'	=> array(
				'custom'			=> array( 'stream_custom_members' )
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\Member( 'stream_custom_members', $this->custom_members ? array_map( array( 'IPS\Member', 'load' ), explode( ',', $this->custom_members ) ) : NULL, NULL, array( 'multiple' => NULL ), NULL, NULL, NULL, 'stream_custom_members' ) );
		
		/* Read */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_read', $this->read, TRUE, array( 'options' => array(
			'all'				=> 'stream_read_all',
			'unread'			=> 'stream_read_unread',
		) ) ) );
		
		/* Follow */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_follow', $this->follow, TRUE, array(
			'options' 	=> array(
				'all'		=> 'stream_follow_all',
				'followed'	=> 'stream_follow_followed',
			),
			'toggles'	=> array(
				'followed'	=> array( 'stream_followed_types' )
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'stream_followed_types', $this->followed_types ? explode( ',', $this->followed_types ) : array( 'items' ), NULL, array( 'options' => array(
			'containers'	=> 'stream_followed_types_areas',
			'items'			=> 'stream_followed_types_items',
			'members'		=> 'stream_followed_types_members',
		) ), NULL, NULL, NULL, 'stream_followed_types' ) );
		
		/* Date */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_date_type', $this->date_type, TRUE, array(
			'options' => array(
				'all'				=> 'stream_date_type_all',
				'last_visit'		=> 'stream_date_type_last_visit',
				'relative'			=> 'stream_date_type_relative',
				'custom'			=> 'stream_date_type_custom',
			),
			'toggles' => array(
				'relative'			=> array( 'stream_date_relative_days' ),
				'custom'			=> array( 'stream_date_range' )
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'stream_date_relative_days', $this->date_relative_days ?: 7, NULL, array(), function( $val )
		{
			if ( \IPS\Request::i()->stream_date_type == 'relative' and !$val )
			{
				throw new \DomainException('form_required');
			}
		}, \IPS\Member::loggedIn()->language()->addToStack('stream_date_relative_days_prefix'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'stream_date_relative_days' ) );
		$form->add( new \IPS\Helpers\Form\DateRange( 'stream_date_range', array( 'start' => $this->date_start, 'end' => $this->date_end ), NULL, array(), function( $val )
		{
			if ( \IPS\Request::i()->stream_date_type == 'custom' and !$val['start'] and !$val['end'] )
			{
				throw new \DomainException('form_required');
			}
		}, NULL, NULL, 'stream_date_range' ) );
		
		/* Sort */
		$form->add( new \IPS\Helpers\Form\Radio( 'stream_sort', $this->sort, TRUE, array( 'options' => array(
			'newest'	=> 'stream_sort_newest',
			'oldest'	=> 'stream_sort_oldest',
		) ) ) );
		
		if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'stream_default', $this->default, FALSE ) );
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		/* Title */
		if ( !$this->id )
		{
			$this->save();
		}
		if ( isset( $values['stream_title'] ) and is_array( $values['stream_title'] ) )
		{
			\IPS\Lang::saveCustom( 'core', "stream_title_{$this->id}", $values['stream_title'] );
			$values['stream_title'] = NULL;
		}
		unset( $values['__custom_stream'] );
						
		/* Sort out stream_classes_type */
		if ( $values['stream_classes_type'] )
		{
			$classes = array();
			$containers = NULL;
			foreach ( $values['stream_classes'] as $class )
			{
				$classes[] = $class;
				if ( isset( $values[ 'stream_containers_' . $class::$title ] ) and $values[ 'stream_containers_' . $class::$title ] )
				{
					$containers[ $class ] = array_keys( $values[ 'stream_containers_' . $class::$title ] );
				}
			}
						
			$values['stream_classes'] = implode( ',', $classes );
			$values['stream_containers'] = $containers ? json_encode( $containers ) : NULL;
		}
		else
		{
			$values['stream_classes'] = NULL;
			$values['stream_containers'] = NULL;
		}
		unset( $values['stream_classes_type'] );
		
		/* And tags */
		if ( $values['stream_tags_type'] == 'all' or !$values['stream_tags'] )
		{
			$values['stream_tags'] = NULL;
		}
		else
		{
			$values['stream_tags'] = implode( ',', $values['stream_tags'] );
		}
		unset( $values['stream_tags_type'] );
		
		/* And follows */
		$values['stream_followed_types'] = ( $values['stream_follow'] == 'followed' ? implode( ',', $values['stream_followed_types'] ) : NULL );
		
		/* And members */
		if ( $values['stream_ownership'] == 'custom' )
		{
			$members = array();
			foreach ( $values['stream_custom_members'] as $member )
			{
				$members[] = $member->member_id;
			}
			$values['stream_custom_members'] = implode( ',', $members );
		}
		else
		{
			$values['stream_custom_members'] = NULL;
		}
		
		/* And dates */
		if ( $values['stream_date_type'] )
		{
			$values['stream_date_start'] = $values['stream_date_range']['start'] ? $values['stream_date_range']['start']->getTimestamp() : NULL;
			$values['stream_date_end'] = $values['stream_date_range']['end'] ? $values['stream_date_range']['end']->getTimestamp() : NULL;
		}
		unset( $values['stream_date_range'] );
		
		if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' and ! empty( $values['stream_default'] ) )
		{
			$where = ( $this->id ) ? array( 'id !=?', $this->id ) : NULL;
			\IPS\Db::i()->update( 'core_streams', array( 'default' => 0 ), $where );
		}
		
		/* Remove stream_ prefix */
		$_values = $values;
		$values = array();
		foreach ( $_values as $k => $v )
		{
			if( mb_substr( $k, 0, 15 ) === 'stream_classes_' or mb_substr( $k, 0, 18 ) === 'stream_containers_' )
			{
				continue;
			}
			if( mb_substr( $k, 0, 7 ) === 'stream_' )
			{
				$values[ mb_substr( $k, 7 ) ] = $v;
			}
			else
			{
				$values[ $k ]	= $v;
			}
		}

		/* Return */
		return $values;
	}
	
	/**
	 * Get blurb
	 *
	 * @return	string
	 */
	public function blurb()
	{
		if ( $this->classes )
		{
			$classes = array();
			foreach ( explode( ',', $this->classes ) as $class )
			{
				if ( class_exists( $class ) )
				{
					if ( in_array( 'IPS\Content\Review', class_parents( $class ) ) )
					{
						$classes[ $class::$itemClass ]['reviews'] = $class;
					}
					elseif ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
					{
						$classes[ $class::$itemClass ]['comments'] = $class;
					}
					elseif ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
					{
						$classes[ $class ]['items'] = $class;
					}
				}
			}
			
			$types = array();
			$allowedContainers = $this->containers ? json_decode( $this->containers, TRUE ) : array();
			foreach ( $classes as $itemClass => $subClasses )
			{
				$_types = array();
				foreach ( $subClasses as $class )
				{
					$_types[] = \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) );
				}
				$_types = \IPS\Member::loggedIn()->language()->formatList( $_types );
												
				if ( isset( $allowedContainers[ $itemClass ] ) )
				{
					$containers = array();
					$containerClass = $itemClass::$containerNodeClass;
					foreach ( $allowedContainers[ $itemClass ] as $id )
					{
						try
						{
							$containers[] = $containerClass::loadAndCheckPerms( $id )->_title;
						}
						catch ( \OutOfRangeException $e ) { }
					}
					$containers = \IPS\Member::loggedIn()->language()->formatList( $containers );
					
					$types[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_in_containers', FALSE, array( 'sprintf' => array( $_types, $containers ) ) );
				}
				else
				{
					$types[] = $_types;
				}
			}
						
			$type = \IPS\Member::loggedIn()->language()->formatList( $types );
		}
		else
		{
			$type = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_all');
		}
				
		$terms = array();
		
		if ( $this->tags )
		{
			$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_includes_tags', FALSE, array( 'sprintf' => \IPS\Member::loggedIn()->language()->formatList( array_map( function( $val ){
				return '\'' . $val . '\'';
			}, explode( ',', $this->tags ) ), \IPS\Member::loggedIn()->language()->get('or_list_format') ) ) );
		}
		
		switch ( $this->ownership )
		{
			case 'started':
				$terms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_i_started');
				break;
			
			case 'postedin':
				$terms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_i_posted_in');
				break;
							
			case 'custom':
				$memberNames = array();
				foreach ( explode( ',', $this->custom_members ) as $memberId )
				{
					$_member = \IPS\Member::load( $memberId );
					if ( $_member->member_id )
					{
						$memberNames[] = $_member->name;
					}
				}
				$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_by_members', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $memberNames, \IPS\Member::loggedIn()->language()->get('or_list_format') ) ) ) );
				break;
		}
		if ( $this->read == 'unread' )
		{
			$terms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_unread');
		}
		if ( $this->follow == 'followed' )
		{
			$followTerms = array();
			foreach ( explode( ',', $this->followed_types ) as $followType )
			{
				switch ( $followType )
				{
					case 'containers':
						$followTerms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_following_containers');
						break;
					case 'items':
						$followTerms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_following_items');
						break;
					case 'members':
						$followTerms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_following_members');
						break;
				}
			}
			
			$terms[] = \IPS\Member::loggedIn()->language()->formatList( $followTerms, \IPS\Member::loggedIn()->language()->get('or_list_format') );
		}
		switch ( $this->date_type )
		{
			case 'last_visit':
				$terms[] = \IPS\Member::loggedIn()->language()->addToStack('stream_blurb_since_last_visit');
				break;
			case 'relative':
				$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_relative', FALSE, array( 'sprintf' => array( $this->date_relative_days ) ) );
				break;
			case 'custom':
				if ( $this->date_start and $this->date_end )
				{
					$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_date_between', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( $this->date_start ), \IPS\DateTime::ts( $this->date_end ) ) ) );
				}
				elseif ( $this->date_start )
				{
					$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_date_after', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( $this->date_start ) ) ) );
				}
				elseif ( $this->date_end )
				{
					$terms[] = \IPS\Member::loggedIn()->language()->addToStack( 'stream_blurb_date_before', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( $this->date_end ) ) ) );
				}
				break;
		}
		
		if ( count( $terms ) )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( 'steam_blurb_with_terms', FALSE, array( 'sprintf' => array( $type, \IPS\Member::loggedIn()->language()->formatList( $terms ) ) ) );
		}
		else
		{
			return \IPS\Member::loggedIn()->language()->addToStack( 'steam_blurb_no_terms', FALSE, array( 'sprintf' => array( $type ) ) );
		}
	}
	
	/**
	 * Get results
	 *
	 * @param	\IPS\Member|null	$member	The member to get the results as
	 * @return	\IPS\Content\Search\Query
	 */
	public function query( $member = NULL )
	{
		/* Init */
		$query = \IPS\Content\Search\Query::init( $member );
		
		/* Content Filters */
		$filters = array();
		$allowedContainers = $this->containers ? json_decode( $this->containers, TRUE ) : array();
		if ( $this->classes )
		{			
			/* Translate how we store this into the format needed for filterByContent */
			$classes = array();
			foreach ( explode( ',', $this->classes ) as $class )
			{
				if ( class_exists( $class ) )
				{
					$classes[ $class ]['items'] = TRUE;
					if ( $this->include_comments and isset( $class::$commentClass ) )
					{
						$classes[ $class ]['comments'] = TRUE;
					}
					if ( $this->include_comments and isset( $class::$reviewClass ) )
					{
						$classes[ $class ]['reviews'] = TRUE;
					}
				}
			}
			
			/* Build the filters */
			foreach ( $classes as $class => $options )
			{
				/* Init */
				if ( isset( $options['items'] ) and !isset( $options['comments'] ) and in_array( 'IPS\Content\Item', class_parents( $class ) ) and $class::$firstCommentRequired )
				{
					$filter = \IPS\Content\Search\ContentFilter::init( $class, TRUE, TRUE, isset( $options['reviews'] ) )->onlyLastComment();
				}
				else
				{
					$filter = \IPS\Content\Search\ContentFilter::init( $class, isset( $options['items'] ), isset( $options['comments'] ), isset( $options['reviews'] ) );
				}
				
				/* Are we restricted to certain containers? */
				if ( isset( $allowedContainers[ $class ] ) )
				{
					$filter->onlyInContainers( $allowedContainers[ $class ] );
				}
				
				/* Add to the array */
				$filters[] = $filter;
			}
		}
		elseif ( !$this->include_comments )
		{
			foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter', FALSE ) as $object )
			{
				foreach ( $object->classes as $class )
				{
					if ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
					{
						if ( $class::$firstCommentRequired )
						{
							$filters[] = \IPS\Content\Search\ContentFilter::init( $class, TRUE, TRUE, FALSE )->onlyLastComment();
						}
						else
						{
							$filters[] = \IPS\Content\Search\ContentFilter::init( $class, TRUE, FALSE, FALSE );
						}
					}
				}
			}
		}
		if ( count( $filters ) )
		{
			$query->filterByContent( $filters );
		}
		
		/* Ownership */
		switch ( $this->ownership )
		{
			case 'started':
				$query->filterByItemAuthor( \IPS\Member::loggedIn() );
				break;
			
			case 'postedin':
				$query->filterByItemsIPostedIn();
				break;
							
			case 'custom':
				$query->filterByAuthor( explode( ',', $this->custom_members ) );
				break;
		}
		
		/* Read */
		if ( $this->read == 'unread' )
		{
			$query->filterByUnread();
		}
				
		/* Follow */
		if ( $this->follow == 'followed' )
		{
			$followTypes = explode( ',', $this->followed_types );
			$query->filterByFollowed( in_array( 'containers', $followTypes ), in_array( 'items', $followTypes ), in_array( 'members', $followTypes ) );
		}
		
		/* Date */
		switch ( $this->date_type )
		{
			case 'last_visit':
				$query->filterByLastUpdatedDate( \IPS\DateTime::ts( \IPS\Member::loggedIn()->last_visit ) );
				break;
			case 'relative':
				$query->filterByLastUpdatedDate( \IPS\DateTime::create()->sub( new \DateInterval( 'P' . intval( $this->date_relative_days ) . 'D' ) ) );
			case 'custom':
				$query->filterByLastUpdatedDate( $this->date_start ? \IPS\DateTime::ts( $this->date_start ) : NULL, $this->date_end ? \IPS\DateTime::ts( $this->date_end ) : NULL );
				break;
		}
		
		/* Sort */
		if ( $this->include_comments )
		{
			if ( $this->sort === 'oldest' )
			{
				$query->setOrder( \IPS\Content\Search\Query::ORDER_OLDEST_CREATED );
			}
			else
			{
				$query->setOrder( \IPS\Content\Search\Query::ORDER_NEWEST_CREATED );
			}
		}
		else
		{
			if ( $this->sort === 'oldest' )
			{
				$query->setOrder( \IPS\Content\Search\Query::ORDER_OLDEST_UPDATED );
			}
			else
			{
				$query->setOrder( \IPS\Content\Search\Query::ORDER_NEWEST_UPDATED );
			}
		}
				
		/* Return */
		return $query;
	}
	
	/**
	 * URL to this stream
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if ( $this->id )
		{
			switch ( $this->id )
			{
				case 1:
					$furlKey = 'discover_unread';
					break;
				case 2:
					$furlKey = 'discover_istarted';
					break;
				case 3:
					$furlKey = 'discover_followed';
					break;
				case 4:
					$furlKey = 'discover_following';
					break;
				case 5:
					$furlKey = 'discover_posted';
					break;
				default:
					$furlKey = 'discover_stream';
					break;
			}
			return \IPS\Http\Url::internal( "app=core&module=discover&controller=streams&id={$this->id}", 'front', $furlKey );
		}
		else
		{
			return \IPS\Http\Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' );
		}
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		
		if ( !$this->member )
		{
			unset( \IPS\Data\Store::i()->globalStreamIds );
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( $this->member )
		{
			parent::delete();
		}
		else
		{
			parent::delete();
			unset( \IPS\Data\Store::i()->globalStreamIds );
		}
	}
}
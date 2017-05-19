<?php
/**
 * @brief		Forum Post Model
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		8 April 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class _CommentTopicSync extends \IPS\cms\Records\Comment
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'pid';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'forums_posts';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = '';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'post',
		'date'				=> 'post_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'new_topic'
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'forums';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comment';
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'pid';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'display', 'cms', 'database' ), 'commentContainer' );
	
	/**
	 * @brief	Bitwise values for post_bwoptions field
	 */
	public static $bitOptions = array(
		'post_bwoptions' => array(
			'post_bwoptions' => array(
				'best_answer'	=> 1
			)
		)
	);

	/**
	 * Get containing item
	 *
	 * @return	\IPS\cms\Record
	 */
	public function item()
	{
		$itemClass = static::$itemClass;
		$lookFor = 'IPS\cms\Records\RecordsTopicSync';

		if ( mb_substr( $itemClass, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$id = $this->topic_id;
		}
		else
		{
			$id = \IPS\Request::i()->id;
		}
		
		return $itemClass::load( $id );
	}
	
	/**
	 * Delete Comment
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* We need the classname to be \IPS\forums\Topic\Post so topic/forum sync occur and to ensure post is removed from search index */
		$comment = \IPS\forums\Topic\Post::load( $this->pid );
		$comment->delete();
	}
	
		/**
	 * Edit Comment Contents - Note: does not add edit log
	 *
	 * @param	string	$newContent	New content
	 * @return	string|NULL
	 */
	public function editContents( $newContent )
	{
		$comment = \IPS\forums\Topic\Post::load( $this->pid );
		$comment->editContents( $newContent );
	}
	
	
	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere()
	{
		return NULL;
	}

	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( $id )
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ) );
	}
}
<?php
/**
 * @brief		Abstract Search Index
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Search Index
 */
abstract class _Index extends \IPS\Patterns\Singleton
{
	/**
	 * @brief	Singleton Instances
	 */
	protected static $instance = NULL;
	
	/**
	 * Get instance
	 *
	 * @return	static
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			static::$instance = new \IPS\Content\Search\Mysql\Index;
		}
		
		return static::$instance;
	}
	
	/**
	 * Clear and rebuild search index
	 *
	 * @return	void
	 */
	public function rebuild()
	{
		/* Delete everything currently in it */
		$this->prune();		
		
		/* If the queue is already running, clear it out */
		\IPS\Db::i()->delete( 'core_queue', array( "`key`=?", 'RebuildSearchIndex' ) );
		
		/* And set the queue in motion to rebuild */
		foreach ( \IPS\Content::routedClasses( FALSE ) as $class )
		{
			try
			{
				if( is_subclass_of( $class, 'IPS\Content\Searchable' ) )
				{
					\IPS\Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => $class ), 5, TRUE );
				}
			}
			catch( \OutOfRangeException $ex ) {}
		}
	}
	
	/**
	 * Get index data
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	array|NULL
	 */
	public function indexData( \IPS\Content\Searchable $object )
	{
		/* Init */
		$class = get_class( $object );
		$idColumn = $class::$databaseColumnId;
		$tags = ( $object instanceof \IPS\Content\Tags ) ? implode( ',', array_filter( array_merge( array( $object->prefix() ), $object->tags() ) ) ) : NULL;
		
		/* If this is an item where the first comment is required, don't index because the comment will serve as both */
		if ( $object instanceof \IPS\Content\Item and $class::$firstCommentRequired )
		{
			return NULL;
		}

		/* Don't index if this is an item to be published in the future */
		if ( $object->isFutureDate() )
		{
			return NULL;
		}

		/* Or if this *is* the first comment, add the title and replace the tags */
		$title = $object->mapped('title');
		$isForItem = FALSE;
		if ( $object instanceof \IPS\Content\Comment )
		{
			$itemClass = $class::$itemClass;
			if ( $itemClass::$firstCommentRequired and $object->isFirst() )
			{
				$title = $object->item()->mapped('title');
				$tags = ( $object->item() instanceof \IPS\Content\Tags ) ? implode( ',', array_filter( array_merge( array( $object->item()->prefix() ), $object->item()->tags() ) ) ) : NULL;
				$isForItem = TRUE;
			}
		}
		
		/* Get the last updated date */
		if ( $isForItem )
		{
			$dateUpdated = $object->item()->mapped('last_comment');
		}
		else
		{
			$dateUpdated = ( $object instanceof \IPS\Content\Item ) ? $object->mapped('last_comment') : $object->mapped('edit_time');
		}
		
		/* Is the the latest content? */
		$isLastComment = 0;
		if ( $object instanceof \IPS\Content\Comment )
		{
			$item = $object->item();
			$latestThing = 0;
			foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
			{
				if ( isset( $item::$databaseColumnMap[ $k ] ) and ( $item->mapped( $k ) < time() AND $item->mapped( $k ) > $latestThing ) )
				{
					$latestThing = $item->mapped( $k );
				}
			}
			
			if ( $object->mapped('date') >= $latestThing )
			{
				$isLastComment = 1;
			}
		}
		
		/* Take the HTML out of the content */		
		$content = trim( strip_tags( preg_replace( "/(<br(?: \/)?>|<\/p>)/i", ' ', preg_replace( "#<blockquote(.*?)>(.*)</blockquote>#uis", "", preg_replace( "#<script(.*?)>(.*)</script>#uis", "", ' ' . $object->mapped('content') . ' ' ) ) ) ) );

		/* Work out the hidden status */
		$hiddenStatus = $object->hidden();
		if ( $hiddenStatus === 0 and method_exists( $object, 'item' ) and $object->item()->hidden() )
		{
			$hiddenStatus = $isForItem ? 1 : 2;
		}
		if ( $hiddenStatus !== 0 and method_exists( $object, 'item' ) and $object->item()->isFutureDate() )
		{
			$hiddenStatus = 0;
		}
		
		/* Get the item index ID */
		$itemIndexId = NULL;
		if ( $object instanceof \IPS\Content\Comment )
		{
			$itemClass = $object::$itemClass;
			if ( $itemClass::$firstCommentRequired )
			{
				try
				{
					$itemIndexId = $this->getIndexId( $object::load( $object->item()->mapped('first_comment_id') ) );
				}
				catch ( \Exception $e ) { }
			}
			else
			{
				$itemIndexId = $this->getIndexId( $object->item() );
			}
		}
								
		/* Return */
		return array(
			'index_class'			=> $class,
			'index_object_id'		=> $object->$idColumn,
			'index_item_id'			=> ( $object instanceof \IPS\Content\Item ) ? $object->$idColumn : $object->mapped('item'),
			'index_container_id'	=> ( $object instanceof \IPS\Content\Item ) ? (int) $object->searchIndexContainer() : (int) $object->item()->mapped('container'),
			'index_title'			=> $title,
			'index_content'			=> $content,
			'index_permissions'		=> $object->searchIndexPermissions(),
			'index_date_created'	=> intval( $object->mapped('date') ),
			'index_date_updated'	=> intval( $dateUpdated ?: $object->mapped('date') ),
			'index_author'			=> (int) $object->mapped('author'),
			'index_tags'			=> $tags,
			'index_hidden'			=> $hiddenStatus,
			'index_item_index_id'	=> $itemIndexId,
			'index_item_author'		=> intval( ( $object instanceof \IPS\Content\Item ) ? $object->mapped('author') : $object->item()->mapped('author') ),
			'index_is_last_comment'	=> $isLastComment
		);
	}
	
	/**
	 * Index an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	abstract public function index( \IPS\Content\Searchable $object );
	
	/**
	 * Retrieve the search ID for an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	abstract public function getIndexId( \IPS\Content\Searchable $object );
	
	/**
	 * Remove item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to remove
	 * @return	void
	 */
	abstract public function removeFromSearchIndex( \IPS\Content\Searchable $object );
	
	/**
	 * Removes all content for a classs
	 *
	 * @param	string		$class 	The class
	 * @param	int|NULL	$containerId		The container ID to update, or NULL
	 * @return	void
	 */
	abstract public function removeClassFromSearchIndex( $class, $containerId=NULL );
	
	/**
	 * Removes all content for a specific application from the index (for example, when uninstalling).
	 *
	 * @param	\IPS\Application	$application The application
	 * @return	void
	 */
	public function removeApplicationContent( \IPS\Application $application )
	{
		foreach ( $application->extensions( 'core', 'ContentRouter' ) as $router )
		{
			foreach( $router->classes AS $class )
			{
				if ( is_subclass_of( $class, 'IPS\Content\Searchable' ) )
				{
					$this->removeClassFromSearchIndex( $class );
					
					if ( isset( $class::$commentClass ) )
					{
						$commentClass = $class::$commentClass;
						if ( is_subclass_of( $commentClass, 'IPS\Content\Searchable' ) )
						{
							$this->removeClassFromSearchIndex( $commentClass );
						}
					}
					
					if ( isset( $class::$reviewClass ) )
					{
						$reviewClass = $class::$reviewClass;
						if ( is_subclass_of( $reviewClass, 'IPS\Content\Searchable' ) )
						{
							$this->removeClassFromSearchIndex( $reviewClass );
						}
					}
				}
			}
		}
	}
		
	/**
	 * Mass Update (when permissions change, for example)
	 *
	 * @param	string				$class 				The class
	 * @param	int|NULL			$containerId		The container ID to update, or NULL
	 * @param	int|NULL			$itemId				The item ID to update, or NULL
	 * @param	string|NULL			$newPermissions		New permissions (if applicable)
	 * @param	int|NULL			$newHiddenStatus	New hidden status (if applicable) special value 2 can be used to indicate hidden only by parent
	 * @param	int|NULL			$newContainer		New container ID (if applicable)
	 * @param	int|NULL			$authorId			The author ID to update, or NULL
	 * @param	int|NULL			$newItemId			The new item ID (if applicable)
	 * @param	int|NULL			$$newItemAuthorId	The new item author ID (if applicable)
	 * @return	void
	 */
	abstract public function massUpdate( $class, $containerId = NULL, $itemId = NULL, $newPermissions = NULL, $newHiddenStatus = NULL, $newContainer = NULL, $authorId = NULL, $newItemId = NULL, $newItemAuthorId = NULL );
	
	/**
	 * Update data for the first and last comment after a merge
	 * Sets index_is_last_comment on the last comment, and, if this is an item where the first comment is indexed rather than the item, sets index_title and index_tags on the first comment
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	void
	 */
	abstract public function rebuildAfterMerge( \IPS\Content\Item $item );
	
	/**
	 * Prune search index
	 *
	 * @param	\IPS\DateTime|NULL	$cutoff	The date to delete index records from, or NULL to delete all
	 * @return	void
	 */
	abstract public function prune( \IPS\DateTime $cutoff = NULL );
}
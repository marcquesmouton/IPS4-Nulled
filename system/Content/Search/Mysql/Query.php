<?php
/**
 * @brief		MySQL Search Query
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search\Mysql;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MySQL Search Query
 */
class _Query extends \IPS\Content\Search\Query
{	
	/**
	 * @brief		The SELECT clause
	 */
	protected $select = array( 'main' => 'main.*' );
	
	/**
     * @brief       The WHERE clause
     */
    protected $where = array();
    
    /**
     * @brief       The WHERE clause for hidden/unhidden
     */
    protected $hiddenClause = NULL;
    
    /**
     * @brief       The offset
     */
    protected $offset = 0;
    
    /**
     * @brief       The ORDER BY clause
     */
    protected $order = NULL;
    
    /**
     * @brief       Joins
     */
    protected $joins = array();
    
    /**
     * @brief       Item classes included
     */
    protected $itemClasses = NULL;
    
    /**
     * @brief       Force specific table index
     */
    protected $forceIndex = NULL;
    
    /**
     * @brief       Filter by items I posted in?
     * @see			filterByItemsIPostedIn()
     */
    protected $filterByItemsIPostedIn = FALSE;
    	
	/**
	 * Filter by multiple content types
	 *
	 * @param	array	$contentFilters	Array of \IPS\Content\Search\ContentFilter objects
	 * @param	bool	$type			TRUE means only include results matching the filters, FALSE means exclude all results matching the filters
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByContent( array $contentFilters, $type = TRUE )
	{
		/* Init */
		$filters = array();
		$params = array();
		if ( $type )
		{
			$this->itemClasses = array();
		}

		/* Loop the filters */
		foreach ( $contentFilters as $filter )
		{
			$clause = array();
			if ( $type )
			{
				$this->itemClasses[] = $filter->itemClass;
			}
			
			/* Set the class */
			if ( count( $filter->classes ) > 1 )
			{
				$clause[] = \IPS\Db::i()->in( 'index_class', $filter->classes );
			}
			else
			{
				$clause[] = 'index_class=?';
				$params[] = array_pop( $filter->classes );
			}
			
			/* Set the containers */
			if ( $filter->containerIdFilter !== NULL )
			{
				$clause[] = \IPS\Db::i()->in( 'index_container_id', $filter->containerIds, $filter->containerIdFilter === FALSE );
			}
			
			/* Set the item IDs */
			if ( $filter->itemIdFilter !== NULL )
			{
				$clause[] = \IPS\Db::i()->in( 'index_item_id', $filter->itemIds, $filter->itemIdFilter === FALSE );
			}
			
			/* Minimum comments/reviews/views? */
			if ( $filter->minimumComments or $filter->minimumReviews or $filter->minimumViews )
			{
				$class = $filter->itemClass;
				
				$this->joins[] = array( 'from' => $class::$databaseTable, 'where' => $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId . '=main.index_item_id' );
				
				if ( $filter->minimumComments )
				{
					$this->select[ $class::$databaseTable . '_comments' ] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'];
					$clause[] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>=' . intval( $filter->minimumComments );
				}
				
				if ( $filter->minimumReviews )
				{
					$this->select[ $class::$databaseTable . '_reviews' ] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_reviews'];
					$clause[] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_reviews'] . '>=' . intval( $filter->minimumReviews );
				}
				
				if ( $filter->minimumViews )
				{
					$this->select[ $class::$databaseTable . '_views' ] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['views'];
					$clause[] = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['views'] . '>=' . intval( $filter->minimumViews );
				}
			}
			
			/* Only first comment? */
			if ( $filter->onlyFirstComment )
			{
				$clause[] = "index_title IS NOT NULL";
			}
			
			/* Only last comment? */
			if ( $filter->onlyLastComment )
			{
				$clause[] = "index_is_last_comment=1";
			}
			
			/* Put it together */
			if ( count( $clause ) > 1 )
			{
				$filters[] = '( ' . implode( ' AND ', $clause ) . ' )';
			}
			else
			{
				$filters[] = array_pop( $clause );
			}
		}
		
		/* Put it all together */
		$this->where[] = array_merge( array( $type ? ( '( ' . implode( ' OR ', $filters ) . ' )' ) : ( '!( ' . implode( ' OR ', $filters ) . ' )' ) ), $params );
		
		/* Return */
		return $this;
	}
		
	/**
	 * Filter by author
	 *
	 * @param	\IPS\Member|int|array	$author		The author, or an array of author IDs
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByAuthor( $author )
	{
		if ( is_array( $author ) )
		{
			$this->where[] = array( \IPS\Db::i()->in( 'index_author', $author ) );
		}
		else
		{
			$this->where[] = array( 'index_author=?', $author instanceof \IPS\Member ? $author->member_id : $author );
		}
		 
		return $this;
	}
	
	/**
	 * Filter for profile
	 *
	 * @param	\IPS\Member	$member	The member whose profile is being viewed
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterForProfile( \IPS\Member $member )
	{
		$this->where[] = array( '( index_author=? OR ( index_class=? AND index_container_id=? ) )', $member->member_id, 'IPS\core\Statuses\Status', $member->member_id );
		 
		return $this;
	}
	
	/**
	 * Filter by item author
	 *
	 * @param	\IPS\Member	$author		The author
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByItemAuthor( \IPS\Member $author )
	{
		$this->where[] = array( 'index_item_author=?', $author->member_id );
		 
		return $this;
	}
	
	/**
	 * Filter by content the user follows
	 *
	 * @param	bool	$includeContainers	Include content in containers the user follows?
	 * @param	bool	$includeItems		Include items and comments/reviews on items the user follows?
	 * @param	bool	$includeMembers		Include content posted by members the user follows?
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByFollowed( $includeContainers, $includeItems, $includeMembers )
	{
		$where = array();
		$params = array();
		
		/* Are we including items or containers? */
		if ( $includeContainers or $includeItems )
		{
			/* Work out what classes we need to examine */
			if ( $this->itemClasses !== NULL )
			{
				$classes = $this->itemClasses;
			}
			else
			{
				$classes = array();
				foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter', FALSE ) as $object )
				{
					$classes = array_merge( $object->classes, $classes );
				}
			}
			
			/* Loop them */
			$followApps = $followAreas = $_classes = $case = $containerCase = array();
			foreach ( $classes as $class )
			{
				if( is_subclass_of( $class, 'IPS\Content\Followable' ) )
				{
					$followApps[ $class::$application ] = $class::$application;
					$followArea = mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) );
					
					if ( $includeContainers and $includeItems )
					{
						$followAreas[] = mb_strtolower( mb_substr( $class::$containerNodeClass, mb_strrpos( $class::$containerNodeClass, '\\' ) + 1 ) );
						$followAreas[] = $followArea;
					}
					elseif ( $includeItems )
					{
						$followAreas[] = $followArea;
					}
					elseif ( $includeContainers )
					{
						$followAreas[] = mb_strtolower( mb_substr( $class::$containerNodeClass, mb_strrpos( $class::$containerNodeClass, '\\' ) + 1 ) );
					}
					
					/* Work out what classes this applies to - need to specify comment and review classes */
					if ( ! $class::$firstCommentRequired )
					{
						$_classes[] = array( $class );
						$case[ $followArea ][] = $class;
					}
					else if( $includeContainers )
					{
						$containerCase[ $followArea ] = mb_strtolower( mb_substr( $class::$containerNodeClass, mb_strrpos( $class::$containerNodeClass, '\\' ) + 1 ) ) ;
					}
					
					if ( isset( $class::$commentClass ) )
					{
						$_classes[] = $class::$commentClass;
						$case[ $followArea ][] = $class::$commentClass;
					}
					if ( isset( $class::$reviewClass ) )
					{
						$_classes[] = $class::$reviewClass;
						$case[ $followArea ][] = $class::$reviewClass;
					}
				}
			}
		}

		$caseQuery = array();
		foreach( $case as $followArea => $classes )
		{
			$caseQuery[] = "WHEN " . \IPS\Db::i()->in( 'index_class', $classes ) . " THEN '" . \IPS\Db::i()->real_escape_string( $followArea ) . "'";

			if( isset( $containerCase[ $followArea ] ) )
			{
				$where[] = '( ' . \IPS\Db::i()->in( 'index_class', $_classes ) . " AND `core_follow`.follow_rel_id=`main`.index_container_id AND `core_follow`.follow_area='" . \IPS\Db::i()->real_escape_string( $containerCase[ $followArea ] ) . "' )";
			}
		}

		if( count( $caseQuery ) )
		{
			$where[] = '( ' . \IPS\Db::i()->in( 'index_class', $_classes ) . ' AND `core_follow`.follow_rel_id=`main`.index_item_id AND ( CASE ' . implode( "\n", $caseQuery ) . ' ELSE NULL END )=`core_follow`.follow_area )';
		}

		/* Are we including content posted by followed members? */
		if ( $includeMembers )
		{
			$where[] = 'index_author IN(?)';
			$params[] = \IPS\Db::i()->select( 'follow_rel_id', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_member_id=?', 'core', 'member', $this->member->member_id ) );			
		}

		/* Put it all together */
		if ( count( $where ) )	
		{
			$this->where[] = array_merge( array( '( ' . implode( ' OR ', $where ) . ' )' ), $params );
		}
		
		$this->joins[] = array(
			'from'  => array( 'core_follow', 'core_follow' ),
			'where' => array( array( \IPS\Db::i()->in( 'follow_app', $followApps ) . ' AND ' . \IPS\Db::i()->in( 'follow_area', $followAreas ) . ' AND follow_member_id=?', $this->member->member_id ) ),
			'type'  => 'INNER'
		);
		
		/* Some MySQL optimisers miss this and when they do, it's pretty bad */
		$this->forceIndex = 'item';
		
		/* And return */
		return $this;
	}
	
	/**
	 * Filter by content the user has posted in. This must be at the end of the chain.
	 *
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByItemsIPostedIn()
	{
		/* We have to set a property because we need the other data like other filters and ordering to figure this out */
		$this->filterByItemsIPostedIn = TRUE;
		
		/* Return for daisy chaining */
		return $this;	
	}
	
	/**
	 * Filter by content the user has not read
	 *
	 * @note	If applicable, it is more efficient to call filterByContent() before calling this method
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByUnread()
	{		
		/* Work out what classes we need to examine */
		if ( $this->itemClasses !== NULL )
		{
			$classes = $this->itemClasses;
		}
		else
		{
			$classes = array();
			foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter', FALSE ) as $object )
			{
				$classes = array_merge( $object->classes, $classes );
			}
		}
		
		/* Loop them */
		$where = array();
		$params = array();
		foreach ( $classes as $class )
		{
			if( is_subclass_of( $class, 'IPS\Content\ReadMarkers' ) )
			{
				/* Get the actual clause */
				$unreadWhere = $this->_getUnreadWhere( $class );
				
				/* Work out what classes this applies to - need to specify comment and review classes */
				$_classes = array( $class );
				if ( isset( $class::$commentClass ) )
				{
					$_classes[] = $class::$commentClass;
				}
				if ( isset( $class::$reviewClass ) )
				{
					$_classes[] = $class::$reviewClass;
				}
				
				/* Add it to the array */
				$clause = array( \IPS\Db::i()->in( 'index_class', $_classes ) );
				foreach ( $unreadWhere as $_clause )
				{
					$clause[] = array_shift( $_clause );
					$params = array_merge( $params, $_clause );
				}
				$where[] = '( ' . implode( ' AND ', $clause ) . ' )';
			}
		}
		
		if ( count( $where ) )
		{
			/* Put it all together */		
			$this->where[] = array_merge( array( '( ' . implode( ' OR ', $where ) . ' )' ), $params );
		}
	}
	
	/**
	 * Get the 'unread' where SQL
	 *
	 * @param	string	$class 		Content class (\IPS\forums\Forum)
	 * @return	array
	 */
	protected function _getUnreadWhere( $class )
	{
		$classBits	    = explode( "\\", $class );
		$application    = $classBits[1];
		$resetTimes	    = $this->member->markersResetTimes( NULL );
		$resetTimes		= isset( $resetTimes[ $application ] ) ? $resetTimes[ $application ] : array();
		$oldestTime	    = time();
		$markers	    = array();
		$excludeIds     = array();
		$where          = array();
		$unreadWheres	= array();
		$containerIds	= array();
		$containerClass = ( $class::$containerNodeClass ) ? $class::$containerNodeClass : NULL;
		
		foreach( $resetTimes as $containerId => $timestamp )
		{
			/* Pages has different classes per database, but recorded as 'cms' and the container ID in the marking tables */
			if ( $containerClass and method_exists( $containerClass, 'isValidContainerId' ) )
			{
				if ( ! $containerClass::isValidContainerId( $containerId ) )
				{
					continue;
				}
			}

			$timestamp	= $timestamp ?: $this->member->marked_site_read;
	
			$containerIds[]	= $containerId;
			$unreadWheres[]	= '( index_container_id=' . $containerId . ' AND index_date_updated > ' . (int) $timestamp . ')';
			
			$items = $this->member->markersItems( $application, \IPS\Content\Item::makeMarkerKey( $containerId ) );
			
			if ( count( $items ) )
			{
				foreach( $items as $mid => $mtime )
				{
					if ( $mtime > $timestamp )
					{
						/* If an item has been moved from one container to another, the user may have a marker
							in it's old location, with the previously 'read' time. In this circumstance, we need
							to only use more recent read time, otherwise the topic may be incorrectly included
							in the results */
						if ( in_array( $mid, $markers ) )
						{
							$_key = array_search( $mid, $markers );
							$_mtime = intval( mb_substr( $_key, 0, mb_strpos( $_key, '.' ) ) );
							if ( $_mtime < $mtime )
							{
								unset( $markers[ $_key ] );
							}
						}
						
						$markers[ $mtime . '.' . $mid ] = $mid;
					}
				}
			}
		}
		
		if( count( $containerIds ) )
		{
			$unreadWheres[]	= "( index_date_updated > " . intval( $this->member->marked_site_read ) . " AND ( index_container_id NOT IN(" . implode( ',', $containerIds ) . ") ) )";
		}
		else
		{
			$unreadWheres[]	= "( index_date_updated > " . intval( $this->member->marked_site_read ) . ")";
		}
	
		if( count( $unreadWheres ) )
		{
			$where[] = array( "(" . implode( " OR ", $unreadWheres ) . ")" );
		}
	
		if ( count( $markers ) )
		{
			/* Avoid packet issues */
			krsort( $markers );
			$useIds = array_flip( array_slice( $markers, 0, 500, TRUE ) );
			$select = '';
			$from   = '';
			$notIn  = array();
			
			/* What is the best date column? */
			$dateColumns = array();
			foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
			{
				if ( isset( $class::$databaseColumnMap[ $k ] ) )
				{
					if ( is_array( $class::$databaseColumnMap[ $k ] ) )
					{
						foreach ( $class::$databaseColumnMap[ $k ] as $v )
						{
							$dateColumns[] = " IFNULL( " . $class::$databaseTable . '.'. $class::$databasePrefix . $v . ", 0 )";
						}
					}
					else
					{
						$dateColumns[] = " IFNULL( " . $class::$databaseTable . '.'. $class::$databasePrefix . $class::$databaseColumnMap[ $k ] . ", 0 )";
					}
				}
			}
			$dateColumnExpression = count( $dateColumns ) > 1 ? ( 'GREATEST(' . implode( ',', $dateColumns ) . ')' ) : array_pop( $dateColumns );
			
			foreach( \IPS\Db::i()->select( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId. ' as _id, ' . $dateColumnExpression . ' as _date', $class::$databaseTable, \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnId, array_keys( $useIds ) ) ) as $row )
			{
				if ( isset( $useIds[ $row['_id'] ] ) )
				{
					if ( $useIds[ $row['_id'] ] >= $row['_date'] )
					{
						/* Still read */
						$notIn[] = intval( $row['_id'] );
					}
				}
			}
			
			if ( count( $notIn ) )
			{
				$where[] = array( "( index_item_id NOT IN (" . implode( ',', $notIn ) . ") )" );
			}
		}
		
		return $where;
	}
		
	/**
	 * Filter by start date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByCreateDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL )
	{
		if ( $start )
		{
			$this->where[] = array( 'index_date_created>?', $start->getTimestamp() );
		}
		if ( $end )
		{
			$this->where[] = array( 'index_date_created<?', $end->getTimestamp() );
		}
		return $this;
	}
	
	/**
	 * Filter by last updated date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByLastUpdatedDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL )
	{
		if ( $start )
		{
			$this->where[] = array( 'index_date_updated>?', $start->getTimestamp() );
		}
		if ( $end )
		{
			$this->where[] = array( 'index_date_updated<?', $end->getTimestamp() );
		}
		return $this;
	}
	
	/**
	 * Set hidden status
	 *
	 * @param	int|array	$statuses	The statuses (array of HIDDEN_ constants)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setHiddenFilter( $statuses )
	{
		if ( is_null( $statuses ) )
		{
			$this->hiddenClause = NULL;
		}
		if ( is_array( $statuses ) )
		{
			$this->hiddenClause = array( \IPS\Db::i()->in( 'index_hidden', $statuses ) );
		}
		else
		{
			$this->hiddenClause = array( 'index_hidden=?', $statuses );
		}
		
		return $this;
	}
	
	/**
	 * Set page
	 *
	 * @param	int		$page	The page number
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setPage( $page )
	{
		$this->offset = ( $page - 1 ) * $this->resultsToGet;
		
		return $this;
	}
	
	/**
	 * Set order
	 *
	 * @param	int		$order	Order (see ORDER_ constants)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setOrder( $order )
	{
		switch ( $order )
		{
			case static::ORDER_NEWEST_UPDATED:
				$this->order = 'index_date_updated DESC';
				break;
				
			case static::ORDER_OLDEST_UPDATED:
				$this->order = 'index_date_updated ASC';
				break;
			
			case static::ORDER_NEWEST_CREATED:
				$this->order = 'index_date_created DESC';
				break;
				
			case static::ORDER_OLDEST_CREATED:
				$this->order = 'index_date_updated DESC';
				break;

			case static::ORDER_RELEVANCY:
				$this->order = 'calcscore DESC';
				break;
		}
		
		return $this;
	}
	
	/**
	 * Build where
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	array
	 */
	protected function _searchWhereClause( $term = NULL, $tags = NULL, $method = 1 )
	{
		/* Boolean tags */
		if ( $tags !== NULL )
		{
			$theTags = array();
			foreach( $tags as $tagEntry )
			{
				foreach( explode( ' ', $tagEntry ) as $_term )
				{
					if( mb_strlen( $_term ) > 2 )
					{
						$theTags[] = "+" . $_term;
					}
					else
					{
						$theTags[] = $_term;
					}
				}
	
				$searchTags[] = implode( ' ', $theTags );
			}
		}

		/* Do we have a term? */
		$where = array();
		if ( $term !== NULL )
		{	
			$indexes = 'index_content,index_title';
			if ( $method & static::TERM_TITLES_ONLY )
			{
				$indexes = 'index_title';
			}
			
			/* Default to 'AND' boolean mode search unless asked otherwise*/
			if ( ! ( $method & static::TERM_OR_MODE ) and ! static::termIsPhrase( $term ) )
			{
				$theWords = array();
				foreach( static::termAsWordsArray( $term ) as $_term )
				{
					$potentialOperator = mb_substr( $_term, 0, 1 );
					
					if ( ! in_array( $potentialOperator, array( '-', '+', '~' ) ) )
					{
						if( mb_strlen( $_term ) >= 2 )
						{
							$theWords[] = "+" . $_term;
						}
 					}
				}
	
				$term = implode( ' ', $theWords );
			}
			
			/* If we also have tags, create a combined where */
			if ( $tags !== NULL )
			{
				if ( $method & static::TERM_OR_TAGS )
				{					
					$where[] = array( "( MATCH({$indexes}) AGAINST (? IN BOOLEAN MODE) ) OR " . \IPS\Db::i()->findInSet( 'index_tags', $tags ), $term );
				}
				else
				{
					$where[] = array( "MATCH({$indexes}) AGAINST (? IN BOOLEAN MODE)", $term );
					$where[] = array( \IPS\Db::i()->findInSet( 'index_tags', $tags ) );
				}
			}
			/* Or just use the term */
			else
			{
				$where[] = array( "MATCH({$indexes}) AGAINST (? IN BOOLEAN MODE)", $term );
			}
		}
		/* Or do we have tags? */
		elseif ( $tags !== NULL )
		{			
			$where[] = array( \IPS\Db::i()->findInSet( 'index_tags', $tags ) );
			
			if ( $this->order = 'calcscore DESC' )
			{
				$this->setOrder( static::ORDER_NEWEST_UPDATED );
			}
		}

		/* If we have no term or tags, don't try to sort by calcscore */
		if ( mb_substr( $this->order, 0, 9 ) === 'calcscore' AND $term === NULL )
		{
			$this->setOrder( static::ORDER_NEWEST_UPDATED );
		}
		
		/* Only get stuff we have permission for */
		$where[] = array( "( index_permissions = '*' OR " . \IPS\Db::i()->findInSet( 'index_permissions', $this->permissionArray() ) . ' )' );
		if ( $this->hiddenClause )
		{
			$where[] = $this->hiddenClause;
		}
		
		/* MOST PERTINENT search? */
		if ( mb_substr( $this->order, 0, 9 ) === 'calcscore' )
		{
			$this->select['main'] .= ',' . "( ( MATCH (index_title) AGAINST ('" . \IPS\Db::i()->escape_string( $term ) ."' IN BOOLEAN MODE) * 5 ) + (MATCH ({$indexes}) AGAINST ('" . \IPS\Db::i()->escape_string( $term ) ."' IN BOOLEAN MODE) ) ) / POWER( ( ( UNIX_TIMESTAMP( NOW() ) - index_date_updated ) / 3600 ) + 2, 1.5 ) AS calcscore";
		}
		
		/* Filer by items I posted in? */
		if ( $this->filterByItemsIPostedIn )
		{
			$ids = array();
			if ( $this->member->member_id )
			{
				$where[] = array( 'index_author=' . intval( $this->member->member_id ) );
				
				$query = \IPS\Db::i()->select( 'index_item_index_id, GROUP_CONCAT( index_id ORDER BY index_date_updated ) as index_id', array( 'core_search_index', 'main' ), $where, $this->order, array( $this->offset, $this->resultsToGet ), 'index_item_index_id' );
				foreach ( $this->joins as $data )
				{
					$query->join( $data['from'], $data['where'], isset( $data['type'] ) ? $data['type'] : 'LEFT' );
				}

				foreach( $query as $row )
				{
					if ( mb_strstr( $row['index_id'], ',' ) )
					{
						$row['index_id'] = mb_substr( $row['index_id'], 0, mb_strpos( $row['index_id'], ',' ) );
					}
					
					$ids[] = $row['index_id'];
				}
			}
			
			$where = count( $ids ) ? array( \IPS\Db::i()->in( 'index_id', $ids ) ) : array( '1=0' );
		}
				
		/* Return */
		return $where;
	}
	
	/**
	 * Search
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	\IPS\Content\Search\Results
	 */
	public function search( $term = NULL, $tags = NULL, $method = 1 )
	{
		$where = array_merge( $this->where, $this->_searchWhereClause( $term, $tags, $method ) );
		$query = \IPS\Db::i()->select( implode( ', ', $this->select ), array( 'core_search_index', 'main' ), $where, $this->order, array( $this->offset, $this->resultsToGet ), NULL, NULL );

		foreach ( $this->joins as $data )
		{
			$query->join( $data['from'], $data['where'], isset( $data['type'] ) ? $data['type'] : 'LEFT' );
		}
		
		if ( $this->forceIndex )
		{
			$query->forceIndex( $this->forceIndex );
		}

		$count = $this->count( $term, $tags, $method );
		return new \IPS\Content\Search\Results( iterator_to_array( $query ), $count );
	}
	
	/**
	 * Get count
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	int
	 */
	public function count( $term = NULL, $tags = NULL, $method = 1 )
	{
		$query = \IPS\Db::i()->select( 'COUNT(*)', array( 'core_search_index', 'main' ), array_merge( $this->where, $this->_searchWhereClause( $term, $tags, $method ) ) );
		
		foreach ( $this->joins as $data )
		{
			$query->join( $data['from'], $data['where'], isset( $data['type'] ) ? $data['type'] : 'LEFT' );
		}
		
		return $query->first();
	}
}
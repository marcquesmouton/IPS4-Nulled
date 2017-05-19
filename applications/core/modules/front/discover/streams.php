<?php
/**
 * @brief		streams
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		02 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\discover;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * streams
 */
class _streams extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* Initiate the breadcrumb */
		\IPS\Output::i()->breadcrumb = array( array( \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ), \IPS\Member::loggedIn()->language()->addToStack('activity') ) );

		/* Necessary CSS/JS */
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_streams.js', 'core' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/streams.css' ) );
		
		/* Execute */
		return parent::execute();
	}
	
	/**
	 * View Stream
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* If this request is from an auto-poll, kill it and exit */
		if ( !\IPS\Settings::i()->auto_polling_enabled && \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->after ) ){
			\IPS\Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			return;
		}

		/* Viewing a particular stream? */
		if ( isset( \IPS\Request::i()->id ) )
		{
			/* Get it */
			try
			{
				$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C280/1', 404, '' );
			}
			
			/* Suitable for guests? */
			if ( ! \IPS\Member::loggedIn()->member_id and ! ( ( $stream->ownership == 'all' or $stream->ownership == 'custom' ) and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) )
			{
				\IPS\Output::i()->error( 'stream_no_permission', '2C280/3', 403, '' );
			}
			
			$baseUrl = $stream->url();
			
			if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->default ) )
			{
				if ( \IPS\Request::i()->default )
				{
					\IPS\Member::loggedIn()->defaultStream = $stream->_id;
				}
				else
				{
					\IPS\Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$defaultStream = \IPS\core\Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						\IPS\Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						\IPS\Output::i()->json( array(
							'url'   => $defaultStream->url(),
							'title' => $defaultStream->_title,
							'id'    => $defaultStream->_id
						 ) );
					}
				}
				
				\IPS\Output::i()->redirect( $stream->url() );
			}
			
			if ( isset( \IPS\Request::i()->form ) )
			{
				if ( \IPS\Request::i()->form == 'show' )
				{
					if ( \IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->sendOutput( $this->_buildForm( $stream ), 200, 'text/html' );	
					}
					else
					{
						\IPS\Output::i()->output = $this->_buildForm( $stream );
					}
					
					return;
				}
				else
				{
					/* We want to process the form */
					$this->_buildForm( $stream );
				}
			}
			
			/* Set title and breadcrumb */
			\IPS\Output::i()->breadcrumb[] = array( $stream->url(), $stream->_title );
			\IPS\Output::i()->title = $stream->_title;
		}
		
		/* Or just everything? */
		else
		{
			if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->default ) )
			{
				if ( \IPS\Request::i()->default )
				{
					\IPS\Member::loggedIn()->defaultStream = 0;
				}
				else
				{
					\IPS\Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$defaultStream = \IPS\core\Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						\IPS\Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						\IPS\Output::i()->json( array(
							'url'   => \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ),
							'title' => $defaultStream->_title,
							'id'    => $defaultStream->_id
						 ) );
					}
				}
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ) );
			}

			/* Start with a blank stream */
			$stream = \IPS\core\Stream::allActivityStream();
			$baseUrl = \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' );

			/* Set the title to "All Activity" */
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('all_activity');
		}
				
		/* Changes from Update Only */
		foreach ( array( 'include_comments', 'classes', 'containers', 'ownership', 'read', 'follow', 'date_type', 'date_start', 'date_end', 'date_relative_days', 'sort' ) as $k )
		{
			if ( isset( \IPS\Request::i()->$k ) )
			{
				$stream->$k = \IPS\Request::i()->$k;
				$baseUrl = $baseUrl->setQueryString( $k, \IPS\Request::i()->$k );
			}
		}
		
		/* RSS validate? */
		$member = NULL;
		if ( isset( \IPS\Request::i()->rss ) )
		{
			$member = \IPS\Member::load( \IPS\Request::i()->member );
			if ( \IPS\Request::i()->key != md5( ( $member->members_pass_hash ?: $member->email ) . $member->members_pass_salt ) )
			{
				$member = NULL;
			}
		}
		
		/* Build the query */
		$query = $stream->query( $member );
		
		/* Set page or the before/after date */
		$currentPage = 1;
		if ( isset( \IPS\Request::i()->page ) AND intval( \IPS\Request::i()->page ) > 0 )
		{
			$currentPage = \IPS\Request::i()->page;
			$query->setPage( $currentPage );
		}
		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->before ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( NULL, \IPS\DateTime::ts( \IPS\Request::i()->before ) );
			}
			else
			{
				$query->filterByCreateDate( NULL, \IPS\DateTime::ts( \IPS\Request::i()->before ) );
			}
		}
		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->after ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( \IPS\DateTime::ts( \IPS\Request::i()->after ) );
			}
			else
			{
				$query->filterByCreateDate( \IPS\DateTime::ts( \IPS\Request::i()->after ) );
			}
		}
				
		/* Get the results */
		$results = $query->search( NULL, $stream->tags ? explode( ',', $stream->tags ) : NULL );
		
		/* Load data we need like the authors, etc */
		$results->init();
		
		/* Add in extra stuff? */
		if ( !isset( \IPS\Request::i()->id ) )
		{
			/* Is anything turned on? */
			$extra = array();
			foreach ( array( 'register', 'follow_member', 'follow_content', 'photo', 'like', 'rep_neg' ) as $k )
			{
				$key = "all_activity_{$k}";
				if ( \IPS\Settings::i()->$key )
				{
					$extra[] = $k;
				}
			}
			if ( !empty( $extra ) )
			{
				$results = $results->addExtraItems( $extra, NULL, ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->after ) ) ? \IPS\DateTime::ts( \IPS\Request::i()->after ) : NULL, ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->before ) ) ? \IPS\DateTime::ts( \IPS\Request::i()->before ) : NULL );
			}
		}
		
		/* Condensed or expanded? */
		$view = 'expanded';
		$streamID = ( \IPS\Request::i()->id ) ? \IPS\Request::i()->id : 'all';

		if ( ( isset( \IPS\Request::i()->cookie['stream_view_' . $streamID] ) and \IPS\Request::i()->cookie['stream_view_' . $streamID] == 'condensed' ) or ( isset( \IPS\Request::i()->view ) and \IPS\Request::i()->view == 'condensed' ) )
		{
			$view = 'condensed';
		}


		/* If this is an AJAX request, just show the results */
		if ( \IPS\Request::i()->isAjax() )
		{
			if( isset( \IPS\Request::i()->_changeView ) )
			{
				$output = \IPS\Theme::i()->getTemplate('streams')->stream( $stream, $results, !isset( \IPS\Request::i()->id ), $stream->sort == 'oldest' ? NULL : TRUE, $baseUrl, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );
			}
			else
			{
				$output = \IPS\Theme::i()->getTemplate('streams')->streamItems( $results, $stream->sort == 'oldest' ? NULL : TRUE, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );	
			}				

			$return = array(
				'title' => $stream->_title,
				'blurb' => $stream->blurb(),
				'count' => count( $results ),
				'results' => $output,
				'id' => ( $stream->id ) ? $stream->id : ''
			);

			\IPS\Output::i()->json( $return );			
			return;
		}
		
		/* Display - RSS */
		if ( isset( \IPS\Request::i()->rss ) )
		{
			$document = \IPS\Xml\Rss::newDocument( $baseUrl, $stream->_title, sprintf( \IPS\Member::loggedIn()->language()->get( 'stream_rss_title' ), \IPS\Settings::i()->board_name, $stream->_title ) );
			
			foreach ( $results as $result )
			{
				$result->addToRssFeed( $document );
			}
			
			\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			return;
		}
		
		/* Display - HTML */
		else
		{			
			/* What's the RSS Link? */
			$rssLink = NULL;
			if ( isset( \IPS\Request::i()->id ) )
			{
				$rssLink = \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams&id={$stream->id}", 'front', 'discover_rss' );
				if ( \IPS\Member::loggedIn()->member_id )
				{
					$rssLink = $rssLink->setQueryString( 'member', \IPS\Member::loggedIn()->member_id );
					if ( !\IPS\Member::loggedIn()->members_pass_salt )
					{
						\IPS\Member::loggedIn()->members_pass_salt = \IPS\Member::loggedIn()->generateSalt();
						\IPS\Member::loggedIn()->save();
					}
					$rssLink = $rssLink->setQueryString( 'key', md5( ( \IPS\Member::loggedIn()->members_pass_hash ?: \IPS\Member::loggedIn()->email ) . \IPS\Member::loggedIn()->members_pass_salt ) );
				}
			}
			
			/* Display */
			$output = \IPS\Theme::i()->getTemplate('streams')->stream( $stream, $results, !isset( \IPS\Request::i()->id ), $stream->sort == 'oldest' ? NULL : TRUE, $baseUrl, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );

			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('streams')->streamWrapper( $stream, $output, ( isset( \IPS\Request::i()->id ) and ( !$stream->member or $stream->member == \IPS\Member::loggedIn()->member_id ) ) ? TRUE : FALSE, $rssLink, isset( \IPS\Request::i()->id ) and $stream->member and $stream->member != \IPS\Member::loggedIn()->member_id );
		}
	}
	
	/**
	 * Create a new stream
	 *
	 * @return	void
	 */
	public function create()
	{
		$stream = new \IPS\core\Stream;
		$stream->member = \IPS\Member::loggedIn()->member_id;
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'create_new_stream' );
		\IPS\Output::i()->output = $this->_buildForm( $stream );
	}
	
	/**
	 * Copy a stream
	 *
	 * @return	void
	 */
	public function copy()
	{
		try
		{
			$stream = clone \IPS\core\Stream::load( \IPS\Request::i()->id );
			$stream->member = \IPS\Member::loggedIn()->member_id;
			$stream->save();
			$this->_rebuildStreams();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C280/3', 404, '' );
		}

		\IPS\Output::i()->redirect( $stream->url() );
	}

	/**
	 * Deletes a new stream
	 *
	 * @return	void
	 */
	public function delete()
	{
		try
		{
			$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
			if ( !$stream->member or $stream->member != \IPS\Member::loggedIn()->member_id )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C280/2', 404, '' );
		}
		
		$stream->delete();
		$this->_rebuildStreams();

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ) );
	}

	/**
	 * Build form
	 *
	 * @param	\IPS\core\Stream	$stream	The stream
	 * @return	string
	 */
	protected function _buildForm( \IPS\core\Stream $stream )
	{
		/* Build form */
		$form = new \IPS\Helpers\Form( 'select_blog', 'continue', ( $stream->id ? $stream->url() : NULL ) );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
				
		$stream->form( $form, 'Text', !$stream->id );
		$redirectAfterSave = FALSE;		
		
		/* Note if it's custom */
		if ( $stream->member && \IPS\Member::loggedIn()->member_id )
		{
			$form->hiddenValues['__custom_stream'] = TRUE;
		}
						
		/* Handle submissions */
		if ( $values = $form->values() )
		{			
			/* Update only */
			if ( !$stream->member or isset( \IPS\Request::i()->updateOnly ) )
			{
				$url = $stream->url();
				$formattedValues = $stream->formatFormValues( $values );
				
				foreach ( array( 'include_comments', 'classes', 'containers', 'ownership', 'read', 'follow', 'date_type', 'date_start', 'date_end', 'date_relative_days' ) as $k )
				{
					if ( $stream->$k != $formattedValues[ $k ] )
					{
						$url = $url->setQueryString( $k, $formattedValues[ $k ] );
					}
				}
				
				\IPS\Output::i()->redirect( $url );
			}			
			/* Update & Save */
			else
			{			
				if ( !$stream->id )
				{
					$stream->position = \IPS\Db::i()->select( 'MAX(position)', 'core_streams', array( 'member=?', \IPS\Member::loggedIn()->member_id )  )->first() + 1;
					$redirectAfterSave = TRUE;
				}
				
				if ( count( $values['stream_classes'] ) )
				{
					$values['stream_classes_type'] = 1;
				}
				
				$stream->saveForm( $stream->formatFormValues( $values ) );
				
				$this->_rebuildStreams();
	
				if( $redirectAfterSave )
				{
					\IPS\Output::i()->redirect( $stream->url() );
				}
			}
		}
		
		/* Display */
		return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'streams', 'core' ) ), $stream->id ? ( $stream->member ? 'filterUpdateForm' : 'filterDuplicateForm' ) : 'filterCreateForm' ) );
	}
	
	/**
	 * Rebuild logged in member's streams
	 *
	 * @return	void
	 */
	protected function _rebuildStreams()
	{
		$default = \IPS\Member::loggedIn()->defaultStream;
		\IPS\Member::loggedIn()->member_streams = json_encode( array( 'default' => $default, 'streams' => iterator_to_array( \IPS\Db::i()->select( 'id, title', 'core_streams', array( 'member=?', \IPS\Member::loggedIn()->member_id ) )->setKeyField('id')->setValueField('title') ) ) );
		\IPS\Member::loggedIn()->save();
	}
}
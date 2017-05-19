<?php
/**
 * @brief		Group Blogs Controller
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		03 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\modules\admin\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * groupBlogs
 */
class _blogs extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\blog\Blog';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'blogs_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'blog_blogs', \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs' ) );
		$table->langPrefix = 'blog_';

		/* Column stuff */
		$table->include = array( 'word_custom' );
		$table->mainColumn = 'word_custom';

		$table->joins = array(
			array( 'select' => 'CASE WHEN blog_blogs.blog_member_id THEN blog_blogs.blog_name ELSE w.word_custom END as word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'blogs_blog_', blog_blogs.blog_id ) AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
		);

		/* Sort stuff */
		$table->sortBy = $table->sortBy ?: 'word_custom';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		/* Search */
		$table->quickSearch = 'word_custom';
		$table->advancedSearch = array(
			'word_custom'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'blog_member_id'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			);

		$table->parsers = array(
			'blog_member_id'		=> function( $val, $row )
			{
				return \IPS\Member::load( $val )->link();
			}
		);

		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$blog = \IPS\blog\Blog::constructFromData( $row );

			return array(
				'edit'		=> array(
					'icon'	=> 'pencil',
					'title'	=> 'edit',
					'link'	=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs&do=form&id=' ) . $row['blog_id'],
					'data'	=> array(),
					'hotkey'=> 'e return'
				),
				'copy'		=> array(
					'icon'	=> 'files-o',
					'title'	=> 'copy',
					'link'	=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs&do=copy&id=' ) . $row['blog_id'],
					'data' => array()
				),
				'empty'		=> array(
					'icon'	=> 'trash-o',
					'title'	=> 'empty',
					'link'	=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs&do=delete&deleteNode=0&id=' ) . $row['blog_id'],
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('empty') ),
					'hotkey'=> 'd'
				),
				'delete'	=> array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs&do=delete&deleteNode=1&id=' ) . $row['blog_id'],
					'data' 	=> ( $blog->getContentItemCount() ) ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('delete') ) : array( 'delete' => '' ),
					'hotkey'=> 'd'
				)
			);
		};

		/* Root buttons */
		$table->rootButtons = array(
			'add'		=> array(
				'icon'		=> 'plus',
				'title'		=> 'add_blog',
				'link'		=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=blogs&do=form' ),
				'data'		=> array()
			),
		);

		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('blogs');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form()
	{
		parent::form();

		if ( \IPS\Request::i()->id )
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('edit_blog') . ': ' . \IPS\Output::i()->title;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('add_blog');
		}
	}
}
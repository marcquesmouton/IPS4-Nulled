<?php
/**
 * @brief		Facebook Profile Sync
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		13 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\ProfileSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Facebook Profile Sync
 */
class _Facebook extends ProfileSyncAbstract
{
	/** 
	 * @brief	Login handler key
	 */
	public static $loginKey = 'Facebook';
	
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'facebook-square';
			
	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	public function connected()
	{
		return ( $this->member->fb_uid and $this->member->fb_token );
	}
		
	/**
	 * Get photo
	 *
	 * @return	\IPS\Http\Url|\IPS\File|NULL
	 */
	public function photo()
	{
		try
		{
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}/picture?type=large&redirect=false" )->request()->get()->decodeJson();
			if ( isset( $response['data']['is_silhouette'] ) AND $response['data']['is_silhouette'] === false )
			{
				$file = \IPS\Http\Url::external( $response['data']['url'] )->import( 'core_Profile' );
				$photo = \IPS\Image::create( $file->contents() );
				
				if ( ( $photo->width > $photo->height ) OR ( $photo->height > $photo->width ) )
				{
					if ( $photo->width > $photo->height )
					{
						$difference = $photo->width - $photo->height;
						$photo->cropToPoints( ceil( $difference / 2 ), 0, ceil( $photo->width - ( $difference / 2 ) ), $photo->height );
						$file->replace( (string) $photo );
					}
					else if ( $photo->height > $photo->width )
					{
						$difference = $photo->height - $photo->width;
						$photo->cropToPoints( 0, ceil( $difference / 2 ), $photo->width, ceil( $photo->height - ( $difference / 2 ) ) );
						$file->replace( (string) $photo );
					}
				}
				
				return $file;
			}
			else
			{
				return NULL;
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::i( LOG_ERR )->write( "Facebook photo sync error: " . $ex->getMessage(), 'facebook' );

			return NULL;
		}
	}
	
	/**
	 * Get cover photo
	 *
	 * @return	\IPS\Http\Url|NULL
	 */
	public function cover()
	{
		$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}?access_token={$this->member->fb_token}&fields=cover" )->request()->get()->decodeJson();
		if ( isset( $response['cover'] ) )
		{
			return \IPS\Http\Url::external( $response['cover']['source'] )->import( 'core_Profile' );
		}
		return NULL;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		try
		{
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}?access_token={$this->member->fb_token}" )->request()->get()->decodeJson();
			if ( isset( $response['name'] ) )
			{
				return $response['name'];
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::i( LOG_ERR )->write( "Facebook name fetch error: " . $ex->getMessage(), 'facebook' );

			return NULL;
		}
	}
	
	/**
	 * Get status
	 *
	 * @return	\IPS\core\Statuses\Status|null
	 */
	public function status()
	{
		try
		{
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}/statuses?access_token={$this->member->fb_token}" )->request()->get()->decodeJson();
			if ( !empty( $response['data'] ) )
			{				
				$statusData = array_shift( $response['data'] );

				if( isset( $statusData['message'] ) )
				{
					$status = \IPS\core\Statuses\Status::createItem( $this->member, $this->member->ip_address, new \IPS\DateTime( $statusData['updated_time'] ) );
					$status->content = nl2br( \IPS\Text\Parser::utf8mb4SafeDecode( $statusData['message'] ), FALSE );
					return $status;
				}
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::i( LOG_ERR )->write( "Facebook status import error: " . $ex->getMessage(), 'facebook' );
		}
		return NULL;
	}
	
	/**
	 * Export Status
	 *
	 * @param	\IPS\core\Statuses\Status	$status	The status
	 * @return	void
	 */
	public function exportStatus( \IPS\core\Statuses\Status $status )
	{
		$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}/feed" )->request()->post( array( 'access_token' => $this->member->fb_token, 'message' => strip_tags( $status->content ) ) );

		if( $response->httpResponseCode <> 200 )
		{
			\IPS\Log::i( LOG_ERR )->write( "Facebook status export error: " . json_encode( $response ), 'facebook' );
		}
	}
	
	/**
	 * Disassociate
	 *
	 * @return	void
	 */
	protected function _disassociate()
	{
		$this->member->fb_uid = 0;
		$this->member->fb_token = NULL;
		$this->member->save();
	}
}
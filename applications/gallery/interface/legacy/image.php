<?php
/**
 * @brief		Gallery image download handler
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		30 May 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/gallery/interface/legacy/image.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Session\Front::i();

try
{
	/* Get file and data */
	$config		= \IPS\File::getClass( 'gallery_Images' );
	$file		= \IPS\File::get( 'gallery_Images', \IPS\Request::i()->path );

	$headers	= array_merge( \IPS\Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'inline', $file->originalFilename ), "X-Content-Type-Options" => "nosniff" ) );

	/* Send headers and print file */
	\IPS\Output::i()->sendStatusCodeHeader( 200 );
	\IPS\Output::i()->sendHeader( "Content-type: " . \IPS\File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );

	foreach( $headers as $key => $header )
	{
		\IPS\Output::i()->sendHeader( $key . ': ' . $header );
	}
	\IPS\Output::i()->sendHeader( "Content-Length: " . $file->filesize() );

	$file->printFile();
	exit;
}
catch ( \UnderflowException $e )
{
	\IPS\Output::i()->sendOutput( '', 404 );
}
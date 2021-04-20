<?php

namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Plugin\MediaPlugin as Media;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class FormHelper {

	/**
	 * Get request parameter
	 * @param $data
	 * @param $key
	 * @param int $limit_lengh
	 * @return bool|string
	 */
	public static function getField( $data, $key, $limit_lengh=500 )
	{
		if( !$data )
			$data = json_decode(file_get_contents('php://input'), true);

		if( isset($_FILES[$key]))
		{
			$upload = Media::upload($key, ['image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/zip']);

			if( is_wp_error($upload) )
				return false;

			if( is_multisite() )
				return trim(network_home_url(), '/').$upload['filename'];
			else
				return trim(home_url('/'), '/').$upload['filename'];
		}
		elseif ( !isset( $data[ $key ] ) )
		{
			return false;
		}
		else
		{
			return substr( trim(sanitize_text_field( $data[ $key ] )), 0, $limit_lengh );
		}
	}

	/**
	 * Get whole form
	 * @param array $fields
	 * @param bool $data
	 * @return array
	 */
	public static function get($fields=[], $data=false){

		$form = [];

		foreach ( $fields as $key )
		{
			$form[$key] = self::getField( $data, $key );
		}

		return $form;
	}

	/**
	 * Send form
	 * @param string $to
	 * @param string $subject
	 * @param array $fields
	 * @param array $attachements
	 * @param string $email_id
	 * @return array|\WP_Error
	 */
	public static function send($to='admin', $subject='New message from website', $fields=[], $attachements=[], $email_id='email' ){

		if( !in_array($email_id, $fields) )
			$fields[] = $email_id;

		$fields = array_merge($fields, $attachements);

		$form = self::get($fields);

		if ( is_email( $form[$email_id] ) )
		{
			if(!$to || $to=='admin')
				$to = get_option( 'admin_email' );

			$body = $subject." :\n\n";
			$attachments = [];

			foreach ( $fields as $key ) {

				if ( !$form[$key] || !file_exists( $form[$key] ) )
				$body .= ($form[$key] ? ' - ' . $key . ' : ' . $form[$key] . "\n" : '');
			}

			if ( wp_mail( $to, $subject, $body ) )
				return $form;
			else
				return new \WP_Error('send_mail', "The server wasn't able to send the email.");

		}
		else
		{
			return new \WP_Error('invalid_email', "Invalid email address. Please type a valid email address.");
		}
	}
}

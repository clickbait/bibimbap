<?php

require_once 'inc/theme-support.php';
require_once 'inc/wrapper.php';
require_once 'inc/helper-functions.php';
require_once 'inc/shortcodes.php';
require_once 'inc/scripts.php';
require_once 'inc/libraries.php';
require_once 'inc/cleanup.php';
require_once 'inc/widget-areas.php';
require_once 'inc/menus.php';

add_action( 'gform_after_submission', function ( $entry, $form ) {
	$form_id = (Int)$form['id'];
	$post_id = (Int)$entry['post_id'];

	if ( !in_array( $form_id, array( 1 )) ) { return; }

	$reply = wf()->post( $post_id );

	$board = wf()->board( $entry['5'] );

	if ( $board->exists() ) {
		$topic = wf()->type( 'topic' )->insert( array(
			'title' => $entry['1'],
			'status' => 'publish'
		));

		$reply->details->belongs_to = $topic;

		$reply->update();

		$topic->add_term( $board );

		wp_redirect( $topic->permalink() );
		exit;
	} else {
		$reply->delete();
		wp_die( 'board does not exist' ); // TODO: Prob replace this with a 404
	}

	// redirect to topic
}, 10, 2 );

<?php

add_action( 'init', function() {
	register_nav_menus(
		array(
			'main-menu' => 'Main Menu',
			'footer-menu' => 'Footer Menu'
		)
	);
});
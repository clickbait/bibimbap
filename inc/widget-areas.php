<?php

if ( function_exists('register_sidebar') ):
	register_sidebar(
		array(
			'name' => 'Footer - Stay Updated',
			'id' => 'footer_stay_updated',
			'before_widget' => false,
			'after_widget'  => false
		)
	);

	register_sidebar(
		array(
			'name' => 'Footer - Menu',
			'id' => 'footer_menu',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>'
		)
	);
endif;

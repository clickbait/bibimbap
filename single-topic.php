<div class="container">
	<div class="content">
		<div class="page-meta">
			<h2><?= the()->boards()[0]->title; ?></h2>
			<p><?= the()->boards()[0]->description; ?></p>
		</div>

		<?php $_GET['parent_id'] = the()->id; ?>
		<?= do_shortcode( '[gravityform id=2 title=false description=false]' ); ?>

		<style>
			#field_2_4 {
				display: none;
			}
		</style>

		<div id="replies">
			<?php $first = true; ?>
			<?php foreach ( the()->incoming('post_type=reply&orderby=date&order=asc') as $reply ): ?>
				<div class="reply" id="r<?= $reply->ID; ?>">
					<?php
					$image = $thumb = $reply->featured_image();

					if ( $thumb->exists() ):
						if ( $thumb->height() > 200 ) {
							$thumb = $thumb->resize( 'h=200' );
						}

						if ( $thumb->width() > 200 ) {
							$thumb = $thumb->resize( 'w=200' );
						}

					?>
					<a href="<?= $image->url(); ?>" class="image">
						<?= $thumb; ?>
					</a>

					<?php endif; ?>

					<span class="name"><?= ( $reply->details->name->is_empty() ) ? 'Anonymous' : strip_tags( $reply->details->name ); ?></span>

					<?php if ( $first ): ?>
						<h2><?= strip_tags( $reply->title ); ?></h2>
					<?php $first = false; endif; ?>

					<span class="date"> <?= human_time_diff( $reply->date(), current_time( 'timestamp' ) ); ?> ago </span>

					<a href="#r<?= $reply->ID; ?>"><?= $reply->ID; ?></a>

					<br />
					<?= make_clickable( wpautop( htmlspecialchars( $reply->content( 'raw=1' ) ) ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

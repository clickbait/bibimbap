<div class="page-meta">
	<h2><?= wf()->the_term()->title; ?></h2>
	<p><?= wf()->the_term()->description; ?></p>
</div>

<?php $_GET['board'] = wf()->the_term()->slug; ?>
<?= do_shortcode( '[gravityform id=1 title=false description=false]' ); ?>

<div id="topics">
	<?php foreach ( wf()->loop() as $the ): ?>
		<?php $op = $the->incoming('post_type=reply&orderby=date&order=asc')->first(); ?>

		<div class="topic">
			<?php if ( $op->featured_image()->exists() ): ?>
				<a href="<?= $the->permalink(); ?>">
				<?php
					$thumb = $op->featured_image();

					if ( $thumb->height() > 100 ) {
						$thumb = $thumb->resize( 'h=100' );
					}

					if ( $thumb->width() > 100 ) {
						$thumb = $thumb->resize( 'w=100' );
					}

					echo $thumb;
				?>
				</a>
			<?php endif; ?>
			<?= $the; ?>
			<?= $op->excerpt( 'length=4' ); ?>
		</div>
	<?php endforeach; ?>
</div>

<?php

?>
<?php include alias_include('thm_partials:np-head.php'); ?>

	<h1>Home / Pages</h1>
	<a href="<?= _link('@articles_list', [ 'category' => 'tech', 'slug'     => 'nexipress-core' ]); ?>">test 1</a>
	<a href="<?= _link('#self', ['pagina-2']);; ?>">test 2</a>

	<p>
		<?php if ($checks['config'] && $checks['storage']): ?>
			NexiPress is running correctly.
		<?php else: ?>
			NexiPress loaded, but environment is incomplete.
		<?php endif; ?>
	</p>

	<?php require alias_include('thm_partials:np-footer.php'); ?>

<?php include alias_include('thm_partials:np-foot.php'); //ctx_dump();
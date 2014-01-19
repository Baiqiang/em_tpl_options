<?php
!defined('EMLOG_ROOT') && exit('access deined!');
?>
<div id="tpl-options">
	<div class="tpl-options-close">&laquo;返回</div>
	<form action="<?php echo $this->url(array('template' => $template)); ?>" method="post" class="tpl-options-form">
		<?php $this->renderOptions(); ?>
	</form>
</div>
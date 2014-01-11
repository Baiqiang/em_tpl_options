<?php
if ($code !== null) {
	switch ($code) {
		case 0:
			$msgClass = 'actived';
			break;
		
		default:
			$msgClass = 'error';
			break;
	}
?>
<span class="<?php echo $msgClass; ?>"><?php echo $msg; ?></span>
<?php } ?>
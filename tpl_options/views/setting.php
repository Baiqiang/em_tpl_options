<?php
!defined('EMLOG_ROOT') && exit('access deined!');
?>
<div class="containertitle2">
	<a class="navi1" href="<?php echo $this->url(); ?>">模板列表</a>
	<a class="navi2" href="<?php echo $this->url(array('template' => $template)); ?>">模板设置</a>
	<?php include $this->view('message'); ?>
	<span id="message"></span>
</div>
<form action="<?php echo $this->url(array('template' => $template)); ?>" method="post" id="tpl-options-form">
	<div id="tpl-options">
		<?php $this->renderOptions(); ?>
	</div>
</form>
<iframe name="upload-image" id="upload-image" src="about:blank" frameborder="0" style="display:none"></iframe>
<script>
$(function() {
	var inputs = $('input[type="file"]'), message = $('#message'), optionForm = $('#tpl-options-form');
	var body = $('body'), iframe = $('iframe');
	var timer, form, input, trueInput, nameInput, loadingDom, target;
	var posting = false;
	$('.option-sort-left').each(function() {
		$(this).find('.option-sort-name:first').addClass('selected');
	});
	$('.option-sort-right').each(function() {
		$(this).find('.option-sort-option:first').addClass('selected');
	});
	$('.option-sort-name').on('click', function() {
		var that = $(this);
		if (that.is('.selected')) {
			return;
		}
		var left = that.parent(), right = left.siblings('.option-sort-right');
		left.find('.selected').removeClass('selected');
		that.addClass('selected');
		right.find('.option-sort-option').removeClass('selected').eq(that.index()).addClass('selected');
	});
	$('.option-sort-select').on('change', function() {
		var that = $(this);
		var right = that.parent().siblings('.option-sort-right');
		right.find('.option-sort-option').removeClass('selected').eq(that.find('option:selected').index()).addClass('selected');
	});
	$('.option-rich-text').each(function(i) {
		var that = $(this);
		if (that.attr('id') === undefined) {
			that.attr('id', 'option-rich-text-' + i);
		}
		loadEditor(that.attr('id'));
	});
	optionForm.on('submit', function() {
		var that = $(this);
		$.ajax({
			url: that.attr('action'),
			type: 'post',
			data: that.serialize(),
			dataType: 'json',
			// beforeSend: loading,
			success: function(data) {
				showMsg(data.code, data.msg);
			},
			error: function() {
				showMsg(1, '网络异常');
			},
			complete: function() {
				// loading(false);
			}
		});
		return false;
	}).find('input, textarea').on('change', function() {
		optionForm.trigger('submit');
	});
	if ($('.option-rich-text').length > 0) {
		window.setTimeout(function() {
			for (var id in editorMap) {
				editorMap[id].container[0].style.width = '';
			}
		}, 100);
	}
	if (inputs.length > 0) {
		loadingDom = $('<div />').appendTo(body);
		trueInput = inputs.first().clone().attr('name', 'image').css({
			position: 'absolute',
			margin: 0,
			visibility: 'hidden'
		}).on('change', function() {
			loading();
			target = input.data('target');
			nameInput.val(target);
			form.submit();
		}).on('mouseleave', function() {
			trueInput.css('visibility', 'hidden');
			input.css('visibility', 'visible');
		});
		form = $('<form />', {
			action: '<?php echo $this->url(array("do"=>"upload")); ?>',
			target: 'upload-image',
			enctype: 'multipart/form-data',
			method: 'post'
		}).append(
			trueInput,
			nameInput = $('<input type="hidden" name="target">'),
			'<input type="hidden" name="template" value="<?php echo $template; ?>">'
		).appendTo(body);
		inputs.on('mouseenter', function() {
			input = $(this);
			trueInput.css(input.offset());
			input.css('visibility', 'hidden');
			trueInput.css('visibility', 'visible');
		});
	}
	window.setImage = function(src, code, msg) {
		if (code == 0) {
			$('[name="' + target + '"]').val(src).trigger('change');
			$('[data-name="' + target + '"]').attr('href', src).find('img').attr('src', src);
		} else {
			alert('上传失败：' + msg)
		}
		trueInput.val('');
		target = '';
		loading(false);
	};
	function loading(enable) {
		if (enable === undefined) {
			enable = true;
		}
		if (enable) {
			loadingDom.addClass('loading');
		} else {
			loadingDom.removeClass('loading');
		}
	}
	function showMsg(code, msg) {
		message.text(msg).show();
		if (code == 0) {
			message.attr('class', 'actived');
			if (timer) {
				window.clearTimeout(timer);
			}
			timer = window.setTimeout(function() {
				message.hide();
			}, 2600);
		} else {
			message.attr('class', 'error');
		}
	}
});
</script>
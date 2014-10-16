$(function() {
  //初始化变量
  var tplOptions = window.tplOptions;
  var body = $('body');
  var iframe = $('<iframe name="upload-image" src="about:blank" style="display:none"/>').appendTo(body);
  var optionArea = $('<div/>').appendTo($('#container')).addClass(attr('area')).slideUp();
  var templateContent = $('#container .containertitle2:last, #container table');
  var loadingDom = $('<div />').appendTo(body);
  var message = $('<span />').appendTo($('.containertitle2:first')).css('position', 'fixed');
  var timer, input, targetInput, target, templateInput, template;
  var trueInput = $('<input type="file" name="image">').css({
    position: 'absolute',
    margin: 0,
    visibility: 'hidden'
  }).on('change', function() {
    loading();
    target = input.data('target');
    targetInput.val(target);
    templateInput.val(template);
    form.submit();
  }).on('mouseleave', function() {
    trueInput.css('visibility', 'hidden');
    input.css('visibility', 'visible');
  });
  var form = $('<form id="upload-form" target="upload-image" />').append(
    trueInput,
    targetInput = $('<input type="hidden" name="target">'),
    templateInput = $('<input type="hidden" name="template">')
  ).appendTo(body).attr({
    action: tplOptions.uploadUrl,
    target: 'upload-image',
    enctype: 'multipart/form-data',
    method: 'post'
  });
  //插入设置按钮
  for (var tpl in tplOptions.templates) {
    (function(tpl) {
      var td = $('.adm_tpl_list td a[href*="&tpl=' + tpl + '&"]').parent();
      $('<span>设置</span>').insertBefore(td.find('span')).addClass(attr('setting')).data('template', tpl);
    })(tpl);
  }
  //当前模板
  (function() {
    var currentTemplate = $('table:first img').attr('src').match(/\/templates\/(.*?)\/preview.jpg/)[1];
    if (tplOptions.templates[currentTemplate]) {
      $('<br>').insertBefore($('<span>设置</span>').appendTo($('table:first td:last')).addClass(attr('setting')).data('template', currentTemplate));
    }
  })();
  //绑定事件
  body.on('click', '.' + attr('setting'), function() {
    $.ajax({
      url: tplOptions.baseUrl,
      data: {
        template: $(this).data('template')
      },
      cache: false,
      beforeSend: function() {
        loading();
        editorMap = {};
      },
      success: function(data) {
        templateContent.slideUp(500, function() {
          optionArea.html(data).slideDown();
          window.setTimeout(function() {
            initOptionSort();
            initRichText();
            loading(false);
          }, 0);
        });
      }
    });
  }).on('click', '.tpl-options-close', function() {
    optionArea.slideUp(500, function() {
      templateContent.slideDown();
    });
  }).on('click', '.option-sort-name', function() {
    var that = $(this);
    if (that.is('.selected')) {
      return;
    }
    var left = that.parent(),
      right = left.siblings('.option-sort-right');
    left.find('.selected').removeClass('selected');
    that.addClass('selected');
    right.find('.option-sort-option').removeClass('selected').eq(that.index()).addClass('selected');
  }).on('change', '.option-sort-select', function() {
    var that = $(this);
    var right = that.parent().siblings('.option-sort-right');
    right.find('.option-sort-option').removeClass('selected').eq(that.find('option:selected').index()).addClass('selected');
  }).on('mouseenter', '.tpl-options-form input[type="file"]', function() {
    input = $(this);
    trueInput.css(input.offset());
    input.css('visibility', 'hidden');
    trueInput.css('visibility', 'visible');
  }).on('submit', 'form.tpl-options-form', function() {
    var that = $(this);
    $.ajax({
      url: that.attr('action'),
      type: 'post',
      data: that.serialize(),
      cache: false,
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
  }).on('change', '.tpl-options-form input, .tpl-options-form textarea', function() {
    $('form.tpl-options-form').trigger('submit');
  });
  //定义方法
  var initRichText = (function() {
    var num = 0;
    return function() {
      $('.option-rich-text').each(function() {
        var that = $(this);
        if (that.attr('id') === undefined) {
          that.attr('id', 'option-rich-text-' + (num++));
        }
        loadEditor(that.attr('id'));
      });
      window.setTimeout(function() {
        for (var id in editorMap) {
          editorMap[id].container[0].style.width = '';
        }
      }, 100);
    }
  })();
  window.setImage = function(src, path, code, msg) {
    if (code == 0) {
      $('[name="' + target + '"]').val(path).trigger('change');
      $('[data-name="' + target + '"]').attr('href', src).find('img').attr('src', src);
    } else {
      alert('上传失败：' + msg)
    }
    trueInput.val('');
    target = '';
    loading(false);
  };

  function initOptionSort() {
    $('.option-sort-left').each(function() {
      $(this).find('.option-sort-name:first').addClass('selected');
    });
    $('.option-sort-right').each(function() {
      $(this).find('.option-sort-option:first').addClass('selected');
    });
  }

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
    message.text(msg).css('display', '');
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

  function attr(name) {
    return tplOptions.prefix + name;
  }

  function loadEditor(id) {
    editorMap[id] = editorMap[id] || KindEditor.create('#' + id, {
      resizeMode: 1,
      allowUpload: false,
      allowImageUpload: false,
      allowFlashUpload: false,
      allowPreviewEmoticons: false,
      filterMode: false,
      afterChange: (function() {
        var t, i = 0;
        return function() {
          var that = this;
          if (t) {
            window.clearTimeout(t);
          }
          if (i++ > 0) {
            t = window.setTimeout(function() {
              that.sync();
              $(that.srcElement[0]).trigger('change');
            }, 2000);
          }
        }
      })(),
      urlType: 'domain',
      items: ['bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'hilitecolor', 'fontname', 'fontsize', 'lineheight', 'removeformat', 'plainpaste', 'quickformat', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'justifyleft', 'justifycenter', 'justifyright', 'link', 'unlink', 'image', 'flash', 'table', 'emoticons', 'code', 'fullscreen', 'source', '|', 'about']
    });
  }
});

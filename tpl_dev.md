模板开发规范
--------------

###如何让模板能被插件识别？

在模板目录里放入*options.php*，内容格式如下即可，可以**任意**增加设置项，注意$options变量和注释：

```php
<?php
/*@support tpl_options*/
!defined('EMLOG_ROOT') && exit('access deined!');
$options = array(
	'sidebar' => array(
		'type' => 'radio',
		'name' => '侧边栏位置',
		'values' => array(
			'left' => '左边',
			'right' => '右边'
		),
		'default' => 'right',
	),
	'sortIcon' => array(
		'type' => 'image',
		'name' => '分类图标设置',
		'values' => array(
			TEMPLATE_URL . 'images/star.png',
		),
		'depend' => 'sort',
		'unsorted' => true,
		'description' => '给不同的分类设置不一样的小icon，以20×20为宜',
	),
);
```

###options.php里，每个元素都该写什么？

如上所示，*$options*数组里，key为设置项的id，而value是一个数组，数组里包含若干个元素。其中type属性和name属性必选，name是设置项名字，而type用来指定设置项的类型，支持的类型如下：

> - radio: 单选按钮
> - checkbox: 复选按钮
> - text: 文本
> - image: 图片
> - page: 页面
> - sort: 分类
> - tag: 标签

1. 对于所有类型，default属性用于指定默认值，当没有指定default时，使用values里第一个值，若都没有指定，则会使用奇怪的默认值。
2. 对于radio和chexkbox，values属性用来设置各个按钮的值和显示名称。
3. 除sort外，均可以指定depend为sort，表示该选项可以根据不同的分类设置不同的值，当指定depend为sort时，可选unsorted属性，为true时
4. 表示包括未分类，为false不包括，默认为true。
5. description属性可选，用以描述该选项。
6. 若type为text，可设置multi属性为true，表示多行文本，即input和textarea的区别，可选属性rich用以支持富文本，若设置该值，将加载编辑器。

###模板里如何调用设置项

插件提供简单方法*_g($key)*，如上示例，可以使用*_g('sidebar')*来获取侧边栏的设置，取到的值将为0或者1，使用*_g('sortIcon')*来获取分类icon的全部设置，以分类id为key的数组，使用*_g('sortIcon.1')*来获取分类id为1（如果存在）的sortIcon。需要注意的是，对于类型为page的，将取到页面id，类型为sort的，将取到分类id，类型为tag的，将取到标签名。

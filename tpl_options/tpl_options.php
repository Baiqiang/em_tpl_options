<?php

/*
Plugin Name: 模板设置
Version: -e^iπ
Plugin URL: https://github.com/Baiqiang/em_tpl_options
Description: 为支持的模板设置参数。
ForEmlog: 5.2.0+
Author: 奇遇
Author URL: http://www.qiyuuu.com
*/
!defined('EMLOG_ROOT') && exit('access deined!');

/**
 * 模板设置类
 */
class TplOptions {

	//插件标识
	const ID = 'tpl_options';
	const NAME = '模板设置';

	//数据表前缀
	private $_prefix = 'tpl_options_';

	//数据表
	private $_tables = array(
		'data',
	);

	//运行上传的文件类型
	private $_imageTypes = array(
		'gif',
		'jpg',
		'jpeg',
		'png'
	);

	//实例
	private static $_instance;

	//是否初始化
	private $_inited = false;

	//模板参数
	private $_templateOptions;

	//从模板读取经过处理的原始设置项
	private $_options;

	//支持的参数类型
	private $_types;

	//数据为数组的类型
	private $_arrayTypes = array();

	//数据库连接实例
	private $_db;

	//插件模板目录
	private $_view;

	//插件前端资源路径
	private $_assets;

	//当前模板
	private $_currentTemplate;

	//是否ajax请求
	private $_isAjax = false;

	/**
	 * 单例入口
	 * @return TplOptions
	 */
	public static function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 私有构造函数，保证单例
	 */
	private function __construct() {
	}

	/**
	 * 初始化函数
	 * @return void
	 */
	public function init() {
		if ($this->_inited === true) {
			return;
		}
		$this->_inited = true;

		//初始化各个数据表名
		$tables = array();
		foreach ($this->_tables as $name => $table) {
			$tables[$table] = $this->getTableName($table);
		}
		$this->_tables = $tables;

		//初始化模板设置类型
		$this->_types = array(
			'radio' => array(
				'name' => '单选按钮',
				'allowMulti' => false,
			),
			'checkbox' => array(
				'name' => '复选按钮',
				'allowMulti' => true,
			),
			'text' => array(
				'name' => '文本',
				'allowMulti' => true,
				'allowRich' => true,
			),
			'image' => array(
				'name' => '图片',
				'allowMulti' => false,
			),
			'page' => array(
				'name' => '页面',
				'allowMulti' => true,
			),
			'sort' => array(
				'name' => '分类',
				'allowMulti' => true,
				'allowDepend' => true,
			),
			'tag' => array(
				'name' => '标签',
				'allowMulti' => true,
			),
		);
		$this->_arrayTypes = array(
			'checkbox',
			'tag',
			'sort',
			'page',
		);
		$this->_isAjax = $this->arrayGet($_SERVER, 'HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';

		//设置模板目录
		$this->_view = dirname(__FILE__) . '/views/';
		$this->_assets = BLOG_URL . 'content/plugins/' . self::ID . '/assets/';

		//注册各个钩子
		addAction('adm_sidebar_ext', array(
			$this,
			'hookAdminSidebar'
		));
		addAction('data_prebakup', array(
			$this,
			'hookDataPreBackup'
		));
		addAction('adm_head', array(
			$this,
			'hookAdminHead'
		));
	}

	/**
	 * 后台侧边栏
	 * @return void
	 */
	public function hookAdminSidebar() {
		printf('<div class="sidebarsubmenu" id="%s"><a href="%s">%s</a></div>', self::ID, $this->url(), self::NAME);
	}

	/**
	 * 备份数据表
	 * @return void
	 */
	public function hookDataPreBackup() {
		global $tables;
		$prefixLen = strlen(DB_PREFIX);
		foreach ($this->getTable() as $table) {
			$tables[] = substr($table, $prefixLen);
		}
	}

	/**
	 * 头部，如css文件
	 * @return void
	 */
	public function hookAdminHead() {
		echo sprintf('<link rel="stylesheet" href="%s">', $this->_assets . 'main.css');
		echo '<script charset="utf-8" src="./editor/kindeditor.js"></script>';
		echo '<script charset="utf-8" src="./editor/lang/zh_CN.js"></script>';
	}

	/**
	 * 获取数据表
	 * @param mixed $table 表名缩写，可选，若不设置则返回所有表，否则返回对应表
	 * @return mixed 返回数组或字符串
	 */
	public function getTable($table = null) {
		return $table === null ? $this->_tables : (isset($this->_tables[$table]) ? $this->_tables[$table] : '');
	}

	/**
	 * 获取数据表名
	 * @param string $table 表名缩写
	 * @return string 表全名
	 */
	private function getTableName($table) {
		return DB_PREFIX . $this->_prefix . $table;
	}

	/**
	 * 获取模板参数数据，默认获取当前模板
	 * @param mixed $template 模板名称，可选
	 * @return array 模板参数
	 */
	public function getTemplateOptions($template = null) {
		if ($template === null) {
			$template = Option::get('nonce_templet');
		}
		if (isset($this->_templateOptions[$template])) {
			return $this->_templateOptions[$template];
		}
		$_data = $this->queryAll('data', array(
			'template' => $template,
		));
		$templateOptions = array();
		$options = $this->getTemplateDefinedOptions($template);
		foreach ($_data as $row) {
			extract($row);
			$data = unserialize($data);
			if (!isset($options[$name])) {
				$options[$name] = array();
			}
			switch ($depend) {
				case 'sort':
					$sorts = $this->getSorts(true);
					if (!is_array($data)) {
						$data = array();
					}
					foreach ($sorts as $sort) {
						if (!isset($data[$sort['sid']])) {
							$data[$sort['sid']] = $this->getOptionDefaultValue($options[$name], $template);
						}
					}
					break;
			}
			$templateOptions[$name] = $data;
		}
		return $this->_templateOptions[$template] = $templateOptions;
	}

	/**
	 * 设置模板参数数据
	 * @param string $template 模板名称
	 * @param array $options 模板参数
	 * @return boolean
	 */
	public function setTemplateOptions($template, $options) {
		if ($options === array()) {
			return true;
		}
		$data = array();
		foreach ($options as $name => $option) {
			$data[] = array(
				'template' => $template,
				'name' => $name,
				'depend' => $option['depend'],
				'data' => serialize($option['data']),
			);
		}
		return $this->insert('data', $data, true);
	}

	/**
	 * 获取所有分类
	 * @param boolean $unsorted 是否获取未分类
	 * @return array
	 */
	private function getSorts($unsorted = false) {
		$sorts = Cache::getInstance()->readCache('sort');
		if ($unsorted) {
			array_unshift($sorts, array(
				'sid' => - 1,
				'sortname' => '未分类',
				'lognum' => 0,
				'children' => array(),
			));
		}
		return $sorts;
	}

	/**
	 * 获取数据库连接
	 * @return MySql 数据库连接实例
	 */
	public function getDb() {
		return $this->_db === null ? $this->_db = MySql::getInstance() : $this->_db;
	}

	/**
	 * 从表中查询出所有数据
	 * @param string $table 表名缩写
	 * @param mixed $condition 字符串或数组条件
	 * @return array 结果数据
	 */
	private function queryAll($table, $condition = '', $select = '*') {
		$table = $this->getTable($table) ? $this->getTable($table) : DB_PREFIX . $table;
		$subSql = $this->buildQuerySql($condition);
		$sql = "SELECT $select FROM `$table`";
		if ($subSql) {
			$sql .= " WHERE $subSql";
		}
		$query = $this->getDb()->query($sql);
		$data = array();
		while ($row = $this->getDb()->fetch_array($query)) {
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * 将数据插入数据表
	 * @param string $table 表名缩写
	 * @param array $data 数据
	 * @return array 结果数据
	 */
	private function insert($table, $data, $replace = false) {
		$table = $this->getTable($table);
		$subSql = $this->buildInsertSql($data);
		if ($replace) {
			$sql = "REPLACE INTO `$table`";
		} else {
			$sql = "INSERT INTO `$table`";
		}
		$sql .= $subSql;
		return $this->getDb()->query($sql) !== false;
	}

	/**
	 * 根据条件构造WHERE子句
	 * @param mixed $condition 字符串或数组条件
	 * @return string 根据条件构造的查询子句
	 */
	private function buildQuerySql($condition) {
		if (is_string($condition)) {
			return $condition;
		}
		$subSql = array();
		foreach ($condition as $key => $value) {
			if (is_string($value)) {
				$value = mysql_real_escape_string($value);
				$subSql[] = "(`$key`='$value')";
			} elseif (is_array($value)) {
				$subSql[] = "(`$key` IN (" . $this->implodeSqlArray($value) . '))';
			}
		}
		return implode(' AND ', $subSql);
	}

	/**
	 * 根据数据构造INSERT/REPLACE INTO子句
	 * @param array $data 数据
	 * @return string 根据数据构造的子句
	 */
	private function buildInsertSql($data) {
		$subSql = array();
		if (array_key_exists(0, $data)) {
			$keys = array_keys($data[0]);
		} else {
			$keys = array_keys($data);
			$data = array(
				$data
			);
		}
		foreach ($data as $key => $value) {
			$subSql[] = '(' . $this->implodeSqlArray($value) . ')';
		}
		$subSql = implode(',', $subSql);
		$keys = '(`' . implode('`,`', $keys) . '`)';
		$subSql = "$keys VALUES $subSql";
		return $subSql;
	}

	/**
	 * 将数组展开为可以供SQL使用的字符串
	 * @param array $data 数据
	 * @return string 形如('value1', 'value2')的字符串
	 */
	private function implodeSqlArray($data) {
		return implode(',', array_map(create_function('$val', 'return "\'" . mysql_real_escape_string($val) . "\'";'), $data));
	}

	/**
	 * 插件设置函数
	 * @return void
	 */
	public function setting() {
		$do = $this->arrayGet($_GET, 'do');
		$template = $this->arrayGet($_GET, 'template');
		$code = $this->arrayGet($_GET, 'code');
		$msg = $this->arrayGet($_GET, 'msg');
		if ($do != '') {
			if ($do == 'upload' && isset($_FILES['image'])) {
				$file = $_FILES['image'];
				$target = $this->arrayGet($_POST, 'target');
				$template = $this->arrayGet($_POST, 'template');
				$result = $this->upload($template, $file, $target);
				extract($result);
				if ($path) {
					$src = BLOG_URL . substr($path, 3);
				} else {
					$src = '';
				}
				ob_clean();
				include $this->view('upload');
				exit;
			}
		} elseif ($template === null) {
			$templates = $this->getTemplates();
			$currentTemplate = Option::get('nonce_templet');
			if (isset($templates[$currentTemplate]['support']) && $templates[$currentTemplate]['support']) {
				$toSetTemplate = $currentTemplate;
			} elseif (($firstTemplate = current($templates)) && $firstTemplate['support']) {
				$toSetTemplate = $this->arrayGet(array_keys($templates), 0);
			} else {
				$toSetTemplate = '';
			}
			include $this->view('templates');
		} else {
			if (!is_dir(TPLS_PATH . $template)) {
				emDirect($this->url(array(
					'code' => 1,
					'msg' => '该模板不存在',
				)));
			}
			$options = $this->getTemplateDefinedOptions($template);
			if ($options === false) {
				emDirect($this->url(array(
					'code' => 1,
					'msg' => '该模板不支持本插件设置',
				)));
			}
			$this->_currentTemplate = $template;
			$storedOptions = $this->getTemplateOptions($template);
			foreach ($options as $name => & $option) {
				if (!is_array($option) || !isset($option['name']) || !isset($option['type']) || !isset($this->_types[$option['type']])) {
					unset($options[$name]);
					continue;
				}
				$option['id'] = $name;
				$option['value'] = $this->getOptionValue($option, $storedOptions, $template);
			}
			$this->_options = $options;
			if (!empty($_POST)) {
				$newOptions = array();
				foreach ($_POST as $name => $data) {
					if (!isset($options[$name])) {
						continue;
					}
					$depend = isset($options[$name]['depend']) ? $options[$name]['depend'] : '';
					$type = isset($options[$name]['type']) ? $options[$name]['type'] : '';
					switch ($depend) {
						case 'sort':
							$sorts = $this->getSorts(true);
							if (!is_array($data)) {
								$data = array();
							}
							foreach ($sorts as $sort) {
								$sid = $sort['sid'];
								if (!isset($data[$sid]) || (in_array($type, $this->_arrayTypes) && !is_array($data[$sid]))) {
									$data[$sid] = $this->getOptionDefaultValue($options[$name], $template);
								}
							}
							break;
					}
					if (in_array($type, $this->_arrayTypes) && !is_array($data)) {
						$data = array();
					}
					$newOptions[$name] = array(
						'depend' => $depend,
						'data' => $data,
					);
				}
				$result = $this->setTemplateOptions($template, $newOptions);
				$code = $result ? 0 : 1;
				$data = array(
					'template' => $template,
					'code' => $result ? 0 : 1,
					'msg' => '保存模板设置' . ($result ? '成功' : '失败'),
				);
				if ($this->_isAjax) {
					$this->jsonReturn($data);
				} else {
					emDirect($this->url($data));
				}
			}
			include $this->view('setting');
		}
		include $this->view('footer');
	}

	/**
	 * 上传文件
	 * @param string $template 模板
	 * @param array $file 上传的文件
	 * @param string $target 目标
	 * @return array 上传结果信息
	 */
	private function upload($template, $file, $target) {
		$result = array(
			'code' => 0,
			'msg' => '',
			'name' => $file['name'],
			'size' => $file['size'],
			'path' => '',
		);
		if ($file['error'] == 1) {
			$result['code'] = 100;
			$result['msg'] = '文件大小超过系统限制';
			return $result;
		} elseif ($file['error'] > 1) {
			$result['code'] = 101;
			$result['msg'] = '上传文件失败';
			return $result;
		}
		$extension = getFileSuffix($file['name']);
		if (!in_array($extension, $this->_imageTypes)) {
			$result['code'] = 102;
			$result['msg'] = '错误的文件类型';
			return $result;
		}
		if ($file['size'] > Option::UPLOADFILE_MAXSIZE) {
			$result['code'] = 103;
			$result['msg'] = '文件大小超出emlog的限制';
			return $result;
		}
		$uploadPath = Option::UPLOADFILE_PATH . self::ID . "/$template/";
		$fileName = rtrim(str_replace(array(
			'[',
			']'
		), '.', $target), '.') . '.' . $extension;
		$attachpath = $uploadPath . $fileName;
		$result['path'] = $attachpath;
		if (!is_dir($uploadPath)) {
			@umask(0);
			$ret = @mkdir($uploadPath, 0777, true);
			if ($ret === false) {
				$result['code'] = 104;
				$result['msg'] = '创建文件上传目录失败';
				return $result;
			}
		}
		if (@is_uploaded_file($file['tmp_name'])) {
			if (@!move_uploaded_file($file['tmp_name'], $attachpath)) {
				@unlink($tmpFile);
				$result['code'] = 105;
				$result['msg'] = '上传失败。文件上传目录(content/uploadfile)不可写';
				return $result;
			}
			@chmod($attachpath, 0777);
		}
		return $result;
	}

	/**
	 * 获取设置项的值
	 * @param array $option 模板设置项
	 * @param array $storedOptions 存储的模板设置项
	 * @param string $template
	 * @return mixed
	 */
	private function getOptionValue(&$option, $storedOptions, $template) {
		if (isset($storedOptions[$option['id']])) {
			return $storedOptions[$option['id']];
		}
		return $this->getOptionDefaultValue($option, $template);
	}

	/**
	 * 获取模板设置项的值
	 * @param array $option 模板设置项
	 * @param string $template
	 * @return mixed
	 */
	private function getOptionDefaultValue(&$option, $template) {
		if (isset($option['default'])) {
			$default = $option['default'];
		} else {
			switch ($option['type']) {
				case 'radio':
					if (!isset($option['values']) || !is_array($option['values'])) {
						$option['values'] = array(
							0 => '否',
							1 => '是'
						);
					}
					$default = reset($option['values']);
					break;

				case 'checkbox':
					if (!isset($option['values']) || !is_array($option['values'])) {
						$option['values'] = array();
					}
					$default = $option['values'];
					break;

				case 'text':
				case 'image':
					if (!isset($option['values']) || !is_array($option['values'])) {
						$option['values'] = array();
					}
					$default = reset($option['values']);
					break;

				case 'page':
				case 'sort':
				case 'tag':
					$default = array();
					break;

				default:
					return null;
			}
		}
		return $this->replacePath($default, $template);
	}

	/**
	 * 替换设置项里的url
	 * @param mixed $value
	 * @param string $template
	 * @return mixed
	 */
	private function replacePath($value, $template) {
		$replace = array(
			TEMPLATE_URL => TPLS_URL . $template . '/',
		);
		if (is_string($value)) {
			return strtr($value, $replace);
		} elseif (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->replacePath($val, $template);
			}
			return $value;
		} else {
			return $value;
		}
	}

	/**
	 * 渲染设置页面的设置项
	 * @return void
	 */
	private function renderOptions() {
		foreach ($this->_options as $option) {
			$method = 'render' . ucfirst($option['type']);
			$this->$method($option);
		}
	}

	/**
	 * 渲染模板设置
	 * @return void
	 */
	private function renderByTpl($option, $tpl, $loopValues = true, $placeholder = true) {
		echo '<div class="option">';
		echo '<h4 class="option-name">', $this->encode($option['name']), '</h4>';
		if (!empty($option['description'])) {
			echo '<div class="option-description">', $option['description'], '</div>';
		}
		echo '<div class="option-body">';
		$depend = isset($option['depend']) ? $option['depend'] : '';
		switch ($depend) {
			case 'sort':
				$unsorted = isset($option['unsorted']) ? $option['unsorted'] : true;
				$sorts = $this->getSorts($unsorted);
				if (!is_array($option['value'])) {
					$option['value'] = array();
				}
				echo '<div class="option-sort" data-option-name="', $option['name'], '">';
				echo '<div class="option-sort-left">';
				if (count($sorts) < 8) {
					foreach ($sorts as $sort) {
						echo '<div class="option-sort-name">';
						echo $sort['sortname'];
						echo '</div>';
					}
				} else {
					echo '<select class="option-sort-select">';
					foreach ($sorts as $sort) {
						echo sprintf('<option value="%s">%s</option>', $sort['sortname'], $sort['sortname']);
					}
					echo '</select>';
				}
				echo '</div>';
				echo '<div class="option-sort-right">';
				foreach ($sorts as $sort) {
					$sid = $sort['sid'];
					echo '<div class="option-sort-option">';
					if (!isset($option['value'][$sid])) {
						$option['value'][$sid] = $this->getOptionDefaultValue($option, $this->_currentTemplate);
					}
					if ($loopValues) {
						if ($placeholder) {
							echo sprintf('<input type="hidden" name="%s" value="">', $option['id'] . "[{$sid}]");
						}
						foreach ($option['values'] as $value => $label) {
							echo strtr($tpl, array(
								'{name}' => $option['id'] . "[{$sid}]",
								'{value}' => $this->encode($value),
								'{label}' => $label,
								'{checked}' => $this->getCheckedString($value, $option['value'][$sid]),
							));
						}
					} else {
						echo strtr($tpl, array(
							'{name}' => $option['id'] . "[{$sid}]",
							'{value}' => $this->encode($option['value'][$sid]),
							'{label}' => '',
							'{checked}' => '',
							'{rich}' => $this->getRichString($option),
						));
					}
					echo '</div>';
				}
				echo '</div>';
				echo '<div class="clearfix"></div>';
				echo '</div>';
				break;

			default:
				if ($loopValues) {
					if ($placeholder) {
						echo sprintf('<input type="hidden" name="%s" value="">', $option['id']);
					}
					foreach ($option['values'] as $value => $label) {
						echo strtr($tpl, array(
							'{name}' => $option['id'],
							'{value}' => $this->encode($value),
							'{label}' => $label,
							'{checked}' => $this->getCheckedString($value, $option['value']),
						));
					}
				} else {
					echo strtr($tpl, array(
						'{name}' => $option['id'],
						'{value}' => $this->encode($option['value']),
						'{label}' => '',
						'{checked}' => '',
						'{rich}' => $this->getRichString($option),
					));
				}
		}
		echo '</div></div>';
	}

	/**
	 * @param mixed $value
	 * @param mixed $optionvalue
	 * @return string
	 */
	private function getCheckedString($value, $optionValue) {
		return is_array($optionValue) && in_array($value, $optionValue) || $value == $optionValue ? ' checked="checked"' : '';
	}

	/**
	 * @param array $option
	 * @return string
	 */
	private function getRichString($option) {
		return isset($option['rich']) && isset($this->_types[$option['type']]['allowRich']) ? ' option-rich-text' : '';
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderRadio($option) {
		$tpl = '<label><input type="radio" name="{name}" value="{value}"{checked}> {label}</label>';
		$this->renderByTpl($option, $tpl);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderCheckbox($option) {
		$tpl = '<label><input type="checkbox" name="{name}[]" value="{value}"{checked}> {label}</label>';
		$this->renderByTpl($option, $tpl);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderText($option) {
		if (isset($option['multi']) && $option['multi']) {
			$tpl = '<textarea name="{name}" rows="8" class="option-textarea{rich}">{value}</textarea>';
		} else {
			$tpl = '<input type="text" name="{name}" value="{value}"><br>';
		}
		$this->renderByTpl($option, $tpl, false);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderImage($option) {
		$tpl = '<span class="image-tip">友情提示：选择文件后将会立刻上传覆盖原图</span><br><a href="{value}" target="_blank" data-name="{name}"><img src="{value}"></a><br><input type="file" accept="image/*" data-target="{name}"><input type="hidden" name="{name}" value="{value}">';
		$this->renderByTpl($option, $tpl, false);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderPage($option) {
		$pages = $this->queryAll('blog', array(
			'type' => 'page',
			'hide' => 'n',
		), 'gid, title');
		$values = array();
		foreach ($pages as $page) {
			$values[$page['gid']] = $this->encode($page['title']);
		}
		$option['values'] = $values;
		$this->renderCheckbox($option);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderSort($option) {
		if (isset($option['depend']) && $option['depend'] == 'sort') {
			unset($option['depend']);
		}
		$sorts = $this->getSorts();
		$values = array();
		foreach ($sorts as $sid => $sort) {
			$values[$sid] = $sort['sortname'];
		}
		$option['values'] = $values;
		$this->renderCheckbox($option);
	}

	/**
	 * @param array $option
	 * @return void
	 */
	private function renderTag($option) {
		$tags = Cache::getInstance()->readCache('tags');
		$values = array();
		foreach ($tags as $tag) {
			$values[$tag['tagname']] = "${tag['tagname']} (${tag['usenum']})";
		}
		$option['values'] = $values;
		$this->renderCheckbox($option);
	}

	/**
	 * 转义字符串，防止悲剧
	 * @param string $value
	 * @return string
	 */
	private function encode($value) {
		return htmlspecialchars($value);
	}

	/**
	 * 获取支持的模板
	 * @return array
	 */
	private function getTemplates() {
		$handle = @opendir(TPLS_PATH);
		if ($handle === false) {
			return array();
		}
		$templates = array();
		while ($file = @readdir($handle)) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (@file_exists($headerFile = TPLS_PATH . $file . '/header.php')) {
				$tplData = file_get_contents($headerFile);
				$template = array();
				preg_match("/Template Name:([^\r\n]+)/i", $tplData, $name);
				$template['name'] = isset($name[1]) ? trim($name[1]) : $file;
				$template['file'] = $file;
				$template['preview'] = $this->getTemplatePreview($file);
				$template['support'] = $this->getTemplateDefinedOptions($file) !== false;
				$templates[$file] = $template;
			}
		}
		closedir($handle);
		uasort($templates, array(
			$this,
			'sortTemplate'
		));
		return $templates;
	}

	/**
	 * 给模板排序
	 * @param array $templateA
	 * @param array $templateB
	 * @return int
	 */
	private function sortTemplate($templateA, $templateB) {
		if (!$templateA['support']) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * 获取模板缩略图url
	 * @param string $template 模板
	 * @return string
	 */
	private function getTemplatePreview($template) {
		if (is_file(TPLS_PATH . $template . '/preview.jpg')) {
			return TPLS_URL . $template . '/preview.jpg';
		}
		return $this->_assets . 'preview.jpg';
	}

	/**
	 * 获取模板参数配置
	 * @param string $optionFile
	 * @return mixed false表示不支持本插件
	 */
	private function getTemplateDefinedOptions($template) {
		if (!is_file($optionFile = TPLS_PATH . $template . '/options.php')) {
			return false;
		}
		include $optionFile;
		if (!isset($options) || !is_array($options)) {
			return false;
		}
		if (strpos(file_get_contents($optionFile), '@support tpl_options') !== false) {
			return $options;
		}
		return false;
	}

	/**
	 * 获取模板文件
	 * @param string $view 模板名字
	 * @param string $ext 模板后缀，默认为.php
	 * @return string 模板文件全路径
	 */
	public function view($view, $ext = '.php') {
		return $this->_view . $view . $ext;
	}

	/**
	 * 根据参数构造url
	 * @param array $params
	 * @return string
	 */
	public function url($params = array()) {
		$baseUrl = './plugin.php?plugin=' . self::ID;
		$url = http_build_query($params);
		if ($url === '') {
			return $baseUrl;
		} else {
			return $baseUrl . '&' . $url;
		}
	}

	/**
	 * 以json输出数据并结束
	 * @param mixed $data
	 * @return void
	 */
	public function jsonReturn($data) {
		ob_clean();
		echo json_encode($data);
		exit;
	}

	/**
	 * 从数组里取出数据，支持key.subKey的方式
	 * @param array $array
	 * @param string $key
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	public function arrayGet($array, $key, $default = null) {
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		foreach (explode('.', $key) as $segment) {
			if (!is_array($array) || !array_key_exists($segment, $array)) {
				return $default;
			}
			$array = $array[$segment];
		}
		return $array;
	}

	/**
	 * 魔术方法，用以获取模板设置
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		$object = new stdClass();
		$object->name = $name;
		$object->data = $this->arrayGet($this->getTemplateOptions(), $name);
		doAction('tpl_options_get', $object);
		return $object->data;
	}
}
function _g($name) {
	return TplOptions::getInstance()->$name;
}
TplOptions::getInstance()->init();

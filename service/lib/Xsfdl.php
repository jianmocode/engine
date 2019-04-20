<?php

namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Utils.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Utils;
use \Twig_Loader_Array;
use \Twig_Environment;
use \Twig_Filter;

/**
 * XpmJS 简单表单描述语言解析器
 * XSFDL (XpmJS Simple Form Description Language)  PHP 解析器
 * Version 1.0
 */
class XSFDL {

	private $option = [];
	
	/**
	 * 选中的字段清单 ([field,...])
	 * @var array
	 */
	private  $selected = [];

	/**
	 * 重载的字段清单 ( [field=>[...], ...])
	 * @var array
	 */
	private  $reload = [];


	/**
	 * 错误信息 ( [ field=>message, ... ])
	 * @var array
	 */
	public $errors = [];


	/**
	 * 字段模板
	 * @var array
	 */
	public  $template = [];


	/**
	 * 模板 Filter 
	 * @var array
	 */
	public $filters = [];


	public  $db = [];
	public  $code = [];


	/**
	 * @var 验证方法
	 * @see https://jqueryvalidation.org/documentation/
	 */
	public static $vmap = [
		"required" 		=>	"required",
		"remote" 		=>	"remote",
		"minlength" 	=>	"minlength",
		"maxlength" 	=>	"maxlength",
		"rangelength" 	=>	"rangelength",
		"min" 			=>	"min",
		"max" 			=>	"max",
		"range" 		=>	"range",
		"step" 			=>	"step",
		"email" 		=>	"email",
		"url" 			=>	"url",
		"date" 			=>	"date",
		"dateISO" 		=>	"dateISO",
		"number" 		=>	"number",
		"digits" 		=>	"digits",
		"equalTo" 		=>	"equalTo",
		"match" 		=>	"match",
	];


	function __construct( $option = [] ) {

		$this->option = array_merge([
			'slient' => true,  // 静默模式
			'autoescape'=>false,
			"phpcode" => false,  // 是否包含PHP代码
			"debug" => false  // 是否为 Debug 模式
		], $option );

	}

	/**
	 * 载入表单描语言文档
	 * @param  [type] $json_file [description]
	 * @param  array  $option	[description]
	 * @return [type]			[description]
	 */
	public static function load( $json_file, $option = [] ) {
		$xsf = new Self( $option );
		$xsf->loadByFile($json_file);
		return $xsf;
	}

	/**
	 * 添加模板 Filter 文件
	 * @param [type] $filter_file [description]
	 */
	public function addFilter( $filter_file ) {
		if ( is_readable($filter_file) ) {
			$_Twig_Filters = [];
			include( $filter_file );
			$this->setFilters(array_merge($this->filters, $_Twig_Filters ));
		}
		return $this;
	}

	/**
	 * 设定模板 Filters
	 * @param [type] $filters [description]
	 */
	public function setFilters( $filters ) {
		$this->filters = $filters;
		return $this;
	}



	/**
	 * 载入模板文件
	 * @param  [type] $template_file [description]
	 * @return [type]				[description]
	 */
	public function loadTemplate( $template_file ) {
		if ( !is_readable($template_file) ) {
			throw new Excp("无法读取模板文件", 500, ['filename'=> $template_file] );
		}

		$template_content = file_get_contents($template_file);
		$this->loadTemplateContent( $template_content );
		return $this;
	}


	/**
	 * 选中字段清单
	 * @return [type] [description]
	 */
	public function select() {
		
		$args = func_get_args();
		if ( is_array($args[0]) ) {
			$this->selected = $args[0];
		} else {
			$this->selected = $args;
		}

		return $this;
	}

	/**
	 * 设定重载字段信息
	 * @param  [type] $fields [description]
	 * @return [type]		 [description]
	 */
	public function reload( $fields ) {
		$this->reload = $fields;
		return $this;
	}


	/**
	 * 设定 Errors 
	 * @param  [type] $errors [description]
	 * @return [type]		 [description]
	 */
	public function errors( $errors ) {
		$this->errors = $errors;
		return $this;
	}



	/**
	 * 渲染字段列表
	 * @param  [type] $data   [description]
	 * @param  array  $fields [description]
	 * @return
	 */
	public function render( $data=[], $fields=[],  $fields_overload = [] ) {

		if ( empty($fields) ) {
			if ( empty($this->selected) ) {
				$fields = array_keys( $this->db );
			} else {
				$fields = $this->selected;
				$this->selected = [];
			}
		}

		if ( empty($fields_overload) && !empty($this->reload) ) {
			$fields_overload = $this->reload;
			$this->reload = [];
		}

		$resp = [];
		foreach ( $fields as $fd_name ) {

			$field = $this->db[$fd_name];
			if ( empty($field) ) {
				continue;
			}

			if ( array_key_exists($fd_name, $fields_overload) ) {
				$field = array_merge($field, $fields_overload[$fd_name]);
			}


			$field["_name"] =  $fd_name;
			$field["_error"] = $this->errors[$fd_name];

			if ( is_array($data) ) {
				$field["_value"] = $data[$fd_name];
				$resp[$fd_name] = $this->renderField( $field );
			} else if ( is_string($data) ) { // 转换成PHP变量
				$resp[$fd_name] = $this->renderField( $field, $data );
			} else {
				$field["_value"] = [];
				$resp[$fd_name] = $this->renderField( $field );
			}


			if ( empty($resp[$fd_name]) && $this->option['slient'] == false ) {
				echo "<!-- {$field['type']}: {$field["_name"]}{$field["_value"]} 空值-->\n";	
			}
			
			
		}

		$this->code = $resp;
		return $this;
	}


	/**
	 * 读取解析好的字段代码
	 * @param  [type] $field [description]
	 * @return [type]		[description]
	 */
	public function get( $field = null ) {

		if ( empty($field) ) {
			return $this->code;
		}

		return $this->code[$field];
	}


	/**
	 * 校验required数据
	 * @param  [type] $data			[description]
	 * @param  array  $fields		  [description]
	 * @param  array  $fields_overload [description]
	 * @return [type]				  [description]
	 */
	public function required( $data, $fields=[], $fields_overload = [] ) {

		if ( empty($fields) ) {
			if ( empty($this->selected) ) {
				$fields = array_keys( $this->db );
			} else {
				$fields = $this->selected;
			}
		}

		if ( empty($fields_overload) && !empty($this->reload) ) {
			$fields_overload = $this->reload;
		}

		$error = [];
		foreach ( $fields as $fd_name ) {
			
			$field = $this->db[$fd_name];
			if ( empty($field) ) {
				continue;
			}

			if ( array_key_exists($fd_name, $fields_overload) ) {
				$field = array_merge($field, $fields_overload[$fd_name]);
			}


			$rule = $field["rule"];
			if ( !is_array($rule) ) {
				continue;
			}

			foreach ($rule as $vt => $vv ) {

				$vt = strtolower($vt);
				if( $vt != 'required' ) {
					continue;
				}

				$type = "v".self::$vmap[$vt];
				
				// Utils::out( "\n=====:", $vt . "  " . $vv . ":====\n" );

				if ( method_exists($this,$type)  && call_user_func("self::$type", $data[$fd_name], $vv) === false ) {

					$msg = $field["message"];
					if ( is_array($msg) ) {
						$msg = $msg[$vt];
					}

					if ( empty($msg) ) {
						$msg = $field["name"] . "格式不正确";
					}

					$error[$fd_name] = $msg;
					// Utils::out( "\n=====:", $error[$fd_name] . ":====\n" );
					break;
				}
			}
		}

		$this->errors = $error;
		return $this;

	}


	
	/**
	 * 校验数据合法性
	 * @param  [type] $data   [description]
	 * @param  [type] $fields [description]
	 * @return [type]		 [description]
	 */
	public function validate( $data, $fields=[], $fields_overload = [] ) {
		if ( empty($fields) ) {
			if ( empty($this->selected) ) {
				$fields = array_keys( $this->db );
			} else {
				$fields = $this->selected;
			}
		}

		if ( empty($fields_overload) && !empty($this->reload) ) {
			$fields_overload = $this->reload;
		}

		$error = [];
		foreach ( $fields as $fd_name ) {
			
			$field = $this->db[$fd_name];
			if ( empty($field) ) {
				continue;
			}

			if ( array_key_exists($fd_name, $fields_overload) ) {
				$field = array_merge($field, $fields_overload[$fd_name]);
			}


			$rule = $field["rule"];
			if ( !is_array($rule) ) {
				continue;
			}

			foreach ($rule as $vt => $vv ) {

				$vt = strtolower($vt);
				$type = "v".self::$vmap[$vt];
				
				// Utils::out( "\n=====:", $vt . "  " . $vv . ":====\n" );

				if ( method_exists($this,$type)  && call_user_func("self::$type", $data[$fd_name], $vv) === false ) {

					$msg = $field["message"];
					if ( is_array($msg) ) {
						$msg = $msg[$vt];
					}

					if ( empty($msg) ) {
						$msg = $field["name"] . "格式不正确";
					}

					$error[$fd_name] = $msg;
					// Utils::out( "\n=====:", $error[$fd_name] . ":====\n" );
					break;
				}
			}
		}

		$this->errors = $error;
		return $this;
	}



	static function vMinlength( $value, $vv ) {
		if( mb_strlen($value) < $vv ) {
			return false;
		}
		return true;
	}

	static function vMaxlength( $value, $vv ) {
		if( mb_strlen($value) > $vv ) {
			return false;
		}
		return true;
	}

	static function vMatch( $value, $vv ) {
		return preg_match( $value, $vv );
	}

	static function vRequired( $value, $vv ) {

		if ( is_numeric($value) ) {
			return true;
		}
		
		if ( $vv === true && ( $value == "" || $value == null ) ) {
			return false;
		}
		return true;
	}



	/**
	 * 转换成 JS 校验类数据
	 * @param  [type] $fields [description]
	 * @return [type]		 [description]
	 */
	public function getValidationJSCode( $fields=[], $fields_overload = [] ) {
		if ( empty($fields) ) {
			if ( empty($this->selected) ) {
				$fields = array_keys( $this->db );
			} else {
				$fields = $this->selected;
			}
		}

		if ( empty($fields_overload) && !empty($this->reload) ) {
			$fields_overload = $this->reload;
		}

		$rules = []; $message = [];
		foreach ( $fields as $fd_name ) {
			
			$field = $this->db[$fd_name];
			if ( empty($field) ) {
				continue;
			}

			if ( array_key_exists($fd_name, $fields_overload) ) {
				$field = array_merge($field, $fields_overload[$fd_name]);
			}

			$rule = $field["rule"];
			if ( !is_array($rule) ) {
				continue;
			}

			$msg = $field["message"];
			if ( empty($msg) ) {
				$msg = $field["name"] . "格式不正确";
			}

			$rules["$fd_name"] = $rule;
			$message["$fd_name"] = $msg;
		}

		return ["rules"=>$rules, "messages"=>$message];
	}


	public function toPHPFunToOption( & $tpl, $fd_name, $data_name ) {

		// toOption( _value, attr.default )
		$reg = '/toOption[ ]*\([ ]*_value[ ]*,[ ]*attr.default[ ]*\)/';
		if ( preg_match_all($reg, $tpl, $match) ) {
			foreach ($match as $ma ) {
				$str =  $ma[0];
				if ( $this->option['phpcode'] === true ) {
					$tpl = str_replace( $str, "toOption ('{$data_name}[\"{$fd_name}\"]', attr.default, 2)", $tpl);
				} else {
					$tpl = str_replace( $str, "toOption ('{$data_name}[\"{$fd_name}\"]', attr.default, 1)", $tpl);
				}
			}
        }
        
		// toRadio( _value, attr.default, _name)
		$reg = '/toRadio[ ]*\([ ]*_value[ ]*,[ ]*attr.default[ ]*,[ ]*_name[ ]*,[ ]*readonly[ ]*\)/';
		if ( preg_match_all($reg, $tpl, $match) ) {
		    foreach ($match as $ma ) {
				$str =  $ma[0];
				if ( $this->option['phpcode'] === true ) {
					$tpl = str_replace( $str, "toRadio ('{$data_name}[\"{$fd_name}\"]', attr.default, _name, readonly, 2)", $tpl);
					} else {
						$tpl = str_replace( $str, "toRadio ('{$data_name}[\"{$fd_name}\"]', attr.default, _name, readonly, 1)", $tpl);
					}
            }
            // echo "toRadio:: {$tpl} ==\n";
        }

		// toCheckbox( _value, attr.default, _name)
		$reg = '/toCheckbox[ ]*\([ ]*_value[ ]*,[ ]*attr.default[ ]*,[ ]*_name[ ]*,[ ]*readonly[ ]*\)/';
		if ( preg_match_all($reg, $tpl, $match) ) {
		    foreach ($match as $ma ) {
				$str =  $ma[0];
				if ( $this->option['phpcode'] === true ) {
					$tpl = str_replace( $str, "toCheckbox ('{$data_name}[\"{$fd_name}\"]', attr.default, _name, readonly, 2)", $tpl);
				} else {
					$tpl = str_replace( $str, "toCheckbox ('{$data_name}[\"{$fd_name}\"]', attr.default, _name, readonly, 1)", $tpl);
				}
            }
            // echo "toCheckbox:: {$tpl} ==\n";
        }
        
        
	}	

	public function toPHPLoop( & $tpl, $fd_name, $data_name ) {
		$reg = "/(\{%[ ]*for[ ]+([a-zA-Z0-9\_]+)[ ]+in[ ]+(_value[ ]*[\|]*[ ]*[minLength]*[\(]*[0-9a-z]*[\)]*)[ ]*%\})([\s\S]*)(\{%[ ]+endfor[ ]+%\})/";

		if ( preg_match($reg, $tpl, $match ) ) {

			$for = $match[1];
			$endfor = $match[5];
			$item = $match[2];
			$value = $match[3];
			$content = $match[4];
			$before = "";

			if ( preg_match("/minLength\(([0-9a-z]+)\)/", $for, $ma ) ){

				if ( $ma[1] == "pair" ) {
					if ( $this->option['phpcode'] === true ) {
						$before = "<?php echo '<?php if( !is_array(\${$data_name}[\'{$fd_name}\']) || empty(\${$data_name}[\'{$fd_name}\'])  ): ?>';?>";
						$before .= "\n<?php echo '<?php \${$data_name}[\'{$fd_name}\'] = [\'\'=>\'\']; ?>';?>";
						$before .= "<?php echo '<?php endif ?>';?>\n";

						/*
						echo "===== before =====\n";
						$before .= "<?php echo '<?php print_r(\${$data_name}[\'{$fd_name}\']); ?>';?>\n";
						echo $before;
						echo "===== before =====\n";
						// exit; */
			
					} else {
						$before = "<?php if(@count(\${$data_name}['{$fd_name}']) < $min): ?>";
						$before .= "\n<?php \$steps = $min -  @count(\${$data_name}['{$fd_name}']); for( \$i=0; \$i<\$steps; \$i++){ \${$data_name}['{$fd_name}'][] = [];} ?>";
						$before .= "\n<?php endif ?>'\n";
					}
				} else {

					$min = intval($ma[1]);
					if ( $this->option['phpcode'] === true ) {
						$before = "<?php echo '<?php if(@count(\${$data_name}[\'{$fd_name}\']) < $min): ?>';?>";
						$before .= "\n<?php echo '<?php \$steps = $min -  @count(\${$data_name}[\'{$fd_name}\']); for( \$i=0; \$i<\$steps; \$i++){ \${$data_name}[\'{$fd_name}\'][] = [];} ?>';?>";
						$before .= "<?php echo '<?php endif ?>';?>\n";
						$before .= "<?php echo '<?php if( is_array(\${$data_name}[\'{$fd_name}\']) && utils::array_depth(\${$data_name}[\'{$fd_name}\']) != 2): ?>';?>\n";
						$before .= "<?php echo '<?php foreach(\${$data_name}[\'{$fd_name}\'] as \$idx=>\$it ){ \${$data_name}[\'{$fd_name}\'][\$idx] = [\'{$item}\'=>\$it]; }?>';?>\n"; 
						$before .= "<?php echo '<?php endif ?>';?>\n";

						/*
						echo "===== before =====\n";
						$before .= "<?php echo '<?php print_r(\${$data_name}[\'{$fd_name}\']); ?>';?>\n";
						echo $before;
						echo "===== before =====\n";
						// exit; */
			
					} else {
						$before = "<?php if(@count(\${$data_name}['{$fd_name}']) < $min): ?>";
						$before .= "\n<?php \$steps = $min -  @count(\${$data_name}['{$fd_name}']); for( \$i=0; \$i<\$steps; \$i++){ \${$data_name}['{$fd_name}'][] = [];} ?>";
						$before .= "\n<?php endif ?>'\n";
					}
				}

			}

			if ( $this->option['phpcode'] === true ) {

				// <?php foreach ( $_value as $img ) ? >
				$tpl = str_replace($for, "{$before}<?php echo '<?php foreach ( \${$data_name}[\'{$fd_name}\'] as \$__key=>\${$item} ): ?>' ?>", $tpl );
				// <?php endforeach ? >
				$tpl = str_replace($endfor, "<?php echo '<?php endforeach; ?>'; ?>", $tpl );
				
				// content 
				$this->toPHPValue($match[4], "", $item, $item . ".");
				$tpl = str_replace($content, $match[4], $tpl );

			} else {

				// <?php foreach ( $_value as $img ) ? >
				$tpl = str_replace($for, "{$before}<?php foreach ( \${$data_name}['{$fd_name}'] as \$__key=>\${$item} ): ?>", $tpl );
				// <?php endforeach ? >
				$tpl = str_replace($endfor, "<?php endforeach; ?>", $tpl );
				
				// content 
				$this->toPHPValue($match[4], "", $item, $item . ".");
				$tpl = str_replace($content, $match[4], $tpl );
			}

		}
	}


	public function toPHPValue( & $tpl, $fd_name=null, $data_name = null, $varstr='_value' ) {
		// $reg = "/\{\{[ ]*({$varstr}[\.a-zA-Z\-0-9]*)[ ]*\}\}/";
		$reg = "/\{\{[ ]*({$varstr}[\_\.a-zA-Z\-0-9\|\(\)\"\', ]*)[ ]*\}\}/";

		if ( preg_match_all($reg, $tpl, $match ) ) {

			foreach ($match[0] as $idx=>$var_name ) {
				// echo "=== $fd_name ====\n";
				// print_r(  $var_name );
				// echo "END === $fd_name ====\n";
				$var_tpl = $match[1][$idx];
				$var_tpl = str_replace('\'', '\\\'', $var_tpl);
				$var_tpl = trim(str_replace($varstr, $fd_name, $var_tpl));

				// $value_tpl = str_replace('\'', '\\\'', $var_name); "tag_variable": ["[{$","}]"],
				if ( $this->option['phpcode'] === true ) {
					$var_tpl = str_replace('\\', '\\\\', $var_tpl);
					$var_tpl = str_replace('\'', '\\\'', $var_tpl);
					 $code = "<?php echo '<?=T::v(\'<%={$var_tpl}%>\', \$$data_name )?>'; ?>"; 
				} else {
					$code = "<?=T::t('<%={$var_tpl}%>', \$$data_name)?>"; 
				}

				if ( !empty($data_name) ) {
					$tpl = str_replace($var_name, $code, $tpl );
				}
			}
		}
	}




	/**
	 * 渲染字段 
	 * @data_name 不为空，则渲染为 PHP表达式，模板解析时运行
	 * @param  [type] $data  [description]
	 * @param  [type] $field [description]
	 * @return [type]		[description]
	 */
	public function renderField(  $field_data, $data_name=null ) {

		$fd_name = $field_data["_name"];
		if ( empty($fd_name) ) {
			return "";
		}

		$type = $field_data["type"];
		if ( empty($type) || empty($this->template[$type]) ) {
			return "";
		}

		$tpl = $this->template[$type];

		if(  $data_name != null ) {
			$this->toPHPLoop( $tpl, $fd_name, $data_name );
			$this->toPHPFunToOption( $tpl, $fd_name, $data_name );
			$this->toPHPValue( $tpl, $fd_name, $data_name );
		}


		$loader = new Twig_Loader_Array(["code" => $tpl]);
		$twig = new Twig_Environment( $loader, $this->option );
		$this->add_filters( $twig );
		

		// 处理 ENum
		if (array_key_exists("enum", $field_data) ) {
			$enum = $field_data["enum"];
			$enums = [];
			foreach ($enum as $v => $name ) {
				$ev = $name;
				if ( is_array($name) ) {
					$ev = $name;
					array_push($enums, $ev);
				} else if ( is_string($name) || is_numeric($name) ){
					$ev = ["name"=>$name, "value"=>$v, "default"=>false];
					array_push($enums,$ev);
				}

				// if ( empty($field_data["_value"] ) &&  $ev["default"] == true ) {
				// 	$field_data["_value"] = $ev["value"];
				// }
			}

			$field_data['enum'] = $enums;
		}

		
		if ( $this->option['phpcode'] === true ) {
			try {
				ob_start();
				eval("?>" . $twig->render('code', $field_data ) );
				$code = ob_get_contents();
				ob_end_clean();
			} catch( \Exception $e ) {
				$code =  "";
			}

			return $code;
		}

		// 添加自定义函数
		
		return $twig->render('code', $field_data );
	}


	public function add_filters( $twig ) {

		$filters = [];
		$filter_file = realpath(__DIR__ . '/xsfdl/Filter.php');
		if ( is_readable($filter_file) ) {
			$_Twig_Filters = [];
			include( $filter_file );
			$filters = array_merge( $_Twig_Filters, $this->filters );
		}

		foreach ($filters as $name=>$filter) {
			$twig->addFilter($filter);
		}
	}


	public function loadByFile( $json_file ) {

		if ( !is_readable($json_file) ) {
			throw new Excp("无法读取表单描述文件", 500, ['filename'=> $json_file] );
		}

		$json_text = file_get_contents($json_file);

		if ( $this->option['debug'] ) {
			$json_data = Utils::json_decode($json_text);
		} else {
			$json_data = json_decode($json_text, true);
			if ( $json_data === false) {
				throw new Excp("无法解析单描述文件", 500, ['filename'=> $json_file, "json_error"=>json_last_error(),  "json_text"=>$json_text] );
			}
		}

		$this->loadFields( $json_data );
		return $this;
	}

	public function loadFields( $fileds_arr ) {
		$this->db = $fileds_arr;
		return $this;
	}


	public function loadTemplateContent(  & $template_content ) {

		$tpls = explode('</template>', $template_content);
		$reg = "/<template[ ]+type=([\"\'0-9a-zA-Z\-_]+)>/u";
		foreach ($tpls as $tpl) {
			$resp = preg_match( $reg, $tpl, $match );
			if ( $resp ) {
				$type = $match[1];
				$type = str_replace("\"", "", $type);	
				$type = str_replace("\'", "", $type);
				$this->template[$type] = str_replace($match[0], '', $tpl);
			}
		}

		return $this;


		// exit;


		// // $reg = "/<template[ ]+type=([\"\'0-9a-zA-Z\-_]+)>((.|\n)*?)<\/template>[^<template]*/u";
		// $reg = "/<template[ ]+type=([\"\'0-9a-zA-Z\-_]+)>((?:.|\n)*?)<\/template>/u";
		// $resp = preg_match_all($reg, $template_content, $match );
		// if ( $resp ) {
		// 	// print_r( $match );
		// 	foreach( $match[1] as $idx => $type ) {
		// 		$type = str_replace("\"", "", $type);	
		// 		$type = str_replace("\'", "", $type);
		// 		$this->template[$type] = $match[2][$idx];
		// 	}
		// }

		// // exit;
		// return $this;
	}
}
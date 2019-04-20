<?php
/**
 * XSFDL 模板过滤器
 */



// Twig 模板默认过滤器
// https://twig.symfony.com/doc/2.x/filters/index.html

$_Twig_Filters = [

	// 判断是否包含数值
	"inArray" =>new Twig_Filter('inArray', function ($string, $array) {
		if ( is_string($array) ) {
			$array = explode(",", $array);
		}
		return in_array($string, $array);
	}),

	// 判断数据类型是不是数组
	"isArray" => new Twig_Filter('isArray', function ($value) {
		return is_array($value);
	}),
	//抽取日期
	"toDate" => new Twig_Filter('toDate', function ( $value, $fmt='Y-m-d') {
 		if ( $value == null || $value ==  "CURR" ) {
 			$value = date('Y-m-d H:i:s');
 		}
 		return date($fmt, strtotime($value) );
	}),
	//抽取日期
	"toTime" => new Twig_Filter('toTime', function ( $value, $fmt='H:i:') {
 		if ( $value == null || $value ==  "CURR" ) {
 			$value = date('Y-m-d H:i:s');
 		}
 		return date($fmt, strtotime($value) );
	}),
	//抽取日期时间
	"toDateTime" => new Twig_Filter('toDateTime', function ( $value, $fmt='Y-m-d') {
 		if ( $value == null || $value ==  "CURR" ) {
 			$value = date('Y-m-d H:i:s');
 		}
 		return date($fmt, strtotime($value) );
	}),


	// 驼峰风格
	"camelCase" => new Twig_Filter('camelCase', function ( $string ) {
		$namer = explode("_", $string );
		foreach ($namer as & $n ) {
			$n = ucfirst(strtolower($n));
		}
		return implode("", $namer );
	}),


	// 数组转换为MAP
	"toMap" => new Twig_Filter('toMap', function ( $array, $key ) {

		if ( is_string($array) ) {
			return $array;
		}

		$map = [];
		foreach ($array as $rs) {
			if ( !array_key_exists($key, $rs) ) {
				return $array;
			}
			$map[$rs[$key]] = $rs;
		}

		return $map;
	}),

	// renderField -> ToPHPCode
	"toHTML" => new Twig_Filter('toHTML', function ( $column, $data_name, $template_name, $root) {

		$form_struct  = [];
		$name = $column['name'];
		$form_struct[$name] = $column;

		if ( empty($name) ) {
			return [];
		}

		$form_struct[$name]["name"] = $column["cname"];

		if ( is_array($column['validator']) ) {
			foreach ($column['validator'] as $v ) {
				$method = $v['method'];
				if ( empty($method) ){
					continue;
				}
				$form_struct[$name]['rule'][$method] = $v['value'];
				$form_struct[$name]['message'][$method] = $v['message'];
			}
			unset($form_struct[$name]['validator']);
		}

		if ( empty($form_struct) ) {
			return ;
		}

		// templates & filters 
		$content = "";
		$filter_file = "{$root}/templates/Filter.php";
		$template_files = [__DIR__ . "/{$template_name}.tpl.html", "{$root}/templates/{$template_name}.tpl.html"];
		foreach ($template_files as $file ) {
			if ( is_readable($file) ) {
				$content .= file_get_contents($file);
			}
		}

		// XSFDL 
		$xsf = new \Xpmse\XSFDL(['phpcode'=>true, 'slient'=>false]);
		$xsf->loadFields( $form_struct )
			->addFilter( $filter_file )
		    ->loadTemplateContent( $content )
		    ->render( $data_name )
		;

		$form = $xsf->get();
		return $form[$name];
	}),
		

	// minLength
	"minLength" => new Twig_Filter('minLength', function ( $array, $length ) {

		if (  $length == "pair" ){
			
		}

		
		if ( !is_array($array) ) {
			$array = [];
		}

		$steps = intval($length) -  count($array);
		for( $i=0; $i<$steps; $i++){
			$array[] = [];
		}

		return $array;

	}),

	// pair
	"pair" => new Twig_Filter('pair', function ( $array ) {
		print_r(  $array );
			
		$keys = array_keys($array);

		if ( !empty($keys) ) {
			$array = [];
		}
		return $array;
	}),

	//toRaido
	"toRadio" => new Twig_Filter('toRadio', function ( $string, $value, $default, $name, $readonly="", $phpcode=false ) {

        $disabled = ( $readonly == "1" ) ? "disabled" : "";

		if ( $phpcode === false ) {
			$response = '';
		} else if ( $phpcode === 1 ) {
			$response = "<?php if (is_null(\$$value)) { \$$value = \"$default\"; } ?>\n"; 
		} else if ( $phpcode === 2 ) {
			$response = "<?php echo '<?php if (is_null(\$$value)) { \$$value = \"$default\"; } ?>';  ?>\n"; 
		}
		$opts = explode(',', $string );
		foreach( $opts  as & $opt  ){
		 	$arr = explode('=', $opt);
		 	$opt = [
		 		"name"=>$arr[0],
		 		"value"=>is_null($arr[1]) ? $arr[0] : $arr[1]
		 	];
            if ( $phpcode === false ) {
                if ( is_string($value) ) {
                    $value = [$value];
                }
                $opt['selected'] = ( ( $opt['value'] == $default && is_null($value) )|| in_array($opt['value'], $value) ) ? 'checked':'';
                $response .= "\n<label class=\"css-input css-radio css-radio-lg css-radio-primary push-10-r\"><input {$disabled} name=\"{$name}\" value=\"{$opt['value']}\" type=\"radio\" data-uncheck-value=\"{$opt['value']}\" {$opt['selected']} /> <span></span> {$opt['name']}</label>";
                } else if ( $phpcode === 1 ) {
                    $opt['selected'] = "<?=(\"{$opt['value']}\" == \$$value)  ? \"checked\" : \"\"?>";
                    $response .= "\n<label  class=\"css-input  css-radio css-radio-lg css-radio-primary push-10-r\"> <input {$disabled} type=\"radio\" name=\"{$name}\" data-uncheck-value=\"{$opt['value']}\" value=\"{$opt['value']}\" {$opt['selected']} > <span></span> {$opt['name']}</label>";
                } else if ( $phpcode === 2 ) {
                    $opt['selected'] = "<?php echo '<?=(\"{$opt['value']}\" == \$$value) ? \"checked\" : \"\"?>'; ?>";
                    $response .= "\n<label class=\"css-input  css-radio css-radio-lg css-radio-primary push-10-r\"><input {$disabled}  type=\"radio\" name=\"{$name}\" data-uncheck-value=\"{$opt['value']}\" value=\"{$opt['value']}\" {$opt['selected']} > <span></span> {$opt['name']}</label>";
                }
        }
		return $response;
    }),
    
    //toCheckbox
	"toCheckbox" => new Twig_Filter('toCheckbox', function ( $string, $value, $default, $name, $readonly="", $phpcode=false ) {

        $disabled = ( $readonly == "1" ) ? "disabled" : "";

		if ( $phpcode === false ) {
			$response = '';
		} else if ( $phpcode === 1 ) {
			$response = "<?php if (is_null(\$$value)) { \$$value = \"$default\"; } ?>\n"; 
		} else if ( $phpcode === 2 ) {
			$response = "<?php echo '<?php if (is_null(\$$value)) { \$$value = \"$default\"; } ?>';  ?>\n"; 
		}
		$opts = explode(',', $string );
		foreach( $opts  as & $opt  ){
		 	$arr = explode('=', $opt);
		 	$opt = [
		 		"name"=>$arr[0],
		 		"value"=>is_null($arr[1]) ? $arr[0] : $arr[1]
		 	];
            if ( $phpcode === false ) {
                if ( is_string($value) ) {
                    $value = [$value];
                }
                $opt['selected'] = ( ( $opt['value'] == $default && is_null($value) )|| in_array($opt['value'], $value) ) ? 'checked':'';
                $response .= "\n<label class=\"css-input css-checkbox css-checkbox-lg css-checkbox-primary push-10-r\"><input {$disabled} name=\"{$name}\" value=\"{$opt['value']}\" type=\"checkbox\" data-uncheck-value=\"\" {$opt['selected']} /> <span></span> {$opt['name']}</label>";


            } else if ( $phpcode === 1 ) {
                $opt['selected'] = "<?=(\"{$opt['value']}\" == \$$value || is_array(\$$value) && in_array(\"{$opt['value']}\",\$$value) )   ? \"checked\" : \"\"?>";
                $response .= "\n<label  class=\"css-input  css-checkbox css-checkbox-lg css-checkbox-primary push-10-r\"> <input {$disabled} type=\"checkbox\" name=\"{$name}\" data-uncheck-value=\"\" value=\"{$opt['value']}\" {$opt['selected']} > <span></span> {$opt['name']}</label>";
            } else if ( $phpcode === 2 ) {
                $opt['selected'] = "<?php echo '<?=(\"{$opt['value']}\" == \$$value || is_array(\$$value) &&  in_array(\"{$opt['value']}\",\$$value) ) ? \"checked\" : \"\"?>'; ?>";
                $response .= "\n<label class=\"css-input  css-checkbox css-checkbox-lg css-checkbox-primary push-10-r\"><input {$disabled}  type=\"checkbox\" name=\"{$name}\" data-uncheck-value=\"\" value=\"{$opt['value']}\" {$opt['selected']} > <span></span> {$opt['name']}</label>";
            }
        }
		return $response;
  	}),


	// toOption
	"toOption" => new Twig_Filter('toOption', function ( $string, $value, $default, $phpcode=false ) {

		if ( $phpcode === false ) {
			$response = '';
		} else if ( $phpcode === 1 ) {
			$response = "<?php  \$$value =  is_string(\$$value) ? [\$$value] : \$$value; if (empty(\$$value)) { \$$value = [\"$default\"]; } ?>\n"; 
		} else if ( $phpcode === 2 ) {
			$response = "<?php echo '<?php \$$value = is_string(\$$value) ? [\$$value] : \$$value ; if (empty(\$$value)) { \$$value = [\"$default\"]; } ?>';  ?>\n"; 
		}

		$opts = explode(',', $string );
		
		foreach( $opts  as & $opt  ){
			$arr = explode('=', $opt);
			$opt = [
				"name"=>$arr[0],
				"value"=>is_null($arr[1]) ? $arr[0] : $arr[1]
			];

			if ( $phpcode === false ) {
				if ( is_string($value) ) {
					$value = [$value];
				} 
				$opt['selected'] = ( ( $opt['value'] == $default && empty($value) )|| in_array($opt['value'], $value) ) ? 'selected' : '';
				$response .= "\n<option value=\"{$opt['value']}\"  {$opt['selected']}> {$opt['name']}</option>";
			} else if ( $phpcode === 1 ) {
				$opt['selected'] = "<?=in_array(\"{$opt['value']}\", \$$value)  ? \"selected\" : \"\"?>";
				$response .= "\n<option value=\"{$opt['value']}\" {$opt['selected']} > {$opt['name']}</option>";

			} else if ( $phpcode === 2 ) {
				$opt['selected'] = "<?php echo '<?=in_array(\"{$opt['value']}\", \$$value) ? \"selected\" : \"\"?>'; ?>";
				$response .= "\n<option value=\"{$opt['value']}\" {$opt['selected']} > {$opt['name']}</option>";
			}
		}

		return $response;
	})
];
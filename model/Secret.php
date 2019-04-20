<?php
namespace Xpmse\Model;

/**
 * 
 * 密钥模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *      \Xpmse\Secret
 *            |
 *   \Xpmse\Model\Secret
 *
 * USEAGE: 
 *
 */


use \Xpmse\Secret  as SecretModel;


class Secret extends SecretModel {
 	function __construct( $param=[] ) {
 		parent::__construct($param);
 	}
}

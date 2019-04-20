<?php 
/**
 * MINA Pages Delta 数据渲染器
 * 
 * @package	  \Mina\Delta
 * @author	   天人合一 <https://github.com/trheyi>
 * @copyright	Xpmse.com
 * 
 * @example
 * 
 * <?php
 * 		
 *  
 */

namespace Mina\Delta;

use \Mina\Cache; 
use \Mina\Delta\Delta; 
use \Mina\Delta\Wxapp;
use \Exception;

class Render  {

	private $wxapp =  null;
	private $delta = null;
	private $delta_text = null;
	private $images = [];
	private $videos = [];
	private $files = [];
	private $html = null;


	function __construct( $options = [] ) {
	}

	function load( $delta ) {
		$this->delta = $delta;
		// $this->delta_text = json_encode( $this->delta );
		return $this;
	}

	function loadByHTML( $html ) {
		
		$this->html = $html;
		$this->delta = null;
		$this->wxapp = null;
		
		return $this;
		// $deltaParser =  new Delta();
		// $this->delta =  $deltaParser->importHtml( $html );
		// $this->images = $deltaParser->images();
		// return $this;
	}



	function wxapp() {

		if ( empty($this->wxapp) && !empty($this->html)  ) {
			$wxappParser =  new Wxapp();
			$this->wxapp =  $wxappParser->importHtml( $this->html );
			$this->images = $wxappParser->images();
			$this->videos = $wxappParser->videos();
		}

		return $this->wxapp;
	}
	

	function delta() {

		if ( empty($this->delta) && !empty($this->html)  ) {
			$deltaParser =  new Delta();
			$this->delta =  $deltaParser->importHtml( $this->html );
			$this->images = $deltaParser->images();
			$this->videos = $deltaParser->videos();
		}

		return $this->delta;
	}

	
	function html() {
		$utils = new \Mina\Delta\Utils;
		$html = $utils->load($this->delta)->convert()->render();
		$this->images = $utils->images();
		$this->videos = $utils->videos();
		$this->files = $utils->files();
		return $html;
	}


	function images( $html = null){

		return $this->images;
	}

	function videos( $html = null ){
		return $this->videos;
	}

	function files( $html = null){
		return $this->files;
	}



	function markdown() {
		$utils = new \Mina\Delta\Utils;
		$html = $utils->load($this->delta)->convert()->render();
		$this->images = $utils->images();
		return $html;
	}

	
}
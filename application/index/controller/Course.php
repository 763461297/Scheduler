<?php

namespace app\index\controller;

use think\Request;

class Course extends BaseController {
	
	public function index() {
		return "123";
	}
	
	public function item() {
		$request = Request::instance();
		if($request->isAjax()) {
			
		} else {
			$this->error();
		}
	}
}
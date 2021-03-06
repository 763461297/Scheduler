<?php
use think\Config;
use think\Loader;
use think\File;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 创建PHPExcel对象
 * @param string $filename 将指定文件实例化成对象，为空则创建新对象
 * @return PHPExcel
 */
function createPHPExcelObj($filename = null) {
	loadPHPExcelLibrary();
	if(isset($filename)) {		
		return PHPExcel_IOFactory::load($filename);
	} else {		
		return new PHPExcel();
	}
}

/**
 * 导出excel文件到浏览器（下载）
 * @param PHPExcel $excel PHPExcel对象
 * @param string $filename 输出的文件名，不加扩展名，默认为当前时间戳秒数
 * @param string $type 文件类型Excel5|Excel2007，默认从配置文件读取
 */
function exportExcel(PHPExcel $excel, $filename = null, $type = null) {
	$type = ifdefault($type, ifdefault(Config::get('excel.type'), 'Excel2007'));
	$filename = ifdefault($filename, time());
	$filename = $filename.($type == 'Excel5' ? '.xls' : '.xlsx');
	if($type == 'Excel5') {
		// Excel5 xls
		header('Content-Type: application/vnd.ms-excel');
	} else {
		// Excel2007 xlsx
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	}
	header('Content-Disposition: attachment;filename="'.$filename.'"');
	header('Cache-Control: max-age=0');
	$writer = PHPExcel_IOFactory::createWriter($excel, $type);
	$writer->save("php://output");
}

/**
 * 导出pdf文件到浏览器（下载）
 * @param PHPExcel $excel PHPExcel对象
 * @param string $filename 输出的文件名，不加扩展名，默认为当前时间戳秒数
 */
function exportPDF(PHPExcel $excel, $filename = null) {
	$filename = ifdefault($filename, time()).'.pdf';
	$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
	$rendererLibraryPath = dirname(__FILE__).'/../extend/PDF/';
	if (!PHPExcel_Settings::setPdfRenderer(
		$rendererName,
		$rendererLibraryPath
		)) {
		die(
			'NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
			'<br />' .
			'at the top of this script as appropriate for your directory structure'
		);
	}
 	header('Content-Type: application/pdf');
 	header('Content-Disposition: attachment;filename="'.$filename.'"');
 	header('Cache-Control: max-age=0');
	
 	$writer = PHPExcel_IOFactory::createWriter($excel, 'PDF');
	$writer->save("php://output");
}

/**
 * 将Excel文件的第一张工作表转换成数组对象
 * @param File $file 请求的File对象
 * @param bool $delete 是否在完成后删除源文件
 * @param bool $trim 是否自动删除末尾空白行
 * @return array
 */
function getExcelArray(File $file = null, $delete = true, $trim = true) {
	if(!isset($file)) {
		return null;
	}
	$info = $file->validate([
			'ext' => 'xls,xlsx',
	])->move(ROOT_PATH . 'public' . DS . 'uploads', timestamp());
	if($info) {
		$filename = $info->getRealPath();
		$excel = createPHPExcelObj($filename);
		unset($info);
		if($delete) {			
			unlink($filename);
		}
		$arr = $excel->getSheet(0)->toArray();
		if(!$trim) {
			return $arr;
		}
		for($i = count($arr) - 1 ; $i >= 0 ; $i--) {
			$isNull = true;
			foreach($arr[$i] as $cell) {
				if(!empty($cell)) {
					$isNull = false;
					break;
				}
			}
			if(!$isNull) {
				break;
			}
			unset($arr[$i]);
		}
		return $arr;
	} else {
		return null;
	}
}

/**
 * 生成json响应字符串（Ajax）
 * @param string $msg 传输的消息
 * @param string $ok 请求是否完成
 * @return \think\response\Json json字符串
 */
function getAjaxResp($msg="error", $ok=false, $code=0) {
	return json([
			"success" => $ok,
			"msg" => $msg,
			"code" => $code,
	]);
}

/**
 * 加载PHPExcel类库
 */
function loadPHPExcelLibrary() {
	Loader::import(ifdefault(Config::get('excel.library'), 'PHPExcel.PHPExcel'));
}

/**
 * 检查对象并设置默认值
 * @param mixed $object 要检查的对象
 * @param mixed $def 默认值
 * @return mixed 对象非空则返回对象，否则返回默认值
 */
function ifdefault($object, $def = null) {
	return isset($object) ? $object : $def;
}
/**
 * 获取Unix时间戳秒数
 * @return int
 */
function timestamp() {
	list($msec, $sec) = explode(' ', microtime());
	$msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	return $msectime;
}
/**
 * 将整数与-,分隔符表示的区间转换成二进制位标记的十进制形式，如1-2,4-6表示成二进制1110110，十进制118
 * @param string $str 要转换的字符串，只包含整数、分隔符','，区间符'-'
 * @return int 返回十进制整数
 */
function intervalToInteger($str) {
	$arr = explode(',', $str);
	$ret = 0;
	foreach ($arr as $item) {
		$bet = explode('-', $item);
		if(count($bet) == 1) {
			$ret = $ret | (1 << intval($bet[0]));
		} else {
			$n = intval($bet[1]);
			for($i = intval($bet[0]); $i <= $n; $i++) {
				$ret = $ret | (1 << $i);
			}
		}
	}
	return $ret;
}
/**
 * 将整数转换成区间表示，整数二进制位1代表可用区间
 * @param int $num
 * @return string 转换后的字符串
 */
function integerToInterval($num) {
	$x = 0;
	$ret = [];
	$lft = -1;
	while($num > 0) {
		$tp = $num & 1;
		if($tp == 1 && $lft == -1) {
			$lft = $x;
		} else if($tp == 0 && $lft != -1) {
			if($x -1 == $lft) {
				array_push($ret, (string)$lft);
			} else {					
				array_push($ret, $lft.'-'.($x - 1));
			}
			$lft = -1;
		}
		$x++;
		$num = $num >> 1;
	}
	if($lft != -1) {
		if($x -1 == $lft) {
			array_push($ret, (string)$lft);
		} else {					
			array_push($ret, $lft.'-'.($x - 1));
		}
	}
	$str = '';
	foreach ($ret as $it) {
		if($str != '') {
			$str .= ',';
		}
		$str .= $it;
	}
	return $str;
}
/**
 * 根据日期获取周数和星期数
 */
function getWeekDayByDate($term, $date) {
	$rep_week = ['日', '一', '二', '三', '四', '五', '六'];
	$d_term = date_create($term['start']);
	$d_now = date_create($date);
	$_week = date('w', date_timestamp_get($d_now));
	if(date_timestamp_get($d_now) >= date_timestamp_get($d_term)) {
		$differ = floor(date_diff($d_term, $d_now)->format('%a') / 7) + 1;
		return '第'.$differ.'周周'.$rep_week[$_week]; 
	}
	return '';
}
/**
 * 根据周数获取日期
 * @param unknown $term
 * @param unknown $week
 */
function getDateByWeek($term, $week) {
	$d_term = date_create($term['start']);
	$day_num_s = ($week - 1) * 7;
	date_add($d_term,date_interval_create_from_date_string($day_num_s." days"));
	$date_s = date('Y.m.d', date_timestamp_get($d_term));
	date_add($d_term,date_interval_create_from_date_string("6 days"));
	$date_e = date('Y.m.d', date_timestamp_get($d_term));
	return $date_s."-".$date_e;
}
<?php
class Stock {
	private static $ifeng_url = "http://api.finance.ifeng.com/akdaily/?type=last&code=";
	private static $result_dir = "result";
	private static $eastmoney_url = "http://quote.eastmoney.com/stocklist.html";
	private static $date_list = array();
	private static $date_format = "Y-m-d";

	public static function init() {
		$result_dir = __DIR__."/".self::$result_dir;
		if (!is_dir($result_dir)) {
			mkdir($result_dir, 0755, true);
		}
		self::$result_dir = $result_dir;
	}

	private static function append_loc($stock) {
		$valid = array("00", "30", "60");
		if (strlen($stock) != 6) return false;
		$st = substr($stock, 0, 2);
		if (!in_array($st, $valid)) return false;
		if ($st == "60") {
			$stock = "sh".$stock;
		} else {
			$stock = "sz".$stock;
		}
		return $stock;
	}

	private static function crawler($stock_number) {
		$url = self::$ifeng_url.$stock_number;
		$content = file_get_contents($url);
		return $content;
	}

	private static function get_line_content($indexes, $date) {
		return isset($indexes[$date]) ? $indexes[$date]."\n" : "-\n";
	}

	private static function update_single_day($stock_number, $date) {
		$date_list = self::get_date_list();
		if (!isset($date_list[$date])) return false;
		$content = self::crawler($stock_number);
		$index = self::index_by_date($content);
		if ($index === false) return false;
		$fn = self::$result_dir."/".$stock_number.".txt";
		$line = self::get_line_content($index, $date);
		return file_put_contents($fn, $line, FILE_APPEND);
	}

	private static function update_single($stock_number) {
		$content = self::crawler($stock_number);
		if (strlen($content) < 5 ) {
			return false;
		}
		return self::write_single_result($stock_number, $content);
	}

	private static function write_single_result($stock_number, $content) {
		$fn = self::$result_dir."/".$stock_number.".txt";
		$body = "";
		$index = self::index_by_date($content);
		if ($index === false) return false;
		$date_list = self::get_date_list();
		foreach($date_list as $date => $v) {
			if (isset($index[$date])) {
				$body .= $index[$date]."\n";
			} else {
				$body .= "$date -\n";
			}
		}
		return file_put_contents($fn, $body);
	}

	private static function index_by_date($content) {
		$res = array();
		$content = json_decode($content, true);
		if (!isset($content["record"]) || count($content["record"]) == 0) {
			return false;
		}
		foreach ($content["record"] as $line) {
			if (count($line) > 0) {
				$res[$line[0]] = implode(" ", $line);
			}
		}
		return $res;
	}

	public static function get_stock_list() {
		$res = array();
		$valid = array("00", "30", "60");
		//<li><a target="_blank" href="http://quote.eastmoney.com/sh600038.html">中直股份(600038)</a></li>
		$content = file_get_contents(self::$eastmoney_url);
		$pat = "/<li><a .*\((.*)\)<\/a><\/li>/siU";
        preg_match_all($pat, $content, $mat);
		if (count($mat[0]) < 2) {
			return false;
		}
		foreach ($mat[1] as $stock) {
			$stock = self::append_loc($stock);
			if ($stock === false) continue;
			$res[] = $stock;
        }
        print_r($res);
		return $res;
	}

	private static function run_all_by_day($l) {
		$date = date(self::$date_format, time() - 3600*24*$l);
		$stocks = self::get_stock_list();
		foreach ($stocks as $stock) {
			echo "$stock $date\n";
			self::update_single_day($stock, $date);
		}
	}

	public static function get_date_list() {
		if (count(self::$date_list) > 0) {
			return self::$date_list;
		}
		$res = array();
		$stock_number = "sh000001";
		$url = self::$ifeng_url.$stock_number;
		$content = file_get_contents($url);
		$content = json_decode($content, true);
		if (count($content) == 0) { return false;}
		foreach ($content["record"] as $line) {
			$res[$line[0]] = 1;
			echo $line[0]."\n";
		}
		self::$date_list = $res;
		return $res;
	}

	public static function run_all() {
		$stocks = self::get_stock_list();
		foreach ($stocks as $stock) {
			echo $stock."\n";
			self::update_single($stock);
		}
	}

	public static function run_single($stock_number) {
		$tmp = self::append_loc($stock_number);
		if ($tmp === false) {
			$stock = $stock_number;
		} else {
			$stock = $tmp;
		}
		echo $stock."\n";
		self::update_single($stock);
	}

	public static function run_today() {
		return self::run_all_by_day(0);
	}

	public static function run_yestoday() {
		return self::run_all_by_day(1);
	}

	public static function run_lastdays($l) {
		for ($i = l-1; $i >=0 ; $i--) {
			self::run_all_by_day($i);
		}
	}

}
?>

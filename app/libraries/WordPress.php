<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

require_once APPPATH . 'third_party/phpQuery/phpQuery.php';

class WordPress {
	
	//配置文件
	private $wpPath;
	private $CI;
	private $_error;
	
	public function __construct() {
		if (!isset($this->CI)) {
			$this->CI =& get_instance();
		}
		$this->CI->load->helper('xml');
		$this->CI->load->helper('file');
    	$this->wpPath = str_replace("\\", "/", dirname(APPPATH)) . '/wordpress.xml';
	}
	
	//读取配置文件
	public function loadWP() {
		if (file_exists($this->wpPath)) {
			phpQuery::newDocumentFileXML($this->wpPath);
			$itemArr = pq("channel item");
			phpQuery::each($itemArr, "WordPress::parseWpItem");
		} else {
		    $this->_error = "wordpress.xml文件不存在";
		}
	}
	
	//创建博客头部
	public static function createMarkdownContent($wpObj) {
		$headerList = array("author", "head", "date", "title", "summary", "tags", "category", "status");
		$headerArray = array();
		array_push($headerArray, "<!--\n");
		
		foreach ($headerList as $headName) {
			switch ($headName) {
				case "author":
					array_push($headerArray, "author: " . $wpObj['author'] . "\n");
					break;
				case "date":
					array_push($headerArray, "date: " . date('Y-m-d', strtotime($wpObj['date'])) . "\n");
					break;
				case "title":
					array_push($headerArray, "title: " . $wpObj['title'] . "\n");
					break;
				case "tags":
					array_push($headerArray, "tags: " . implode(",", $wpObj['tags']) . "\n");
					break;
				case "category":
					array_push($headerArray, "category: " . implode(",", $wpObj['category']) . "\n");
					break;
				case "status":
					array_push($headerArray, "status: " . $wpObj['status'] . "\n");
					break;
				case "summary":
					array_push($headerArray, "status: " . $wpObj['title'] . "\n");
					break;
				default:
					break;
			}
		}
		array_push($headerArray, "-->\n\n");
		array_push($headerArray, $wpObj['content']);
		
		return $headerArray;
	}
	
	public static function writeMarkdown($wpObj) {
		$wpmdPath = str_replace("\\", "/", dirname(APPPATH)) . '/posts/wp/';
		if (!file_exists($wpmdPath)) mkdir($wpmdPath);
		
		//创建文件
		$mdfile = $wpmdPath . $wpObj['fileName'] . ".md";
		
		file_put_contents($mdfile, self::createMarkdownContent($wpObj));
	}
	
	//解析wordpress XML
	public static function parseWpItem($index, $item) {
		$wpItem = pq($item);
		
		$title = $wpItem->find("title")->html();
		$author = $wpItem->find("creator")->get(0)->nodeValue;
		$content = $wpItem->find("encoded")->get(0)->nodeValue;
		$status = $wpItem->find("status")->html();
		if ($status != "publish") $status = "draft";
		
		$tags = $wpItem->find("category[domain=post_tag]");
		$categorys = $wpItem->find("category[domain=category]");
		
		$tagsArr = array();
		$categoryArr = array();
		
		for($i = 0; $i < $tags->size(); $i++) {
			$tagNode = $tags->get($i);
			$tagval = $tagNode->nodeValue;
			if (!in_array($tagval, $tagsArr)) {
				array_push($tagsArr, $tagval);
			}
		}
		
		for($i = 0; $i < $categorys->size(); $i++) {
			$tagNode = $categorys->get($i);
			$tagval = $tagNode->nodeValue;
			if (!in_array($tagval, $categoryArr) && $tagval != "未分类") {
				array_push($categoryArr, $tagval);
			}
		}
		
		$wpObj = array(
			"author" => $author,
			"content" => $content,
			"title" => $wpItem->find("title")->html(),
			"date" => $wpItem->find("post_date")->html(),
			"ctime" => $wpItem->find("post_date_gmt")->html(),
			"fileName" => $wpItem->find("post_id")->html(),
			"status" => $status,
			"category" => $categoryArr,
			"tags" => $tagsArr
		);
		
		self::writeMarkdown($wpObj);
	}
	
	//返回错误信息
	public function errMsg() {
		return $this->_error;
	}
}

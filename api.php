<?php
	@header("Content-type: application/json; charset=utf-8");
	error_reporting(0);

	require './lib/lastRSS.class.php';

	// 参数配置
	/**
	 * @param name lofter自定义域名
	 */
	$prefix = isset($_GET['name']) && trim($_GET['name']) != "" ? strip_tags(trim($_GET['name'])) : "";
	if(!$prefix){
		// 从name.php默认配置文件中获取域名
		$nameList = @include_once './lib/name.php';
		if($nameList){
			if(!empty($nameList)){
				// 打乱数组 从数组中随机获取一个
				shuffle($nameList);
				$prefix = $nameList[0];
			}else{
				exit("Lofter name required.");
			}
		}else{
			exit("Lofter name required.");
		}
	}

	/**
	 * @param pagesize 分页大小 最大50 默认10
	 */
	$pagesize = (isset($_GET['pagesize']) && intval($_GET['pagesize']) > 0) ? intval($_GET['pagesize']) : 10;
	if($pagesize > 50){
		$pagesize = 50;
	}

	/**
	 * @param page 当前页码 配合分页大小使用
	 */
	$page = (isset($_GET['page']) && intval($_GET['page']) > 0) ? intval($_GET['page']) : 1;
	
	$start = ($page - 1) * $pagesize;

	/**
	 * @mode 模式 simple只输出图片url，full输出title等 默认simple
	 */
	$mode = isset($_GET['mode']) && $_GET['mode'] == 'full' ? 'full' : 'simple';

	$rss=new lastRSS();
	$rss->cache_dir = 'cache';      //设置缓存目录，要手动建立
	$rss->cache_time = 1800;        //设置缓存时间。默认为0，即随访问更新缓存；建议设置为3600，一个小时
	$rss->default_cp = 'UTF-8';     //设置RSS字符编码，默认为UTF-8
	$rss->cp = 'UTF-8';               //设置输出字符编码，默认为GBK
	$rss->items_limit = 50;         //设置输出数量，默认为10
	$rss->date_format = 'Y-m-d H:i:s';        //设置时间格式。默认为字符串；U为时间戳，可以用date设置格式
	$rss->stripHTML = false;         //设置过滤html脚本。默认为false，即不过滤
	$rss->CDATA = 'content';        //设置处理CDATA信息。默认为nochange。另有strip和content两个选项
	$url = 'http://'. $prefix .'.lofter.com/rss';
	$data = $rss->Get($url);        //处理RSS并获取内容

	// data list array
	$items = $data['items'];

	// output
	$result = array();

	for ($i=0; $i < count($items); $i++) { 
		// html
		$description = $items[$i]['description'];

		// preg_match_all("/(src)=[\"|'| ]{0,}([^>]*\.(gif|jpg|bmp|png))/isU",$description,$img_array);
		preg_match_all("/(src)=\"([^>]*\.(gif|jpg|bmp|png))[\"|?]/isU",$description,$img_array);

		if(!empty($img_array[2])){
			// 当有图片时才输出 否则不输出
			if($mode == 'full'){
				$result[] = array(
					'title'		=>	$items[$i]['title'],
					'date'	=>	$items[$i]['pubDate'],
					'link'		=>	$items[$i]['link'],
					// 'desc'		=>	$description,
					'list'		=>	$img_array[2],
				);
			}else{
				$result = array_merge($result, $img_array[2]);
			}
		}
	}

	// page=1 pagesize=10 => 0-9  start=(page-1)*pagesize
	// page=2 pagesize=10 => 10-19

	if(count($result) <= $start){
		// 无数据
		echo json_encode(array());exit();
	}

	$output = array_slice($result, $start, $pagesize);

	echo json_encode($output);
	exit();

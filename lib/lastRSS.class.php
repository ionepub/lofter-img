<?php
/**
* lastRSS
* 简单但功能强大的PHP解析RSS文件类。
*/
class lastRSS {
    // -------------------------------------------------------------------
    // 共有属性
    // -------------------------------------------------------------------
    var $default_cp = 'UTF-8';
    var $CDATA = 'nochange';
    var $cp = '';
    var $items_limit = 0;
    var $stripHTML = False;
    var $date_format = '';
    // -------------------------------------------------------------------
    // 私有属性
    // -------------------------------------------------------------------
    var $channeltags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
    var $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');
    var $imagetags = array('title', 'url', 'link', 'width', 'height');
    var $textinputtags = array('title', 'description', 'name', 'link');
    // -------------------------------------------------------------------
    // 解析RSS文件，并返回关联数组。
    // -------------------------------------------------------------------
    function Get ($rss_url) {
        //如果启用缓存
        if ($this->cache_dir != '') {
            $cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
            $timedif = @(time() - filemtime($cache_file));
            if ($timedif < $this->cache_time) {
                // 缓存文件是最新,则返回缓存数组
                $result = unserialize(join('', file($cache_file)));
                // 如果缓存不为空,则设置$cached=1
                if ($result) $result['cached'] = 1;
            } else {
                // 缓存文件已过期,则创建新的缓存文件
                $result = $this->Parse($rss_url);
                $serialized = serialize($result);
                if ($f = @fopen($cache_file, 'w')) {
                    fwrite ($f, $serialized, strlen($serialized));
                    fclose($f);
                }
                if ($result) $result['cached'] = 0;
            }
        }
        // 如果未启用缓存,则直接加载文件
        else {
            $result = $this->Parse($rss_url);
            if ($result) $result['cached'] = 0;
        }
        return $result;
    }
    
    // -------------------------------------------------------------------
    // 重定义preg_match(); 返回修正过后的第一个匹配
    // from 'classic' preg_match() array output
    // -------------------------------------------------------------------
    function my_preg_match ($pattern, $subject) {
        // 开始正在匹配
        preg_match($pattern, $subject, $out);
        // 如果结果不为空,则继续
        if(isset($out[1])) {
            // 处理 CDATA (如果存在)
            if ($this->CDATA == 'content') { // 获取 CDATA内容 (不存在 CDATA 标签)
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            } elseif ($this->CDATA == 'strip') { // 去除 CDATA
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            }
            //转换成设置的编码
            if ($this->cp != '')
                $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);
            return trim($out[1]);
        } else {
            return '';
        }
    }
    // -------------------------------------------------------------------
    // 替换html实体为真实字符
    // -------------------------------------------------------------------
    function unhtmlentities ($string) {
        // Get HTML entities table
        $trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
        // Flip keys<==>values
        $trans_tbl = array_flip ($trans_tbl);
        // Add support for ' entity (missing in HTML_ENTITIES)
        $trans_tbl += array("'" => "'");
        // Replace entities by values
        return strtr ($string, $trans_tbl);
    }
    // -------------------------------------------------------------------
    // Parse() 是由GET()调用的私有方法,用来解析RSS文件.
    // 所以不要在你的代码中使用Parse(),而是用 Get($rss_file)方法来替代.
    // -------------------------------------------------------------------
    function Parse ($rss_url) {
        //打开RSS文件
        if ($f = @fopen($rss_url, 'r')) {
            $rss_content = '';
            while (!feof($f)) {
                $rss_content .= fgets($f, 4096);
            }
            fclose($f);
            // 解析文件编码 
            $result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
            //如果文件编码一致则直接使用
            if ($result['encoding'] != '')
                { $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
            //否则使用默认的编码
            else
                { $this->rsscp = $this->default_cp; } // This is used in my_preg_match()
            // 解析 CHANNEL信息
            preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
            foreach($this->channeltags as $channeltag)
            {
                $temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
                if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
            }
            // If date_format is specified and lastBuildDate is valid
            if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {
                        // 解析 lastBuildDate 到指定的时间格式
                        $result['lastBuildDate'] = date($this->date_format, $timestamp);
            }
            // 解析 TEXTINPUT
            preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
                // This a little strange regexp means:
                // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
            if (isset($out_textinfo[2])) {
                foreach($this->textinputtags as $textinputtag) {
                    $temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
                    if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
                }
            }
            // 解析 IMAGE
            preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach($this->imagetags as $imagetag) {
                    $temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
                    if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
                }
            }
            // 解析 ITEMS
            preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
            $rss_items = $items[2];
            $i = 0;
            $result['items'] = array(); // create array even if there are no items
            foreach($rss_items as $rss_item) {
                // If number of items is lower then limit: Parse one item
                if ($i < $this->items_limit || $this->items_limit == 0) {
                    foreach($this->itemtags as $itemtag) {
                        $temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
                        if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
                    }
                    // Strip HTML tags and other bullshit from DESCRIPTION
                    if ($this->stripHTML && $result['items'][$i]['description'])
                        $result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
                    // Strip HTML tags and other bullshit from TITLE
                    if ($this->stripHTML && $result['items'][$i]['title'])
                        $result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
                    // If date_format is specified and pubDate is valid
                    if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
                        // convert pubDate to specified date format
                        $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                    }
                    // Item 计数
                    $i++;
                }
            }
            $result['items_count'] = $i;
            return $result;
        }
        else // 文件打开错误返回False
        {
            return False;
        }
    }
}
# 网易LOFTER图片链接获取

[TOC]

## 简介

Lofter上有很多美图，特意做了这个，功能就是从LOFTER上获取图片地址，以json的形式输出。

## 文档

[Docs](http://mypic.4host.cn/doc)

## Demo

[Demo](http://mypic.4host.cn/demo)


## API参数

- **name**
	- LOFTER名称，Required 必填参数
	- 如地址为 `http://9mouth.lofter.com`，则`name`参数填入`9mouth`即可

- **mode**
	- 输出json模式，可选
	- 可选项：
	- `simple` (默认) 仅输出图片Url数组
	- `full` 输出文章标题、链接和图片Url数组

- **pagesize**
	- 分页大小，可选
	- 默认`pagesize=10`，最大可为50

- **page**
	- 当前页码，Required 必填参数
	- 默认`page=1`


## 返回值

### simple mode

```json
[
 "http://demo.jpg",
 "http://demo2.jpg",
 "http://demo3.jpg"
]
```

### full mode

```json
[
    {
        "title":"图片",
        "date":"2017-02-13 22:13:32",
        "link":"http://9mouth.lofter.com/post/11c344_e350819",
        "list":[
            "http://imglf1.nosdn.127.net/img/bzdNQ3lVZUc4R1QzdWJ4c3BUTGpuUjk4OGRMZTZUcitqZEhEbi9mTDJpbWNGYTMvM0hMT3VRPT0.jpg"
        ]
    }
]
```

|参数名|类型|说明|
|:-----  |:-----|-----             |
|title |string   | LOFTER文章标题 |
|date |string   | 文章发布时间 |
|link |string   | 文章链接 |
|list |array    | 文章中的图片Url列表 |


## 缓存配置

缓存默认开启（文件）

缓存文件夹为`cache`文件夹，需保证可写。

默认缓存时间为`1800`秒，即30分钟

---------------------

### 报错

[Github Issues](https://github.com/ionepub/lofter-img/issues)


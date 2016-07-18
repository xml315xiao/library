<?php

/* 定义分页类 */

class Page
{
    private $pageTotal;             //总显示条数
    private $pageSize;              //每页显示条数
    private $pages;                 //总页数
    private $page;                  //当前页
    private $html;                  //输出网页内容
    private $url;                   //当前页URL
    private $params;                //保存当前参数,以&相连
    private $limit;                 //分页显示条件

    /* 初始化构造函数 */
    public function __construct($pageTotal, $pageSize)
    {
        $this->pageTotal = $pageTotal;
        $this->pageSize = $pageSize;
        $this->pages = $pageTotal ? ceil($pageTotal / $pageSize) : 1;
        $this->url = $_SERVER['PHP_SELF'];
        $this->loadParams();
        $this->setPage();
        $this->setLimit();
    }

    /* 获取Limit属性 */
    public function getLimit()
    {
        return $this->limit;
    }

    /* 设置当前页 */
    public function setPage()
    {
        //默认当前页为首页
        if (isset($_GET['page'])) {
            $this->page = intval($_GET['page']);
        } else {
            $this->page = 1;
        }
        //确保数据合法性
        if ($this->page <= 0) {
            $this->page = 1;
        }
        if ($this->page >= $this->pages) {
            $this->page = $this->pages;
        }
    }

    /* 设置Limit条件 */
    public function setLimit()
    {
        $start = ($this->page - 1) * $this->pageSize;
        $end = $this->pageSize;
        $this->limit = $start . ',' . $end;
    }

    /* 显示操控标签 */
    public function showHtml()
    {
        $html = '';
        $html .= "第<strong>{$this->page}</strong>页&nbsp" . "/&nbsp;共<strong>{$this->pages}</strong>页";
        $html .= "<a href='{$this->url}?page=1{$this->params}'>首页</a>";
        $html .= "<a href='{$this->url}?page=" . ($this->page - 1) . "{$this->params}'>上一页</a>";
        $html .= "<a href='{$this->url}?page=" . ($this->page + 1) . "{$this->params}'>下一页</a>";
        $html .= "<a href='{$this->url}?page={$this->pages}{$this->params}'>尾页</a>";

        $this->html = $html;
        return $this->html;
    }

    /* 传递参数 */
    private function loadParams()
    {
        //遍历$_GET[]
        foreach ($_GET as $key => $val) {
            if ($key != 'page') {
                $params[] = $key . '=' . $val;
            }
            if (!empty($params)) {
                $this->params = '&' . implode('&', $params);
            } else {
                $this->params = '';
            }
        }
    }
}

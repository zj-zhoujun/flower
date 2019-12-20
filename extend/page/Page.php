<?php
namespace cus;


use think\Paginator;

///自定义样式分页类
class Page extends Paginator
{
    //首页
    protected function home() {
        if ($this->currentPage() > 1) {
            return "<a href='" . $this->url(1) . "' title='首页'>首页</a>";
        } else {
            return '';
        }
    }
    //上一页
    protected function prev() {
        if ($this->currentPage() > 1) {
            return "<a  class='prev' href='" . $this->url($this->currentPage - 1) . "' title='上一页'>上一页</a>";
        } else {
            return '<span>上一页</span>';
        }
    }
    //下一页
    protected function next() {
        if ($this->hasMore) {
            return "<a class='next' href='" . $this->url($this->currentPage + 1) . "' title='下一页'>下一页</a>";
        } else {
            return '<span>下一页</span>';
        }
    }
    //尾页
    protected function last() {
        if ($this->hasMore) {
            return "<a href='" . $this->url($this->lastPage) . "' title='尾页'>尾页</a>";
        } else {
            return '';
        }
    }
    //统计信息
    protected function info(){
        return "<p class='page-info'>共<b>" . $this->lastPage .
            "</b>页 <b>" . $this->total . "</b>条数据</p>";
    }
    /**
     * 页码按钮
     * @return string
     */
    protected function getLinks()
    {

        $block = [
            'first'  => null,
            'slider' => null,
            'last'   => null
        ];
        $side   = 3;
        $window = $side * 2;


        if ($this->lastPage < $window + 6) {

            $block['first'] = $this->getUrlRange(1, $this->lastPage);
        } elseif ($this->currentPage <= $window) {

            $block['first'] = $this->getUrlRange(1, $window + 2);
            $block['last']  = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        } elseif ($this->currentPage > ($this->lastPage - $window)) {

            $block['first'] = $this->getUrlRange(1, 2);
            $block['last']  = $this->getUrlRange($this->lastPage - ($window + 2), $this->lastPage);
        } else {

            $block['first']  = $this->getUrlRange(1, 2);
            $block['slider'] = $this->getUrlRange($this->currentPage - $side, $this->currentPage + $side);
            $block['last']   = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        }

        $html = '';
        if (is_array($block['first'])) {
            $html .= $this->getUrlLinks($block['first']);
        }
        if (is_array($block['slider'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['slider']);
        }
        if (is_array($block['last'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['last']);
        }

        return $html;
    }
    /**
     * 渲染分页html
     * @return mixed
     */
    public function render()
    {
        if ($this->hasPages()) {
            if ($this->simple) {
                return sprintf(
                    '<div class="page">%s %s %s</div>',
                    $this->prev(),
                    $this->getLinks(),
                    $this->next()
                );
            } else {
                return sprintf(
                    '<div class="page">%s %s %s %s %s %s</div>',
                    $this->home(),
                    $this->prev(),
                    $this->getLinks(),
                    $this->next(),
                    $this->last(),
                    $this->info()
                );
            }
        }
        return '';
    }
    /**
     * 生成一个可点击的按钮
     *
     * @param  string $url
     * @param  int    $page
     * @return string
     */
    protected function getAvailablePageWrapper($url, $page)
    {
        return '<a href="' . htmlentities($url) . '" title="第 '. $page .' 页" >' . $page . '</a>';
    }
    /**
     * 生成一个禁用的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getDisabledTextWrapper($text)
    {
        return '<span class="node-border">' .$text. '</span>';
    }
    /**
     * 生成一个激活的按钮{当前页}
     *
     * @param  string $text
     * @return string
     */
    protected function getActivePageWrapper($text)
    {

        return '<span class="current">' .$text. '</span>';
    }
    /**
     * 为了样式美观生成空格按钮
     *
     * @return string
     */
    protected function getDots()
    {
        return $this->getDisabledTextWrapper(' ');
    }
    /**
     * 批量生成页码按钮.
     *
     * @param  array $urls
     * @return string
     */
    protected function getUrlLinks(array $urls)
    {
        $html = '';
        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }
        return $html;
    }
    /**
     * 生成普通页码按钮
     *
     * @param  string $url
     * @param  int    $page
     * @return string
     */
    protected function getPageLinkWrapper($url, $page)
    {
        if ($page == $this->currentPage()) {
            return $this->getActivePageWrapper($page);
        }
        return $this->getAvailablePageWrapper($url, $page);
    }
}
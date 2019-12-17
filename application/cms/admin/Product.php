<?php
namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Product as ProductModel;
use app\cms\model\ProductCategory as ProductCategoryModel;
use think\facade\Validate;

/**
 * 产品控制器
 * @package app\cms\admin
 */
class Product extends Admin
{
    /**
     * 产品列表
     * @author zj <254575481@qq.com>
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = ProductModel::where($map)->order($order)->paginate();

        $btnType = [
            'class' => 'btn btn-info',
            'title' => '产品分类',
            'icon'  => 'fa fa-fw fa-sitemap',
            'href'  => url('ProductCategory/index')
        ];

        $list_type = ProductCategoryModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '产品名称', 'text.edit'],
                ['typeid', '分类', 'select', $list_type],
                ['market_price', '市场价', 'text.edit'],
                ['price', '本店价', 'text.edit'],
                ['name', '产品名称', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addTopButton('custom', $btnType) // 添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,name,typeid,timeset,ad_type,create_time,update_time')
            ->setRowList($data_list) // 设置表格数据
            ->addValidate('Product', 'name')
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Product');
            if (true !== $result) $this->error($result);
            if ($product = ProductModel::create($data)) {
                // 记录行为
                action_log('product_add', 'cms_product', $product['id'], UID, $data['name']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $list_type = ProductCategoryModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法添加的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['select', 'typeid', '产品分类', '', $list_type, 0],
                ['text', 'name', '产品名称'],
                ['image', 'image', '图片', '<code>必填</code>'],
                ['text','market_price', '市场价'],
                ['text','price', '本店价'],
                ['text', 'title', '产品介绍', '<code>必填</code>'],
                ['ckeditor', 'content', '产品内容', '可作为单页使用'],
                ['colorpicker', 'color', '文字颜色', '', '', 'rgb'],
//                ['text', 'size', '文字大小', '只需填写数字，例如:12，表示12px', '',  ['', 'px']],
                ['text', 'alt', '图片描述', '即图片alt的值'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->setTrigger('timeset', '1', 'start_time')
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 广告id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Product');
            if (true !== $result) $this->error($result);

            if (ProductModel::update($data)) {
                // 记录行为
                action_log('product_edit', 'cms_product', $id, UID, $data['name']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $list_type = ProductCategoryModel::where('status', 1)->column('id,name');
        array_unshift($list_type, '默认分类');

        $info = ProductModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->setPageTips('如果出现无法添加的情况，可能由于浏览器将本页面当成了广告，请尝试关闭浏览器的广告过滤功能再试。', 'warning')
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'tagname'],
                ['text', 'name', '产品名称'],
                ['image', 'image', '图片'],
                ['text','market_price', '市场价'],
                ['text','price', '本店价'],
                ['select', 'typeid', '产品分类', '', $list_type],
                ['ckeditor', 'content', '产品内容'],
                ['radio', 'status', '立即启用', '', ['否', '是']]
            ])
            ->setTrigger('timeset', '1', 'start_time')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除广告
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete');
    }

    /**
     * 启用广告
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用广告
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置广告状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids         = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $product_name = ProductModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['product_'.$type, 'cms_product', 0, UID, implode('、', $product_name)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $product  = ProductModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $product . ')，新值：(' . $value . ')';
        return parent::quickEdit(['product_edit', 'cms_product', $id, UID, $details]);
    }
}

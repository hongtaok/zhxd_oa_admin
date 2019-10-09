define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'apply_notice/index' + location.search,
                    add_url: 'apply_notice/add',
                    edit_url: 'apply_notice/edit',
                    del_url: 'apply_notice/del',
                    multi_url: 'apply_notice/multi',
                    table: 'apply_notice',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function () {
                return '申请id/申请人/客户经理';
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                pageSize: 30,
                showToggle: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'apply_id', title: '申请id'},
                        {field: 'user.username', title: '申请人'},
                        {field: 'admin.username', title: '客户经理'},
                        {field: 'title', title: __('Title'), operate: false},
                        {field: 'content', title: __('Content'), operate: false},
                        {field: 'created_time', title: __('Created_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
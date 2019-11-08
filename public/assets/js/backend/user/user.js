define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "昵称/姓名";};

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'openid', title:__('Openid')},
                        {field: 'parent.username', title: '上级'},

                        {field: 'team.total', title: '团队总人数'},
                        {field: 'team.first_total', title: '一级人数'},
                        {field: 'team.second_total', title: '二级人数'},
                        {field: 'income_total', title: '总业绩'},
                        {field: 'withdraw_total', title: '已提现金额'},
                        {field: 'withdraw_balance', title: '剩余可提现金额'},

                        // {field: 'bank_card', title: '银行卡号', operate: false},
                        // {field: 'bank_name', title: '开户行', operate: false},
                        //
                        // {field: 'bank_front_image', title: '银行卡正面', events: Table.api.events.image, formatter: Table.api.formatter.image,operate: false},
                        // {field: 'bank_back_image', title: '银行卡背面', events: Table.api.events.image, formatter: Table.api.formatter.image,operate: false},

                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'withdraw',
                                    text: '提现',
                                    title: '提现',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-money',
                                    url: 'user/user/withdraw',
                                    visible: function (row) {
                                        if (row.income_total <= 0) {
                                            return false;
                                        }

                                        if (row.withdraw_balance <= 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                }
                            ]
                        }
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
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'apply/index' + location.search,
                    add_url: 'apply/add',
                    edit_url: 'apply/edit',
                    del_url: 'apply/del',
                    multi_url: 'apply/multi',
                    table: 'apply',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "产品名称";};
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showColumns: false,
                pageSize:25,
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'product.name', title: __('Product_id'), operate:false},
                        {field: 'user.username', title: __('User_id'), operate:false},
                        {field: 'admin.username', title: __('Admin_id'), operate:false},
                        {field: 'first_check_fund', title: __('First_check_fund'), operate:'BETWEEN'},
                        {field: 'final_check_fund', title: __('Final_check_fund'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: {"0":'未审核',"1":'审核中',"2":'审核拒绝',"3":'审核通过'}, formatter: Table.api.formatter.status, custom: {0:'info', 1:'success', 2:'danger', 3:'gray'}},
                        {field: 'apply_time', title: __('Apply_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'upload_evidence_time', title: __('Upload_evidence_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'allot_time', title: __('Allot_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'report_fund_time', title:'尽调定额时间', operate:'RANGE', addclass:'datetimerange'},
                        {field: 'first_check_time', title: __('First_check_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'middle_check_time', title: '中审时间', operate:'RANGE', addclass:'datetimerange'},
                        {field: 'final_check_time', title: __('Final_check_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'upload_evidence',
                                    text: '资料',
                                    title: '资料',
                                    classname: 'btn btn-xs btn-primary btn-addtabs',
                                    icon: 'fa fa-folder-o',
                                    url: 'apply/upload_evidence',
                                },
                                {
                                    name: 'allot',
                                    text: '分配',
                                    title: '分配',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-user',
                                    url: 'apply/allot',
                                    visible: function (row) {
                                        // 如果没有上传资料， 不能分配
                                        if (row.upload_evidence_time == '' || row.upload_evidence_time == null || row.upload_evidence_time == 'undefined') {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'report_check_fund',
                                    text: '尽调定额',
                                    title: '尽调定额',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-money',
                                    url: 'apply/report_check_fund',
                                    visible: function (row) {
                                        // 如果未分配客户经理， 不能上传尽调资料
                                        if (row.admin_id == '' || row.admin_id == null || row.admin_id == 'undefined') {
                                            return false;
                                        }

                                        // 如果申请已经通过， 则不显示
                                        if (row.status == 3) {
                                            return false;
                                        }

                                        // 如果风控初审已过， 则不显示
                                        if (row.first_check_time != null) {
                                            return false;
                                        }

                                        return true;
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '驳回',
                                    title: '驳回',
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    icon: 'fa fa-remove',
                                    url: 'apply/reject',
                                    visible: function (row) {
                                        // 风控终审通过， 且已经完成， 则不显示
                                        if (row.status == 3) {
                                            return false;
                                        }

                                        // 已经处于驳回状态， 则不显示
                                        if (row.status == 2) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'unreject',
                                    text: '取消驳回',
                                    title: '取消驳回',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'apply/unreject',
                                    confirm: '<div>确认撤销驳回并重新提交吗？</div>',
                                    success: function (data, ret) {
                                        //如果需要阻止成功提示，则必须使用return false;
                                        Layer.alert('操作成功');
                                        location.reload();
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },

                                    visible: function (row) {
                                        return row.status == 2;
                                    },

                                },
                                {
                                    name: 'first_check',
                                    text: '初审通过',
                                    title: '初审通过',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-check-circle-o',
                                    url: 'apply/first_check',
                                    confirm: '<div>确认初审通过码?</div>',
                                    success: function (data, ret) {
                                        Layer.alert('操作成功');
                                        location.reload();
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        // 如果审核驳回，则不显示
                                        if (row.status == 2) {
                                            return false;
                                        }

                                        // 如果客户经理没有上传尽调报告，则不显示
                                        if (row.report_fund_time == '' || row.report_fund_time == null || row.report_fund_time == 'undefined') {
                                            return false;
                                        }

                                        // 如果已经通过风控初审， 则不显示
                                        if (row.first_check_time != null) {
                                            return false;
                                        }
                                        return true;
                                    }

                                },
                                {
                                    name: 'middle_check',
                                    text: '中审通过',
                                    title: '中审通过',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-check-circle-o',
                                    url: 'apply/middle_check',
                                    confirm: '<div>确认中审通过码?</div>',
                                    success: function (data, ret) {
                                        Layer.alert('操作成功');
                                        location.reload();
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        // 如果审核驳回，则不显示
                                        if (row.status == 2) {
                                            return false;
                                        }

                                        // 如果初审没有通过，则不显示
                                        if (row.first_check_time == '' || row.first_check_time == null || row.first_check_time == 'undefined') {
                                            return false;
                                        }

                                        // 如果已经通过风控中审， 则不显示
                                        if (row.middle_check_time != null) {
                                            return false;
                                        }
                                        return true;
                                    }

                                },
                                {
                                    name: 'final_check',
                                    text: '终审通过',
                                    title: '终审通过',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-check-circle-o',
                                    url: 'apply/final_check',
                                    visible: function (row) {
                                        // 如果审核驳回，则不显示
                                        if (row.status == 2) {
                                            return false;
                                        }

                                        // 如果中审没有通过，则不显示
                                        if (row.middle_check_time == '' || row.middle_check_time == null || row.middle_check_time == 'undefined') {
                                            return false;
                                        }

                                        // 如果已经通过风控终审， 则不显示
                                        if (row.final_check_time != null) {
                                            return false;
                                        }
                                        return true;
                                    }

                                },

                            ]
                        },
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
        upload_evidence: function () {
            Controller.api.bindevent();
        },
        allot: function () {
            Controller.api.bindevent();
        },
        report_check_fund: function () {
            Controller.api.bindevent();
        },
        reject: function () {
            Controller.api.bindevent();
        },
        unreject: function () {
            Controller.api.bindevent();
        },
        first_check: function () {
            Controller.api.bindevent();
        },
        middle_check: function () {
            Controller.api.bindevent();
        },
        final_check: function () {
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
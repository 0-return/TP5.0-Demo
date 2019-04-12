<?php
 // +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

/****************************用户端*************************************/
//获取验证码
Route::post('uapi/:version/getcode', 'uapi/:version.User/getcode');
//获取auth
Route::post('uapi/:version/getauth', 'uapi/:version.Token/getAuth');
//获取token
Route::post('uapi/:version/token', 'uapi/:version.Token/token');
//登录检测
Route::post('uapi/:version/tap', 'uapi/:version.Home/tap');
//轮播广告
Route::post('uapi/:version/home/adv', 'uapi/:version.Home/adv');
//产品分类
/*Route::post('uapi/:version/home/goods', 'uapi/:version.Home/goods');*/
//法律小讲堂
Route::post('uapi/:version/home/content', 'uapi/:version.Home/content');
// 注册接口
Route::post('uapi/:version/user/add', 'uapi/:version.User/add');
// 登录接口
Route::post('uapi/:version/user/login', 'uapi/:version.User/login');
// 重置密码接口
Route::post('uapi/:version/member/forget', 'uapi/:version.Member/forget');
// 资料修改
Route::post('uapi/:version/member/edit', 'uapi/:version.Member/edit');
// 换绑手机时验证原手机
Route::post('uapi/:version/member/checkphone', 'uapi/:version.Member/checkPhone');
// 换绑手机
Route::post('uapi/:version/member/binding', 'uapi/:version.Member/binding');
// 显示个人信息
Route::post('uapi/:version/member/index', 'uapi/:version.Member/index');
// 我的律师
Route::post('uapi/:version/member/mylawyer', 'uapi/:version.Member/mylawyer');
// 我的订单
Route::post('uapi/:version/member/myorder', 'uapi/:version.Member/myorder');
//用户信息
Route::post('uapi/:version/member/show', 'uapi/:version.Member/show');
// 确认订单
Route::post('uapi/:version/member/endorder', 'uapi/:version.Member/endOrder');
// 删除订单
Route::post('uapi/:version/member/delorder', 'uapi/:version.Member/delorder');
// 订单退款
Route::post('uapi/:version/member/refund', 'uapi/:version.Member/refund');
//取消订单
Route::post('uapi/:version/member/cancelorder', 'uapi/:version.Member/cancelOrder');
// 订单提交
Route::post('uapi/:version/order/add', 'uapi/:version.order/add');
// 重新支付
Route::post('uapi/:version/order/repay', 'uapi/:version.order/repay');
// 订单详情
Route::post('uapi/:version/order/show', 'uapi/:version.order/show');
// 充值
Route::post('uapi/:version/order/recharge', 'uapi/:version.order/recharge');
//支付宝支付回调
Route::post('uapi/:version/notify/alipayrefund', 'uapi/:version.Notify/alipayRefund');
//微信支付回调
Route::post('uapi/:version/notify/wxpayrefund', 'uapi/:version.Notify/wxpayRefund');

// 支付宝充值回调
Route::post('uapi/:version/notify/alipayrechargerefund', 'uapi/:version.Notify/alipayRechargeRefund');
// 微信充值回调
Route::post('uapi/:version/notify/wxpayrechargerefund', 'uapi/:version.Notify/wxpayRechargeRefund');
// 卡密兑换接口
Route::post('uapi/:version/card/exchange', 'uapi/:version.Card/exchange');
// 申请退款接口
Route::post('uapi/:version/aftersale/askrefund', 'uapi/:version.Aftersale/askrefund');
// 取消申请退款接口
Route::post('uapi/:version/aftersale/cancelrefund', 'uapi/:version.Aftersale/cancelrefund');
// 获取广告接口
Route::post('uapi/:version/advertisement/showbyid', 'uapi/:version.Advertisement/showbyid');
// 获取协议接口
Route::post('uapi/:version/agreement/showbyid', 'uapi/:version.Agreement/showbyid');
// 订单评价接口
Route::post('uapi/:version/comment/add', 'uapi/:version.Comment/add');
// 查看订单评价接口
Route::post('uapi/:version/comment/showbyid', 'uapi/:version.Comment/showbyid');
// 首页获取轮播图
Route::post('uapi/:version/home/indexTopAdv', 'uapi/:version.Home/indexTopAdv');
// 首页获取法律讲堂数据
Route::post('uapi/:version/home/classroom', 'uapi/:version.Home/classroom');
// 首页法律讲堂换一换数据
Route::post('uapi/:version/home/changeonce', 'uapi/:version.Home/changeonce');
// 首页政务窗口接口
Route::post('uapi/:version/home/governmentaffairs', 'uapi/:version.Home/governmentaffairs');
// 获取版本信息，强制更新使用
Route::post('uapi/:version/home/version', 'uapi/:version.Home/version');
//等待接单
Route::post('uapi/:version/home/jiedanajax', 'uapi/:version.Home/jiedanajax');
// 工具页服务动态接口
Route::post('uapi/:version/tool/get_active', 'uapi/:version.Tool/get_active');
// 工具页推荐律师接口
Route::post('uapi/:version/tool/getdata', 'uapi/:version.Tool/getdata');
// 发现页获取分类接口
Route::post('uapi/:version/find/gettype', 'uapi/:version.Find/gettype');
// 发现页根据id获取所有内容接口
Route::post('uapi/:version/find/getdatabyid', 'uapi/:version.Find/getdatabyid');
// 发现页根据id获取文章详情内容接口
Route::post('uapi/:version/find/getdetail', 'uapi/:version.Find/getdetail');
// 点赞接口
Route::post('uapi/:version/find/zan', 'uapi/:version.Find/zan');
// 关注接口
Route::post('uapi/:version/find/fans', 'uapi/:version.Find/fans');
// 收藏接口
Route::post('uapi/:version/find/collection', 'uapi/:version.Find/collection');
// 获取合同商品类型列表并展示第一个类型的商品列表
Route::post('uapi/:version/goods/newgetgoods', 'uapi/:version.Goods/newgetgoods');
// 根据合同商品类型id获取商品列表
Route::post('uapi/:version/goods/newgethtgoodsbyid', 'uapi/:version.Goods/newgethtgoodsbyid');
// 根据合同商品id获取商品详情
Route::post('uapi/:version/goods/newgethtdetailbyid', 'uapi/:version.Goods/newgethtdetailbyid');
// 根据标识获取法务服务、法律培训、律师函、服务商城商品列表
Route::post('uapi/:version/goods/newgetgoodsbyid', 'uapi/:version.Goods/newgetgoodsbyid');
// 根据商品id获取法务服务、法律培训、律师函服务商城对应的详情
Route::post('uapi/:version/goods/newgetdetailbyid', 'uapi/:version.Goods/newgetdetailbyid');
// 根据法务服务、法律培训、律师函服务商城列表获取到的iid获取全部商品
Route::post('uapi/:version/goods/newgetallgoods', 'uapi/:version.Goods/newgetallgoods');
// 获取文书服务详情
Route::post('uapi/:version/goods/newgetdoc', 'uapi/:version.Goods/newgetdoc');
// 发布留言咨询
Route::post('uapi/:version/question/add', 'uapi/:version.Question/add');
// 删除没有回答过的问答记录
Route::post('uapi/:version/question/delByid', 'uapi/:version.Question/delByid');
// 采纳回答
Route::post('uapi/:version/question/editByid', 'uapi/:version.Question/editByid');
// 根据问题id获取第一条回答记录
Route::post('uapi/:version/question/showByid', 'uapi/:version.Question/showByid');
// 根据问题id获取所有问题及回答记录
Route::post('uapi/:version/question/showallByid', 'uapi/:version.Question/showallByid');
// 根据uid获取回答记录
Route::post('uapi/:version/question/showanswer', 'uapi/:version.Question/showanswer');
// 根据问题id、律师id获取问题及当前律师所有回答记录
Route::post('uapi/:version/question/showallBylid', 'uapi/:version.Question/showallBylid');
// 问题列表展示，带分页
Route::post('uapi/:version/question/showall', 'uapi/:version.Question/showAll');
// 获取追问追答数据接口
Route::post('uapi/:version/question/showother', 'uapi/:version.Question/showother');
// 追问律师接口
Route::post('uapi/:version/question/questionagain', 'uapi/:version.Question/questionagain');
//待问答的问答接口
Route::post('uapi/:version/question/showquestion', 'uapi/:version.Question/showquestion');
// 快速咨询提交接口
Route::post('uapi/:version/consultation/add', 'uapi/:version.Consultation/add');
// 推送
Route::post('uapi/:version/Order/test', 'uapi/:version.Order/test');

// 法律援助发起订单
Route::post('uapi/:version/help/helporder', 'uapi/:version.Help/helporder');
// 结束订单
Route::post('uapi/:version/help/endorder', 'uapi/:version.Help/endorder');
// 获取订单列表
Route::post('uapi/:version/help/orderlist', 'uapi/:version.Help/orderlist');
// 获取聊天列表
Route::post('uapi/:version/help/chatlist', 'uapi/:version.Help/chatlist');
// 取消订单
Route::post('uapi/:version/help/cancelOrder', 'uapi/:version.Help/cancelOrder');
// （检测是否已经接单ajax）
Route::post('uapi/:version/help/jiedanajax', 'uapi/:version.Help/jiedanajax');
//系统消息
Route::post('uapi/:version/msg/sysmsg', 'uapi/:version.Msg/sysmsg');
//获取协议、关于我们等
Route::post('uapi/:version/user/agree', 'uapi/:version.User/agree');
//所有配置信息
Route::post('uapi/:version/config/show', 'uapi/:version.Config/show');
//行业分类
Route::post('uapi/:version/home/showall', 'uapi/:version.Home/showall');
//反馈意见
Route::post('uapi/:version/user/complaint', 'uapi/:version.User/complaint');
//退出登录
Route::post('uapi/:version/user/logout', 'uapi/:version.User/logout');
//获取专题律师
Route::post('uapi/:version/lawyer/showall', 'uapi/:version.Lawyer/showall');
//获取我的收藏接口
Route::post('uapi/:version/member/getcollection', 'uapi/:version.Member/getcollection');
//获取我的关注接口
Route::post('uapi/:version/member/getguanzhu', 'uapi/:version.Member/getguanzhu');
//获取我的收藏接口
Route::post('uapi/:version/member/delcollect', 'uapi/:version.Member/delcollect');
//聊天快捷短语
Route::post('uapi/:version/chat/getqcl', 'uapi/:version.Chat/getquickchatlist');
//发条搜索
Route::post('uapi/:version/find/globalsearch', 'uapi/:version.Find/globalsearch');
//获取正在咨询订单列表
Route::post('uapi/:version/chat/chatlist', 'uapi/:version.Chat/chatlist');
//确认电话联系
Route::post('uapi/:version/member/confirmcontactorder', 'uapi/:version.member/confirmContactOrder');
//专题页获取全部数据
Route::post('uapi/:version/special/index', 'uapi/:version.Special/index');
//专题页切换查看视频、文章、音频
Route::post('uapi/:version/special/getbyflag', 'uapi/:version.Special/getbyflag');
//评论文章、视频接口
Route::post('uapi/:version/comment/comment', 'uapi/:version.Comment/comment');
//重新分配律师
Route::post('uapi/:version/member/getlawyer', 'uapi/:version.Member/getLawyer');
// 分享接口
Route::post('uapi/:version/find/share', 'uapi/:version.Find/share');

/****************************用户端结束*************************************/


/******************************律师端***************************************/
//所有配置信息
Route::post('lapi/:version/config/show', 'lapi/:version.Config/show');
//注册
Route::post('lapi/:version/user/add', 'lapi/:version.User/add');
//登录
Route::post('lapi/:version/user/login', 'lapi/:version.User/login');
//退出登录
Route::post('lapi/:version/user/logout', 'lapi/:version.User/logout');
//获取验证码
Route::post('lapi/:version/user/getcode', 'lapi/:version.User/getcode');
//用户资料
Route::post('lapi/:version/user/show', 'lapi/:version.User/show');
//资料修改
Route::post('lapi/:version/user/edit', 'lapi/:version.User/edit');
//密码重置
Route::post('lapi/:version/user/reset', 'lapi/:version.User/reset');
//反馈意见
Route::post('lapi/:version/user/complaint', 'lapi/:version.User/complaint');
//评论列表
Route::post('lapi/:version/user/comment', 'lapi/:version.User/comment');
//我的客户
Route::post('lapi/:version/user/customer', 'lapi/:version.User/customer');
//vip状态检测
Route::post('lapi/:version/user/checkmembervipstatus', 'lapi/:version.User/checkmembervipstatus');
//协议获取
Route::post('lapi/:version/user/agree', 'lapi/:version.User/agree');
//是否接单开关
Route::post('lapi/:version/user/is_receipt', 'lapi/:version.User/is_receipt');
//聊天快捷短语
Route::post('lapi/:version/chat/getqcl', 'lapi/:version.Chat/getquickchatlist');
//聊天列表
Route::post('lapi/:version/chat/showall', 'lapi/:version.Chat/showall');
//聊天详情
Route::post('lapi/:version/chat/show', 'lapi/:version.Chat/show');
//判断订单是否结束
Route::post('lapi/:version/chat/checkline', 'lapi/:version.Chat/checkline');
//系统消息
Route::post('lapi/:version/msg/sysmsg', 'lapi/:version.Msg/sysmsg');
//认证接口
Route::post('lapi/:version/cert/add', 'lapi/:version.Cert/add');
//认证行业分类
Route::post('lapi/:version/cert/show', 'lapi/:version.Cert/show');
//搜索结果
Route::post('lapi/:version/cert/getstr', 'lapi/:version.Cert/getstr');
//广告显示
Route::post('lapi/:version/adv/show', 'lapi/:version.Adv/show');
//商品列表
Route::post('lapi/:version/goods/showall', 'lapi/:version.Goods/showall');
//商品详情
Route::post('lapi/:version/goods/show', 'lapi/:version.Goods/show');
//专题页获取数据
Route::post('lapi/:version/special/index', 'lapi/:version.Special/index');          //已经弃用
//专题页切换获取数据
Route::post('lapi/:version/special/getbyflag', 'lapi/:version.Special/getbyflag');  //已经弃用
//问题列表
Route::post('lapi/:version/lawyer/question', 'lapi/:version.Lawyer/question');
//回答列表
Route::post('lapi/:version/lawyer/answer', 'lapi/:version.Lawyer/answer');
//回答问题
Route::post('lapi/:version/lawyer/doanswer', 'lapi/:version.Lawyer/doanswer');
//留言分类
Route::post('lapi/:version/lawyer/goodstype', 'lapi/:version.Lawyer/goodstype');
//律师提现
Route::post('lapi/:version/member/putcash', 'lapi/:version.Member/putcash');
// 换绑手机时验证原手机
Route::post('lapi/:version/member/checkphone', 'lapi/:version.Member/checkphone');
// 换绑手机
Route::post('lapi/:version/member/binding', 'lapi/:version.Member/binding');
//接单列表
Route::post('lapi/:version/order/tporder', 'lapi/:version.Order/tporder');
//订单列表
Route::post('lapi/:version/order/showall', 'lapi/:version.Order/showall');
//订单详情
Route::post('lapi/:version/order/show', 'lapi/:version.Order/show');
//删除订单
Route::post('lapi/:version/order/del', 'lapi/:version.Order/del');
//手动接单
Route::post('lapi/:version/order/receipt', 'lapi/:version.Order/receipt');
//自动接单
Route::post('lapi/:version/order/autoreceipt', 'lapi/:version.Order/autoreceipt');
//律师联系用户
Route::post('lapi/:version/order/contactuser', 'lapi/:version.Order/contactuser');
//生成第二订单并发送给用户
Route::post('lapi/:version/order/secondorder', 'lapi/:version.Order/secondorder');
//律师结束服务
Route::post('lapi/:version/order/endorder', 'lapi/:version.Order/endorder');
//首页信息
Route::post('lapi/:version/home/show', 'lapi/:version.Home/show');
//检查是否有未接订单 (uid,token)
Route::post('lapi/:version/home/isorder', 'lapi/:version.Home/isorder');
//获取版本信息，强制更新使用
Route::post('lapi/:version/home/version', 'lapi/:version.Home/version');
//律师短文章发布
Route::post('lapi/:version/shortxt/add', 'lapi/:version.Shortxt/add');
//律师视频发布
Route::post('lapi/:version/video/add', 'lapi/:version.Video/add');
//律师视频详情
Route::post('lapi/:version/video/show', 'lapi/:version.Video/show');
//律师视频列表
Route::post('lapi/:version/video/showall', 'lapi/:version.Video/showall');
//律师评论列表
Route::post('lapi/:version/video/comment', 'lapi/:version.Video/comment');
//律师音频发布
Route::post('lapi/:version/audio/add', 'lapi/:version.Audio/add');
//律师音频详情
Route::post('lapi/:version/audio/show', 'lapi/:version.Audio/show');
//律师音频列表
Route::post('lapi/:version/audio/showall', 'lapi/:version.Audio/showall');
//文章列表
Route::post('lapi/:version/content/showall', 'lapi/:version.Content/showall');
//文章详情
Route::post('lapi/:version/content/show', 'lapi/:version.Content/show');
//律师评论列表
Route::post('lapi/:version/content/comment', 'lapi/:version.Content/comment');
//文章列表
Route::post('lapi/:version/shortxt/showall', 'lapi/:version.Shortxt/showall');
//验证手机
Route::post('lapi/:version/user/checkmobile', 'lapi/:version.User/checkmobile');







/******************************律师端***************************************/


/****************************服务端*************************************/
Route::get('server/:version/setInterval_sms', 'server/:version.Server/setInterval_sms');
Route::get('server/:version/setInterval_ortmp', 'server/:version.Server/setInterval_ortmp');
Route::get('server/:version/setInterval_chat', 'server/:version.Server/setInterval_chat');
Route::get('server/:version/setInterval_status_by_lawyer', 'server/:version.Server/setInterval_status_by_lawyer');
Route::get('server/:version/setInterval_chat_by_user', 'server/:version.Server/setInterval_chat_by_user');
/****************************服务端*************************************/


/****************************客户网页端*************************************/

/*验证码*/
Route::get('client/:version/user/verfiy', 'client/:version.User/verfiy');
/*登录*/
Route::post('client/:version/user/login', 'client/:version.User/login');
/*个人信息*/
Route::post('client/:version/home/show', 'client/:version.Home/show');
/*个人信息*/
Route::post('client/:version/home/showall', 'client/:version.Home/showall');
/*文章分类*/
Route::post('client/:version/content/cate', 'client/:version.Content/cate');
/*内容添加*/
Route::post('client/:version/content/add', 'client/:version.Content/add');
/*内容删除*/
Route::post('client/:version/content/del', 'client/:version.Content/del');
/*内容详情*/
Route::post('client/:version/content/show', 'client/:version.Content/show');
/*内容编辑*/
Route::post('client/:version/content/edit', 'client/:version.Content/edit');
/*内容列表*/
Route::post('client/:version/content/showall', 'client/:version.Content/showall');
/*视频添加*/
Route::post('client/:version/video/add', 'client/:version.Video/add');
/*视频详情*/
Route::post('client/:version/video/show', 'client/:version.Video/show');
/*视频列表*/
Route::post('client/:version/video/showall', 'client/:version.Video/showall');

/****************************客户网页端*************************************/



//所有路由匹配不到情况下触发该路由
Route::miss('\app\Exception::miss');

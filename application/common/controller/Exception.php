<?php
namespace app\common;
use app\common\controller\Send;
/**
 * 异常提示
 */
class Exception
{
	use Send;

	/**
	 * 路由不存在情况
	 */
	public static function miss()
	{
		return self::returnMsg(40004,'route not found');
	}
}
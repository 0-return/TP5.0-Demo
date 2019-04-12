<?php
namespace app\common\controller;

use think\Controller;

Vendor('jpush.autoload');
use JPush\Client as JPush;

class Push extends Controller
{
	private $client;
	private $lawyerclient;
	#极光推送
	private $LAWYER_JMESSAGE_APPKEY = '29399beed8b26c070e45ea93';
	private $LAWYER_JMESSAGE_MASTERSECRET = '0432478feb3ec8917e42d6f4';
	private $JPUSH_APPKEY = '0d0eee6530276ace5a9713f0';
	private $JPUSH_MASTERSECRET = '7635652bbb7fe7ca61c3e324';
	public function __construct()
	{
		$this->client = new JPush($this->JPUSH_APPKEY, $this->JPUSH_MASTERSECRET, null);
		$this->lawyerclient = new JPush($this->LAWYER_JMESSAGE_APPKEY, $this->LAWYER_JMESSAGE_MASTERSECRET, null);
	}
	/*
    * 极光推送
	* @g
    * $content:推送内容
    * $json:推送类型参数array
    */
	public function pushMessage($content, $json, $duan = 'user')
	{
		if (!$content || !$json) {
			$msg['code'] = 'FAIL';
			return  $msg;
		}
		$string = htmlspecialchars_decode($content); // '' "" >> 转义两次
		$alert = htmlspecialchars_decode($string); // '' "" >> 转义两次
		$extras = json_decode(htmlspecialchars_decode($json), true);
		$platform = ['ios', 'android']; //设备
		$options = array( //参数设置
			'apns_production' => true,
			'sendno' => 100,
		);
		$ios_notification = array(
			"sound" => "default",
			'extras' => $extras,
		);
		$android_notification = array(
			'extras' => $extras,
		);
		// 判断推送端
		if ($duan == 'user') {
			$pushobj = $this->client;
			$lawyerpushobj = '';
		} elseif ($duan == 'lawyer') {
			$pushobj = $this->lawyerclient;
			$lawyerpushobj = '';
		} else {
			$pushobj = $this->client;
			$lawyerpushobj = $this->lawyerclient;
		}
		try {
			$response = $pushobj->push()
				->setPlatform($platform)
				->addAllAudience()
				->iosNotification($alert, $ios_notification)
				->androidNotification($alert, $android_notification)
				->options($options)
				->send();
			if (!empty($lawyerpushobj)) {
				$response = $lawyerpushobj->push()
					->setPlatform($platform)
					->addAllAudience()
					->iosNotification($alert, $ios_notification)
					->androidNotification($alert, $android_notification)
					->options($options)
					->send();
			}
			if ($response) {
				$msg['code'] = 'SUCCESS';
				return  $msg;
			} else {
				$msg['code'] = 'FAIL';
				return  $msg;
			}
		} catch (\JPush\Exceptions\JPushException $e) {
			// print $e;
			$msg['code'] = 'FAIL';
			return  $msg;
		}
	}
	/*
    * 定时推送
	* @g
    * $content:推送内容
    * $json:推送类型参数array
	* $time 定时时间
    */
	public function set_schedule($content, $json, $time, $duan = 'user')
	{
		if (!$content || !$json || !$time) {
			$msg['code'] = 'FAIL';
			return  $msg;
		}

		$string = htmlspecialchars_decode($content);
		$alert = htmlspecialchars_decode($string); // '' "" >> 转义两次
		$extras = json_decode(htmlspecialchars_decode($json), true);
		$options = array( //参数设置
			'apns_production' => true,
			'sendno' => 100,
		);
		$platform = ['ios', 'android']; //设备
		$ios_notification = array(
			"sound" => "default",
			'extras' => $extras,
		);
		$android_notification = array(
			'extras' => $extras,
		);
		// 判断推送端
		if ($duan == 'user') {
			$pushobj = $this->client;
			$lawyerpushobj = '';
		} elseif ($duan == 'lawyer') {
			$pushobj = $this->lawyerclient;
			$lawyerpushobj = '';
		} else {
			$pushobj = $this->client;
			$lawyerpushobj = $this->lawyerclient;
		}
		//构建推送对象build
		$payload = $pushobj->push()
			->setPlatform($platform)
			->addAllAudience()
			->iosNotification($alert, $ios_notification)
			->androidNotification($alert, $android_notification)
			->options($options)
			->build();
		if (!empty($lawyerpushobj)) {
			$payload2 = $lawyerpushobj->push()
				->setPlatform($platform)
				->addAllAudience()
				->iosNotification($alert, $ios_notification)
				->androidNotification($alert, $android_notification)
				->options($options)
				->build();
		}
		// 创建在指定时间点触发的定时任务
		try {
			$response = $pushobj->schedule()->createSingleSchedule((string)$time, $payload, array("time" => date("Y-m-d H:i:s", $time)));
			if (!empty($lawyerpushobj)) {
				$response = $lawyerpushobj->schedule()->createSingleSchedule((string)$time, $payload2, array("time" => date("Y-m-d H:i:s", $time)));
			}

			if ($response['http_code'] == 200) {
				$msg['code'] = 'SUCCESS';
				$msg['data'] = $response['body']['schedule_id'];
				return  $msg;
			} else {
				$msg['code'] = 'FAIL';
				return  $msg;
			}
			// echo 'Result=' . json_encode($response);
		} catch (\JPush\Exceptions\JPushException $e) {
			// print $e;
			$msg['code'] = $content;
			return  $msg;
		}
	}
	/*
	* @g
    * 获取定时列表
    */
	public function get_schedule($duan = 'user')
	{
		// 判断推送端
		if ($duan == 'user') {
			$pushobj = $this->client;
			$lawyerpushobj = '';
		} elseif ($duan == 'lawyer') {
			$pushobj = $this->lawyerclient;
			$lawyerpushobj = '';
		} else {
			$pushobj = $this->client;
			$lawyerpushobj = $this->lawyerclient;
		}
		$response = $pushobj->schedule()->getSchedules();
		if (!empty($lawyerpushobj)) {
			$response2 = $lawyerpushobj->schedule()->getSchedules();
		}
		echo json_encode($response);
	}
	/*
	* @g
    * 更新定时任务
    */
	public function update_schedule($schedule_id, $time, $duan = 'user')
	{
		if (!$schedule_id) {
			$msg['code'] = 'FAIL';
			return  $msg;
		}
		if ($time) {
			$trigger['time'] = $time ? $time : null;
		}
		// 判断推送端
		if ($duan == 'user') {
			$pushobj = $this->client;
			$lawyerpushobj = '';
		} elseif ($duan == 'lawyer') {
			$pushobj = $this->lawyerclient;
			$lawyerpushobj = '';
		} else {
			$pushobj = $this->client;
			$lawyerpushobj = $this->lawyerclient;
		}
		// 更新指定的定时任务
		try {
			$response = $pushobj->schedule()->updateSingleSchedule($schedule_id, null, null, null, $trigger);
			if (!empty($lawyerpushobj)) {
				$response2 = $lawyerpushobj->schedule()->updateSingleSchedule($schedule_id, null, null, null, $trigger);
			}
			if ($response['http_code'] == 200) {
				$msg['code'] = 'SUCCESS';
				return  $msg;
			} else {
				$msg['code'] = 'FAIL';
				return  $msg;
			}
		} catch (\JPush\Exceptions\JPushException $e) {
			// print $e;
			$msg['code'] = 'FAIL';
			return  $msg;
		}
	}
	/*
	* @g
    * 删除定时任务
    */
	public function del_schedule($schedule_id, $duan = 'user')
	{
		if (!$schedule_id) {
			$msg['code'] = 'FAIL';
			return  $msg;
		}
		// 判断推送端
		if ($duan == 'user') {
			$pushobj = $this->client;
			$lawyerpushobj = '';
		} elseif ($duan == 'lawyer') {
			$pushobj = $this->lawyerclient;
			$lawyerpushobj = '';
		} else {
			$pushobj = $this->client;
			$lawyerpushobj = $this->lawyerclient;
		}
		$response = $pushobj->schedule()->deleteSchedule($schedule_id);
		if (!empty($lawyerpushobj)) {
			$response2 = $lawyerpushobj->schedule()->deleteSchedule($schedule_id);
		}
		if ($response['http_code'] == 200) {
			$msg['code'] = 'SUCCESS';
			return  $msg;
		} else {
			$msg['code'] = 'FAIL';
			return  $msg;
		}
	}
}

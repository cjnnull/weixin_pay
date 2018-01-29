<?php 
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);
require_once "../lib/WxPay.Exception.php";
require_once "../lib/WxPay.Config.php";
require_once "../lib/WxPay.Data.php";
require_once "../lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";
require_once 'log.php';

function alert($msg,$redirect){
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><script>alert("'.$msg.'");';
	if($redirect=='BACK')
		echo 'history.back();</script></head></html>';
	elseif($redirect=='CLOSE')
		echo 'window.close();</script></head></html>';
	else
		echo 'location.href="'.$redirect.'";</script></head></html>';
	exit;
}
//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}
//①、获取用户openid
$tools = new JsApiPay();
//$openId = $tools->GetOpenid();

//②、统一下单
$state=explode('|',$_GET['state']);
$payorder_order_id=$state[0];
$amount=$state[1];
$payorder_type=$state[2];
$body=@urldecode($state[3]);
$openId=@($state[4]);


$input = new WxPayUnifiedOrder();
$input->SetBody($body);
$input->SetAttach($payorder_order_id);
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
$input->SetTotal_fee($amount*100);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("");
$input->SetNotify_url("http://zuren.haiwei007.com/api/weixin/example/notify.php");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);
try{
	$jsApiParameters = $tools->GetJsApiParameters($order);
}catch(Exception $e){
	if($payorder_type=='BUYVIP'){
		header('location:http://zuren.haiwei007.com/m/myorder');
	}elseif(strstr($payorder_type,'DATE')){
		$date_id=end(explode('_',$payorder_type));
		header('location:http://zuren.haiwei007.com/m/mydatedetails/'.$date_id);
	}elseif($payorder_type=='TESTPAY'){
		header('location:http://zuren.haiwei007.com/m/myorder');
	}elseif($payorder_type=='BUYSHOP'){
		header('location:http://zuren.haiwei007.com/m/myorder');
	}elseif($payorder_type=='SHOP'){
		header('location:http://zuren.haiwei007.com/m/orders');
	}
}


//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */
 
?>

<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/> 
	<title>微信支付</title>
	<script type="text/javascript">
	function jsApiCall(){
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			<?php echo $jsApiParameters; ?>,
			function(res){
				<?php
					$payorder_order_id_list=array();
					$cookie=@explode(',',$_COOKIE['payorder_order_id_list']);
					$cookie[]=$payorder_order_id;
					setcookie('payorder_order_id_list',implode(',',$cookie),time()+864000,'','','');
				?>
				<?php if($payorder_type=='BUYVIP' or $payorder_type=='BUYSHOP'):?>
					location.href='http://zuren.haiwei007.com/m/myorder';
				<?php elseif(strstr($payorder_type,'DATE')):?>
					<?php
						$date_id=end(explode('_',$payorder_type));
					?>
					location.href='http://zuren.haiwei007.com/m/mydatedetails/<?php echo $date_id;?>';
				<?php elseif($payorder_type=='SHOP'):?>
					location.href='http://zuren.haiwei007.com/m/orders';
				<?php elseif($payorder_type=='TESTPAY'):?>
					location.href='http://zuren.haiwei007.com/m/orders';
				<?php endif;?>
			}
		);
	}

	function callpay(){
		if (typeof WeixinJSBridge == "undefined"){
			if( document.addEventListener ){
				document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
			}else if (document.attachEvent){
				document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
				document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
			}
		}else{
			jsApiCall();
		}
	}
	callpay();
	</script>
</head>
<body>
</body>
</html>
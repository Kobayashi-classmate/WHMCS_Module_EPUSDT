<?php

function epusdt_config()
{
	return [
		"FriendlyName" => array("Type" => "System", "Value" => "EPUSDT"),
		"api" => array("FriendlyName" => "API域名", "Type" => "text"),
		"token" => array("FriendlyName" => "Token", "Type" => "text"),
	];
}

function epusdt_link($params)
{
	$systemurl = rtrim($params['systemurl'], '/');

	# Gateway Specific Variables
	$gatewayToken = $params['token'];

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$amount = (float)$params['amount']; # Format: ##.##

	# Payment Process 
	$order_id = "usdt-" . substr(md5(uniqid()), 15, 8) . "-" . $invoiceid;

	$data = [
		'order_id' => $order_id,
		'amount' => $amount,
		'notify_url' => $systemurl . '/modules/gateways/callback/epusdt.php',
		'redirect_url' => $systemurl . '/viewinvoice.php?id=' . $invoiceid,
	];
	$data['signature'] = epusdt_sign($data, $gatewayToken);

	$body = epusdt_curl(rtrim($params['api'], "/") . '/api/v1/order/create-transaction', json_encode($data), ["content-type: application/json"]);
	$response = json_decode($body, true);
	if ($response['status_code'] != 200) {
		return "支付网关返回状态码 (" . $response['status_code'] . ') 消息: ' . $response['message'];
	}

	return <<<EOF
<button style="width: 143px; height: 40px; background: url(data:image/png;base64,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) no-repeat left top; color: #FFF;" onclick="window.location.href='{$response["data"]["payment_url"]}'">去支付</button>
EOF;
	//return ;

}

function epusdt_sign(array $parameter, string $signKey)
{
	ksort($parameter);
	reset($parameter);
	$sign = '';
	foreach ($parameter as $key => $val) {
		if ($val == '') continue;
		if ($key != 'signature') {
			if ($sign != '') {
				$sign .= "&";
			}
			$sign .= "$key=$val";
		}
	}
	$sign = md5($sign . $signKey);
	return $sign;
}

function epusdt_curl($url, $data = null, $header = null)
{
	$curl = curl_init();
	if (!empty($header)) {
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HEADER, 0); //返回response头部信息
	}

	//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
	//curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	if (!empty($data)) {
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$output = curl_exec($curl);
	curl_close($curl);
	return $output;
}

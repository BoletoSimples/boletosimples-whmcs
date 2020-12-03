<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';
$params = getGatewayVariables('gofasboletosimples');
if (!$params['type']) {die("Module Not Activated");}
$post = json_decode(file_get_contents('php://input'), true);
if ($params['log']) {
    logModuleCall('gofasboletosimples','receive_callback',array('module_version'=>'1.0.0','POST'=>$data),'', array( 'error'=>'') );
}
$invoice_id		= $post['object']['control_number'];
$charge_code	= $post['object']['id'];
if($invoice_id and $charge_code){
	$invoice = localAPI('getinvoice',array('invoiceid'=>$invoice_id),(int)$params['admin']);
	if( (int)$invoice['invoiceid'] !== (int)$invoice_id) {
		$error = 'Invoice Not Found';
	}
	elseif( (int)$invoice['invoiceid'] === (int)$invoice_id and $invoice['status'] === 'Unpaid' ) {
		if( !function_exists('gbsp_callback') ) {
			function gbsp_callback($charge_url,$charge_code,$token){		
			$curl = curl_init($charge_url.'bank_billets/'.$charge_code);
  			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token,'Content-Type: application/json',));
  			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  			curl_setopt($curl, CURLOPT_POST, false);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
  			curl_setopt($curl, CURLOPT_USERAGENT, 'Módulo Gofas Boleto Simples para WHMCS (gbsp@gofas.net)');
			$result = json_decode(curl_exec($curl), true);
			curl_close($curl);
            return $result;
		}}
        if($params['sandbox']){
			$api_mode='sandbox';
            $token=$params['sandbox_token'];
            $charge_url='https://sandbox.boletosimples.com.br/api/v1/';
        }
        if(!$params['sandbox']){
			$api_mode='live';
            $token = $params['token'];
            $charge_url='https://boletosimples.com.br/api/v1/';
        }
		$callback = gbsp_callback($charge_url,$charge_code,$token);
        if ($params['log']) {
           logModuleCall('gofasboletosimples','gbsp_callback',array('module_version'=>'1.0.0','callback'=>$callback),'', array( 'error'=>'') );
        }
		if ((string)$callback['status'] === (string)'paid') {
			$payment_id			= $callback['bank_billet_payments']['0']['id'];
			$payment_amount		= $callback['bank_billet_payments']['0']['paid_amount'];
			if ( $payment_amount > $invoice['total'] ) {
				$update_invoice = localAPI('updateinvoice', array( 'invoiceid' => $invoice_id, 'newitemdescription' => array('Acréscimos calculados no momento da emissão do boleto'),'newitemamount' => array( (float)($payment_amount - $invoice['total'] )) ), (int)$params['admin'] );
			}
			if ( $payment_amount < $invoice['total'] ) {
				$update_invoice = localAPI('updateinvoice', array( 'invoiceid' => $invoice_id, 'newitemdescription' => array('Descontos calculados no momento da emissão do boleto'),'newitemamount' => array( (float)($invoice['total'] - $payment_amount)) ), (int)$params['admin'] );
			}
			if( $update_invoice['result'] and $update_invoice['result'] !== 'success') {
				$error = $update_invoice['result'];
			}
			elseif($update_invoice['result'] === 'success' or !$update_invoice['result']) {
 				$addtransvalues['userid']			= $invoice['userid'];
 				$addtransvalues['invoiceid']		= $invoice_id;
 				$addtransvalues['description']		= 'Boleto pago';
 				$addtransvalues['amountin']			= $payment_amount;
 				$addtransvalues['fees']				= '';
 				$addtransvalues['paymentmethod']	= 'gofasboletosimples';
 				$addtransvalues['transid']			= 'gbsp_'.$api_mode.'_paid-'.$charge_code.'';
 				$addtransvalues['date']				= date('d/m/Y');
				$addtransresult						= localAPI( 'addtransaction' , $addtransvalues, (int)$params['admin']);
			}
		}
	}
	if ($params['log']) {
		logModuleCall('gofasboletosimples','receive_callback',array('module_version'=>'1.3.0','POST'=>$post,'invoice'=>$invoice,'postfields'=>$postfields),'', array( 'error'=>$error, 'callback'=>$callback,'UpdateInvoice'=> $update_invoice) );
	}
}
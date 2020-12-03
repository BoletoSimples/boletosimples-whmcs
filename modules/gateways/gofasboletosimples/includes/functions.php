<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */

if (!defined("WHMCS")) {die();}
use WHMCS\Database\Capsule;

/**
 *
 * Get Config
 * @return Array
 *
 */
if(!function_exists('gbsp_config')){
	function gbsp_config($set=false){
		$params = getGatewayVariables('gofasboletosimples');
		if($set) {
			return $params[$set];
		}
		return $params;
	}
}

/**
 *
 * Obter OAuth Token
 * @ggnb_get_token
 * $token = ggnb_get_token($client_id, $client_secret, $api_url);
 *
 
if( !function_exists('gbsp_get_token') ) {
	function gbsp_get_token($api_url,$token) {
		$curl = curl_init($api_url.'userinfo');
  		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token,'Content-Type: application/json',));
  		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
  		curl_setopt($curl, CURLOPT_USERAGENT, 'Módulo Gofas Boleto Simples para WHMCS (gbsp@gofas.net)');
		$result = json_decode(curl_exec($curl), true);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return $result;
	}
}
*/
/**
 *
 * Connect to Gateway
 * @ post Array
 * @ return Array
 *
 */
if( !function_exists('gbsp_charge') ) {
function gbsp_charge($charge_url,$postfields) {
	// '{"bank_billet":{"amount":12.34, "expire_at": "2021-11-15", "description": "Prestação de Serviço", "customer_person_name": "Nome do Cliente", "customer_cnpj_cpf": "125.812.717-28", "customer_zipcode": "12312123", "customer_address": "Rua quinhentos", "customer_city_name": "Rio de Janeiro", "customer_state": "RJ", "customer_neighborhood": "bairro"}}' 
	if((string)$postfields['endpoint'] === (string)'bank_billets'){
		$post = array('bank_billet'=>array(
		'amount'=>(float)$postfields['amount'],
		'expire_at'=>$postfields['expire_at'],
		'description'=>$postfields['description'],
		'customer_person_name'=>$postfields['customer_person_name'],
		'customer_cnpj_cpf'=>$postfields['customer_cnpj_cpf'],
		'customer_zipcode'=>$postfields['customer_zipcode'],
		'customer_address'=>$postfields['customer_address'],
		'customer_city_name'=>$postfields['customer_city_name'],
		'customer_state'=>$postfields['customer_state'],
		'customer_neighborhood'=>$postfields['customer_neighborhood'],
		'control_number'=>$postfields['control_number'],
		));
	}
	if((string)$postfields['endpoint'] === (string)'webhooks'){
		$post = array('webhook'=>array(
			'url'=>$postfields['url'],
		));
	}
    	$curl = curl_init($charge_url.$postfields['endpoint']);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$postfields['token'],'Content-Type: application/json',));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Módulo Gofas Boleto Simples para WHMCS (gbsp@gofas.net)');
		$result = json_decode(curl_exec($curl), true);
    	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return $result;
}}

/**
 *
 * Gravar transação no WHMCS
 * @gbsp_add_trans
 *
 */
if( !function_exists('gbsp_add_trans') ) {
function gbsp_add_trans( $user_id, $invoice_id, $charge_id, $api_mode, $status ) {	
 	$addtransvalues['userid'] = $user_id;
 	$addtransvalues['invoiceid'] = $invoice_id;
 	$addtransvalues['description'] = "Boleto gerado.";
 	$addtransvalues['amountin'] = '0.00';
 	$addtransvalues['fees'] = '0.00';
 	$addtransvalues['paymentmethod'] = 'gofasboletosimples';
 	$addtransvalues['transid'] = 'gbsp_'.$api_mode.'_'.$status.'-'.$charge_id.'';
 	$addtransvalues['date'] = date('d/m/Y');
	$addtransresults = localAPI( "addtransaction", $addtransvalues,(int)gbsp_config('admin'));

	if ( $addtransresults['result'] === 'success' ) {
		return array('values'=>$addtransvalues, 'result'=>$addtransresults);
	}
	elseif ($addtransresults['result'] !== 'success') {
		$error = '<b>Não foi possível armazenar o Boleto gerado, consulte o suporte.</b>';
		return array('error'=>$error, 'values'=>$addtransvalues, 'result'=>$addtransresults);
	}
}}
/**
 *
 * Verify Webhook
 * @return Array
 *
 */
 
if(!function_exists('gbsp_verify_webhook')) {
	function gbsp_verify_webhook($charge_url,$postfields) {
		// List
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbsp_webhook') -> get( array( 'value' ) ) as $gbsp_webhook_ ) {
			$gbsp_webhook					= $gbsp_webhook_->value;
		}
		if($gbsp_webhook){
			$curl = curl_init($charge_url.'webhooks/'.$gbsp_webhook);
  			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$postfields['token'],'Content-Type: application/json',));
  			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  			curl_setopt($curl, CURLOPT_POST, false);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
  			curl_setopt($curl, CURLOPT_USERAGENT, 'Módulo Gofas Boleto Simples para WHMCS (gbsp@gofas.net)');
			$gbsp_webhook = json_decode(curl_exec($curl), true);
			curl_close($curl);
			if($postfields['url'] === $gbsp_webhook['url']){
				return array('exist'=>$gbsp_webhook);
			}
		}
		else{
			// Create
			$webhook = gbsp_charge($charge_url,$postfields);
			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'gbsp_webhook', 'value' => $webhook['id'], 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }
			
			if(!$webhook['error'] and !$webhook['errors']){
				return array('created'=>$webhook,$webhooks);
			}
			elseif($webhook['errors']){
				foreach($webhook['errors'] as $key => $value){
					$error .= $key.' '.implode("\n", $value).'<br>';
				}
				return array('error'=>$error,$webhook,$webhooks);
			}
			elseif($webhook['error']){
				return array('error'=>$webhook['error'],$webhook,$webhooks);
			}
		}
	}
}

/**
 *
 * Verify Instalation
 * @return Array
 *
 */
 
if(!function_exists('gbsp_verify_install')) {
function gbsp_verify_install() {
	if(!Capsule::schema()->hasTable('gofasboletosimples')){
    	try{
			Capsule::schema()->create('gofasboletosimples', function($table){
        		$table->increments('id');
				$table->string('invoice_id');
				$table->string('billet_id');
				$table->string('url');
				$table->string('expire_at');
				$table->string('amount');
				$table->string('paid_amount');
				$table->string('status');
				$table->string('line');
				$table->string('api_mode');
    		});
		}
		catch(\Exception $e){
    		$error .= "Não foi possível criar a tabela do módulo no banco de dados: {$e->getMessage()}";
		}
	}
	if(!$error) {
		return array('sucess'=>1);
	}
	elseif($error) {
		return array('error'=>$error);
	}
}}

/**
 *
 * Grava Boleto no DB
 *
 */
if( !function_exists('gbsp_store_billet') ) {
function gbsp_store_billet($billet,$api_mode) {
	 $date = str_replace('/', '-', $billet['data']['charges']['0']['dueDate']) ;
	 $dueDate = date("Y-m-d", strtotime($date));
	 $data = array(
				'invoice_id'=>$billet['control_number'],
				'billet_id'=>$billet['id'],
				'url'=>$billet['url'],
				'expire_at'=>$billet['expire_at'],
				'amount'=>$billet['amount'],
				'paid_amount'=>$billet['paid_amount'],
				'status'=>$billet['status'],
				'line'=>$billet['line'],
				'api_mode'=>$api_mode,
			);
	 try {
		$save_billet = Capsule::table('gofasboletosimples') ->insert($data);
	}
	catch (\Exception $e) {
		$error .= "Não foi possível salvar o Boleto no banco de dados. {$e->getMessage()}";
	}
	
	if ($error) {
		if (gbsp_config('log')) {
			return array('error'=>$error, 'data'=>$data, 'date'=>$date,'duedate'=>$dueDate, 'save_billet'=>$save_billet);
		}
		else {
			return array('error'=>$error);
		}
	}
	elseif (!$error) {
		if (gbsp_config('log')) {
			return array('sucess'=>true, 'data'=>$data, 'date'=>$date,'duedate'=>$dueDate, 'save_billet'=>$save_billet);
		}
		else {
			return array('sucess'=>true);
		}
		
	}
		
}}

/**
 *
 * Envia email ao admin em caso de erro
 * gbsp_send_error_email
 *
 */
if( !function_exists('gbsp_send_error_email') ) {
function gbsp_send_error_email( $invoice_id, $error) {
 	$sendEOEvalues['customsubject'] = 'Erro ao gerar boleto - fatura #'.$invoice_id;
	$sendEOEvalues['custommessage'] = '<br/>Olá administrador,<br/>
		Ocorreu uma falha ao gerar um Boleto para a <a href="'.gbsp_whmcs_admin_url().'invoices.php?action=edit&id='.$invoice_id.'">Fatura #'.$invoice_id.'</a>.<br/><br/>
		<b>Erro exibido na ao cliente Fatura:</b><br/><i>"'.$error.'"</i><br/><br/>
		Email gerado de acordo com às configurações do módulo <a title="Ir para as configurações do módulo ↗" href="'.gbsp_whmcs_admin_url().'configgateways.php?updated=gofasboletosimples#m_gofasboletosimples">Gofas Boleto Simples</a>.<br/><br/>';
 	$sendEOEvalues['type'] = 'system';
 	$sendEOEvalues['deptid'] = gbsp_config('emailonerror');
 	$sendEOEresults = @localAPI("sendadminemail",$sendEOEvalues,(int)gbsp_config('admin'));
		
	if (gbsp_config('log') and $sendEOEresults['result'] === 'success'){
		$debug_result['Email enviado ao admin do WHMCS notificando o erro'] = $sendEOEresults;
	} elseif(gbsp_config('log') and $sendEOEresults['result'] !== 'success') {
		$debug_result['Falha ao enviar email ao admin do WHMCS notificando o erro'] = $sendEOEresults;
	}
	if(gbsp_config('log')) {
		return array('values'=>$sendEOEvalues,'result'=>$sendEOEresults, 'debug'=> $debug_result );
	}
	else{
		return array('values'=>$sendEOEvalues,'result'=>$sendEOEresults, 'debug'=>false );
	}
}}

/**
 *
 * Get customer details
 * @gbsp_customer
 * $customer = ggnc_customer($client_id);
 *
 */
if( !function_exists('gbsp_customer') ) {
	function gbsp_customer($client_id) {
		$client = localAPI('GetClientsDetails',array( 'clientid' => $client_id, 'stats' => false, ), (int)gbsp_config('admin'));
		//Determine custom fields id
		$customfields = array();
		foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' )  -> get( array( 'fieldname', 'id' ) ) as $customfield ) {
			$customfield_id					= $customfield->id;
			$customfield_name				= ' '.strtolower( $customfield->fieldname );
	
			// cpf
			if( strpos( $customfield_name, 'cpf') and !strpos( $customfield_name, 'cnpj') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client['userid'] ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}	
			// cnpj
			if( strpos( $customfield_name, 'cnpj') and !strpos( $customfield_name, 'cpf') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client['userid'] ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// cpf + cnpj
			if( strpos( $customfield_name, 'cpf') and strpos( $customfield_name, 'cnpj') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client['userid'] ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
		}
		// nascimento
		if( strpos( $customfield_name, 'nascimento') ) {
			foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $client['userid'] ) -> get( array( 'value' ) ) as $customfieldvalue ) {
				$birt_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
			}
		}
		$birthday_pre			= preg_replace('/[^\da-z]/i', '', $birt_customfield_value);
		if(strlen($birthday_pre) === 8) {
			$birth_ = $birthday_pre;
		}
		elseif( strlen($birthday_pre) === 7 ) {
			$birth_ = '0'.$birthday_pre;
		}
		$birth_Y					= substr($birth_, -4);
		$birth_m					= substr($birth_, 2, -4);
		$birth_d					= substr($birth_, 0, -6);
		$birthday					= $birth_Y.'-'.$birth_m.'-'.$birth_d; 
		$customer['birthday'] 		= $birthday;

		// Cliente possui CPF e CNPJ
		// CPF com 1 nº a menos, adiciona 0 antes do documento
		if( strlen( $cpf_customfield_value ) === 10 ) {
			$cpf = '0'.$cpf_customfield_value;
		}
		// CPF com 11 dígitos
		elseif( strlen( $cpf_customfield_value ) === 11) {
			$cpf = $cpf_customfield_value;
		}
		// CNPJ no campo de CPF com um dígito a menos
		elseif( strlen( $cpf_customfield_value ) === 13 ) {
			$cpf = false; 
			$cnpj = '0'.$cpf_customfield_value;
		}
		// CNPJ no campo de CPF
		elseif( strlen( $cpf_customfield_value ) === 14 ) {
			$cpf 				= false;
			$cnpj				= $cpf_customfield_value;
		}
		// cadastro não possui CPF
		elseif( !$cpf_customfield_value || strlen( $cpf_customfield_value ) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen( $cpf_customfield_value ) !== 13 || strlen($cpf_customfield_value) !== 14 ) {	
			$cpf = false;
		}
		// CNPJ com 1 nº a menos, adiciona 0 antes do documento
		if( strlen($cnpj_customfield_value) === 13 ) {
			$cnpj = '0'.$cnpj_customfield_value;
		}
		// CNPJ com nº de dígitos correto
		elseif( strlen($cnpj_customfield_value) === 14 ) {
			$cnpj = $cnpj_customfield_value;
		}
		// Cliente não possui CNPJ
		elseif( !$cnpj_customfield_value and strlen( $cnpj_customfield_value ) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen( $cpf_customfield_value ) !== 13 and strlen( $cpf_customfield_value ) !== 14  ) {
			$cnpj = false;
		}
		if( ( $cpf and $cnpj ) or ( !$cpf and $cnpj ) ) {
			$customer['doc_type']	= 2;
			$customer['document']	= $cnpj;
			if( $client['companyname'] ) {
				$customer['name']	= $client['companyname'];
			}
			elseif( !$client['companyname'] ) {
				$customer['name']	= $client['firstname'].' '.$client['lastname'];
			}
		}
		elseif( $cpf and !$cnpj ) {
			$customer['doc_type']	= 1;
			$customer['document']	= $cpf;
			$customer['name']	= $client['firstname'].' '.$client['lastname'];
		}

		if(!$cpf and !$cnpj ) {
			$error = 'CPF e/ou CNPJ ausente.';
		}
		
		if($client['phonenumber']){
			$customer_phone = preg_replace('/[^\da-z]/i', '', $params['clientdetails']['phonenumber']);
			if(strlen($customer_phone) === 12){ // +55
				$customer['phone'] = substr($customer_phone, 1);
			}
			if(strlen($customer_phone) === 13){ // +55
				$customer['phone'] = substr($customer_phone, 2);
			}
			else {
				$customer['phone'] = $customer_phone;
			}
		}
		if(!$client['phonenumber']){
			$error = 'Telefone inválido';
		}
		if($error) {
			$customer['error'] = $error;
		}
		return $customer;
	}
}
if( !function_exists('gbsp_whmcs_url') ) {
	function gbsp_whmcs_url(){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsurl') -> get( array('value') ) as $gbspwhmcsurl_ ) {
			$gbspwhmcsurl					= $gbspwhmcsurl_->value;
		}
		return $gbspwhmcsurl;
	}
}
if( !function_exists('gbsp_whmcs_admin_url') ) {
	function gbsp_whmcs_admin_url(){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsadminurl') -> get( array('value') ) as $gbspwhmcsadminurl_ ) {
			$gbspwhmcsadminurl				= $gbspwhmcsadminurl_->value;
		}
		return $gbspwhmcsadminurl;
	}
}
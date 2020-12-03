<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */
if(!defined('WHMCS')) { die(); }
use WHMCS\Database\Capsule;
// Define debug
if ( stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php')){
	if($params['log']){
		$debug				= true;
	}
	if($params['log']){
		$log				= true;
	}
	if($params['log'] || $params['debug']){
		$debug_or_log		= true;
	}
}
else {
	$debug				= false;
}
$debug_result = array();

// Parâmetros do sistema
$module_version				= '1.0.0';
$companyName				= $params['companyname'];

// Get WHMCS System Info
foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsurl') -> get( array('value')) as $gbspwhmcsurl_ ) {
	$whmcs_url	= $gbspwhmcsurl_->value;
}
foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsadminurl') -> get( array('value')) as $gbspwhmcsadminurl_ ) {
	$whmcs_admin_url = $gbspwhmcsadminurl_->value;
}
foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsadminpath') -> get( array('value')) as $gbspwhmcsadminpath_ ) {
	$whmcs_admin_path = $gbspwhmcsadminpath_->value;
}
$return_url					= $whmcs_url.'modules/gateways/gofasboletosimples/includes/callback.php';

if($debug_or_log) {
	$debug_result['Informações da instalação'] =array('whmcs_url'=>$whmcs_url, 'admin_path'=> $whmcs_admin_path, 'admin_url'=> $whmcs_admin_url);
}
if (!$debug) {
	$redirect_to_billet	= $params['redirecttobillet'];
}
elseif ($debug) {
	$redirect_to_billet	= false;
}
$fine						= $params['fine']; // Multa
$interest					= $params['interest']; // Juros
$fee						= $params['fee'];
$show_due_date				= $params['showduedate'];
$show_bar_code				= $params['showbarcode'];
$show_discount_tax			= $params['showdiscountortax'];
$email_on_error				= $params['emailonerror'];
// Dias adicionais à Data de vencimento
if ( $params['daysfordue'] ) {
	$days_for_due		= '+'.$params['daysfordue'].' days';

} elseif ( $params['daysfordue'] === '0' ) {
	$days_for_due		= 'zero';
}

elseif ( !$params['daysfordue'] ) {
	$days_for_due		= '+1 day';
}
else {
	$days_for_due		= false;
}

if ( $params['minimun_amount'] ) {
	$minimunAmount			= $params['minimun_amount'];
}
elseif ( !$params['minimun_amount'] || $params['minimun_amount'] < '2.50' ) {
	$minimunAmount			= '2.50' ;
}

// Pay Button
if ($params['paybutton']){
	$payButton				= '<img alt="Visualizar Boleto" src="'.$params['paybutton'].'">';
}elseif(!$params['paybutton']){
	$payButton				= 'Visualizar Boleto';
}
if($params['admin']) {
	$whmcs_admin				= $params['admin'];
}elseif(!$params['admin']){
	$whmcs_admin				= 1;
}

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

// Parâmetros da fatura
$invoice = localAPI('getinvoice',array('invoiceid'=>$params['invoiceid']),(int)$params['admin']);

// Data de vencimento da fatura
$invoice_duedate					= $invoice['duedate'];
$debug_result['invoice_due_date']	= $invoice_duedate;

if ( $invoice_duedate >= date('Y-m-d') ) {
	$billet_duedate			= date('Y-m-d', strtotime($invoice_duedate));
	
} elseif( $invoice_duedate < date('Y-m-d') and !$days_for_due ) {
	$billet_duedate			= date('Y-m-d', strtotime('+1 day')); // Se fatura já venceu, data de vencimento do boleto = Hoje + 1 dia
	
} elseif( $invoice_duedate < date('Y-m-d') and $days_for_due and $days_for_due !== 'zero' ) {
	$billet_duedate			= date('Y-m-d', strtotime( $days_for_due )); // Se fatura já venceu, data de vencimento do boleto = Hoje + X dia(s)

} elseif( $invoice_duedate < date('Y-m-d') and $days_for_due and $days_for_due === 'zero' ) {
	$billet_duedate			= date('Y-m-d'); // Se fatura já venceu, data de vencimento do boleto = Hoje
}

////
$invoiceTotal		=	$invoice['total'];
$invoice_credit	=	$invoice['credit'];

// Parâmetros das transações associadas à Fatura
$trans_idendA				= $invoice['transactions'];
if($trans_idendA) {
	$trans_idend				= $trans_idendA['transaction'];
}
if ($trans_idend) {
	$trans_idp				= end( $trans_idend );
	$trans_id_				= $trans_idp['transid'];
	
	// Verifica se a transação pertence ao módulo
	if ( strpos( $trans_id_, 'gbsp') !== false and strpos( $trans_id_, $api_mode) !== false ) {
		$trans_id					= (int)preg_replace('/\D/', '', $trans_id_ );
	}
	else {
		$trans_id				= false;
	}
	
}

// Itens de Linha - Serviços/produtos relacionados à fatura
$invoice_items_item	= $invoice['items']['item'];
$line_items = array();
foreach( $invoice_items_item as $Value){
	$line_items[]	= substr( $Value['description'],  0, 80).' | R$ '.number_format( $Value['amount'],  2, ',', '.' );	
}
$customer = array();
// Parametros do Cliente
$user_id					= $params['clientdetails']['id'];
$customer['id']				= $params['clientdetails']['id'];
$customer['address1']		= $params['clientdetails']['address1'];
$customer['address2']		= $params['clientdetails']['address2']; // Bairro
$customer['postcode']		= preg_replace('/[^0-9]/', '', $params['clientdetails']['postcode']);
$customer['state']			= $params['clientdetails']['state'];
$customer['city']			= $params['clientdetails']['city'];
$customer['phone']			= preg_replace('/[^0-9]/', '', $params['clientdetails']['phonenumber']);


/**
 *
 * Determine custom fields id
 *
 */
$customfields = array();
foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' )  -> get( array( 'fieldname', 'id' ) ) as $customfield ) {
	
	$customfield_id					= $customfield->id;
	$customfield_name				= ' '.strtolower( $customfield->fieldname );
	
	// cpf
	if ( strpos( $customfield_name, 'cpf') and !strpos( $customfield_name, 'cnpj') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}	
	// cnpj
	if ( strpos( $customfield_name, 'cnpj') and !strpos( $customfield_name, 'cpf') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}
	// cpf + cnpj
	if ( strpos( $customfield_name, 'cpf') and strpos( $customfield_name, 'cnpj') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
			$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}
}

if($debug_or_log) {
	$debug_result['customfields'] =array('customfield'=>$customfield, 'customfields'=> $customfields, 'cpf_customfield_value'=> $cpf_customfield_value, 'cnpj_customfield_value'=> $cnpj_customfield_value);
}

// Cliente possui CPF e CNPJ
// CPF com 1 nº a menos, adiciona 0 antes do documento
if ( strlen( $cpf_customfield_value ) === 10 ) {
	$cpf = '0'.$cpf_customfield_value;
}
// CPF com 11 dígitos
elseif ( strlen( $cpf_customfield_value ) === 11) {
	$cpf = $cpf_customfield_value;
}
// CNPJ no campo de CPF com um dígito a menos
elseif ( strlen( $cpf_customfield_value ) === 13 ) {
	$cpf = false; 
	$cnpj = '0'.$cpf_customfield_value;
}
// CNPJ no campo de CPF
elseif ( strlen( $cpf_customfield_value ) === 14 ) {
	$cpf 				= false;
	$cnpj				= $cpf_customfield_value;
}
// cadastro não possui CPF
elseif ( !$cpf_customfield_value || strlen( $cpf_customfield_value ) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen( $cpf_customfield_value ) !== 13 || strlen($cpf_customfield_value) !== 14 ) {	
	$cpf = false;
}
// CNPJ com 1 nº a menos, adiciona 0 antes do documento
if ( strlen($cnpj_customfield_value) === 13 ) {
	$cnpj = '0'.$cnpj_customfield_value;
}
// CNPJ com nº de dígitos correto
elseif ( strlen($cnpj_customfield_value) === 14 ) {
	$cnpj = $cnpj_customfield_value;
}
// Cliente não possui CNPJ
elseif ( !$cnpj_customfield_value and strlen( $cnpj_customfield_value ) !== 14 and strlen($cnpj_customfield_value) !== 13
and strlen( $cpf_customfield_value ) !== 13 and strlen( $cpf_customfield_value ) !== 14  ) {
	$cnpj = false;
}
if ( ( $cpf and $cnpj ) or ( !$cpf and $cnpj ) ) {
	$customer['doc_type']	= 2;
	$customer['document']	= $cnpj;
	if ( $params['clientdetails']['companyname'] ) {
		$customer['name']	= $params['clientdetails']['companyname'];
	}
	elseif ( !$params['clientdetails']['companyname'] ) {
		$customer['name']	= $params['clientdetails']['firstname'].' '.$params['clientdetails']['lastname'];
	}
}
elseif ( $cpf and !$cnpj ) {
	$customer['doc_type']	= 1;
	$customer['document']	= $cpf;
	$customer['name']	= $params['clientdetails']['firstname'].' '.$params['clientdetails']['lastname'];
}

if (!$cpf and !$cnpj ) {
	$error = 'CPF e/ou CNPJ ausente.';
}
// CSS da fatura
$css = '<style type="text/css">';
$css .= '
	a, a:hover {cursor: pointer;}
	.gbspp {font-size:12px;margin: 0;}
	.gbspspan{font-size:12px;}
	span.gbsperror {color: red;}
	.invoice-container .payment-btn-container {
    	margin-top: 5px;
    	text-align: center;
    	width: 150%;
    	margin-left: -25%;
	}
	.debug {
		padding:5px;
	}
	.debug .ok {
		color:#5cb85c;
		font-weight: 600;
	}
	.debug a,
	.debug p a {
		text-decoration: underline;
	}
	.error,
	.debug .error {
		color: red;
	}
	#gbspclic {
		font-size:13px; font-weight: 700;color: #458ec9;
	}
	#linDig {
		font-size: 12px; border-bottom: 1px solid #9E9E9E; max-width: 360px; margin: 0 auto; padding: 0px 0px 10px 0px;
	}
	#gbspbilletinfo {
		text-align: right; max-width: 300px; margin: 10px auto;
	}
	div#gbspbilletinfo p {
    	line-height: 1;
	}
	';
if ( !$params['paybutton'] ) {
	$css .= '
		a#gbspviewbillet {
			background: #1992c6;
			color: #fff;
			border:none;
			padding:10px 20px;
			position: relative;
			top: 10px;
			cursor:pointer;
		}
		a#gbspviewbillet:hover, a#gbspviewbillet:active {
			background:#20b1ef;
			text-decoration: none;
			cursor: pointer;
		}
';
}
$css .= '</style>';
/*
 *
 * Descontos e Taxas
 * @params:
 *
 * * discountorfee			= 1-Desconto ou 2-Taxa
 * * valueofdiscountorfee	= Valor do Desconto ou da Taxa
 * * daysfordiscount		= 'Validade do Desconto' nº de dias antes do vencimento para dar desconto
 * * fine					= Multa após o vencimento
 * * interest				= Juros após o vencimento
 * 
 *
*/


// Define desconto personalizado 
$customdiscountfield		= 'customfields'.$params['customdiscountfield']; // ID do campo
$customdiscounttypefield	= 'customfields'.$params['customdiscounttypefield']; // ID do campo
$custom_discount_type		= $params['clientdetails']["$customdiscounttypefield"];
$custom_discount_value		= $params['clientdetails']["$customdiscountfield"];
if ( $custom_discount_value and $custom_discount_type ) {
	$discount_tax			= 1; // %
	$discount_tax_value		= $custom_discount_value;
	if ( strpos( $custom_discount_type, '%' ) !== false ) {
		$discount_tax_type		= 1; // %
	}
	if ( strpos( $custom_discount_type, '$' ) !== false ) {
		$discount_tax_type		= 2; // R$
	}
}
else {
	$discount_tax				= (int)$params['discountorfee'];      // 1 = desconto, 2 = taxa
	$discount_tax_type			= (int)$params['tipeofdiscountorfee'];  // 1 = %, 2 = R$  
	$discount_tax_value 		= $params['valueofdiscountorfee'];    // valor do desconto ou da taxa 
}

// Validade do desconto
$days_for_discount			= (string)$params['daysfordiscount']; // $invoice_duedate - $days_for_discount

// Desconto até X dias antes da data de vencimento
if ( (int)$days_for_discount >= 1 ) {
	$discount_valid_until = date('Y-m-d', strtotime($invoice_duedate.' -'.$days_for_discount.' days'));
	if(strtotime($discount_valid_until) >= strtotime(date( 'Y-m-d' ))){
		$billet_duedate = $discount_valid_until;
		$maxoverduedays  = 0;
	}
	$debug_result['discount_valid_until situation'] = '1';
	$debug_result['discount_valid_until'] = $discount_valid_until;
}
// Desconto até a data de vencimento
if ( (int)$days_for_discount === 0 || empty($days_for_discount) || !$days_for_discount) {
	$discount_valid_until = $invoice_duedate;
	$maxoverduedays  = 0;
	$debug_result['discount_valid_until situation'] = '2';
	$debug_result['discount_valid_until'] = $discount_valid_until;
}
// Desconto depois da data de vencimento (cobra juros e multa sobre o valor com desconto)
if ( (string)$days_for_discount === (string)'pos' ) {
//if ( empty($days_for_discount) and (int)$days_for_discount != 0 ) {
	$discount_valid_until = date('Y-m-d', strtotime($invoice_duedate.' +'.$maxoverduedays.' days'));//date('Y-m-d');
	//$fine_value = false;
	//$interest_value = false;
	$debug_result['discount_valid_until situation'] = '2.1';
	$debug_result['discount_valid_until'] = $discount_valid_until;
}

// Define se data de validade do desconto é aplicável
if ( $discount_tax === 1 and $discount_valid_until and strtotime($discount_valid_until) < strtotime(date( 'Y-m-d' )) ) { // desconto expirou
	$discount_tax_value = 0;
	$debug_result['discount_valid_until situation'] = '3';
	$debug_result['discount_valid_until'] = $discount_valid_until;
	$debug_result['billet_duedate__'] = $billet_duedate;
}
// Desconto depois da data de vencimento 2
if ( $discount_tax === 1 and $discount_valid_until and strtotime($discount_valid_until) >= strtotime(date( 'Y-m-d' )) and (string)$days_for_discount === (string)'pos') {
		//$billet_duedate = $discount_valid_until;
		$maxoverduedays = (int)$params['maxoverduedays'];
		//$discount_valid_until = date('Y-m-d', strtotime($discount_valid_until.' +'.$maxoverduedays.' days'));//date('Y-m-d');
		$fine = false;
		$interest = false;
	
		$debug_result['discount_valid_until situation'] = '4';
		$debug_result['discount_valid_until'] = $discount_valid_until;
		$debug_result['billet_duedate__'] = $billet_duedate;
}

// Desconto do WHMCS / Itens com valor negativo
$disc_item = array();
foreach( $invoice_items_item as $Key => $Value){
	if ($Value['amount'] < 0 ) {
		$n_item = (string)$Value['amount'] ;
		$ngtv_item = preg_replace('/[^0-9]/', '', $n_item);
		$negative_item = $ngtv_item;
		$disc_item[] = $negative_item; // Array com itens negativos
		$discount_item = array_sum( $disc_item );
	}
}
if($invoice_credit and $invoice_credit > '0.00'){
	$line_items[]	= 'Crédito | R$ -'.number_format( $invoice_credit,  2, ',', '.' );
}
if ( $invoice_credit > 0 and $discount_item > 0 ) {
	$whmcs_discount = $invoice_credit + $discount_item;
}
elseif ( $invoice_credit > 0 and !$discount_item ) {
	$whmcs_discount = $invoice_credit;
}
elseif ( !$invoice_credit and $discount_item > 0 ) {
	$whmcs_discount = $discount_item;
}

// Cálculo de multa e juros
if( !function_exists('gbsp_calculate_fine_interest') ) {
function gbsp_calculate_fine_interest( $value, $fine, $interest, $invoice_duedate, $debug ) {
	$today = date('Y-m-d');
	$due_date = date('Y-m-d', strtotime($invoice_duedate));
	$datetime1 = new DateTime( $today );
	$datetime2 = new DateTime( $due_date );
	$interval = $datetime1->diff( $datetime2 );
	$due_days = $interval->format('%d');

	if ( $fine and $invoice_duedate >= date('Y-m-d') ) {
		$fine_value = false;
	}
	elseif ( $fine and $invoice_duedate < date('Y-m-d') ) {
		$fine_value = ( ( $fine / 100 ) * $value );
	}
	if ( $interest and $invoice_duedate >= date('Y-m-d') ) {
		$interest_value = false;
	}
	elseif ( $interest and $invoice_duedate < date('Y-m-d') ) {
		$interest_value = ( ($due_days * ($interest / 30)) / 100 ) * $value;
	}
	
	if ( $fine_value and $interest_value ) {
		$new_value = ($value) + ($fine_value + $interest_value);
	}
	elseif ( $fine_value and !$interest_value) {
		$new_value = ($value) + $fine_value;
	}
	elseif ( !$fine_value and $interest_value) {
		$new_value = ($value ) + $interest_value;
	}
	elseif ( !$fine_value and !$interest_value) {
		$new_value = $value;
	}
	return array('new_value'=>$new_value, 'fine_value'=> $fine_value, 'interest_value'=> $interest_value);
}}

// Desconto em porcentagem %
if ( $discount_tax === 1 and $discount_tax_type === 1 and $discount_tax_value ) {
	$discount_tax_valueRS			= ( $discount_tax_value / 100 ) * $invoiceTotal;
	$invoice_amount_ 				= ( $invoiceTotal - $discount_tax_valueRS );
	$discount_tax_message			= '<p>Desconto de '.$discount_tax_value.'% (R$'.number_format( $discount_tax_valueRS,  2, ',', '.' ).') para Boleto</p>';
	$discount_value					= $discount_tax_value;
	$line_items[]					= 'Desconto de '.$discount_tax_value.'% | R$ -'.number_format( $discount_tax_valueRS,  2, ',', '.' );
	$debug_result['Total com Desconto em porcentagem %'] = $invoice_amount_;
}
// Desconto Fixo R$
elseif ( $discount_tax === 1 and $discount_tax_type === 2 and $discount_tax_value ) {
	$invoice_amount_ 				= $invoiceTotal - $discount_tax_value;
	$discount_tax_message			= '<p>Desconto de R$'.$discount_tax_value.' para Boleto</p>';
	$discount_value					= $discount_tax_value;
	$line_items[]					= 'Desconto fixo de | R$ -'.number_format( $discount_tax_value,  2, ',', '.' );
	$debug_result['Total com Desconto Fixo R$'] = $invoice_amount_;
}
// Taxa em porcentagem %
elseif ( $discount_tax === 2 and $discount_tax_type === 1 and $discount_tax_value ) {
	$discount_tax_valueRS			= ( $discount_tax_value / 100 ) * ($invoiceTotal);
	$invoice_amount_ 				= $invoiceTotal + $discount_tax_valueRS;
	$discount_tax_message			= '<p>Tarifa do Boleto: ('.$discount_tax_value.'%) R$'.number_format( $discount_tax_valueRS,  2, ',', '.' ).'</p>';
	$line_items[]					= 'Tarifa de '.$discount_tax_value.'% do Boleto | R$ '.number_format( $discount_tax_valueRS,  2, ',', '.' );
	$debug_result['Total com Taxa em porcentagem %'] = $invoice_amount_;
}

// Taxa Fixa R$
elseif ( $discount_tax === 2 and $discount_tax_type === 2 and $discount_tax_value ) {
	$invoice_amount_ 				= $invoiceTotal + $discount_tax_value;
	$discount_tax_message			= '<p>Tarifa do Boleto: R$'.$discount_tax_value.'</p>';
	$line_items[]					= 'Tarifa do Boleto:  | R$ '.number_format( $discount_tax_value,  2, ',', '.' );
	$debug_result['Total Com Taxa Fixa R$'] = $invoice_amount_;
}
// Valor sem Desconto ou Taxa
elseif ( !$discount_tax_value ) {
	$invoice_amount_ = $params['amount'];
}
// Calculate fine and interest
$gbsp_calculate_fine_interest = gbsp_calculate_fine_interest( $invoice_amount_, $fine, $interest, $invoice_duedate, $debug );
$invoice_amount = $gbsp_calculate_fine_interest['new_value'];

if ($gbsp_calculate_fine_interest['fine_value'] || $gbsp_calculate_fine_interest['interest_value'] ) {
	$maxoverduedays	= 0;
	$billet_duedate			= date('Y-m-d');
}
if ($gbsp_calculate_fine_interest['fine_value'] and $show_discount_tax) {
	$line_items['fine_line_item']	= 'Multa de '.$fine.'% | R$ '.number_format( $gbsp_calculate_fine_interest['fine_value'],  2, ',', '.' );				
	$debug_result['fine_line_item'] = $fine_line_item;
}
if ($gbsp_calculate_fine_interest['interest_value'] and $show_discount_tax) {
	$line_items['interest_line_item']		= 'Juros de '.$interest.'% / mês | R$ '.number_format( $gbsp_calculate_fine_interest['interest_value'],  2, ',', '.' );
	$debug_result['interest_line_item'] = $interest_line_item;		
}
$debug_result['Total Sem Desconto ou Taxa'] = $invoice_amount_;
$debug_result['discount_tax_message'] = $discount_tax_message;

$charge = array();
	$postfields = array(
		'token'=> $token,
		'control_number'=> $params['invoiceid'],
		'amount' => $invoice_amount,
		'expire_at' => date('Y-m-d',strtotime($billet_duedate)),
		'description'=> substr( implode("<br>",$line_items),  0, 400),
		'customer_person_name' => $customer['name'],
		'customer_cnpj_cpf' => $customer['document'],
		'customer_address' => $customer['address1'],
		'customer_zipcode' => $customer['postcode'],
		'customer_city_name'=>$customer['city'],
		'customer_state'=>$customer['state'],
		'customer_neighborhood'=>$customer['address2'],
		'endpoint' => 'bank_billets',
	);
////
foreach( glob( __DIR__.'/params/*.php') as $file ) {
       include $file;
}
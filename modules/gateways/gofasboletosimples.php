<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */
if(!defined('WHMCS')){die();}
use WHMCS\Database\Capsule;
function gofasboletosimples_MetaData(){
    return array(
        'DisplayName' => 'Gofas - Boleto Simples',
        'APIVersion' => '1.1',
    );
}
require_once __DIR__.'/gofasboletosimples/includes/hooks.php';
require_once __DIR__.'/gofasboletosimples/includes/configuration.php';
function gofasboletosimples_link($params) {
	
	// Verifica se a página é uma fatura
	if (stripos($_SERVER['REQUEST_URI'],'viewinvoice') || $params['billetonemail']){
		$generate_billet = true;
	}
	
	if ($generate_billet) {
		require __DIR__.'/gofasboletosimples/includes/params.php';
		require __DIR__.'/gofasboletosimples/includes/functions.php';
		
		############### Start Process #############
	     // Verify Database
		 $gbsp_verifyInstall = gbsp_verify_install();
		 if($gbsp_verifyInstall['error']) {
			 $error = $gbsp_verifyInstall['error'];
		 }
		 if($debug_or_log) {
			$debug_result['gbsp_verifyInstall'] = $gbsp_verifyInstall;
			$debug_result['whmcs_admin'] = gbsp_config('admin');
		}
		$webhook = gbsp_verify_webhook($charge_url,array('url'=>$return_url,'endpoint'=>'webhooks','token'=>$token));
		if($webhook['error']) {
			 $error = $webhook['error'];
		 }
		 if($debug_or_log) {
			$debug_result['webhook'] = $webhook;
		}
		/**
		 *
		 * Verify Transactions and Billets for this Invoice
		 *
		 */
		 if ($trans_id and !$error) {
			 if($debug_or_log) {
				$debug_result['Código do Boleto associado à Fatura'] = $trans_id;
			}
			// Saved Billets
			$billet_saved = array();
			foreach( Capsule::table('gofasboletosimples') -> where('billet_id', '=', $trans_id)->orderBy('billet_id','desc')-> get() as $key => $value ) {
				$billets_for_invoice[$key]					= json_decode(json_encode($value), true);
			}
			$billet_saved = $billets_for_invoice['0'];
			
			if($debug_or_log) {
				$debug_result['Boleto Salvo'] = $billet_saved;
				$debug_result['Valor da Fatura X Valor do Boleto Salvo'] = array('invoiceAmount'=>$invoice_amount, 'billet_saved_amount'=>$billet_saved['amount']);
			}
			// Verify billet duedate
			if ( $maxoverduedays === 0 ) {
				$billet_saved_overdueDate = $billet_saved['expire_at'];
			}
			elseif( $maxoverduedays > 0 ) {
				$billet_saved_overdueDate = date('Y-m-d', strtotime( $billet_saved['expire_at']. '+'.$maxoverduedays.' days'));
			}
			
			if ( 
				//$billet_saved['dueDate'] >= $invoice_duedate and
				$billet_saved['expire_at'] >= date('Y-m-d') and// Data de vencimento é maior ou igual a hoje
				$billet_saved_overdueDate >= date('Y-m-d') and// Data máxima para pagamento é maior ou igual a hoje
				(float)$billet_saved['amount'] === (float)$invoice_amount  and // Total da Fatura continua sendo o total do boleto
				(string)$billet_saved['api_mode'] === (string)$api_mode
			) {
			
				$billet_url		= $billet_saved['url'];
				$barcode		= $billet_saved['line'];
				
				if($debug_or_log) {
					$debug_result['Boleto Salvo ainda é válido'] = array( 'Vencimento'=> $billet_saved['expire_at'], 'Data máxima para pagamento'=> $billet_saved_overdueDate);
					$debug_result['invoice_amount'] = gettype($invoice_amount).' - '.$invoice_amount;
					$debug_result['billet_saved_amount'] = gettype($billet_saved['amount']).' - '.$billet_saved['amount'];
				}
			}
		 }
		 
		/**
		 *
		 * Generat New Billet
		 *
		 */
		if ( $invoice_amount < $minimunAmount) {
			$error = 'Valor mínimo por boleto é R$'.$minimunAmount.' mas o valor da fatura é R$'.$invoice_amount.'.';
			if($debug_or_log){
				$debug_result['error'] = $error;
			}
		}
		if(!$error and !$billet_url ) {
			if($debug_or_log) {
					$debug_result['Boleto Salvo é Inválido'] = array( 'invoice_duedate'=>$invoice_duedate,'billet_saved_dueDate'=> $billet_saved['dueDate'], 'billet_saved_overdueDate' => $billet_saved_overdueDate, 'saved_billet_url'=> $billet_url);
					$debug_result['invoice_amount'] = gettype($invoice_amount).' - '.$invoice_amount;
					$debug_result['billet_saved_amount'] = gettype($billet_saved['amount']).' - '.$billet_saved['amount'];
				}
			//$token =  gbsp_get_token($charge_url,$postfields['token']);
			$billet = gbsp_charge($charge_url, $postfields);
			if($debug_or_log){
				//$debug_result['token'] = $token;
				$debug_result['billet'] = $billet;
			}
			if($billet['error'] ) {
				$error = $billet['error'];
				$debug_result['error'] = $error;
			}
			if($billet['errors'] ) {
				foreach($billet['errors'] as $key => $value){
					$error .= $key.' '.implode("\n", $value).'<br>';
				}
				$debug_result['error'] = $error;
			}
			if((string)$billet['status'] === (string)'generating'){
				$billet_url		= $billet['url'];
				$barcode	= $billet['line'];
				// Add WHMCS transaction
				if($billet['id'] and $billet['status']){
					$gbsp_add_trans = gbsp_add_trans( $user_id, $params['invoiceid'], $billet['id'], $api_mode, 'new' );
				}
				else{
					$error = 'Não foi possível gerar o boleto, tente novamente em instantes.';
				}
				if ($gbsp_add_trans['error']){
					$error = $gbsp_add_trans['error'];
				}
				if ($debug_or_log) {
					if (!$gbsp_add_trans['error']){
						$debug_result['Transação gravada com sucesso'] = $gbsp_add_trans;
					}
					if ($gbsp_add_trans['error']){
						$debug_result['Erro ao gravar a transação'] = $gbsp_add_trans;
					}
				}
				if(!$error) {
					// Save Billet on Database
					$gbsp_store_billet = gbsp_store_billet($billet,$api_mode);
					if ($gbsp_store_billet['error']) {
						$error = $gbsp_store_billet['error'];
					}
					if($debug_or_log){
						$debug_result['gbsp_store_billet'] = $gbsp_store_billet;
					}
				}
			}
		
			if($debug_or_log) {
				$debug_result['Charge_URL'] = $charge_url;
				$debug_result['Postfields'] = $postfields;
				//$debug_result['Postfields GET URL'] = $charge_url.'?'.http_build_query($postfields);
				$debug_result['billet'] = $billet;
			}
		} // end of if (!$error)
		
		############### Result - Finalize Process #############
		
		// Results
		if ( !$error and !$redirect_to_billet) {
			
			$result .= '<p><a id="gbspviewbillet" href="'.$billet_url.'" target="_blank">' . $payButton . '</a></p>';
			
			if($show_bar_code) {
				$result .= '<br><p id="gbspclic">Clique para copiar a Linha Digitável do Boleto:</p>
				<p id="linDig" onfocus="select_all_and_copy(this)" onclick="select_all_and_copy(this)">'.$barcode.'</p>';
			}
			if($show_discount_tax || $show_due_date) {
				$result .= '<div id="gbspbilletinfo">';
			}
			if($show_discount_tax and $discount_tax_message) {
				$result .= $discount_tax_message;
			}
			if($show_discount_tax and $line_items['fine_line_item']) {
				$result .= '<p>'.$line_items['fine_line_item'].'</p>';
			}
			if($show_discount_tax and $line_items['interest_line_item']) {
				$result .= '<p>'.$line_items['interest_line_item'].'</p>';
			}
			if($show_discount_tax || $show_due_date) {
				$result .= '<p>Total do Boleto: R$ '.number_format( $invoice_amount,  2, ',', '.' ).'</p>';
			}
			if($show_due_date /* and $invoice_duedate !== $billet_duedate*/ ) {
				$result .= '<p>Vencimento do Boleto: '.date('d/m/Y',strtotime($billet_duedate)).'</p>';
			}
			if($show_discount_tax || $show_due_date) {
				$result .= '</div>';
			}
		}
		elseif ( !$error and $redirect_to_billet and stripos($_SERVER['REQUEST_URI'], 'viewinvoice') ) {
			header_remove();
			header("Location: $billet_url",true,303);
			exit;
		} 
		elseif ($error) {
			$result .= '<h3 class="error">Erro ao gerar o Boleto</h3>';
			$result .= '<p class="error">'.$error.'</p>';
			if ($email_on_error) {
				$send_error_email = gbsp_send_error_email( $params['invoiceid'], $whmcs_admin_url, $error, $debug_or_log);
				if($send_error_email['debug'] and $debug_or_log ){
					$debug_result['gbsp_send_error_email'] = array('send_error_email'=>$send_error_email);
				}
			} 
		}
		// Debug
		//require_once __DIR__.'/gofasboletosimples/includes/debug.php';
		
		// Register Log
		if ($log) {
				logModuleCall('gofasboletosimples','generate_billet',array('module_version'=>'1.0.0',$debug_result),'', $billet );
		}
		// Finalize
		if($debug){
			$define_version = '?v='.time();
		}
		elseif(!$debug) {
			$define_version = '';
		}
		$result .= '<script type="text/javascript" src="'.$whmcs_url.'modules/gateways/gofasboletosimples/assets/js/copy.js'.$define_version.'" charset="UTF-8">
</script>';
		return $result.$css;
	}
}
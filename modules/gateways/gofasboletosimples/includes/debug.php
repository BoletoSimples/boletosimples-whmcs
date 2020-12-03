<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */
//use WHMCS\Database\Capsule;
if(!defined('WHMCS')) { die(); }
// Debug
if ($debug) {
	echo'<pre style="height:300px;max-width: 850px;margin: 20px auto;padding: 5px 15px 5px 15px;" class="debug">';
	echo'<h4 style="text-align:center;line-height: 1.4;border-bottom: 1px solid black;padding: 0px 0px 12px 0px;margin: 11px 0px 20px 0px;">Se você está lendo isso é por que a opção "<i>debug</i>" do módulo <br><b style="text-decorationunderline;">Gofas Boleto Simples para WHMCS versão '.$module_version.' está ativa.</b></h4>';
	echo'<p">Para obter ajuda consulte o <a href="https://gofas.net/foruns/?rf=gbspviewinvoice" target="_blank">fórum mantido pelos usuários</a>.<br>Se não encontrar nenhum tópico relacionado ao seu caso:</p>';
	echo'<p  onfocus="select_all_and_copy(debugDiv)" onclick="select_all_and_copy(debugDiv)"">1) <span style="cursor:copy;text-decoration: underline; ">Clique aqui para copiar as informações de diagnóstico (debug)</span>.</p>';
	echo'<p>2) <a target="_blank" tyle="cursor:alias;" href="https://gofas.net/foruns/">Clique aqui para publicar no fórum do módulo as informações de diagnósico</a>.</p>';
}
if ($debug_or_log) {
	$debug_result['Versão do módulo'] = $module_version;
	$debug_result['Todos os parâmetros do módulo'] = $params;
	// Debug fatura
	$debug_result['invoice'] = $invoice;
	
	if ($trans_id) {
		$debug_result['Transações da fatura'] = 'Transação existente: '.$trans_id;
	}
	else {
		$debug_result['Transações da fatura'] = 'Nenhuma transação registrada.';
	}

	// Debug de juros e multa
	if ( $fine || $interest ) {
		$debug_result['Multa'] = $params['fine'].'% equivale a fine = '. $fine;
		$debug_result['Juros'] = $params['interest'].'% equivale a interest = '. $interest;
		
	}

	// Debug Desconto Personalizado
	$debug_result['Campos de Desconto Personalizado'] = array('Tipo' => $custom_discount_type,'Valor'=>$custom_discount_value);
	$debug_result['Itens com valor negativo'] = $disc_item;
	
	$debug_result['Itens da Fatura'] = $item;
	$debug_result['Soma dos itens com valor negativo'] = array( 'discount_item' => $discount_item);
	$debug_result['Desconto do WHMCS'] = $whmcs_discount;
	
	//$debug_result['Desconto válido até'] = $discount_valid_until. ' | '. $days_for_discount. ' dias antes do vencimento';

	$debug_result['Cálculos'] = array(
		'Hoje' => $today,
		'Vencimento' => $due_date,
		'Diferença entre datas' => $due_days.' dia(s)',
		'Multa' => $fine_value,
		'Juros' => $interest_value,
		'Valor original ' => $VALUE ,
		'Total' => $new_value,
	);
	$debug_result['Dados do cliente enviados ao gateway'] = array( 'cpf' => $cpf, 'cnpj' => $cnpj ) ;
}
if($debug) {
	echo '<div id="debugDiv"><br>',
	print_r($debug_result,true),
	'</div></pre>';
}
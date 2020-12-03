<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */

if( !defined('WHMCS')) { die(''); }
use WHMCS\Database\Capsule;
function gofasboletosimples_config() {
	$module_version = '1.0.0';
	$module_version_int = (int)preg_replace("/[^0-9]/", "", $module_version);
	$customfields = array();
	$customfields[] = '';
	foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' ) -> get( array( 'fieldname', 'sortorder', 'id' ) ) as $customfield ) {
		$customfield_id		= $customfield->id;
		$customfield_name	= $customfield->fieldname;
		$customfields[]		= $customfield_id.' - '.$customfield_name;
	}
	$tblticketdepartments = array();
	$tblticketdepartments[] = '';
	foreach( Capsule::table('tblticketdepartments') -> get() as $tblticketdepartments_ ) {
		$tblticketdepartments_id			= $tblticketdepartments_->id;
		$tblticketdepartments_name			= $tblticketdepartments_->name;
		$tblticketdepartments[]				= $tblticketdepartments_id.' - '.$tblticketdepartments_name;
	}
	// Get Config
	$actual_link		= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	if ( stripos( $actual_link, '/configgateways.php') ) {
		// Local V URL
		$whmcs_url__ = str_replace("\\",'/',(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'].substr(getcwd(),strlen($_SERVER['DOCUMENT_ROOT'])));
		$admin_url = $whmcs_url__.'/';
		$vtokens = explode('/', $actual_link);
		$whmcs_admin_path = '/'.$vtokens[sizeof($vtokens)-2].'/';
		$whmcs_url = str_replace( $whmcs_admin_path, '', $admin_url).'/';
	
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsurl') -> get( array( 'value','created_at' ) ) as $gbspwhmcsurl_ ) {
			$gbspwhmcsurl					= $gbspwhmcsurl_->value;
			$gbspwhmcsurl_created_at			= $gbspwhmcsurl_->created_at;
		}
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsadminurl') -> get( array( 'value','created_at' ) ) as $gbspwhmcsadminurl_ ) {
			$gbspwhmcsadminurl				= $gbspwhmcsadminurl_->value;
			$gbspwhmcsadminurl_created_at	= $gbspwhmcsurl_->created_at;
		}
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gbspwhmcsadminpath') -> get( array( 'value','created_at' ) ) as $gbspwhmcsadminpath_ ) {
			$gbspwhmcsadminpath				= $gbspwhmcsadminpath_->value;
			$gbspwhmcsadminpath_created_at	= $gbspwhmcsurl_->created_at;
		}
		
		if ( !$gbspwhmcsurl ) {
			// Set config
			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'gbspwhmcsurl', 'value' => $whmcs_url, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }
			
			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'gbspwhmcsadminurl', 'value' => $admin_url, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }
			
			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'gbspwhmcsadminpath', 'value' => $whmcs_admin_path, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }
		}

		// Update Settings
		if ( $gbspwhmcsurl and ($whmcs_url !== $gbspwhmcsurl) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'gbspwhmcsurl')->update(array('value' => $whmcs_url, 'created_at' =>  $gbspwhmcsurl_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
		if ( $gbspwhmcsadminurl and ($admin_url !== $gbspwhmcsadminurl) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'gbspwhmcsadminurl')->update(array('value' => $admin_url, 'created_at' =>  $gbspwhmcsadminurl_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
		if ( $gbspwhmcsadminpath and ($whmcs_admin_path !== $gbspwhmcsadminpath) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'gbspwhmcsadminpath')->update(array('value' => $whmcs_admin_path, 'created_at' =>  $gbspwhmcsadminpath_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
	}
	
	if( !function_exists('gbsp_verify_module_updates') ) {
	function gbsp_verify_module_updates($page_id, $referer,$module_version) {
   		$query = 'https://gofas.net/br/updates/?software='.$page_id.'&referer='.$referer.'&version='.$module_version;
    	$curl = curl_init();
    	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    	curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
    	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return array(
			'http_status' => $http_status,
			'result' => $result,
		);
	}}
	$available_update_ = gbsp_verify_module_updates('13549',$whmcs_url,$module_version);
	if ( (int)$available_update_['http_status'] === 200 ) {
		$available_update = $available_update_['result'];
		$available_update_int = (int)preg_replace("/[^0-9]/", "", $available_update);
	}
	else {
		$available_update_int = 000;
	}
	if( $available_update_int === $module_version_int ) {
		$available_update_message = '<p style="color: green"><i class="fas fa-check-square"></i> Você está executando a versão mais recente do módulo.</p>';
	}
	if( $available_update_int > $module_version_int ) {
		$available_update_message = '<p style="font-size: 14px; color: red;"><i class="fas fa-exclamation-triangle"></i> Atualização disponível, verifique a <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p=13549" target="_blank">versão '.$available_update.'</a>';
	}
	if( $available_update_int < $module_version_int ) {
		$available_update_message = '<p style="font-size: 14px; color: orange;"><i class="fas fa-exclamation-triangle"></i> Você está executando uma versão Beta desse módulo.<br>Não recomendamos o uso dessa versão em produção.<br>Baixar versão estável: <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p=12042" target="_blank">v'.$available_update.'</a>';
	}
	if( $available_update_int === 000 ) {
		$available_update_message = '';
	}
	
	$tbladmins = array();
	foreach( Capsule::table('tbladmins') -> get() as $tbladmins_ ) {
		$tbladmins[$tbladmins_->id] = $tbladmins_->id.' - '.$tbladmins_->firstname.' '.$tbladmins_->lastname.' ('.$tbladmins_->username.')';
	}
	$opt_num = 1;
	// Renderize options
	return array(
		// Nome de exibição amigável para o gateway
		'FriendlyName' => array(
			'Type' => 'System',
			'Value' => 'Gofas - Boleto Simples',
		),
		/*
		 * Separador 1
		 * Configurações Básicas
		 *
		*/
		'separator_1' => array(
			'Description' => '
			<link href="'.$gbspwhmcsurl.'modules/gateways/gofasboletosimples/assets/css/admin.css" rel="stylesheet" type="text/css" />
			<div class="gbsp_separator" style="padding: 1px 15px 9px;">
				<div style="width:215px; float: right;padding: 4px 0px;">
					<a target="_blank" href="https://gofas.net/br/?ref=gbspAdminPanel"><img style=" width: 80px; margin: 0 10px 0 0;" src="'.$gbspwhmcsurl.'modules/gateways/gofasboletosimples/assets/img/gofas.png"></a>
					<a target="_blank" href="https://gofas.net/br/?ref=gbspAdminPanel"><img style=" width: 120px;" src="'.$gbspwhmcsurl.'modules/gateways/gofasboletosimples/assets/img/boletosimples.png"></a>
				</div>
				<div style="margin-left: 10px;"><h4 style="padding-top: 5px;">Módulo Gofas Boleto Simples para WHMCS v'.$module_version.'</h4>
					'.$available_update_message.'</div>
				
			</div>',
		),
		// Secret Token
		'token' => array(
			'FriendlyName' => $opt_num++.'- Token de Acesso - Produção<span class="gbsp_required">*</span>',
			'Type' => 'text',
			'Size' => '45',
			'Default' => '',
			'Description' => '<span class="gbsp_required_txt">(Obrigatório)</span> <a target="_blank" style="text-decoration:underline;" href="https://boletosimples.com.br/conta/api/tokens">Obter token de acesso</a>',
		),
		// Sandbox Secret Token
		'sandbox_token' => array(
			'FriendlyName' => $opt_num++.'- Token de Acesso - Sandbox<span class="gbsp_required">*</span>',
			'Type' => 'text',
			'Size' => '45',
			'Default' => '',
			'Description' => '<span class="gbsp_required_txt">(Obrigatório)</span> <a target="_blank" style="text-decoration:underline;" href="https://sandbox.boletosimples.com.br/conta/api/tokens">Obter token de acesso</a>',
		),
		'admin' => array(
			'FriendlyName' => $opt_num++.'- Administrador do WHMCS<span class="gbsp_required">*</span>',
			'Type'          => 'dropdown',
			'Default' 		=> key(reset($tbladmins)),
            'Options'       => $tbladmins,
			'Description' => 'Defina o administrador com permissões para utilizar a API interna do WHMCS.',
		),
		// Sandbox
		'sandbox' => array(
			'FriendlyName' => $opt_num++.'- <i>Sandbox</i>',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Ative essa opção para gerar cobranças em modo de testes.',
		),
		// Log
		'log' => array(
			'FriendlyName' => $opt_num++.'- Salvar Logs',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Salva informações de diagnóstico em <a target="_blank" style="text-decoration: underline;" href="'.$gbspwhmcsadminurl.'systemmodulelog.php">Utilitários > Logs > Log de Módulo</a>. Para funcionar, antes é necessário ativar o debug de módulo clicando em "Ativar Log de Debug". <a target="_blank" style="text-decoration: underline;" href="'.$gbspwhmcsadminurl.'systemmodulelog.php">VER LOG</a>.',
		),
		// minimum amount
		'minimun_amount' => array(
			'FriendlyName' => $opt_num++.'- Valor mínimo do Boleto',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '2.50',
			'Description' => 'Insira o valor total mínimo da fatura para permitir pagamento via Boleto. Formato: Decimal, separado por ponto. Maior ou igual a sua tarifa (a partir de 2.50) e menor ou igual a 1000000.00.',
		),
		
		/*
		 * Separador 2
		 * Ações Automatizadas
		 *
		*/
		
		'separator_2' => array(
			'Description' => '
			<div class="gbsp_separator">
				<h4>Ações Automatizadas</h4>
			</div>',
		),
		// Billet on email
		'billetonemail' => array(
			'FriendlyName' => $opt_num++.'- Informações do Boleto no email',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Adiciona link, linha digitável, vencimento e outras informações do boleto no corpo dos emails de faturas. Essa opção faz o módulo gerar os boletos no momento em que a fatura é gerada e enviada por email. Desative para gerar o boleto no 1º acesso à fatura. <a style="font-weight: bold;text-decoration:underline;" target="_blank" href="https://gofas.net/?p=13549#mergetags">Veja aqui a lista de tags disponíveis para os emails.</a> .',
		),
		
		// Replace Invoice link for Billet link on email
		'linkbilletonemail' => array(
			'FriendlyName' => $opt_num++.'- Link direto para o Boleto no email',
			'Type' => 'yesno',
			//'Default' => 'yes',
			'Description' => 'Substitui o URL da Fatura pelo URL do Boleto nos emails de "Nova Fatura" (tag <code>{$invoice_link}</code> do template de email <i>Invoice Created</i>).',
		),
		// Dias + vencimento
		'daysfordue' => array(
            'FriendlyName'      => $opt_num++.'- Dias adicionais para novo vencimento',
            'Type'              => 'text',
			'Size'				=> '10',
			'Default' 			=> '2',
            'Description'       => 'Número de dias que serão somados a data do vencimento do Boleto, ao gerar segunda via do boleto ou quando o cliente acessa uma fatura vencida. Essa opção aplica-se apenas a Faturas vencidas, faturas que ainda não venceram sempre irão gerar Boletos com a mesma data de vencimento da Fatura. As configurações de juros e multa anulam essa configuração.',
        ),
		// Notificar admin sobre erros
		'emailonerror' => array(
			'FriendlyName' => $opt_num++.'- Notificar admin do WHMCS sobre erros',
			'Type'          => 'dropdown',
            'Options'       => $tblticketdepartments,
			'Description' => 'Escolha o departamento de suporte que receberá notificação por email quando houver erros ao gerar o boleto. Esse recurso possibilita uma tomada de ação antes que o cliente contacte o suporte ou desista da compra, como por exemplo, quando o boleto não é gerado por um erro de cadastro do cliente.',
		),
		
		/*
		 * Separador 4
		 * Descontos e Acréscimos
		 *
		*/
		
		'separator_4' => array(
			'Description' => '
			<div class="gbsp_separator">
				<h4>Descontos e Acréscimos</h4>
			</div>',
		),
		
		// Desconto ou taxa
		'discountorfee'      => array(
            'FriendlyName'  => $opt_num++.'- Desconto ou Taxa',
            'Type'          => 'dropdown',
            'Options'       => array(
                	'1'         => 'Desconto',
                	'2'         => 'Taxa adicional',
            	),
            'Description'   => 'Escolha de deseja oferecer desconto ou acrescentar taxa para pagamentos via Boleto.',
        ),
		// Tipo de Desconto ou taxa
		'tipeofdiscountorfee'      => array(
            'FriendlyName'  => $opt_num++.'- Tipo de Desconto ou Taxa',
            'Type'          => 'dropdown',
            'Options'       => array(
                	'1'         => 'Porcentagem (%)',
                	'2'         => 'Valor fixo (R$)',
            	),
            'Description'   => 'Escolha de deseja oferecer desconto ou acrescentar taxa para pagamentos via Boleto.',
        ),
		// valor do desconto/taxa
		'valueofdiscountorfee' => array(
			'FriendlyName' => $opt_num++.'- Valor do Desconto ou da Taxa',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '',
			'Description' => 'Valor em R$ ou % que será abatido ou acrescentado do valor total das faturas.',
		),
		
		// dias antes do vencimento para aplicar desconto
		'daysfordiscount' => array(
			'FriendlyName' => $opt_num++.'- Validade do Desconto',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '0',
			'Description' => 'Defina o prazo do desconto.<br>- Insira 0 (zero) ou deixe em branco para aplicar desconto até a data de vencimento da fatura. <br>- Insira um número igual ou maior que 1 que será a validade do desconto. A data de vencimento do boleto será antecipada para a data limite de desconto e será adicionada ao boleto a instrução "Não aceitar pagamento após o vencimento".<br>- Insira <b>pos</b> (exatamente assim) para aplicar desconto após o vencimento (juros e multas serão cobrados sobre o valor com desconto, se configurados).<br>',
		),
		// customfield Desconto- Valor
		'customdiscountfield' => array(
			'FriendlyName' => $opt_num++.'- Campo do Perfil "Valor do Desconto Personalizado"',
			'Type'          => 'dropdown',
			'Default' 		=> '0',
            'Options'       => $customfields,
			'Description' => 'Escolha o <i title="WHMCS > Opções > Campos personaliz. Clientes" style="cursor: help;">Campo Personalizado de Clientes</i> usado para aplicar descontos diferentenciados para clientes específicos. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor que o valor da cobrança.',
		),
		// customfield Desconto- Tipo
		'customdiscounttypefield' => array(
			'FriendlyName' => $opt_num++.'- Campo do Perfil "Tipo de Desconto Personalizado"',
			'Type'          => 'dropdown',
			'Default' 		=> '0',
            'Options'       => $customfields,
			'Description' => 'Selecione o ampo Personalizado de Clientes que define o tipo de desconto personalizado em R$(Reais) e %(Porcentagem). <a style="text-decoration: underline;" href="https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2017/04/WHMCS_-_Campos_Personalizados_dos_Clientes.png" target="_blank">Veja aqui</a> como configurar os <i title="WHMCS > Opções > Campos personaliz. Clientes" style="cursor: help;">Campos Personalizados de Clientes</i>.',
		),
		// Multa por atraso
		'fine' => array(
			'FriendlyName' => $opt_num++.'- Multa após o vencimento',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '0.00',
			'Description' => 'Multa ( %/mês ) para pagamento após o vencimento. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor ou igual a 20.00 (2.00 é o valor máximo permitido por lei). Valor padrão: 0.00',
		),
		// Juros por atraso
		'interest' => array(
			'FriendlyName' => $opt_num++.'- Juros após o vencimento',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '0.00',
			'Description' => 'Juro ( %/mês ) para pagamento após o vencimento. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor ou igual a 20.00 (1 é o valor máximo permitido por lei, 1% ao mês equivale a 0.033% ao dia). Valor padrão: 0.00.',
		),
		
		/*
		 * Separador 5
		 * Exibição da Fatura e do Boleto
		 *
		*/
		
		'separator_5' => array(
			'Description' => '
			<div class="gbsp_separator">
				<h4>Visualização da Fatura e do Boleto</h4>
			</div>',
		),
		
		// Linha digitável
		'showbarcode' => array(
			'FriendlyName' => $opt_num++.'- Exibir linha digitável na Fatura',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Exibe a linha digitável/código de barras do Boleto na fatura, abaixo do botão "visualizar boleto".',
		),
		// Data de vencimento
		'showduedate' => array(
			'FriendlyName' => $opt_num++.'- Exibir data de Vencimento',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Exibe a data de vencimento do Boleto na fatura, abaixo do botão "visualizar boleto".',
		),
		
		// Exibir informação sobre Desconto / Taxa na fatura
		'showdiscountortax' => array(
			'FriendlyName' => $opt_num++.'- Exibir Descontos e Acréscimos',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Exibe desconto, taxa, multa e juros na Fatura e no Boleto.',
		),
		
		// Redirecionar para o link do boleto
		'redirecttobillet' => array(
			'FriendlyName' => $opt_num++.'- Redirecionar para o Boleto',
			'Type' => 'yesno',
			'Description' => 'Redireciona o cliente diretamente para o URL do boleto ao acessar a fatura.',
		),
		
		// Botão "Visualizar boleto"
		'paybutton' => array(
			'FriendlyName' => $opt_num++.'- Imagem do botão "Visualizar Boleto"',
			'Type' => 'text',
			'Size' => '90',
			'Default' => '',
			'Description' => 'Insira o URL da imagem que será usada como botão "Visualizar Boleto" (tamanho recomendado: 160x43px).',
		),
		'footer' => array(
			'Description' => '<div class="gbsp_section">'.$available_update_message.'
			<p>&copy; '.date('Y').' <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net">Gofas.net</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net/?p=13549#changelog">Versão '.$module_version.'</a> | <a  style="text-decoration:underline;"target="_blank" title="↗ Documentação" href="https://gofas.net/?p=13549">Documentação</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Fórum de Suporte" href="https://gofas.net/foruns/">Suporte</a>.</p>
			<p style="font-size: 11px;">
			Ao utilizar esse módulo você concorda com nosso <a style="text-decoration:underline;" target="_blank" title="↗ Contrato de licença de uso de software" href="https://gofas.net?p=9340">contrato de licença de uso de software</a>.
			</p>
			</div>',
		),
	);
}
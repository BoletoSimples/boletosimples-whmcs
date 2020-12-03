<?php
/**
 * Módulo Gofas Boleto Simples para WHMCS
 * docs        https://gofas.net/?p=13549
 * copyright          2020 Gofas Software
 * version                          1.0.0
 * license                            MIT
 */

use WHMCS\Database\Capsule;
add_hook('EmailPreSend', 1, function($vars) {
    $params = getGatewayVariables('gofasboletosimples');
    if($vars['messagename'] === 'Invoice Created' || $vars['messagename'] === 'Invoice Payment Reminder' || $vars['messagename'] === 'First Invoice Overdue Notice' || $vars['messagename'] === 'Second Invoice Overdue Notice' || $vars['messagename'] === 'Third Invoice Overdue Notice'){
        $gbsp_merge_fields     = array();
        $invoice             = localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), (int)$params['admin']);
        
        
        if( $invoice['total'] > '0.00' and $invoice['paymentmethod'] === 'gofasboletosimples' ) {
             // Saved Billets
             $billet_saved = array();
             foreach( Capsule::table('gofasboletosimples') -> where('invoice_id', '=', $vars['relid'])->orderBy('billet_id','desc')-> get() as $key => $value ) {
                $billets_for_invoice[$key] = json_decode(json_encode($value), true);
             }
             $billet_saved = $billets_for_invoice['0']; // Array
                
             // Merge Fields
             $gbsp_merge_fields['gbsp_link']     = $billet_saved['url'];
             $gbsp_merge_fields['gbsp_barcode']  = $billet_saved['line'];
             $gbsp_merge_fields['gbsp_due_date'] = date('d/m/Y', strtotime($billet_saved['expire_at']));
             $gbsp_merge_fields['gbsp_amount']   = number_format($billet_saved['amount'],  2, ',', '.' );
             $gbsp_merge_fields['gbsp_id']       = $billet_saved['billet_id'];
             
             if($params['linkbilletonemail']) {
                $gbsp_merge_fields['invoice_link']        = $billet_saved['url'];
             }

             $gbsp_merge_fields['gbsp_billet_info']     .= '<br>------------------------------------------------------';
             $gbsp_merge_fields['gbsp_billet_info']     .= '<br>Linha digitável do Boleto:<br><b>'.$billet_saved['line'];
             $gbsp_merge_fields['gbsp_billet_info']     .= '</b><br>Vencimento do Boleto: '.date('d/m/Y', strtotime($billet_saved['expire_at'])) ;
             $gbsp_merge_fields['gbsp_billet_info']     .= '<br>Valor do Boleto:  R$ '.number_format($billet_saved['amount'],  2, ',', '.' ) ;
             $gbsp_merge_fields['gbsp_billet_info']     .= '<br>Código do Boleto: '.$billet_saved['billet_id'];
             $gbsp_merge_fields['gbsp_billet_info']     .= '<br><b><a href="'.$billet_saved['url'].'">Visualizar Boleto</a></b>';
             $gbsp_merge_fields['gbsp_billet_info']     .= '<br>------------------------------------------------------';
             
             
             if($params['debug']) {
                $gbsp_merge_fields['gbsp_debug'] .= "Debug:\n".(string)json_encode($vars).json_encode($invoice);
             }
             // Debug Log
             if($params['log']) {
                logModuleCall('gofasboletosimples','attach_billet',$vars,'', $invoice );
             }
        }
        return $gbsp_merge_fields;
    }
     else { // Not
        return;
     }
});
// https://developers.whmcs.com/hooks-reference/everything-else/#emailpresend
//Output additional merge fields in the list when editing an email template
add_hook('EmailTplMergeFields', 1, function($vars) {
    $gbsp_merge_fields = array();
     $gbsp_merge_fields['gbsp_billet_info']     = 'Juno: Itens do Boleto HTML';
    $gbsp_merge_fields['gbsp_link']             = 'Juno: Link para o bBoleto';
     $gbsp_merge_fields['gbsp_barcode']        = 'Juno: Linha digitável do Boleto';
     $gbsp_merge_fields['gbsp_due_date']        = 'Juno: Vencimento do Boleto';
     $gbsp_merge_fields['gbsp_amount']             = 'Juno: Total do Boleto';
     $gbsp_merge_fields['gbsp_debug']             = 'Juno: Debug no email (para disgnosticar erros)';
    return $gbsp_merge_fields;
});

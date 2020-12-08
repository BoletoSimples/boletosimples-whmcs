![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/12/02165830/boleto_simples_gofas_whmcs.png)

# Módulo Boleto Simples para WHMCS

Boletos bancários via API [Boleto Simples](https://www.boletosimples.com.br/) diretamente do seu [WHMCS](https://gofas.net/gwh).

## Capturas de Tela
#### [Visualização da Fatura](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27155817/gofasboletosimples-fatura.gif)
![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27155817/gofasboletosimples-fatura.gif)
#### [Tela de configuração](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27160410/gofasboletosimples-tela-configuracoes.png)
![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27160410/gofasboletosimples-tela-configuracoes-876x1200.png)
#### [Edição do template de email](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27161314/WHMCS_-_Modelos_de_Email.png)
![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27161314/WHMCS_-_Modelos_de_Email.png)
#### [Informações do boleto no email](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27161347/Nova_Fatura_-E-mail_gofasboletosimples.png)
![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/11/27161347/Nova_Fatura_-E-mail_gofasboletosimples.png)
## Principais Funcionalidades

✓ Juros e multas após o vencimento do boleto

✓ 2ª via automática com valor atualizado (cálculo de multa e juros) ao acessar a fatura

✓ Boleto com suas cores e logotipo

✓ Linha digitável e informações do boleto nos emails das faturas

✓ Link direto para o boleto no email de faturas

✓ Redireciona para o boleto ao acessar a fatura (opcional)

✓ Dispensa configurações de campos personalizados (CPF/CNPJ)

✓ Notifica administradores do WHMCS sobre erros ao gerar boletos

✓ Exibe a linha digitável do boleto na fatura, com opção de copiar apenas com um clique;

✓ Imagem personalizada para o botão "Finalizar Pagamento"

✓ Desconto por método de pagamento

✓ Desconto para pagamento X dias antes do vencimento

✓ Descontos personalizados em R\$ e % diretamente no perfil de clientes específicos

✓ Permite adicionar tarifa adicional / boleto\*

✓ Exibe(ou não) na fatura informações de desconto, taxas e demais cálculos

✓ Confirmação de pagamento automática via recebimento de notificações (callback / webhook)

✓ Permite customizações sem alterar o código fonte do módulo (consulte a [wiki](https://github.com/BoletoSimples/boletosimples-whmcs/wiki))

✓ E mais...

## Requisitos e compatibilidade
* WHMCS versão 6 ou superior

## Instalação
Ao descompactar o arquivo do download, observe que os diretórios foram distribuídos seguindo a mesma hierarquia dos diretórios padrão do WHMCS, o arquivo + pasta do módulo *Gateway* está localizado no diretório */modules/gateways/*. Siga os passos a seguir se precisar de mais
detalhes:
1.  Faça [download do módulo](https://github.com/BoletoSimples/boletosimples-whmcs/archive/main.zip);
2.  Descompacte o arquivo .zip;
3.  Copie o arquivo *gofasboletosimples.php* + o diretório */gofasboletosimples/*, localizados na pasta */modules/gateways/* do arquivo recém descompactado, para a pasta */modules/gateways/* da instalação do seu [WHMCS](https://gofas.net/gwh);
### Pré configuração e ativação
1.  Crie um campo personalizado de cliente(_custom field_) para CPF e/ou CNPJ, ou se preferir, crie dois campos distintos, um campo apenas para CPF e outro campo para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente;
2.  Opcional: Crie dois campos personalizados se desejar oferecer descontos personalizados para clientes específicos, semelhantes aos campos [nesta imagem](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2017/04/WHMCS_-_Campos_Personalizados_dos_Clientes.png).
3.  Ative o Gateway em Opções \> Pagamentos \> Portais de Pagamento \> Aba "All Payment Gateways" \> Clique em "Gofas - Boleto Simples";
4.  Defina o nome de exibição do método de pagamento, exemplo: "Boleto Bancário". Após esses passos básicos, sigas as instruções a seguir atentamente para entender como funciona cada configuração do módulo.
### Preferências
1.  **Token de Acesso - Produção**: (Obrigatório) Obtenha o token de acesso em
    [https://boletosimples.com.br/conta/api/tokens](https://boletosimples.com.br/conta/api/tokens);
2.  **Token de Acesso - Sandbox**: (Obrigatório) Obtenha o token de acesso em [https://sandbox.boletosimples.com.br/conta/api/tokens](https://sandbox.boletosimples.com.br/conta/api/tokens)
3.  **Administrador do WHMCS**: Defina o administrador com permissões para utilizar a API interna do WHMCS;
4.  ***Sandbox**:* Ative essa opção para gerar cobranças em modo de testes;
5.  **Salvar Logs**: Salva informações de diagnóstico em [*Utilitários \> Logs \> Log de Módulo*](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/10/13003919/WHMCS_-_Log_de_Debug_dos_Mo%CC%81dulos_do_Sistema.png). Para funcionar, antes é necessário ativar o debug de módulo clicando em "Ativar Log de Debug".
6.  **Valor mínimo do Boleto**:  Insira o valor total mínimo da fatura para permitir pagamento via Boleto. Formato: Decimal, separado por ponto. Maior ou igual a sua tarifa (a partir de 2.50) e menor ou igual a 1000000.00.
7.  **Informações do Boleto no email**:  Adiciona link, linha digitável, vencimento e outras informações do boleto no corpo dos emails de faturas. Essa opção faz o módulo gerar os boletos no momento em que a fatura é gerada, (do contrário o Boleto é gerado no 1º acesso à Fatura). **Veja abaixo a lista de tags de mesclagem disponíveis**.
8. **Link direto para o Boleto no email**:  Substitui o URL padrão gerado pela merge tag {\$invoice\_link}, do template de email de faturas, pelo URL do boleto, nos emails de nova cobrança e de lembretes de atraso;
9.  **Dias adicionais para novo vencimento**:  Número de dias que serão somados a data do vencimento do Boleto, ao gerar segunda via do boleto ou quando o cliente acessa uma fatura vencida. Essa opção aplica-se apenas a Faturas vencidas, faturas que ainda não venceram sempre irão gerar Boletos com a mesma data de vencimento da Fatura. As configurações de juros e multa anulam essa configuração;
10. **Notificar admin do WHMCS sobre erros**: Escolha o departamento de suporte que receberá notificação por email quando houver erros ao gerar o boleto. Esse recurso possibilita uma tomada de ação antes que o cliente contacte o suporte ou desista da compra, como por exemplo, quando o boleto não é gerado por um erro de cadastro do cliente.
11. **Desconto ou Taxa**:  Escolha de deseja oferecer desconto ou acrescentar taxa para pagamentos via Boleto. Nota: Todos sabemos que cobrar taxa para emissão de boleto é proibido, por isso essa funcionalidade leva esse nome para auxiliar na contabilidade interna do WHMCS mas deve ser apresentada ao cliente de forma que de a entender que,é oferecido um desconto ao optar por outro método de pagamento diferente de boleto;
12. **Tipo de Desconto ou Taxa**:  Valor em R\$ ou % que será abatido ou acrescentado do valor total das faturas;
13. **Valor do Desconto ou da Taxa**:  Valor em R\$ ou % que será abatido ou acrescentado do valor total das faturas;
14. **Validade do Desconto**: Defina o prazo do desconto.
     - Insira 0 (zero) ou deixe em branco para aplicar desconto até a
    data de vencimento da fatura.\
     - Insira um número igual ou maior que 1 que será a validade do
    desconto. A data de vencimento do boleto será antecipada para a data
    limite de desconto e será adicionada ao boleto a instrução "Não
    aceitar pagamento após o vencimento".\
     - Insira **pos** (exatamente assim) para aplicar desconto após o
    vencimento (juros e multas serão cobrados sobre o valor com
    desconto, se configurados);

15. **Campo do Perfil "Valor do Desconto Personalizado"**:  Escolha o *Campo Personalizado de Clientes* usado para aplicar descontos diferentenciados para clientes específicos. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor que o valor da cobrança.

16. **Campo do Perfil "Tipo de Desconto Personalizado"**:  Selecione o campo Personalizado de Clientes que define o tipo de desconto personalizado em R\$(Reais) e %(Porcentagem). [Veja aqui](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2017/04/WHMCS_-_Campos_Personalizados_dos_Clientes.png) como configurar os *Campos Personalizados de Clientes para aplicar corretamnete o desconto personalizaodo*.

17. **Multa após o vencimento**:  Multa ( %/mês ) para pagamento após o vencimento. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor ou igual a 20.00 (2.00 é o valor máximo permitido por lei). Valor padrão: 0.00;

18. **Juros após o vencimento**:  Juro ( %/mês ) para pagamento após o vencimento. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor ou igual a 20.00 (1 é o valor máximo permitido por lei, 1% ao mês equivale a 0.033% ao dia). Valor padrão: 0.00.

19. **Exibir linha digitável na Fatura**:  Exibe a linha digitável/código de barras do Boleto na fatura, abaixo do botão "visualizar boleto";

20. **Exibir data de Vencimento**:  Exibe a data de vencimento do Boleto na fatura, abaixo do botão "visualizar boleto";

21. **Exibir Descontos e Acréscimos**: Exibe desconto, taxa, multa e juros na Fatura e no Boleto;
22. **Redirecionar para o Boleto**:  Redireciona o cliente diretamente para o URL do boleto ao acessar a fatura;

23. **Imagem do botão "Visualizar Boleto"**: Insira o URL da imagem que será usada como botão "Visualizar Boleto" (tamanho recomendado: 160x43px).

## Templates de email

Utilize as *mergetags* geradas pelo módulo *addon* para exibir informações do Boleto nos emails de Faturas do WHMCS.
Para adicionar as *tags* edite os templates de email referentes à faturas em WHMCS admin \> Opções \> Modelos de Email.

As tags de mesclagem permitem adicionar um bloco html ao email com as principais informações do boleto, ou cada informação separadamente, para auxiliar na formatação do texto dos emails.

### Tags disponíveis:[](#mergetags)

**{\$gbsp\_billet\_info}**: Exibe um bloco com as principais informações sobre o boleto;

**{\$gbsp\_link}**: Exibe o URL do boleto;

**{\$gbsp\_barcode}**: Exibe a linha digitável do Boleto (nº que forma o
código de barras);

**{\$gbsp\_due\_date}**: Exibe a data de vencimento do Boleto;

**{\$gbsp\_amount}**: Exibe o valor total do Boleto;

**{\$gbsp\_id}**: Exibe o código do Boleto(ID registrado na API Boleto Simples);

## Changelog
-   **v1.0.0** - *27/11/2020*
    -   Lançamento;

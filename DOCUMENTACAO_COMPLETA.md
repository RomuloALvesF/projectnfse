# Documentação Completa - Sistema NFSe

## Visão Geral do Sistema

Este é um sistema completo para geração de Notas Fiscais de Serviço Eletrônicas (NFSe) que se comunica com a API da Prefeitura de Belo Horizonte. O sistema utiliza certificado digital para autenticação e validação.

---

## Arquitetura do Sistema

### **Fluxo de Funcionamento:**
```
Usuário → View → Controller → Service → API da Prefeitura
   ↑                                    ↓
   ←────────── Resposta ────────────────←
```

---

## Estrutura de Arquivos e Componentes

### **1. Arquivo .env (Configurações)**
```bash
# Localização: projectnfse/.env
CERTIFICATE_PASSWORD=182838
NFSE_API_URL=https://bhissdigital.pbh.gov.br
```

**O que faz:**
- **Armazena configurações sensíveis** (senhas, URLs)
- **Separa configurações** por ambiente (desenvolvimento/produção)
- **Não é versionado** no Git (por segurança)

**Como funciona:**
- Laravel lê essas variáveis usando `env('VARIAVEL')`
- Permite diferentes configurações para diferentes ambientes
- Centraliza configurações sensíveis

---

### **2. Certificado Digital (cert.pfx)**
```bash
# Localização: storage/certificates/private/cert.pfx
```

**O que é:**
- **Certificado digital A1** (arquivo .pfx)
- **Contém chave privada** e certificado público
- **Autentica** o sistema perante a Prefeitura
- **Criptografado** com senha

**Como funciona:**
- Usado para **assinatura digital** das requisições
- **Valida identidade** do prestador de serviços
- **Obrigatório** para comunicação com API da Prefeitura

---

### **3. Service Layer (NfseApiService.php)**

#### **Localização:** `app/Services/NfseApiService.php`

#### **Responsabilidades:**
- **Validação do certificado**
- **Comunicação com API da Prefeitura**
- **Tratamento de erros**
- **Logs de requisições**

#### **Métodos Principais:**

##### **a) Constructor (Construtor)**
```php
public function __construct()
{
    $this->certificatePath = storage_path('certificates/private/cert.pfx');
    $this->certificatePassword = env('CERTIFICATE_PASSWORD', '182838');
    $this->baseUrl = env('NFSE_API_URL', 'https://bhissdigital.pbh.gov.br');
    $this->timeout = 30;
}
```

**O que faz:**
- **Inicializa configurações** do serviço
- **Lê variáveis** do arquivo .env
- **Define caminhos** e timeouts

##### **b) validateCertificate()**
```php
public function validateCertificate()
{
    // 1. Verifica se arquivo existe
    if (!file_exists($this->certificatePath)) {
        throw new Exception("Certificado não encontrado");
    }

    // 2. Tenta abrir com senha
    $certData = openssl_pkcs12_read(
        file_get_contents($this->certificatePath),
        $certInfo,
        $this->certificatePassword
    );

    // 3. Extrai informações
    $cert = openssl_x509_read($certInfo['cert']);
    $certDetails = openssl_x509_parse($cert);

    return [
        'valid' => true,
        'subject' => $certDetails['subject'],
        'issuer' => $certDetails['issuer'],
        'validFrom' => $certDetails['validFrom_time_t'],
        'validTo' => $certDetails['validTo_time_t']
    ];
}
```

**O que faz:**
- **Verifica existência** do arquivo de certificado
- **Testa senha** do certificado
- **Extrai informações** (emissor, validade, etc.)
- **Retorna status** de validação

##### **c) makeRequest()**
```php
public function makeRequest($endpoint, $xmlData, $method = 'POST')
{
    // 1. Valida certificado primeiro
    $certValidation = $this->validateCertificate();
    if (!$certValidation['valid']) {
        throw new Exception('Certificado inválido');
    }

    // 2. Configura cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $this->baseUrl . '/' . $endpoint,
        CURLOPT_SSLCERT => $this->certificatePath,
        CURLOPT_SSLCERTPASSWD => $this->certificatePassword,
        CURLOPT_SSLCERTTYPE => 'P12',
        // ... outras configurações
    ]);

    // 3. Executa requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'response' => $response
    ];
}
```

**O que faz:**
- **Valida certificado** antes de cada requisição
- **Configura cURL** com certificado digital
- **Envia requisição** para API da Prefeitura
- **Retorna resposta** formatada

##### **d) testConnection()**
```php
public function testConnection()
{
    return $this->makeRequest('', '', 'GET');
}
```

**O que faz:**
- **Testa conectividade** básica com API
- **Verifica se certificado** funciona
- **Retorna status** da conexão

---

### **4. Controller Layer (nfseController.php)**

#### **Localização:** `app/Http/Controllers/nfseController.php`

#### **Responsabilidades:**
- **Recebe requisições HTTP**
- **Processa regras de negócio**
- **Chama Services**
- **Retorna respostas**

#### **🔧 Métodos Principais:**

##### **a) index()**
```php
public function index()
{
    // Regra de negócio: Horário comercial
    $horaAtual = date('H');

    if ($horaAtual < 9 || $horaAtual >= 22) {
        $mensagem = "Sistema disponível apenas em horário comercial";
        $sistemaAtivo = false;
    } else {
        $mensagem = "Sistema ativo - Horário comercial";
        $sistemaAtivo = true;
    }

    return view('nfse-form', [
        'mensagem' => $mensagem,
        'sistemaAtivo' => $sistemaAtivo,
        'horaAtual' => $horaAtual
    ]);
}
```

**O que faz:**
- **Verifica horário** de funcionamento
- **Aplica regra de negócio** (9h às 22h)
- **Passa dados** para a view
- **Controla acesso** ao sistema

##### **b) testarConexao()**
```php
public function testarConexao()
{
    try {
        $nfseService = new NfseApiService();

        // Testa certificado
        $certValidation = $nfseService->validateCertificate();

        // Testa API
        $connectionTest = $nfseService->testConnection();

        return response()->json([
            'success' => true,
            'certificate' => $certValidation,
            'api_connection' => $connectionTest
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
```

**O que faz:**
- **Cria instância** do Service
- **Testa certificado** e API
- **Retorna JSON** com resultados
- **Trata erros** adequadamente

---

### **5. Routes (web.php)**

#### **Localização:** `routes/web.php`

#### **Rotas Definidas:**

```php
// Rota principal do formulário
Route::get('/nfse', [nfseController::class, 'index']);

// Rota para testar conexão
Route::get('/nfse/testar-conexao', [nfseController::class, 'testarConexao']);
```

**Como funciona:**
- **Mapeia URLs** para métodos do Controller
- **Define endpoints** da aplicação
- **Controla acesso** às funcionalidades

---

### **6. View Layer (Blade Templates)**

#### **Layout Principal:** `resources/views/layouts/main.blade.php`

**O que faz:**
- **Template base** da aplicação
- **Inclui CSS/JS** (Bootstrap, FontAwesome)
- **Define estrutura** HTML comum
- **Usa @yield** para conteúdo dinâmico

#### **View do Formulário:** `resources/views/nfse-form.blade.php`

**Componentes:**

##### **a) Regra de Negócio (Horário)**
```php
<div class="alert alert-{{ $sistemaAtivo ? 'success' : 'warning' }}">
    <strong>{{ $mensagem }}</strong>
    <small>Hora atual: {{ $horaAtual }}h</small>
</div>
```

**O que faz:**
- **Exibe status** do sistema
- **Mostra horário** atual
- **Aplica cores** baseadas no status

##### **b) Botão de Teste**
```html
<button id="testarConexao" class="btn btn-info">
    <i class="fas fa-plug"></i> Testar Conexão com API
</button>
```

**O que faz:**
- **Interface** para testar conexão
- **Chama JavaScript** para requisição AJAX
- **Exibe resultados** dinamicamente

##### **c) JavaScript (AJAX)**
```javascript
fetch('/nfse/testar-conexao')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Exibe sucesso
        } else {
            // Exibe erro
        }
    });
```

**O que faz:**
- **Faz requisição** AJAX para API
- **Processa resposta** JSON
- **Atualiza interface** sem recarregar página
- **Trata erros** adequadamente

---

## Fluxo Completo de Funcionamento

### **1. Inicialização**
```
1. Usuário acessa /nfse
2. Laravel carrega rotas (web.php)
3. Rota chama nfseController@index
4. Controller verifica horário
5. Controller retorna view com dados
```

### **2. Validação do Certificado**
```
1. Service lê arquivo .env
2. Service carrega certificado (cert.pfx)
3. Service testa senha do certificado
4. Service extrai informações (emissor, validade)
5. Service retorna status de validação
```

### **3. Conexão com API**
```
1. Service configura cURL com certificado
2. Service envia requisição para API da Prefeitura
3. API da Prefeitura valida certificado
4. API retorna resposta
5. Service processa resposta
6. Service retorna dados formatados
```

### **4. Interface do Usuário**
```
1. JavaScript faz requisição AJAX
2. Controller processa requisição
3. Controller chama Service
4. Service executa validação/conexão
5. Controller retorna JSON
6. JavaScript atualiza interface
```

---

## Configurações e Variáveis

### **Arquivo .env**
```bash
# Senha do certificado digital
CERTIFICATE_PASSWORD=182838

# URL da API da Prefeitura
NFSE_API_URL=https://bhissdigital.pbh.gov.br

# Outras configurações Laravel
APP_NAME="NFSe Project"
APP_ENV=local
APP_DEBUG=true
```

### **Certificado Digital**
- **Formato:** .pfx (PKCS#12)
- **Localização:** `storage/certificates/private/cert.pfx`
- **Senha:** Definida em `CERTIFICATE_PASSWORD`
- **Validade:** Verificada automaticamente

---

## Segurança

### **Medidas Implementadas:**
1. **Certificado digital** para autenticação
2. **Arquivo .env** não versionado
3. **Validação de horário** de funcionamento
4. **Tratamento de erros** adequado
5. **Logs** de todas as requisições

### **Pontos de Atenção:**
1. **Senha do certificado** deve ser segura
2. **Arquivo .env** não deve ser commitado
3. **Certificado** deve estar atualizado
4. **Permissões** de arquivo adequadas

---

## Debug e Logs

### **Logs Automáticos:**
```php
// No Service
Log::info("NFSe API Request", [
    'endpoint' => $endpoint,
    'method' => $method,
    'http_code' => $httpCode
]);

Log::error('Erro na validação do certificado: ' . $e->getMessage());
```

### **Como Debugar:**
1. **Verificar logs:** `storage/logs/laravel.log`
2. **Testar certificado:** `php test_certificate.php`
3. **Testar Service:** `php test_service.php`
4. **Verificar .env:** `php test_env.php`

---

## Deploy e Produção

### **Checklist de Produção:**
- [ ] Certificado digital válido
- [ ] Arquivo .env configurado
- [ ] Permissões de arquivo corretas
- [ ] Logs configurados
- [ ] Backup do certificado
- [ ] Monitoramento de erros

### **Configurações de Produção:**
```bash
APP_ENV=production
APP_DEBUG=false
CERTIFICATE_PASSWORD=senha_segura_producao
NFSE_API_URL=https://api.producao.prefeitura.gov.br
```

---

## Conceitos Importantes

### **Certificado Digital:**
- **A1:** Arquivo (.pfx) com chave privada
- **A3:** Token físico (cartão/chip)
- **Validade:** Período em que o certificado é aceito
- **Emissor:** Autoridade Certificadora (AC)

### **API REST:**
- **GET:** Buscar informações
- **POST:** Enviar dados
- **PUT/PATCH:** Atualizar dados
- **DELETE:** Remover dados

### **MVC (Model-View-Controller):**
- **Model:** Dados e regras de negócio
- **View:** Interface do usuário
- **Controller:** Controla fluxo da aplicação

### **Service Layer:**
- **Separa responsabilidades**
- **Reutiliza código**
- **Facilita testes**
- **Melhora manutenibilidade**

---

## Próximos Passos

### **Melhorias Sugeridas:**
1. **Cache** de validação do certificado
2. **Retry automático** em caso de falha
3. **Monitoramento** de performance
4. **Testes automatizados**
5. **Documentação da API**
6. **Interface mais robusta**

### **Funcionalidades Futuras:**
1. **Geração real** de NFSe
2. **Histórico** de notas fiscais
3. **Relatórios** e estatísticas
4. **Múltiplos certificados**
5. **Integração** com sistemas externos

---

## Recursos Adicionais

### **Links Úteis:**
- [Documentação Laravel](https://laravel.com/docs)
- [cURL PHP](https://www.php.net/manual/en/book.curl.php)
- [OpenSSL PHP](https://www.php.net/manual/en/book.openssl.php)
- [API Prefeitura BH](https://bhissdigital.pbh.gov.br)

### **Livros Recomendados:**
- "Laravel: Up & Running" - Matt Stauffer
- "PHP Web Services" - Lorna Jane Mitchell
- "RESTful Web Services" - Leonard Richardson

---


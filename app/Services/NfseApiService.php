<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class NfseApiService
{
    private $certificatePath;
    private $certificatePassword;
    private $baseUrl;
    private $timeout;

    public function __construct()
    {
        // Configurações do certificado
        $this->certificatePath = storage_path('certificates/private/cert.pfx');
        $this->certificatePassword = env('CERTIFICATE_PASSWORD', '182838'); // Senha do certificado

        // URL da API da Prefeitura de BH
        $this->baseUrl = env('NFSE_API_URL', 'https://bhissdigital.pbh.gov.br');

        // Timeout da conexão
        $this->timeout = 30;
    }

   //validação certificado
    public function validateCertificate()
    {
        try {
            if (!file_exists($this->certificatePath)) {
                throw new Exception("Certificado não encontrado em: {$this->certificatePath}");
            }

            // Verifica se consegue abrir o certificado
            $certData = openssl_pkcs12_read(
                file_get_contents($this->certificatePath),
                $certInfo,
                $this->certificatePassword
            );

            if (!$certData) {
                throw new Exception("Não foi possível ler o certificado. Verifique a senha.");
            }

            // Extrai informações do certificado usando openssl_x509_parse
            $cert = openssl_x509_read($certInfo['cert']);
            $certDetails = openssl_x509_parse($cert);

            return [
                'valid' => true,
                'subject' => $certDetails['subject'],
                'issuer' => $certDetails['issuer'],
                'validFrom' => $certDetails['validFrom_time_t'],
                'validTo' => $certDetails['validTo_time_t']
            ];

        } catch (Exception $e) {
            Log::error('Erro na validação do certificado: ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    //faz uma requisição para a api da prefeitura
    public function makeRequest($endpoint, $xmlData, $method = 'POST')
    {
        try {
            // Valida o certificado primeiro
            $certValidation = $this->validateCertificate();
            if (!$certValidation['valid']) {
                throw new Exception('Certificado inválido: ' . $certValidation['error']);
            }

            // Configura  cURL
            $ch = curl_init();

            $url = $this->baseUrl . '/' . $endpoint;

            $headers = [];

            if ($method === 'POST' && !empty($xmlData)) {
                $headers = [
                    'Content-Type: application/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen($xmlData)
                ];
            } else {
                $headers = [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ];
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSLCERT => $this->certificatePath,
                CURLOPT_SSLCERTPASSWD => $this->certificatePassword,
                CURLOPT_SSLCERTTYPE => 'P12',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_VERBOSE => true,
                CURLOPT_STDERR => fopen('php://temp', 'w+')
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
            }

            // Faz  requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if ($error) {
                throw new Exception("Erro cURL: {$error}");
            }

            // Log da requisição
            Log::info("NFSe API Request", [
                'endpoint' => $endpoint,
                'method' => $method,
                'http_code' => $httpCode,
                'response_length' => strlen($response)
            ]);

            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'http_code' => $httpCode,
                'response' => $response,
                'response_preview' => substr($response, 0, 500), // Primeiros 500 caracteres
                'error' => $error
            ];

        } catch (Exception $e) {
            Log::error('Erro na requisição NFSe API: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Testa a conexão com a API
     */
    public function testConnection()
    {
        // estar apenas uma requisição GET simples
        return $this->makeRequest('', '', 'GET');
    }

    /**
     * Verifica horário de funcionamento do sistema
     */
    public function verificarHorarioFuncionamento()
    {
        $horaAtual = date('H');

        if ($horaAtual < 9 || $horaAtual >= 22) {
            return [
                'ativo' => false,
                'mensagem' => 'Sistema disponível apenas em horário comercial (9h às 18h)',
                'horaAtual' => $horaAtual
            ];
        }

        return [
            'ativo' => true,
            'mensagem' => 'Sistema ativo - Horário comercial',
            'horaAtual' => $horaAtual
        ];
    }
}

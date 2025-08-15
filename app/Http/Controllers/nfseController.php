<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NfseApiService;
class NfseController extends Controller
{
    public function index()
    {
        // REGRA DE NEGÓCIO BÁSICA
        $horaAtual = date('H'); // Pega a hora atual (0-23)

        // Regra: Se for antes das 8h ou depois das 18h, mostrar mensagem de horário
        if ($horaAtual < 9 || $horaAtual >= 22) {
            $mensagem = "Sistema disponível apenas em horário comercial (9h às 18h)";
            $sistemaAtivo = false;
        } else {
            $mensagem = "Sistema ativo - Horário comercial";
            $sistemaAtivo = true;
        }

        // Passa os dados processados para a view
        return view('nfse-form', [
            'mensagem' => $mensagem,
            'sistemaAtivo' => $sistemaAtivo,
            'horaAtual' => $horaAtual
        ]);
    }

    /**
     * Testa a conexão com a API da Prefeitura
     */
    public function testarConexao()
    {
        try {
            $nfseService = new NfseApiService();

            // Testa a validação do certificado
            $certValidation = $nfseService->validateCertificate();

            if (!$certValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro no certificado: ' . $certValidation['error']
                ], 400);
            }

            // Testa a conexão com a API
            $connectionTest = $nfseService->testConnection();

            return response()->json([
                'success' => true,
                'certificate' => [
                    'valid' => $certValidation['valid'],
                    'subject' => $certValidation['subject'] ?? 'N/A',
                    'issuer' => $certValidation['issuer'] ?? 'N/A',
                    'validFrom' => isset($certValidation['validFrom']) ? date('Y-m-d', $certValidation['validFrom']) : 'N/A',
                    'validTo' => isset($certValidation['validTo']) ? date('Y-m-d', $certValidation['validTo']) : 'N/A'
                ],
                'api_connection' => [
                    'success' => $connectionTest['success'],
                    'http_code' => $connectionTest['http_code'] ?? 'N/A',
                    'response' => $connectionTest['response'] ?? 'N/A',
                    'error' => $connectionTest['error'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}

@extends('layouts.main')

@section('title', 'Sistema NFSe - Prefeitura de BH')

@section('content')

  <!-- Exibindo a regra de negócio processada no controller -->
  <div class="alert alert-{{ $sistemaAtivo ? 'success' : 'warning' }}">
    <strong>{{ $mensagem }}</strong>
    <br>
    <small>Hora atual: {{ $horaAtual }}h</small>
  </div>

  <!-- Botão para testar conexão com API -->
  <div class="mb-4">
    <button id="testarConexao" class="btn btn-info">
      <i class="fas fa-plug"></i> Testar Conexão com API
    </button>
    <div id="resultadoTeste" class="mt-3"></div>
  </div>

  @if($sistemaAtivo)
    <!-- Formulário vai aparece se o sistema estiver ativo -->
    <div class="form-group">
      <label>Nome do Cliente:</label>
      <input type="text" class="form-control" placeholder="Digite o nome">
    </div>

    <div class="form-group">
      <label>Valor do Serviço:</label>
      <input type="number" class="form-control" placeholder="0,00">
    </div>

    <button class="btn btn-primary">Gerar NFSe</button>
  @else
    <!-- Mensagem quando sistema inativo -->
    <div class="text-center">
      <p class="text-muted">Aguarde o horário comercial para gerar NFSe</p>
    </div>
  @endif

@endsection

@section('footer')
  Todos os direitos reservados copyright 2025
@endsection

@push('scripts')
<script>
document.getElementById('testarConexao').addEventListener('click', function() {
    const button = this;
    const resultadoDiv = document.getElementById('resultadoTeste');

    // Desabilita o botão e mostra loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
    resultadoDiv.innerHTML = '<div class="alert alert-info">Testando conexão...</div>';

    // Faz a requisição
    fetch('/nfse/testar-conexao')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="alert alert-success">';
                html += '<h5>✅ Conexão Testada com Sucesso!</h5>';
                html += '<h6>Certificado:</h6>';
                html += '<ul>';
                html += `<li><strong>Válido:</strong> ${data.certificate.valid ? 'Sim' : 'Não'}</li>`;
                html += `<li><strong>Emissor:</strong> ${data.certificate.issuer}</li>`;
                html += `<li><strong>Válido até:</strong> ${data.certificate.validTo}</li>`;
                html += '</ul>';
                html += '<h6>Conexão API:</h6>';
                html += `<ul><li><strong>Status:</strong> ${data.api_connection.success ? 'Conectado' : 'Erro'}</li>`;
                html += `<li><strong>Código HTTP:</strong> ${data.api_connection.http_code}</li>`;
                if (data.api_connection.error) {
                    html += `<li><strong>Erro:</strong> ${data.api_connection.error}</li>`;
                }
                if (data.api_connection.response_preview) {
                    html += `<li><strong>Resposta (preview):</strong> <pre style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">${data.api_connection.response_preview}</pre></li>`;
                }
                html += '</ul></div>';
                resultadoDiv.innerHTML = html;
            } else {
                let errorHtml = `<div class="alert alert-danger">❌ Erro: ${data.message}</div>`;
                if (data.trace) {
                    errorHtml += `<details><summary>Detalhes técnicos</summary><pre>${data.trace}</pre></details>`;
                }
                resultadoDiv.innerHTML = errorHtml;
            }
        })
        .catch(error => {
            resultadoDiv.innerHTML = `<div class="alert alert-danger">❌ Erro na requisição: ${error.message}</div>`;
        })
        .finally(() => {
            // Reabilita o botão
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-plug"></i> Testar Conexão com API';
        });
});
</script>
@endpush

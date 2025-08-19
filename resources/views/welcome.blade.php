@extends('layouts.main')

@section('title', 'Sistema NFSe - Prefeitura de BH')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Sistema NFSe - Prefeitura de BH</h3>
                </div>
                <div class="card-body text-center">
                    <h5>Bem-vindo ao Sistema de Nota Fiscal de Serviços Eletrônica</h5>
                    <p class="text-muted">Sistema desenvolvido para a Prefeitura de Belo Horizonte</p>

                    <div class="mt-4">
                        <a href="{{ route('nfse.form') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-invoice"></i> Gerar NFSe
                        </a>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            Versão 1.0 | Desenvolvido com Laravel
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

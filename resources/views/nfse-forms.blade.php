@extends('layouts.main')

@section('title')
  Formulário de NFSE
@endsection

@section('content')

  @if ($id != null)
    <h1>Formulário de NFSE: {{$id}}</h1>
  @else
    <h1>Nenhum formulário encontrado</h1>
  @endif


@endsection

@section('footer')
  Todos os direitos reservados LTDA Romulo
@endsection

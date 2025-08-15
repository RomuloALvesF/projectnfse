<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'NFS')</title>

  <!-- fonte google -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto" rel="stylesheet">

  <!-- CSS Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

  <!-- CSS da aplicação -->
  <link rel="stylesheet" href="/css/styles.css">

</head>

<body>

  <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <span class="navbar-brand">NFS-e</span>
      <button class="navbar-toggler" type="text" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item active">
            <a class="nav-link" href="#">Gerar <span class="sr-only">(current)</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Historico</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Relatórios</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Fechar</a>
          </li>
        </ul>
      </div>
    </nav>
  </header>


  @yield('content', "Não há conteúdo para exibir")


  <footer>
    @yield('footer', 'Todos os direitos reservados')
  </footer>


  <!-- JavaScript Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>

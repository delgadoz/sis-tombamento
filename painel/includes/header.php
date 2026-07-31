<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../index');
    exit();
}
?>

<header>
	<nav>
		<ul>
			<li><a href="painel">Início</a></li> |
			<li class="has-submenu">
				<a href="#">Bens Móveis</a>
				<ul class="submenu">
					<li><a href="cadastrar-bem-movel">Cadastrar</a></li>
					<li><a href="alterar-bem-movel">Alterar</a></li>
				</ul>
			</li> |
			<li class="has-submenu">
				<a href="#">Bens Imóveis</a>
				<ul class="submenu">
					<li><a href="#">Cadastrar</a></li>
					<li><a href="#">Alterar</a></li>
				</ul>
			</li> |
			<li class="has-submenu">
				<a href="#">Setores</a>
				<ul class="submenu">
					<li><a href="cadastrar-setor">Cadastrar Setor</a></li>
					<li><a href="cadastrar-subsetor">Cadastrar Subsetor</a></li>
					<li><a href="cadastrar-unidade">Cadastrar Unidade</a></li>
				</ul>
			</li> |
			<li class="has-submenu">
				<a href="#">Relatórios</a>
				<ul class="submenu">
					<li><a href="relatorio-bens-moveis">Bens Móveis</a></li>
					<li><a href="relatorio-movimentos">Movimentações</a></li>
				</ul>
			</li> |
			<li><a href="configuracoes">Configurações</a></li> |
			<li><a href="logout">Sair</a></li>
		</ul>
	</nav>
</header>

<script>
	// Suporte a toque: abre/fecha o submenu ao clicar no mobile
	document.querySelectorAll('.has-submenu > a').forEach(function(link) {
		link.addEventListener('click', function(e) {
			// Só intercepta em dispositivos sem hover (touch)
			if (window.matchMedia('(hover: none)').matches) {
				e.preventDefault();
				const parent = this.parentElement;
				const isActive = parent.classList.contains('active');

				// Fecha todos os outros submenus abertos
				document.querySelectorAll('.has-submenu.active').forEach(function(el) {
					el.classList.remove('active');
				});

				// Alterna o atual
				if (!isActive) {
					parent.classList.add('active');
				}
			}
		});
	});

	// Fecha o submenu ao clicar fora
	document.addEventListener('click', function(e) {
		if (!e.target.closest('.has-submenu')) {
			document.querySelectorAll('.has-submenu.active').forEach(function(el) {
				el.classList.remove('active');
			});
		}
	});
</script>

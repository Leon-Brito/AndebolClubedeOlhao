
document.addEventListener("DOMContentLoaded", () => {

  // 1. Inicialização do Swiper (corrigido e protegido contra duplicidade)
  const swiperEl = document.querySelector('.mySwiper');
  if (swiperEl) {
    // Evita inicialização duplicada
    if (window.swiperInitialized) return;
    window.swiperInitialized = true;

    new Swiper(swiperEl, {
      loop: true,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
    });
  }

  // 2. Inicialização dos Bootstrap Tooltips (única e correta)
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

  // 3. Captcha reload (sem alteração)
  const btnReloadCaptcha = document.getElementById("btn-reload-captcha");
  if (btnReloadCaptcha) {
    btnReloadCaptcha.addEventListener("click", async () => {
      try {
        const response = await fetch("/captcha/refresh/");
        if (!response.ok) throw new Error("Erro ao carregar captcha");
        const data = await response.json();
        document.querySelector(".captcha").src = data.image_url;
        document.getElementById("id_captcha_0").value = data.key;
      } catch (err) {
        console.error(err);
      }
    });
  }

  // 4. Validação de formulários Bootstrap (sem alteração)
  const forms = document.querySelectorAll("form");
  forms.forEach(form => {
    form.addEventListener("submit", e => {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });
});
// --- CÓDIGO PARA O MAIN.JS (Para funcionar em todas as páginas) ---

document.addEventListener("DOMContentLoaded", function() {
    // A ordem importa: primeiro tentamos criar o Admin, depois o Login/Sair
    gerirLoginLogout();    
       
});



// -------------------------------------------------------------------------
// 2. BOTÃO LOGIN / SAIR
// -------------------------------------------------------------------------
function gerirLoginLogout() {
    const menu = document.getElementById('menuPrincipal');
    if (!menu) return;

    if (document.getElementById('navConta')) return;

    const collapse = menu.closest('.navbar-collapse');
    if (!collapse) return;

    let prefixo = "";
    const path = window.location.pathname;

    if (path.includes('/socios/') || path.includes('/equipas/') || path.includes('/loja/')) {
        prefixo = "../";
    }

    const userStr = localStorage.getItem('utilizador');

    const navConta = document.createElement('ul');
    navConta.id = 'navConta';
    navConta.className = 'navbar-nav ms-lg-auto mt-3 mt-lg-0 align-items-lg-center';

    const li = document.createElement('li');

    if (userStr) {
        const utilizador = JSON.parse(userStr);

        const podeVerAdmin =
            utilizador.tipo === 'admin' ||
            utilizador.tipo === 'treinador' ||
            utilizador.admin === 1 ||
            utilizador.treinador === 1;

        li.className = 'nav-item dropdown conta-dropdown';

        li.innerHTML = `
            <a class="nav-link conta-toggle dropdown-toggle" href="#" id="contaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="conta-avatar">
                    <i class="fa-solid fa-user"></i>
                </span>

                <span class="conta-texto">
                    <small>Olá,</small>
                    <strong>${utilizador.nome}</strong>
                </span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end conta-menu" aria-labelledby="contaDropdown">

                <li>
                    <a class="dropdown-item conta-item" href="${prefixo}convocatoria.html">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Minhas Convocatórias</span>
                    </a>
                </li>

                <li>
                    <a class="dropdown-item conta-item" href="${prefixo}financeiro.html">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Financeiro / Recibos</span>
                    </a>
                </li>

                ${podeVerAdmin ? `
                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item conta-item conta-admin" href="/admin">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Painel Admin</span>
                        </a>
                    </li>
                ` : ''}

                <li><hr class="dropdown-divider"></li>

                <li>
                    <button type="button" onclick="sair()" class="dropdown-item conta-item conta-sair">
                        <i class="fa-solid fa-power-off"></i>
                        <span>Sair</span>
                    </button>
                </li>
            </ul>
        `;
    } else {
        li.className = 'nav-item';

        li.innerHTML = `
            <a href="${prefixo}login.html" class="btn-login-navbar">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Entrar</span>
            </a>
        `;
    }

    navConta.appendChild(li);
    collapse.appendChild(navConta);
}
/* =========================================================
   ÁREA PESSOAL — adicionar ao menu do utilizador
========================================================= */

(function adicionarAreaPessoalAoMenu() {
    function criarLigacaoAreaPessoal() {
        let utilizador = null;

        try {
            utilizador = JSON.parse(
                localStorage.getItem('utilizador') || 'null'
            );
        } catch (erro) {
            utilizador = null;
        }

        if (!utilizador) {
            return;
        }

        /*
         * Versão de navbar com menu de conta em dropdown.
         */
        const menuConta = document.querySelector(
            '#navConta .dropdown-menu'
        );

        if (
            menuConta &&
            !document.getElementById(
                'menu-area-pessoal'
            )
        ) {
            const item = document.createElement('li');

            item.innerHTML = `
                <a
                    id="menu-area-pessoal"
                    class="dropdown-item conta-item"
                    href="/area-cliente.html"
                >
                    <i class="fa-solid fa-user-gear me-2"></i>
                    Área pessoal
                </a>
            `;

            menuConta.prepend(item);
        }

        /*
         * Versão antiga de navbar sem dropdown.
         */
        const authContainer = document.getElementById(
            'li-auth-container'
        );

        if (
            authContainer &&
            !document.getElementById(
                'btn-area-pessoal-nav'
            )
        ) {
            const link = document.createElement('a');

            link.id = 'btn-area-pessoal-nav';
            link.href = '/area-cliente.html';
            link.className =
                'btn btn-light btn-sm rounded-pill px-3 fw-bold shadow-sm';

            link.innerHTML = `
                <i class="fa-solid fa-user-gear me-1"></i>
                Minha conta
            `;

            authContainer.prepend(link);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                setTimeout(
                    criarLigacaoAreaPessoal,
                    150
                );
            }
        );
    } else {
        setTimeout(
            criarLigacaoAreaPessoal,
            150
        );
    }
})();

// -------------------------------------------------------------------------
// FUNÇÃO DE SAIR
// -------------------------------------------------------------------------
 // Função Sair
        function sair() { 
            localStorage.removeItem('utilizador'); 
            window.location.href = '/logout'; 
        }
// public/js/auth.js


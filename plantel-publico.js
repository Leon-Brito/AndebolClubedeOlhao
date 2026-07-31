// plantel-publico.js
// Usa isto nas páginas das equipas para mostrar os jogadores vindos da base de dados.
// Exemplo no HTML:
// <div id="plantelDinamico" data-escalao="Sub-14"></div>
// <script src="../plantel-publico.js"></script>

(function () {
    const alvo = document.getElementById('plantelDinamico');

    if (!alvo) return;

    const escalao = alvo.dataset.escalao;

    if (!escalao) {
        alvo.innerHTML = '<div class="alert alert-warning">Escalão não definido.</div>';
        return;
    }

    function fotoOuDefault(foto) {
    return foto && foto.trim() ? foto.trim() : '../img/logo_9JToiDZ.png';
}

    function agruparPorPosicao(jogadores) {
        return jogadores.reduce((grupos, jogador) => {
            const posicao = jogador.posicao || 'Universal';

            if (!grupos[posicao]) {
                grupos[posicao] = [];
            }

            grupos[posicao].push(jogador);
            return grupos;
        }, {});
    }

    function cardJogador(jogador) {
        return `
            <article>
                <div class="thumbnail">
                    ${jogador.numero ? `<div class="player-nr">${jogador.numero}</div>` : ''}

                    <div class="player-photo-box">
                        <img 
                            src="${fotoOuDefault(jogador.foto)}" 
                            alt="${jogador.nome}" 
                            class="player-photo" 
                            onerror="this.onerror=null; this.src='../img/logo_9JToiDZ.png';"
                        >
                    </div>

                    <div class="caption">
                        <p class="player-info">
                            ${jogador.nome}
                            ${jogador.idade ? `<span class="player-dob">${jogador.idade}</span>` : ''}
                        </p>
                    </div>
                </div>
            </article>
        `;
    }

    async function carregarPlantelPublico() {
        alvo.innerHTML = '<div class="text-center py-4 text-muted">A carregar jogadores...</div>';

        try {
            const res = await fetch(`/api/plantel-jogadores?escalao=${encodeURIComponent(escalao)}`);
            const jogadores = await res.json();

            if (!res.ok) {
                throw new Error(jogadores.erro || 'Erro ao carregar jogadores.');
            }

            if (!jogadores.length) {
                alvo.innerHTML = '<div class="alert alert-light border text-center">Ainda não existem jogadores publicados para este escalão.</div>';
                return;
            }

            const grupos = agruparPorPosicao(jogadores);

            alvo.innerHTML = Object.entries(grupos).map(([posicao, lista]) => `
                <div class="players-by-position">
                    <div class="player-position">
                        <h3>${posicao}</h3>
                    </div>

                    <div class="players-wrapper">
                        ${lista.map(cardJogador).join('')}
                    </div>
                </div>
            `).join('');

        } catch (err) {
            console.error(err);
            alvo.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        }
    }

    carregarPlantelPublico();
})();
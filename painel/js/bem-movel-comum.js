/**
 * Funções compartilhadas pelos formulários de cadastro e alteração de bem móvel.
 *
 *  - criarCascataLocalizacao(): selects em cascata Setor → Subsetor → Unidade
 *  - formatarMoeda() / aplicarMascaraMoeda(): campo de custo em R$
 *
 * Os textos vindos do servidor são sempre inseridos com textContent / value
 * (nunca innerHTML), então nomes de setor com < > & " não viram HTML.
 */

/**
 * Configura os selects de localização e devolve funções para carregá-los
 * programaticamente (já selecionando um valor-alvo, se informado).
 */
function criarCascataLocalizacao({ selectSetor, selectSubsetor, selectUnidade, wrapperSubsetor, wrapperUnidade }) {

    // Contadores de requisição: se o usuário trocar o setor antes da resposta
    // anterior chegar, a resposta antiga é ignorada (evita lista trocada).
    let reqSubsetor = 0;
    let reqUnidade  = 0;

    function opcaoUnica(select, texto, desabilitada) {
        select.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = texto;
        opt.selected = true;
        if (desabilitada) opt.disabled = true;
        select.appendChild(opt);
    }

    function resetarUnidade(texto = 'Selecione primeiro o subsetor') {
        reqUnidade++;
        wrapperUnidade.classList.remove('loading');
        opcaoUnica(selectUnidade, texto, false);
        selectUnidade.disabled = true;
    }

    function preencherLista(select, itens, alvo) {
        itens.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.descricao;
            opt.textContent = item.descricao;
            select.appendChild(opt);
        });

        if (alvo && itens.some(item => item.descricao === alvo)) {
            select.value = alvo;
            return true;
        }
        return false;
    }

    /**
     * Carrega as unidades de setor/subsetor. Resolve com true se unidadeAlvo
     * foi encontrada e selecionada.
     */
    function carregarUnidades(setor, subsetor, unidadeAlvo = '') {
        const minhaReq = ++reqUnidade;

        opcaoUnica(selectUnidade, 'Carregando...', false);
        selectUnidade.disabled = true;
        wrapperUnidade.classList.add('loading');

        return fetch('buscar-unidades.php?setor=' + encodeURIComponent(setor) + '&subsetor=' + encodeURIComponent(subsetor))
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(data => {
                if (minhaReq !== reqUnidade) return false;
                wrapperUnidade.classList.remove('loading');

                if (!Array.isArray(data) || data.length === 0) {
                    opcaoUnica(selectUnidade, 'Nenhuma unidade encontrada', false);
                    selectUnidade.disabled = true;
                    return false;
                }

                opcaoUnica(selectUnidade, 'Nenhuma (opcional)', false);
                const achou = preencherLista(selectUnidade, data, unidadeAlvo);
                selectUnidade.disabled = false;
                return achou;
            })
            .catch(() => {
                if (minhaReq !== reqUnidade) return false;
                wrapperUnidade.classList.remove('loading');
                opcaoUnica(selectUnidade, 'Erro ao carregar', false);
                selectUnidade.disabled = true;
                return false;
            });
    }

    /**
     * Carrega os subsetores do setor. Se subsetorAlvo existir na lista, ele é
     * selecionado e as unidades são carregadas (selecionando unidadeAlvo).
     * Resolve com true se o subsetorAlvo foi encontrado.
     */
    function carregarSubsetores(setor, subsetorAlvo = '', unidadeAlvo = '') {
        const minhaReq = ++reqSubsetor;

        resetarUnidade();
        opcaoUnica(selectSubsetor, 'Carregando...', true);
        selectSubsetor.disabled = true;
        wrapperSubsetor.classList.add('loading');

        return fetch('buscar-subsetores.php?setor=' + encodeURIComponent(setor))
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(data => {
                if (minhaReq !== reqSubsetor) return false;
                wrapperSubsetor.classList.remove('loading');

                if (!Array.isArray(data) || data.length === 0) {
                    opcaoUnica(selectSubsetor, 'Nenhum subsetor encontrado', true);
                    return false;
                }

                opcaoUnica(selectSubsetor, 'Selecione o subsetor', true);
                const achou = preencherLista(selectSubsetor, data, subsetorAlvo);
                selectSubsetor.disabled = false;

                if (achou) {
                    return carregarUnidades(setor, subsetorAlvo, unidadeAlvo).then(() => true);
                }
                return false;
            })
            .catch(() => {
                if (minhaReq !== reqSubsetor) return false;
                wrapperSubsetor.classList.remove('loading');
                opcaoUnica(selectSubsetor, 'Erro ao carregar', true);
                return false;
            });
    }

    /** Volta Subsetor e Unidade ao estado inicial ("selecione primeiro..."). */
    function resetar() {
        reqSubsetor++;
        wrapperSubsetor.classList.remove('loading');
        opcaoUnica(selectSubsetor, 'Selecione primeiro o setor', true);
        selectSubsetor.disabled = true;
        resetarUnidade();
    }

    // Interação manual do usuário
    selectSetor.addEventListener('change', function () {
        carregarSubsetores(this.value);
    });

    selectSubsetor.addEventListener('change', function () {
        carregarUnidades(selectSetor.value, this.value);
    });

    return { carregarSubsetores, carregarUnidades, resetar };
}

/**
 * Formata um número (ex.: 1500.9) como moeda brasileira sem o "R$" (ex.: "1.500,90").
 */
function formatarMoeda(numero) {
    const centavos = Math.round(numero * 100);
    const digits   = String(centavos).padStart(3, '0');
    const parteCentavos = digits.slice(-2);
    let   parteReais    = digits.slice(0, -2).replace(/^0+/, '') || '0';
    parteReais = parteReais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parteReais + ',' + parteCentavos;
}

/**
 * Preenche o campo visível (formatado) e o oculto (numérico) a partir de um
 * valor numérico. Valor vazio ou inválido limpa os dois campos.
 */
function definirValorMoeda(inputDisplay, inputHidden, valor) {
    const numero = parseFloat(valor);
    if (valor === '' || valor === null || valor === undefined || isNaN(numero) || numero < 0) {
        inputDisplay.value = '';
        inputHidden.value  = '';
        return;
    }
    inputHidden.value  = numero.toFixed(2);
    inputDisplay.value = formatarMoeda(numero);
}

/**
 * Máscara de digitação do custo: mantém o campo visível formatado e o campo
 * oculto com o valor numérico (ex.: 1500.99) enviado ao PHP.
 */
function aplicarMascaraMoeda(inputDisplay, inputHidden) {
    inputDisplay.addEventListener('input', function () {
        let digits = this.value.replace(/\D/g, '');

        // Campo apagado por completo: limpa também o oculto
        if (digits === '') {
            inputHidden.value = '';
            return;
        }

        // Garante ao menos 3 dígitos (ex: "001" = R$ 0,01)
        digits = digits.padStart(3, '0');

        const centavos = digits.slice(-2);
        let reais      = digits.slice(0, -2).replace(/^0+/, '') || '0';
        reais = reais.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        this.value = reais + ',' + centavos;
        inputHidden.value = reais.replace(/\./g, '') + '.' + centavos;
    });
}

<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Serviços';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="servicos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Tabela de Serviços</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Catálogo com preço mínimo calculado automaticamente</p>
            </div>
            <button class="btn-primary" @click="abrirModal()">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Serviço
            </button>
        </div>

        <!-- Configuração de horas mensais -->
        <div class="card" style="padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div style="font-size:13px; color:#94a3b8;">
                <i data-lucide="info" style="width:14px;height:14px; vertical-align:middle; margin-right:4px;"></i>
                Horas mensais de capacidade da agência (para rateio dos custos fixos):
            </div>
            <input class="input" type="number" min="1" x-model="horasMensais" style="width:100px;" placeholder="160">
            <div style="font-size:13px; color:#6b7280;">
                Custo fixo total mensal:
                <strong style="color:#ef4444;" x-text="formatarMoeda(totalCustosFixos)"></strong>
            </div>
        </div>

        <!-- Tabela de Serviços -->
        <div class="card" style="overflow:hidden;">
            <div class="table-header" style="display:grid; grid-template-columns:2fr 80px 1fr 1fr 80px 100px;">
                <span>Serviço</span><span>Horas</span><span>Custo Total</span><span>Preço Mínimo</span><span>Markup</span><span style="text-align:right;">Ações</span>
            </div>

            <template x-if="carregando">
                <div style="padding:40px; text-align:center; color:#4b5563;">Carregando...</div>
            </template>

            <template x-if="!carregando && lista.length === 0">
                <div style="padding:40px; text-align:center; color:#4b5563;">
                    <i data-lucide="briefcase" style="width:36px;height:36px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    Nenhum serviço cadastrado
                </div>
            </template>

            <template x-for="s in lista" :key="s.id">
                <div class="table-row" style="display:grid; grid-template-columns:2fr 80px 1fr 1fr 80px 100px; align-items:center;">
                    <div class="table-cell">
                        <div style="color:#e2e8f0; font-weight:500;" x-text="s.nome"></div>
                        <div style="color:#6b7280; font-size:12px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="s.descricao || '—'"></div>
                    </div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="s.horas_estimadas + 'h'"></div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="formatarMoeda(parseFloat(s.custo_producao) + parseFloat(s.custos_variaveis))"></div>
                    <div class="table-cell" style="font-weight:700; color:#a78bfa;" x-text="formatarMoeda(calcularPrecoMinimo(s))"></div>
                    <div class="table-cell">
                        <span style="color:#10b981; font-weight:600;" x-text="s.markup + '%'"></span>
                    </div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirModal(s)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="excluir(s.id)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <p style="font-size:12px; color:#4b5563; margin-top:12px;">
            💡 Preço mínimo = (horas / capacidade mensal × custos fixos) + custo variável + markup aplicado.
        </p>

    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;" x-text="form.id ? 'Editar Serviço' : 'Novo Serviço'"></h2>
                <button @click="modalAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <form @submit.prevent="salvar()">
                <div style="margin-bottom:16px;">
                    <label class="label">Nome do Serviço *</label>
                    <input class="input" x-model="form.nome" required placeholder="Ex: Gestão de Tráfego Pago">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">Descrição</label>
                    <textarea class="input" x-model="form.descricao" rows="2" placeholder="Descreva brevemente o serviço" style="resize:vertical;"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Horas Estimadas *</label>
                        <input class="input" type="number" step="0.5" min="0.5" x-model="form.horas_estimadas" required placeholder="Ex: 20">
                    </div>
                    <div>
                        <label class="label">Custo de Produção (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0" x-model="form.custo_producao" required placeholder="0,00">
                    </div>
                    <div>
                        <label class="label">Custos Variáveis (R$)</label>
                        <input class="input" type="number" step="0.01" min="0" x-model="form.custos_variaveis" placeholder="Ferramentas, etc.">
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">Markup Desejado (%) *</label>
                    <input class="input" type="number" step="0.5" min="0" x-model="form.markup" required placeholder="Ex: 30">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">
                        Preço mínimo calculado: <strong style="color:#a78bfa;" x-text="formatarMoeda(calcularPrecoMinimo(form))"></strong>
                    </p>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar Serviço')"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function servicos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        form: {},
        totalCustosFixos: 0,
        horasMensais: 160,

        async init() {
            await Promise.all([this.carregar(), this.carregarCustosFixos()]);
        },

        async carregar() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>');
                this.lista = await r.json();
            } catch(e) { toast('Erro ao carregar serviços', 'erro'); }
            this.carregando = false;
        },

        async carregarCustosFixos() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>');
                const custos = await r.json();
                this.totalCustosFixos = custos
                    .filter(c => c.ativo == '1')
                    .reduce((s, c) => s + (c.recorrencia === 'anual' ? parseFloat(c.valor)/12 : parseFloat(c.valor)), 0);
            } catch(e) {}
        },

        calcularPrecoMinimo(s) {
            const horas = parseFloat(s.horas_estimadas || 0);
            const custo = parseFloat(s.custo_producao || 0) + parseFloat(s.custos_variaveis || 0);
            const rateio = this.horasMensais > 0 ? (horas / this.horasMensais) * this.totalCustosFixos : 0;
            const markup = parseFloat(s.markup || 0) / 100;
            return (rateio + custo) * (1 + markup);
        },

        abrirModal(item = null) {
            this.form = item ? { ...item } : { nome:'', descricao:'', horas_estimadas:'', custo_producao:'', custos_variaveis:'0', markup:'30' };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                if (r.ok) {
                    toast('Serviço salvo!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregar();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este serviço?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) { toast('Serviço excluído', 'sucesso'); await this.carregar(); }
                else { toast('Erro ao excluir', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

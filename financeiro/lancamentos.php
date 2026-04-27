<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Lançamentos';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="lancamentos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Lançamentos</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Contas a pagar e a receber</p>
            </div>
            <button class="btn-primary" @click="abrirModal()">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Lançamento
            </button>
        </div>

        <!-- Filtros -->
        <div class="card" style="padding:16px 20px; margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <div style="display:flex; gap:6px;">
                <button @click="filtros.tipo=''" :class="filtros.tipo==='' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">Todos</button>
                <button @click="filtros.tipo='receber'" :class="filtros.tipo==='receber' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">A Receber</button>
                <button @click="filtros.tipo='pagar'" :class="filtros.tipo==='pagar' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">A Pagar</button>
            </div>
            <select class="select" x-model="filtros.status" style="width:auto; flex:1; min-width:140px;">
                <option value="">Todos os status</option>
                <option value="pendente">Pendente</option>
                <option value="pago_parcial">Pago Parcial</option>
                <option value="pago">Pago</option>
                <option value="atrasado">Atrasado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <input class="input" type="month" x-model="filtros.mes" style="width:auto; max-width:160px;">
            <button class="btn-secondary" @click="carregarLancamentos()" style="padding:6px 14px; font-size:13px;">
                <i data-lucide="search" style="width:13px;height:13px;"></i> Filtrar
            </button>
        </div>

        <!-- Tabela -->
        <div class="card" style="overflow:hidden;">
            <div class="table-header" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr 100px;">
                <span>Descrição</span><span>Vencimento</span><span>Valor</span><span>Pago</span><span>Status</span><span style="text-align:right;">Ações</span>
            </div>

            <template x-if="carregando">
                <div style="padding:40px; text-align:center; color:#4b5563;">Carregando...</div>
            </template>

            <template x-if="!carregando && lancamentosFiltrados.length === 0">
                <div style="padding:40px; text-align:center; color:#4b5563;">
                    <i data-lucide="inbox" style="width:36px;height:36px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    Nenhum lançamento encontrado
                </div>
            </template>

            <template x-for="l in lancamentosFiltrados" :key="l.id">
                <div class="table-row" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr 100px; align-items:center;">
                    <div class="table-cell">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span :style="l.tipo==='receber' ? 'color:#10b981' : 'color:#ef4444'">
                                <i :data-lucide="l.tipo==='receber' ? 'arrow-down-left' : 'arrow-up-right'" style="width:14px;height:14px;"></i>
                            </span>
                            <div>
                                <div style="color:#e2e8f0; font-weight:500;" x-text="l.descricao"></div>
                                <div style="color:#6b7280; font-size:12px;" x-text="l.cliente_fornecedor || l.categoria"></div>
                            </div>
                        </div>
                    </div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="formatarData(l.vencimento)"></div>
                    <div class="table-cell" style="font-weight:600;" :style="l.tipo==='receber' ? 'color:#10b981' : 'color:#ef4444'" x-text="formatarMoeda(l.valor)"></div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="formatarMoeda(l.valor_pago)"></div>
                    <div class="table-cell">
                        <span class="badge" :class="classeStatus(l.status)" x-text="labelStatus(l.status)"></span>
                    </div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirBaixa(l)" title="Registrar pagamento" style="color:#a78bfa; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="abrirModal(l)" title="Editar" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="excluir(l.id)" title="Excluir" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Resumo rodapé -->
        <div style="display:flex; gap:16px; margin-top:16px; flex-wrap:wrap;">
            <div style="font-size:13px; color:#6b7280;">
                Total a receber: <strong style="color:#10b981;" x-text="formatarMoeda(totalReceber)"></strong>
            </div>
            <div style="font-size:13px; color:#6b7280;">
                Total a pagar: <strong style="color:#ef4444;" x-text="formatarMoeda(totalPagar)"></strong>
            </div>
        </div>
    </main>

    <!-- Modal Novo/Editar Lançamento -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;" x-text="form.id ? 'Editar Lançamento' : 'Novo Lançamento'"></h2>
                <button @click="modalAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form @submit.prevent="salvar()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Tipo *</label>
                        <select class="select" x-model="form.tipo" required>
                            <option value="receber">A Receber</option>
                            <option value="pagar">A Pagar</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Modalidade *</label>
                        <select class="select" x-model="form.modalidade" required>
                            <option value="avista">À Vista</option>
                            <option value="parcelado">Parcelado</option>
                            <option value="recorrente">Recorrente</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Descrição *</label>
                    <input class="input" x-model="form.descricao" required maxlength="500" placeholder="Ex: Honorários cliente ABC">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Valor (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="form.valor" required>
                    </div>
                    <div>
                        <label class="label">Vencimento *</label>
                        <input class="input" type="date" x-model="form.vencimento" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select" x-model="form.categoria">
                            <option value="servicos">Serviços</option>
                            <option value="produtos">Produtos</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="impostos">Impostos</option>
                            <option value="folha">Folha</option>
                            <option value="marketing">Marketing</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Cliente / Fornecedor</label>
                        <input class="input" x-model="form.cliente_fornecedor" placeholder="Nome">
                    </div>
                </div>

                <!-- Parcelado -->
                <div x-show="form.modalidade==='parcelado'" style="margin-bottom:16px;">
                    <label class="label">Número de Parcelas</label>
                    <input class="input" type="number" min="2" max="120" x-model="form.total_parcelas" placeholder="Ex: 12">
                </div>

                <!-- Recorrente -->
                <div x-show="form.modalidade==='recorrente'" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Frequência</label>
                        <select class="select" x-model="form.frequencia">
                            <option value="semanal">Semanal</option>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Até (opcional)</label>
                        <input class="input" type="date" x-model="form.data_termino">
                    </div>
                </div>

                <div style="margin-bottom:24px;">
                    <label class="label">Observação</label>
                    <textarea class="input" x-model="form.observacao" rows="2" placeholder="Opcional" style="resize:vertical;"></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar Lançamento')"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Baixa (pagamento) -->
    <div class="modal-overlay" x-show="modalBaixaAberto" x-cloak @click.self="modalBaixaAberto=false">
        <div class="modal" style="max-width:400px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:16px; font-weight:600; color:#f1f5f9;">Registrar Pagamento</h2>
                <button @click="modalBaixaAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <template x-if="lancamentoBaixa">
                <div>
                    <p style="font-size:14px; color:#94a3b8; margin-bottom:4px;" x-text="lancamentoBaixa.descricao"></p>
                    <p style="font-size:20px; font-weight:700; color:#e2e8f0; margin-bottom:20px;">
                        Valor total: <span x-text="formatarMoeda(lancamentoBaixa.valor)"></span>
                    </p>
                    <p style="font-size:13px; color:#6b7280; margin-bottom:16px;">
                        Já pago: <strong style="color:#10b981;" x-text="formatarMoeda(lancamentoBaixa.valor_pago)"></strong>
                    </p>
                    <div style="margin-bottom:20px;">
                        <label class="label">Valor a pagar agora (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="valorBaixa" :max="lancamentoBaixa.valor - lancamentoBaixa.valor_pago">
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button class="btn-secondary" @click="modalBaixaAberto=false">Cancelar</button>
                        <button class="btn-primary" @click="confirmarBaixa()" :disabled="salvando" x-text="salvando ? 'Salvando...' : 'Confirmar'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

<script>
function lancamentos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        modalBaixaAberto: false,
        lancamentoBaixa: null,
        valorBaixa: '',
        filtros: { tipo: '', status: '', mes: '' },
        form: {},

        get lancamentosFiltrados() {
            return this.lista.filter(l => {
                if (this.filtros.tipo   && l.tipo !== this.filtros.tipo) return false;
                if (this.filtros.status && l.status !== this.filtros.status) return false;
                if (this.filtros.mes) {
                    const mes = l.vencimento ? l.vencimento.substring(0, 7) : '';
                    if (mes !== this.filtros.mes) return false;
                }
                return true;
            });
        },

        get totalReceber() {
            return this.lancamentosFiltrados
                .filter(l => l.tipo === 'receber' && l.status !== 'cancelado')
                .reduce((s, l) => s + parseFloat(l.valor || 0), 0);
        },

        get totalPagar() {
            return this.lancamentosFiltrados
                .filter(l => l.tipo === 'pagar' && l.status !== 'cancelado')
                .reduce((s, l) => s + parseFloat(l.valor || 0), 0);
        },

        async init() {
            // Pré-filtrar se vier da query string
            const params = new URLSearchParams(window.location.search);
            if (params.get('filtro')) this.filtros.status = params.get('filtro');
            await this.carregarLancamentos();
        },

        async carregarLancamentos() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>');
                this.lista = await r.json();
            } catch(e) { toast('Erro ao carregar lançamentos', 'erro'); }
            this.carregando = false;
        },

        abrirModal(lancamento = null) {
            this.form = lancamento ? { ...lancamento } : {
                tipo: 'receber', modalidade: 'avista', descricao: '', valor: '',
                vencimento: '', categoria: 'servicos', cliente_fornecedor: '',
                total_parcelas: '', frequencia: 'mensal', data_termino: '', observacao: ''
            };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const res = await r.json();
                if (r.ok) {
                    toast(this.form.id ? 'Lançamento atualizado!' : 'Lançamento(s) criado(s)!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregarLancamentos();
                } else {
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        abrirBaixa(lancamento) {
            this.lancamentoBaixa = lancamento;
            this.valorBaixa = (parseFloat(lancamento.valor) - parseFloat(lancamento.valor_pago)).toFixed(2);
            this.modalBaixaAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async confirmarBaixa() {
            if (!this.valorBaixa || parseFloat(this.valorBaixa) <= 0) return;
            this.salvando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/baixa.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.lancamentoBaixa.id, valor: parseFloat(this.valorBaixa) })
                });
                const res = await r.json();
                if (r.ok) {
                    toast('Pagamento registrado!', 'sucesso');
                    this.modalBaixaAberto = false;
                    await this.carregarLancamentos();
                } else {
                    toast(res.erro || 'Erro ao registrar baixa', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este lançamento?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) {
                    toast('Lançamento excluído', 'sucesso');
                    await this.carregarLancamentos();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao excluir', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        classeStatus(status) {
            const map = {
                pago:         'badge bg-green-500/20 text-green-400 border border-green-500/30',
                pago_parcial: 'badge bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                pendente:     'badge bg-blue-500/20 text-blue-400 border border-blue-500/30',
                atrasado:     'badge bg-red-500/20 text-red-400 border border-red-500/30',
                cancelado:    'badge bg-gray-500/20 text-gray-400 border border-gray-500/30',
            };
            return map[status] || 'badge bg-gray-500/20 text-gray-400';
        },

        labelStatus(status) {
            const map = { pago:'Pago', pago_parcial:'Parcial', pendente:'Pendente', atrasado:'Atrasado', cancelado:'Cancelado' };
            return map[status] || status;
        },

        formatarData(str) { return window.formatarData(str); },
        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

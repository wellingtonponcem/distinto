<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$me = usuarioAtual();
if ($me['nivel'] != 1) {
    echo "Acesso negado. Apenas administradores podem acessar esta página.";
    exit;
}

$tituloPagina = "Gerenciar Usuários";
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" x-data="usuarios">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Usuários do Sistema</h1>
                <p class="page-subtitle">Gerencie quem tem acesso e os níveis de permissão.</p>
            </div>
            <button @click="abrirModal()" class="btn-primary">
                <i data-lucide="plus" style="width:16px;height:16px;"></i>
                Novo Usuário
            </button>
        </div>

        <div class="card overflow-hidden">
            <div class="table-header" style="display:grid; grid-template-columns:1fr 1fr 120px 100px;">
                <span>Nome</span>
                <span>E-mail</span>
                <span>Nível</span>
                <span style="text-align:right;">Ações</span>
            </div>

            <template x-for="u in lista" :key="u.id">
                <div class="table-row" style="display:grid; grid-template-columns:1fr 1fr 120px 100px; align-items:center;">
                    <div class="table-cell font-bold" x-text="u.nome"></div>
                    <div class="table-cell text-gray-500" x-text="u.email"></div>
                    <div class="table-cell">
                        <span class="badge" 
                            :class="u.nivel == 1 ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'"
                            x-text="u.nivel == 1 ? 'Administrador' : 'Usuário'"></span>
                    </div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirModal(u)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                            <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                        </button>
                        <button @click="excluir(u.id)" x-show="u.id !== '<?= $me['id'] ?>'" class="p-2 hover:bg-red-50 text-red-500 rounded-lg">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
            <div class="modal" style="max-width:450px;">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold" x-text="form.id ? 'Editar Usuário' : 'Novo Usuário'"></h2>
                    <button @click="modalAberto=false"><i data-lucide="x"></i></button>
                </div>

                <form @submit.prevent="salvar()">
                    <div class="mb-4">
                        <label class="label">Nome Completo</label>
                        <input class="input" x-model="form.nome" required placeholder="Ex: João Silva">
                    </div>
                    
                    <div class="mb-4">
                        <label class="label">E-mail (Login)</label>
                        <input class="input" type="email" x-model="form.email" required placeholder="email@agencia.com">
                    </div>

                    <div class="mb-4">
                        <label class="label" x-text="form.id ? 'Nova Senha (deixe em branco para manter)' : 'Senha'"></label>
                        <input class="input" type="password" x-model="form.senha" :required="!form.id">
                    </div>

                    <div class="mb-6">
                        <label class="label">Nível de Acesso</label>
                        <select class="select" x-model="form.nivel">
                            <option value="0">Usuário Comum</option>
                            <option value="1">Administrador (Acesso total)</option>
                        </select>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : 'Salvar Usuário'"></button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('usuarios', () => ({
        lista: [],
        modalAberto: false,
        salvando: false,
        form: {},

        async init() {
            await this.carregar();
        },

        async carregar() {
            try {
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php') ?>');
                this.lista = await r.json();
                this.$nextTick(() => lucide.createIcons());
            } catch(e) { toast('Erro ao carregar usuários', 'erro'); }
        },

        abrirModal(usuario = null) {
            this.form = usuario ? { ...usuario, senha: '' } : { nome: '', email: '', senha: '', nivel: 0 };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                if (r.ok) {
                    await this.carregar();
                    this.modalAberto = false;
                    toast('Usuário salvo!', 'sucesso');
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este usuário permanentemente?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/sistema/usuarios.php?id=') ?>' + id, { method: 'DELETE' });
                if (r.ok) {
                    await this.carregar();
                    toast('Usuário excluído', 'sucesso');
                }
            } catch(e) { toast('Erro ao excluir', 'erro'); }
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>

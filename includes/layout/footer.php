    </main><!-- fim #main-content -->
</div><!-- fim #app-wrapper -->

<script>
    // Inicializar ícones Lucide
    lucide.createIcons();

    // Notificações toast globais
    window.toast = function(msg, tipo = 'sucesso') {
        const cores = {
            sucesso: 'border-green-500/40 bg-green-500/10 text-green-300',
            erro:    'border-red-500/40 bg-red-500/10 text-red-300',
            aviso:   'border-yellow-500/40 bg-yellow-500/10 text-yellow-300',
            info:    'border-blue-500/40 bg-blue-500/10 text-blue-300',
        };
        const t = document.createElement('div');
        t.className = `fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl border text-sm font-medium shadow-lg ${cores[tipo] || cores.info}`;
        t.style.animation = 'slideIn 0.2s ease';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 300); }, 3000);
    };

    // Formatar moeda BRL
    window.formatarMoeda = function(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val || 0);
    };

    // Formatar data para pt-BR
    window.formatarData = function(str) {
        if (!str) return '—';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    };

    // Recriar ícones após Alpine.js atualizar o DOM
    document.addEventListener('alpine:initialized', () => lucide.createIcons());
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(20px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
</style>
</body>
</html>

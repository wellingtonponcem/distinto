<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = "Consultor de Precificação IA";
require_once __DIR__ . '/../includes/layout/head.php';
?>
<style>
    .chat-container {
        height: calc(100vh - 220px);
        display: flex;
        flex-direction: column;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        overflow: hidden;
    }
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .message {
        max-width: 80%;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.5;
    }
    .message-ia {
        align-self: flex-start;
        background: #1e1e2d;
        color: #e2e8f0;
        border-bottom-left-radius: 2px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .message-user {
        align-self: flex-end;
        background: #4f46e5;
        color: white;
        border-bottom-right-radius: 2px;
    }
    .chat-input-area {
        padding: 16px 24px;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.05);
        display: flex;
        gap: 12px;
    }
    .typing-indicator {
        font-size: 12px;
        color: #71717a;
        margin-bottom: 8px;
    }
    .markdown-content h1, .markdown-content h2, .markdown-content h3 {
        font-size: 16px;
        font-weight: 700;
        margin: 12px 0 6px;
        color: #fff;
    }
    .markdown-content p { margin-bottom: 8px; }
    .markdown-content ul { margin-left: 20px; margin-bottom: 8px; list-style-type: disc; }
</style>

<div id="app-wrapper" x-data="consultor">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Consultor de Precificação IA</h1>
                <p class="page-subtitle">Converse com a IA para definir o preço ideal de serviços complexos.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button @click="reiniciar" class="btn-secondary">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Nova Consulta
                </button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 300px; gap:20px; flex:1;">
            <div class="chat-container">
                <div class="chat-messages" id="chat-box">
                <template x-for="(msg, index) in mensagens" :key="index">
                    <div :class="'message ' + (msg.role === 'assistant' ? 'message-ia' : 'message-user')">
                        <div class="markdown-content" x-html="renderMarkdown(msg.content)"></div>
                    </div>
                </template>
                <div x-show="carregando" class="message message-ia">
                    <div class="flex gap-1 items-center py-1">
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="enviar" class="chat-input-area">
                <input 
                    type="text" 
                    x-model="input" 
                    placeholder="Responda ou pergunte algo..." 
                    class="input flex-1"
                    :disabled="carregando"
                    autocomplete="off"
                >
                <button type="submit" class="btn-primary" :disabled="carregando || !input.trim()">
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>

        <aside class="flex flex-col gap-4">
            <div class="card p-4">
                <h3 class="text-sm font-bold mb-3 flex items-center gap-2">
                    <i data-lucide="brain" class="w-4 h-4 text-purple-500"></i>
                    Fatos Memorizados
                </h3>
                <div class="text-[11px] text-gray-400 whitespace-pre-wrap leading-relaxed bg-black/20 p-3 rounded-lg border border-white/5" x-text="memoria || 'A IA ainda não memorizou fatos específicos.'"></div>
                <p class="text-[10px] text-gray-500 mt-2">Clique em 'Memorizar' para salvar informações desta conversa para o futuro.</p>
            </div>
        </aside>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('consultor', () => ({
        mensagens: [
            { role: 'assistant', content: 'Olá! Sou seu Consultor de Precificação. Para começarmos, qual tipo de serviço você deseja precificar hoje? (Ex: Campanha de Tráfego, Captação Audiovisual, Identidade Visual, etc.)' }
        ],
        input: '',
        carregando: false,
        memorizando: false,
        memoria: '',

        async init() {
            // Carregar memória inicial
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/consultor.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mensagens: [{role:'user', content:'Olá'}] })
                });
                const res = await r.json();
                if (res.memoria) this.memoria = res.memoria;
            } catch(e) {}
        },

        async memorizar() {
            this.memorizando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/memorizar.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mensagens: this.mensagens })
                });
                const res = await r.json();
                if (res.ok) {
                    this.memoria = res.memoria;
                    toast('Aprendizados memorizados com sucesso!', 'sucesso');
                }
            } catch(e) {
                toast('Erro ao memorizar', 'erro');
            }
            this.memorizando = false;
        },

        renderMarkdown(content) {
            return marked.parse(content);
        },

        async enviar() {
            const texto = this.input.trim();
            if (!texto || this.carregando) return;

            this.mensagens.push({ role: 'user', content: texto });
            this.input = '';
            this.carregando = true;
            this.scrollBottom();

            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/consultor.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mensagens: this.mensagens })
                });
                
                const res = await r.json();
                if (res.resposta) {
                    this.mensagens.push({ role: 'assistant', content: res.resposta });
                    if (res.memoria) this.memoria = res.memoria;
                } else {
                    toast(res.erro || 'Erro na IA', 'erro');
                }
            } catch(e) {
                toast('Erro de conexão', 'erro');
            } finally {
                this.carregando = false;
                this.scrollBottom();
                this.$nextTick(() => lucide.createIcons());
            }
        },

        reiniciar() {
            if (confirm('Deseja iniciar uma nova conversa de precificação?')) {
                this.mensagens = [
                    { role: 'assistant', content: 'Olá! Vamos começar uma nova precificação. Qual serviço você quer analisar agora?' }
                ];
            }
        },

        scrollBottom() {
            this.$nextTick(() => {
                const box = document.getElementById('chat-box');
                box.scrollTop = box.scrollHeight;
            });
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>

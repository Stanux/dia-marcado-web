<script setup>
import { Head } from '@inertiajs/vue3';

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Dia Marcado — Uma plataforma completa para o seu grande dia">
        <meta
            name="description"
            content="Crie seu site, organize convidados, RSVP, lista de presentes e financeiro. Tudo em um só lugar, com elegância e simplicidade."
        />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
        <link
            href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap"
            rel="stylesheet"
        >
    </Head>

    <div class="landing-page font-sans">
        <header class="sticky top-0 z-50 border-b border-black/5 bg-white/70 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="/" class="flex items-center gap-3">
                    <img
                        src="/images/logo-dia-marcado-icon.png"
                        alt="Logo Dia Marcado"
                        class="h-11 w-11 rounded-xl object-cover"
                    >
                    <span class="font-semibold tracking-tight">Dia Marcado</span>
                </a>

                <nav class="hidden items-center gap-8 text-sm text-muted md:flex">
                    <a class="hover:text-black" href="#como-funciona">Como funciona</a>
                    <a class="hover:text-black" href="#recursos">Recursos</a>
                    <a class="hover:text-black" href="#precos">Planos</a>
                    <a class="hover:text-black" href="#faq">FAQ</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="/admin/login" class="hidden text-sm text-muted sm:inline">Entrar</a>
                    <a
                        href="#cta"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        style="background: var(--rose);"
                    >
                        Começar
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </header>

        <section class="bg-soft">
            <div class="mx-auto max-w-6xl px-6 pb-12 pt-16">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-strong">
                            <span class="h-2 w-2 rounded-full brand-gold-bg"></span>
                            Elegância + organização em um só lugar
                        </p>

                        <h1 class="font-serif mt-5 text-4xl font-semibold leading-[1.05] sm:text-5xl lg:text-6xl">
                            Um grande dia merece<br>
                            <span class="brand-rose">uma experiência perfeita.</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg text-muted">
                            Crie um site lindo, organize convidados, receba confirmações, gerencie presentes e acompanhe o financeiro
                            — com simplicidade e carinho em cada detalhe.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                href="/admin"
                                class="inline-flex justify-center rounded-xl px-5 py-3 font-semibold text-white"
                                style="background: var(--rose);"
                            >
                                Criar meu casamento
                            </a>
                            <a href="#como-funciona" class="ring-soft inline-flex justify-center rounded-xl bg-white px-5 py-3 font-semibold">
                                Ver como funciona
                            </a>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-4 text-sm text-muted-strong">
                            <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-black/20"></span>Setup rápido</span>
                            <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-black/20"></span>Pix + Cartão</span>
                            <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-black/20"></span>Tudo integrado</span>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="ring-soft shadow-soft overflow-hidden rounded-[28px] bg-white">
                            <div class="flex items-center justify-between border-b border-black/5 p-4">
                                <span class="text-sm font-semibold">Site do Casal</span>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs"
                                    style="background: rgba(224,77,115,.10); color: var(--rose);"
                                >
                                    elegante • leve • moderno
                                </span>
                            </div>

                            <div class="p-6">
                                <div class="ring-soft grid aspect-[16/10] place-items-center overflow-hidden rounded-2xl bg-[#FFF7F9]">
                                    <span class="text-sm text-muted">[ Foto do casal / Hero do template ]</span>
                                </div>

                                <div class="mt-6 grid grid-cols-3 gap-4">
                                    <div class="ring-soft rounded-2xl bg-white p-4">
                                        <p class="text-xs text-muted">Presença confirmada</p>
                                        <p class="mt-2 text-lg font-semibold">Em tempo real</p>
                                    </div>
                                    <div class="ring-soft rounded-2xl bg-white p-4">
                                        <p class="text-xs text-muted">Presentes</p>
                                        <p class="mt-2 text-lg font-semibold">Pix/Cartão</p>
                                    </div>
                                    <div class="ring-soft rounded-2xl bg-white p-4">
                                        <p class="text-xs text-muted">Planejamento</p>
                                        <p class="mt-2 text-lg font-semibold">Com timeline</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <section id="como-funciona" class="mx-auto max-w-6xl px-6 py-20">
            <div class="max-w-2xl">
                <h2 class="font-serif text-3xl font-semibold sm:text-4xl">Tudo flui — do convite ao “sim”.</h2>
                <p class="mt-4 text-lg text-muted">
                    O Dia Marcado centraliza o que mais dá trabalho: site, convidados, confirmação, presentes e organização.
                </p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="ring-soft rounded-2xl bg-white p-7">
                    <p class="text-sm font-semibold brand-rose">1</p>
                    <p class="mt-2 font-semibold">Crie seu site</p>
                    <p class="mt-2 text-muted">Escolha um template, personalize e publique em minutos.</p>
                </div>
                <div class="ring-soft rounded-2xl bg-white p-7">
                    <p class="text-sm font-semibold brand-rose">2</p>
                    <p class="mt-2 font-semibold">Convide e receba RSVP</p>
                    <p class="mt-2 text-muted">Núcleos familiares, eventos e confirmações com clareza.</p>
                </div>
                <div class="ring-soft rounded-2xl bg-white p-7">
                    <p class="text-sm font-semibold brand-rose">3</p>
                    <p class="mt-2 font-semibold">Organize e acompanhe</p>
                    <p class="mt-2 text-muted">Tarefas, fornecedores, presentes e financeiro no mesmo lugar.</p>
                </div>
            </div>
        </section>

        <section id="recursos" class="border-y border-black/5 bg-white">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="grid items-start gap-10 lg:grid-cols-2">
                    <div>
                        <h2 class="font-serif text-3xl font-semibold sm:text-4xl">Um cuidado a mais em cada detalhe.</h2>
                        <p class="mt-4 text-lg text-muted">
                            Recursos pensados para facilitar a vida do casal — e encantar os convidados.
                        </p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                            <p class="font-semibold">Site elegante</p>
                            <p class="mt-2 text-muted">Templates lindos, identidade visual e domínio.</p>
                        </div>
                        <div class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                            <p class="font-semibold">RSVP inteligente</p>
                            <p class="mt-2 text-muted">Confirmações por família e por evento.</p>
                        </div>
                        <div class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                            <p class="font-semibold">Lista de presentes</p>
                            <p class="mt-2 text-muted">Pix + Cartão, controle e conciliação.</p>
                        </div>
                        <div class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                            <p class="font-semibold">Planejamento + financeiro</p>
                            <p class="mt-2 text-muted">Timeline, tarefas, orçamento e relatórios.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="precos" class="mx-auto max-w-6xl px-6 py-20">
            <div class="max-w-2xl">
                <h2 class="font-serif text-3xl font-semibold sm:text-4xl">Planos para cada fase do sonho.</h2>
                <p class="mt-4 text-lg text-muted">Comece simples e evolua quando quiser.</p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                <div class="ring-soft rounded-2xl bg-white p-7">
                    <p class="font-semibold">Essencial</p>
                    <p class="mt-2 text-muted">Para começar com leveza.</p>
                    <p class="font-serif mt-6 text-4xl font-semibold">R$ 0</p>
                    <a href="/admin" class="ring-soft mt-6 inline-flex w-full justify-center rounded-xl bg-[#FFF7F9] px-5 py-3 font-semibold">
                        Começar
                    </a>
                    <ul class="mt-6 space-y-3 text-sm text-muted">
                        <li>• Site com template</li>
                        <li>• RSVP básico</li>
                        <li>• Planejamento essencial</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-7 shadow-[0_25px_80px_rgba(224,77,115,.18)]">
                    <p class="font-semibold brand-rose">Completo</p>
                    <p class="mt-2 text-muted">Para organizar tudo com tranquilidade.</p>
                    <p class="font-serif mt-6 text-4xl font-semibold">R$ —</p>
                    <a
                        href="/admin"
                        class="mt-6 inline-flex w-full justify-center rounded-xl px-5 py-3 font-semibold text-white"
                        style="background: var(--rose);"
                    >
                        Assinar
                    </a>
                    <ul class="mt-6 space-y-3 text-sm text-muted">
                        <li>• RSVP avançado (núcleos + eventos)</li>
                        <li>• Presentes com Pix/Cartão</li>
                        <li>• Financeiro (orçado vs real)</li>
                        <li>• Relatórios</li>
                    </ul>
                </div>

                <div class="ring-soft rounded-2xl bg-white p-7">
                    <p class="font-semibold">Premium</p>
                    <p class="mt-2 text-muted">Para uma experiência ainda mais exclusiva.</p>
                    <p class="font-serif mt-6 text-4xl font-semibold">R$ —</p>
                    <a href="/admin" class="ring-soft mt-6 inline-flex w-full justify-center rounded-xl bg-white px-5 py-3 font-semibold">
                        Falar com a gente
                    </a>
                    <ul class="mt-6 space-y-3 text-sm text-muted">
                        <li>• Customizações avançadas</li>
                        <li>• App e experiências</li>
                        <li>• Suporte prioritário</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="faq" class="border-t border-black/5 bg-white">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <h2 class="font-serif text-3xl font-semibold sm:text-4xl">Perguntas frequentes</h2>

                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    <details class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                        <summary class="cursor-pointer font-semibold">Consigo personalizar o site?</summary>
                        <p class="mt-3 text-muted">Sim — com template, identidade visual e ajustes para deixar com a cara do casal.</p>
                    </details>

                    <details class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                        <summary class="cursor-pointer font-semibold">Tem Pix e Cartão?</summary>
                        <p class="mt-3 text-muted">Sim. A lista de presentes funciona com Pix e Cartão (checkout transparente).</p>
                    </details>

                    <details class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                        <summary class="cursor-pointer font-semibold">O RSVP é por família?</summary>
                        <p class="mt-3 text-muted">Sim — com núcleos familiares e opção de eventos separados.</p>
                    </details>

                    <details class="ring-soft rounded-2xl bg-[#FFFBF7] p-6">
                        <summary class="cursor-pointer font-semibold">Dá para controlar orçamento?</summary>
                        <p class="mt-3 text-muted">Sim — planejamento com timeline, tarefas, orçamento e visão financeira.</p>
                    </details>
                </div>
            </div>
        </section>

        <section id="cta" class="bg-soft">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="ring-soft shadow-soft rounded-3xl bg-white p-10 md:p-14">
                    <h3 class="font-serif text-3xl font-semibold sm:text-4xl">
                        Prontos para viver essa história<br class="hidden sm:block"> com leveza e organização?
                    </h3>
                    <p class="mt-4 max-w-2xl text-lg text-muted">
                        Crie seu casamento no Dia Marcado e deixe o sistema cuidar do resto: site, convidados, RSVP,
                        presentes e planejamento.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="/admin"
                            class="inline-flex items-center justify-center rounded-xl px-6 py-3 font-semibold text-white"
                            style="background: var(--rose);"
                        >
                            Criar meu casamento
                        </a>
                        <a href="/admin/sites/create" class="ring-soft inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 font-semibold">
                            Ver templates
                        </a>
                    </div>
                </div>

                <footer class="mt-10 flex flex-col items-start justify-between gap-3 text-sm text-muted-strong sm:flex-row sm:items-center">
                    <span>© {{ currentYear }} Dia Marcado</span>
                    <div class="flex gap-4">
                        <a class="hover:text-black" href="/terms">Termos</a>
                        <a class="hover:text-black" href="/privacy">Privacidade</a>
                        <a class="hover:text-black" href="/admin">Contato</a>
                    </div>
                </footer>
            </div>
        </section>
    </div>
</template>

<style scoped>
.landing-page {
    --bg: #fffbf7;
    --text: #1f2937;
    --muted: rgba(31, 41, 55, 0.7);
    --muted-strong: rgba(31, 41, 55, 0.65);
    --rose: #e04d73;
    --gold: #c8a95d;
    background: var(--bg);
    color: var(--text);
}

.font-serif {
    font-family: 'Playfair Display', serif;
}

.font-sans {
    font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}

.bg-soft {
    background:
        radial-gradient(900px 420px at 20% 10%, rgba(246, 215, 222, 0.65), transparent 60%),
        radial-gradient(700px 360px at 80% 20%, rgba(200, 169, 93, 0.18), transparent 55%),
        linear-gradient(to bottom, #fffbf7, #ffffff);
}

.ring-soft {
    box-shadow: 0 0 0 1px rgba(31, 41, 55, 0.08) inset;
}

.shadow-soft {
    box-shadow: 0 20px 60px rgba(31, 41, 55, 0.1);
}

.brand-rose {
    color: var(--rose);
}

.brand-gold-bg {
    background: var(--gold);
}

.text-muted {
    color: var(--muted);
}

.text-muted-strong {
    color: var(--muted-strong);
}

.blob-rose {
    background: radial-gradient(circle, rgba(246, 215, 222, 0.9), transparent 65%);
}

.blob-gold {
    background: radial-gradient(circle, rgba(200, 169, 93, 0.35), transparent 65%);
}
</style>

<x-filament-panels::page>
    @php
        $overview = $this->getOverviewMetrics();
        $quickLinks = $this->getQuickLinks();
    @endphp

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Salvar Configurações
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <div class="mt-6 space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700">Central RSVP</h3>
            <p class="mt-1 text-xs text-gray-500">
                As perguntas personalizadas são configuradas por evento, não nesta tela. Use os atalhos abaixo para gerenciar tudo.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Núcleos</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $overview['households'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Convidados</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $overview['guests'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Eventos RSVP</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $overview['events_total'] }}</p>
                    <p class="text-xs text-gray-500">{{ $overview['events_active'] }} ativo(s)</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Eventos com perguntas</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $overview['events_with_questions'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Perguntas cadastradas</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $overview['questions_total'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Fluxo recomendado</h3>
                <ol class="mt-3 list-decimal space-y-2 pl-4 text-sm text-gray-700">
                    <li>Defina aqui o modo de acesso: aberto ou restrito.</li>
                    <li>Cadastre os eventos RSVP.</li>
                    <li>Em cada evento, configure as perguntas que os convidados devem responder.</li>
                    <li>Cadastre núcleos e convidados.</li>
                    <li>Acompanhe o resultado no Dashboard RSVP.</li>
                </ol>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\GuestEventResource::getUrl('create') }}"
                        icon="heroicon-o-plus"
                    >
                        Criar Evento RSVP
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Atalhos rápidos</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($quickLinks as $link)
                        <a
                            href="{{ $link['url'] }}"
                            class="block rounded-lg border border-gray-200 px-3 py-3 transition hover:border-primary-300 hover:bg-primary-50/40"
                        >
                            <p class="text-sm font-medium text-gray-900">{{ $link['label'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $link['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

<x-filament::page>
    @php
        $consoleData = $this->getConsoleData();
        $summary = $consoleData['summary'] ?? [];
        $items = $consoleData['items'] ?? collect();
        $events = $this->getEventOptions();
        $manualCandidates = $this->getManualCandidates();
        $byMethod = collect($summary['by_method'] ?? []);
        $byEvent = collect($summary['by_event'] ?? []);
    @endphp

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Check-ins (filtro atual)</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['total_checkins'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Convidados únicos</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['unique_checked_in_guests'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Check-ins hoje</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['checkins_today'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Duplicidades 24h</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['duplicates_ignored_24h'] ?? 0 }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Evento</label>
                    <select wire:model.live="eventId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sem evento</option>
                        @foreach ($events as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Método (lista)</label>
                    <select wire:model.live="methodFilter" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="qr">QR</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Limite da lista</label>
                    <select wire:model.live="limit" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Dispositivo (opcional)</label>
                    <input
                        type="text"
                        wire:model.defer="deviceId"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="ex: gate-a"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                x-data="guestCheckinScanner('{{ $this->getId() }}')"
                x-init="initScanner()"
            >
                <h3 class="text-sm font-semibold text-gray-700">Leitura de QR</h3>
                <p class="mt-1 text-xs text-gray-500">Use a câmera para leitura automática ou cole o conteúdo do QR manualmente.</p>

                <div class="mt-4 space-y-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                x-show="!isCameraOn"
                                x-on:click="startCamera()"
                                class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500"
                            >
                                Iniciar câmera
                            </button>
                            <button
                                type="button"
                                x-show="isCameraOn"
                                x-on:click="stopCamera()"
                                class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-500"
                            >
                                Parar câmera
                            </button>
                            <span
                                class="text-xs"
                                :class="statusType === 'error' ? 'text-rose-600' : (statusType === 'warning' ? 'text-amber-700' : 'text-gray-600')"
                                x-text="statusMessage"
                            ></span>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]" x-show="isSupported">
                            <div>
                                <label class="text-xs font-medium text-gray-700">Câmera ativa</label>
                                <select
                                    x-model="selectedCameraId"
                                    x-on:change="switchCamera()"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <template x-for="camera in availableCameras" :key="camera.deviceId">
                                        <option :value="camera.deviceId" x-text="camera.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="self-end">
                                <button
                                    type="button"
                                    x-on:click="refreshCameras()"
                                    class="inline-flex items-center rounded-md bg-gray-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-600"
                                >
                                    Atualizar câmeras
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-md border border-gray-200 bg-black/90" wire:ignore>
                            <video
                                x-ref="video"
                                x-show="isCameraOn"
                                autoplay
                                muted
                                playsinline
                                class="aspect-video w-full object-cover"
                            ></video>
                            <div x-show="!isCameraOn" class="flex aspect-video w-full items-center justify-center px-3 text-center text-xs text-gray-300">
                                A leitura por câmera usa o detector nativo do navegador.
                                Inicie a câmera para escanear QR automaticamente.
                            </div>
                        </div>
                    </div>

                    <textarea
                        wire:model.defer="qrCode"
                        rows="3"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="dmc-checkin:..."
                    ></textarea>

                    <input
                        type="text"
                        wire:model.defer="scanNotes"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Observações do check-in via QR (opcional)"
                    />

                    <x-filament::button
                        color="success"
                        icon="heroicon-o-qr-code"
                        wire:click="scanQr"
                        wire:loading.attr="disabled"
                        wire:target="scanQr"
                    >
                        Registrar via QR
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Check-in manual</h3>
                <p class="mt-1 text-xs text-gray-500">Busque por nome, email ou telefone e registre manualmente.</p>

                <div class="mt-4 space-y-3">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="manualSearch"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Buscar convidado (min. 2 caracteres)"
                    />

                    <input
                        type="text"
                        wire:model.defer="manualNotes"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Observações do check-in manual (opcional)"
                    />
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="pb-2 pr-4">Convidado</th>
                                <th class="pb-2 pr-4">Contato</th>
                                <th class="pb-2 pr-4">Núcleo</th>
                                <th class="pb-2">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($manualCandidates as $candidate)
                                <tr>
                                    <td class="py-2 pr-4">
                                        <div class="font-medium text-gray-900">{{ $candidate['name'] }}</div>
                                        @if ($candidate['already_checked_in'])
                                            <div class="text-xs text-amber-700">Já possui check-in neste contexto</div>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-xs text-gray-600">
                                        {{ $candidate['email'] ?: '-' }}<br>
                                        {{ $candidate['phone'] ?: '-' }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $candidate['household'] ?: '-' }}</td>
                                    <td class="py-2">
                                        <x-filament::button
                                            size="xs"
                                            color="primary"
                                            wire:click="manualCheckin('{{ $candidate['id'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="manualCheckin"
                                        >
                                            Check-in manual
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-sm text-gray-500">Digite pelo menos 2 caracteres para buscar convidados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Distribuição por método</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($byMethod as $method)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ $method['label'] }}</span>
                            <span class="font-medium text-gray-900">{{ $method['total'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sem dados no filtro atual.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Distribuição por evento</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @forelse ($byEvent as $event)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ $event['event_name'] }}</span>
                            <span class="font-medium text-gray-900">{{ $event['total'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sem dados no filtro atual.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-700">Últimos check-ins</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="pb-2 pr-4">Horário</th>
                            <th class="pb-2 pr-4">Convidado</th>
                            <th class="pb-2 pr-4">Evento</th>
                            <th class="pb-2 pr-4">Método</th>
                            <th class="pb-2 pr-4">Operador</th>
                            <th class="pb-2 pr-4">Dispositivo</th>
                            <th class="pb-2">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($items as $checkin)
                            <tr>
                                <td class="py-2 pr-4">{{ $checkin['checked_in_at']?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $checkin['guest']['name'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $checkin['event']['name'] ?? 'Sem evento' }}</td>
                                <td class="py-2 pr-4">{{ $checkin['method_label'] ?? $checkin['method'] }}</td>
                                <td class="py-2 pr-4">{{ $checkin['operator']['name'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $checkin['device_id'] ?: '-' }}</td>
                                <td class="py-2">{{ $checkin['notes'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-3 text-sm text-gray-500">Nenhum check-in encontrado no filtro atual.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        if (window.__guestCheckinScannerRegistered !== true) {
            window.__guestCheckinScannerRegistered = true;

            document.addEventListener('alpine:init', () => {
                Alpine.data('guestCheckinScanner', (componentId) => ({
                    componentId,
                    isSupported: Boolean(window.BarcodeDetector && navigator.mediaDevices?.getUserMedia),
                    isCameraOn: false,
                    statusType: 'info',
                    statusMessage: 'Pronto para leitura de QR.',
                    stream: null,
                    detector: null,
                    availableCameras: [],
                    selectedCameraId: '',
                    lastPayload: null,
                    lastScanAt: 0,
                    cooldownMs: 3500,
                    scanInFlight: false,

                    initScanner() {
                        if (!this.isSupported) {
                            this.statusType = 'warning';
                            this.statusMessage = 'Seu navegador não suporta leitura automática. Use o campo manual.';
                            return;
                        }

                        try {
                            this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                        } catch (error) {
                            this.statusType = 'warning';
                            this.statusMessage = 'Não foi possível inicializar o detector de QR. Use o campo manual.';
                            this.isSupported = false;
                            return;
                        }

                        this.refreshCameras();

                        if (navigator.mediaDevices?.addEventListener) {
                            navigator.mediaDevices.addEventListener('devicechange', () => this.refreshCameras());
                        }

                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) {
                                this.stopCamera();
                            }
                        });

                        document.addEventListener('livewire:navigating', () => this.stopCamera());
                        window.addEventListener('beforeunload', () => this.stopCamera());
                    },

                    async refreshCameras() {
                        if (!navigator.mediaDevices?.enumerateDevices) {
                            return;
                        }

                        try {
                            const devices = await navigator.mediaDevices.enumerateDevices();
                            const cameras = devices
                                .filter((device) => device.kind === 'videoinput')
                                .map((device, index) => ({
                                    deviceId: device.deviceId,
                                    label: (device.label && device.label.trim() !== '') ? device.label : `Câmera ${index + 1}`,
                                }));

                            this.availableCameras = cameras;

                            if (cameras.length === 0) {
                                this.selectedCameraId = '';
                                return;
                            }

                            const exists = cameras.some((camera) => camera.deviceId === this.selectedCameraId);
                            if (!exists) {
                                const preferred = cameras.find((camera) => /back|rear|environment|traseira/i.test(camera.label));
                                this.selectedCameraId = (preferred ?? cameras[0]).deviceId;
                            }
                        } catch (error) {
                            this.statusType = 'warning';
                            this.statusMessage = 'Não foi possível listar as câmeras disponíveis.';
                        }
                    },

                    async switchCamera() {
                        if (!this.isCameraOn) {
                            return;
                        }

                        await this.startCamera();
                    },

                    async startCamera() {
                        if (!this.isSupported) {
                            return;
                        }

                        this.stopCamera();

                        try {
                            const videoConstraints = {
                                width: { ideal: 1280 },
                                height: { ideal: 720 },
                            };

                            if (this.selectedCameraId) {
                                videoConstraints.deviceId = { exact: this.selectedCameraId };
                            } else {
                                videoConstraints.facingMode = { ideal: 'environment' };
                            }

                            this.stream = await navigator.mediaDevices.getUserMedia({
                                video: videoConstraints,
                                audio: false,
                            });

                            this.$refs.video.srcObject = this.stream;
                            this.isCameraOn = true;

                            const track = this.stream.getVideoTracks()?.[0];
                            const activeDeviceId = track?.getSettings?.().deviceId;
                            if (activeDeviceId) {
                                this.selectedCameraId = activeDeviceId;
                            }

                            await this.refreshCameras();

                            this.statusType = 'info';
                            this.statusMessage = 'Câmera ativa. Aponte para o QR.';
                            this.scanLoop();
                        } catch (error) {
                            this.statusType = 'error';
                            this.statusMessage = 'Não foi possível acessar a câmera.';
                        }
                    },

                    stopCamera() {
                        this.isCameraOn = false;
                        this.scanInFlight = false;

                        if (this.stream) {
                            this.stream.getTracks().forEach((track) => track.stop());
                            this.stream = null;
                        }

                        if (this.$refs.video) {
                            this.$refs.video.srcObject = null;
                        }

                        if (this.statusType !== 'error') {
                            this.statusType = 'info';
                            this.statusMessage = 'Leitura automática pausada.';
                        }
                    },

                    async scanLoop() {
                        if (!this.isCameraOn || !this.detector) {
                            return;
                        }

                        if (this.scanInFlight) {
                            requestAnimationFrame(() => this.scanLoop());
                            return;
                        }

                        try {
                            const codes = await this.detector.detect(this.$refs.video);
                            const rawValue = codes?.[0]?.rawValue?.trim();

                            if (rawValue) {
                                await this.submitQr(rawValue);
                            }
                        } catch (error) {
                            this.statusType = 'warning';
                            this.statusMessage = 'Leitura em andamento. Ajuste foco e distância.';
                        } finally {
                            if (this.isCameraOn) {
                                requestAnimationFrame(() => this.scanLoop());
                            }
                        }
                    },

                    async submitQr(rawValue) {
                        const now = Date.now();

                        if (this.lastPayload === rawValue && (now - this.lastScanAt) < this.cooldownMs) {
                            return;
                        }

                        const component = window.Livewire?.find(this.componentId);
                        if (!component) {
                            this.statusType = 'error';
                            this.statusMessage = 'Componente de check-in indisponível. Recarregue a página.';
                            return;
                        }

                        this.lastPayload = rawValue;
                        this.lastScanAt = now;
                        this.scanInFlight = true;
                        this.statusType = 'info';
                        this.statusMessage = 'QR detectado. Registrando check-in...';

                        try {
                            await component.call('scanQrFromCamera', rawValue);
                            this.statusType = 'info';
                            this.statusMessage = 'Check-in processado. Pronto para próximo QR.';
                        } catch (error) {
                            this.statusType = 'error';
                            this.statusMessage = 'Falha ao registrar QR automaticamente.';
                        } finally {
                            this.scanInFlight = false;
                        }
                    },
                }));
            });
        }
    </script>
</x-filament::page>

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            value: $wire.$entangle('{{ $getStatePath() }}'),
            hour: '18',
            minute: '00',
            hours: Array.from({ length: 24 }, (_, index) => String(index).padStart(2, '0')),
            minutes: Array.from({ length: 60 }, (_, index) => String(index).padStart(2, '0')),

            init() {
                this.syncFromValue(this.value);

                if (! this.value) {
                    this.syncToValue();
                }

                this.$watch('value', (newValue) => {
                    this.syncFromValue(newValue);
                    this.alignSelections(true);
                });

                this.$watch('hour', () => this.syncToValue());
                this.$watch('minute', () => this.syncToValue());

                this.alignSelections(true);
            },

            syncFromValue(timeValue) {
                if (! timeValue || typeof timeValue !== 'string') {
                    return;
                }

                const match = timeValue.match(/^(\d{2}):(\d{2})/);

                if (! match) {
                    return;
                }

                this.hour = match[1];
                this.minute = match[2];
            },

            syncToValue() {
                const nextValue = `${this.hour}:${this.minute}`;

                if (this.value !== nextValue) {
                    this.value = nextValue;
                }
            },

            selectHour(option) {
                this.hour = option;
                this.scrollToSelected('hourList', option, false);
            },

            selectMinute(option) {
                this.minute = option;
                this.scrollToSelected('minuteList', option, false);
            },

            alignSelections(initial = false) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.scrollToSelected('hourList', this.hour, initial);
                        this.scrollToSelected('minuteList', this.minute, initial);
                    }, 25);
                });
            },

            scrollToSelected(refName, selectedValue, initial = false) {
                this.$nextTick(() => {
                    const container = this.$refs[refName];

                    if (! container) {
                        return;
                    }

                    const selected = container.querySelector(`[data-value='${selectedValue}']`);

                    if (! selected) {
                        return;
                    }

                    container.scrollTo({
                        top: selected.offsetTop - (container.clientHeight / 2) + (selected.clientHeight / 2),
                        behavior: initial ? 'auto' : 'smooth',
                    });
                });
            },
        }"
        class="wedding-time-picker h-full w-full"
    >
        <div
            class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            style="height: clamp(15rem, 46vh, 20rem);"
        >
            <div class="shrink-0 border-b border-gray-200 bg-gray-50 px-2 py-1 dark:border-gray-700 dark:bg-gray-900 sm:px-3">
                <div class="flex items-center justify-between">
                    <span class="h-6 w-6"></span>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 sm:text-base" x-text="`${hour}:${minute}`"></div>
                    <span class="h-6 w-6"></span>
                </div>
            </div>

            <div class="flex min-h-0 flex-1 items-stretch gap-1.5 p-2 sm:gap-2 sm:p-3" style="min-height: 0;">
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <p class="shrink-0 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-[11px]">Hora</p>
                    <div
                        x-ref="hourList"
                        class="mt-1 min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-md border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800"
                        style="min-height: 0; height: 100%; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch;"
                    >
                        <template x-for="option in hours" :key="`h-${option}`">
                            <button
                                type="button"
                                :data-value="option"
                                @click="selectHour(option)"
                                :class="hour === option
                                    ? 'bg-pink-500 text-white shadow-sm dark:bg-pink-600'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                                class="mb-1 block w-full rounded-md py-0.5 text-center text-sm font-semibold transition last:mb-0 sm:text-base"
                            >
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="mt-4 mb-0.5 w-px shrink-0 self-stretch bg-gray-200 dark:bg-gray-700"></div>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <p class="shrink-0 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-[11px]">Min</p>
                    <div
                        x-ref="minuteList"
                        class="mt-1 min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-md border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800"
                        style="min-height: 0; height: 100%; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch;"
                    >
                        <template x-for="option in minutes" :key="`m-${option}`">
                            <button
                                type="button"
                                :data-value="option"
                                @click="selectMinute(option)"
                                :class="minute === option
                                    ? 'bg-pink-500 text-white shadow-sm dark:bg-pink-600'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                                class="mb-1 block w-full rounded-md py-0.5 text-center text-sm font-semibold transition last:mb-0 sm:text-base"
                            >
                                <span x-text="option"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
